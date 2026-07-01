<?php
/**
 * Lite_Shelf - Setup / Installer
 *
 * Self-contained installer. Collects DB credentials via a UI, creates the
 * database, installs the dashboard + default-app schema, writes config files,
 * seeds an initial admin API key, and reports results with full error handling.
 *
 * After a successful install, DELETE THIS FILE.
 */

@set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', '0');

define('SETUP_ROOT', __DIR__);
define('TEMPLATE_PATH', SETUP_ROOT . '/template/lite-shelf');
define('APPS_SCHEMA_PATH', SETUP_ROOT . '/database/apps_schema.sql');
define('APP_SCHEMA_PATH', TEMPLATE_PATH . '/database/schema.sql');
define('DB_CONFIG_PATH', TEMPLATE_PATH . '/config/database.php');
define('ADMIN_CONFIG_PATH', SETUP_ROOT . '/config/admin.php');

/* ------------------------------------------------------------------ *
 * Self-delete handler
 * ------------------------------------------------------------------ */
$deleteError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_self') {
    if (is_writable(__FILE__) && @unlink(__FILE__)) {
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?deleted=1');
        exit;
    }
    $deleteError = 'Could not delete setup.php automatically (permission denied). Please remove it manually via FTP or shell.';
}

/* ------------------------------------------------------------------ *
 * Install handler
 * ------------------------------------------------------------------ */
$installResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'install') {
    $installResult = runInstall($_POST);
}

/* ------------------------------------------------------------------ *
 * Pre-flight checks (for the form view)
 * ------------------------------------------------------------------ */
$preflight = runPreflight();

/* ------------------------------------------------------------------ *
 * Helpers
 * ------------------------------------------------------------------ */

function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function runPreflight(): array {
    $checks = [];

    $checks[] = [
        'label' => 'PHP version >= 7.4',
        'ok' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'detail' => 'Detected: ' . PHP_VERSION,
    ];
    $checks[] = [
        'label' => 'PDO MySQL extension loaded',
        'ok' => extension_loaded('pdo_mysql'),
        'detail' => extension_loaded('pdo_mysql') ? 'Available' : 'Missing',
    ];
    $checks[] = [
        'label' => 'database/apps_schema.sql readable',
        'ok' => is_readable(APPS_SCHEMA_PATH),
        'detail' => APPS_SCHEMA_PATH,
    ];
    $checks[] = [
        'label' => 'template/lite-shelf/database/schema.sql readable',
        'ok' => is_readable(APP_SCHEMA_PATH),
        'detail' => APP_SCHEMA_PATH,
    ];
    $checks[] = [
        'label' => 'config/database.php is writable',
        'ok' => is_writable(DB_CONFIG_PATH),
        'detail' => DB_CONFIG_PATH,
    ];
    $checks[] = [
        'label' => 'config/admin.php is writable',
        'ok' => is_writable(ADMIN_CONFIG_PATH),
        'detail' => ADMIN_CONFIG_PATH,
    ];

    $uploadsDir = TEMPLATE_PATH . '/uploads';
    if (!is_dir($uploadsDir)) {
        @mkdir($uploadsDir, 0755, true);
    }
    $checks[] = [
        'label' => 'template/lite-shelf/uploads/ writable',
        'ok' => is_dir($uploadsDir) && is_writable($uploadsDir),
        'detail' => $uploadsDir,
    ];

    $logsDir = TEMPLATE_PATH . '/logs';
    if (!is_dir($logsDir)) {
        @mkdir($logsDir, 0755, true);
    }
    $checks[] = [
        'label' => 'template/lite-shelf/logs/ writable',
        'ok' => is_dir($logsDir) && is_writable($logsDir),
        'detail' => $logsDir,
    ];

    // Already-configured hint
    $currentConfig = @include DB_CONFIG_PATH;
    $alreadyConfigured = is_array($currentConfig)
        && (($currentConfig['username'] ?? 'root') !== 'root' || ($currentConfig['password'] ?? '') !== '');
    $checks[] = [
        'label' => 'Fresh install / not yet configured',
        'ok' => !$alreadyConfigured,
        'detail' => $alreadyConfigured
            ? 'config/database.php already has non-default credentials. Re-running is safe (idempotent) but confirm you intend to.'
            : 'Defaults detected — ready for first install.',
        'warning' => $alreadyConfigured,
    ];

    return $checks;
}

function runInstall(array $input): array {
    $steps = [];
    $adminKey = null;

    // 1. Validate inputs
    $host = trim($input['db_host'] ?? '');
    $port = trim($input['db_port'] ?? '3306');
    $dbname = trim($input['db_name'] ?? '');
    $user = trim($input['db_user'] ?? '');
    $pass = (string)($input['db_pass'] ?? '');
    $prefix = trim($input['db_prefix'] ?? '');
    $adminUser = trim($input['admin_user'] ?? '');
    $adminPass = (string)($input['admin_pass'] ?? '');
    $adminPass2 = (string)($input['admin_pass2'] ?? '');
    $appUrl = rtrim(trim($input['app_url'] ?? ''), '/');

    if ($host === '' || $dbname === '' || $user === '') {
        return ['success' => false, 'steps' => $steps, 'error' => 'DB Host, DB Name and DB Username are required.'];
    }
    if (!ctype_digit($port) || (int)$port <= 0 || (int)$port > 65535) {
        return ['success' => false, 'steps' => $steps, 'error' => 'DB Port must be a number between 1 and 65535.'];
    }
    if ($prefix !== '' && !preg_match('/^[a-zA-Z0-9_]+$/', $prefix)) {
        return ['success' => false, 'steps' => $steps, 'error' => 'Table prefix may only contain letters, numbers and underscores.'];
    }
    if ($adminPass !== '' && $adminPass !== $adminPass2) {
        return ['success' => false, 'steps' => $steps, 'error' => 'Dashboard admin passwords do not match.'];
    }
    if ($adminUser !== '' && !preg_match('/^[a-zA-Z0-9_-]+$/', $adminUser)) {
        return ['success' => false, 'steps' => $steps, 'error' => 'Admin username may only contain letters, numbers, dashes and underscores.'];
    }

    // 2. Connect to MySQL server (no dbname)
    try {
        $serverPdo = new PDO(
            "mysql:host={$host};port={$port};charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $steps[] = ['ok' => true, 'label' => 'Connect to MySQL server', 'detail' => "{$user}@{$host}:{$port}"];
    } catch (PDOException $ex) {
        $msg = friendlyPdoMessage($ex);
        $steps[] = ['ok' => false, 'label' => 'Connect to MySQL server', 'detail' => $msg];
        return ['success' => false, 'steps' => $steps, 'error' => 'Could not connect to MySQL: ' . $msg];
    }

    // 3. Create database if missing
    $dbCreated = false;
    try {
        $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $dbCreated = true;
        $steps[] = ['ok' => true, 'label' => 'Ensure database exists', 'detail' => "Database `{$dbname}` ready (created if it was missing)."];
    } catch (PDOException $ex) {
        $msg = friendlyPdoMessage($ex);
        $steps[] = ['ok' => false, 'label' => 'Ensure database exists', 'detail' => $msg];
        return ['success' => false, 'steps' => $steps, 'error' => "Could not create database `{$dbname}`: {$msg}. Create it manually or grant CREATE privilege to the user."];
    }

    // 4. Reconnect with dbname selected
    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $steps[] = ['ok' => true, 'label' => 'Select database', 'detail' => "Connected to `{$dbname}`."];
    } catch (PDOException $ex) {
        $msg = friendlyPdoMessage($ex);
        $steps[] = ['ok' => false, 'label' => 'Select database', 'detail' => $msg];
        return ['success' => false, 'steps' => $steps, 'error' => "Could not select database `{$dbname}`: {$msg}"];
    }

    // 5. Run dashboard schema (apps table + seed). Prefix is NOT applied to the apps table.
    $appsResult = runSqlFile($pdo, APPS_SCHEMA_PATH, '', true, true);
    if ($appsResult['errors']) {
        $steps[] = ['ok' => false, 'label' => 'Install dashboard schema (apps table)', 'detail' => implode('; ', array_slice($appsResult['errors'], 0, 3))];
        return ['success' => false, 'steps' => $steps, 'error' => 'Dashboard schema installation failed: ' . implode('; ', $appsResult['errors'])];
    }
    $steps[] = ['ok' => true, 'label' => 'Install dashboard schema (apps table)', 'detail' => 'apps table created/verified, dashboard row seeded.'];

    // 6. Run app schema with the chosen prefix
    $appResult = runSqlFile($pdo, APP_SCHEMA_PATH, $prefix, true, true);
    if ($appResult['errors']) {
        $steps[] = ['ok' => false, 'label' => 'Install app schema (10 tables)', 'detail' => implode('; ', array_slice($appResult['errors'], 0, 3))];
        return ['success' => false, 'steps' => $steps, 'error' => 'App schema installation failed: ' . implode('; ', $appResult['errors'])];
    }
    $steps[] = ['ok' => true, 'label' => 'Install app schema (10 tables)', 'detail' => 'All app tables created/verified' . ($prefix ? " with prefix `'{$prefix}'`." : '.')];

    // 7. Seed initial admin API key (only if none exists)
    $keyTable = '`' . $prefix . 'api_keys`';
    try {
        $existing = $pdo->query("SELECT id FROM {$keyTable} WHERE is_initial = 1 LIMIT 1")->fetchColumn();
        if ($existing) {
            $steps[] = ['ok' => true, 'label' => 'Seed initial admin API key', 'detail' => 'Skipped — an initial admin key already exists. (Re-using the existing one.)'];
        } else {
            $adminKey = 'app_' . bin2hex(random_bytes(32));
            $keyHash = hash('sha256', $adminKey);
            $stmt = $pdo->prepare(
                "INSERT INTO {$keyTable} (key_hash, name, is_admin, is_active, is_initial, rate_limit, created_at)
                 VALUES (?, 'Initial Admin Key', 1, 1, 1, 1000, NOW())"
            );
            $stmt->execute([$keyHash]);
            $steps[] = ['ok' => true, 'label' => 'Seed initial admin API key', 'detail' => 'A new initial admin API key was generated.'];
        }
    } catch (PDOException $ex) {
        $msg = friendlyPdoMessage($ex);
        $steps[] = ['ok' => false, 'label' => 'Seed initial admin API key', 'detail' => $msg];
        return ['success' => false, 'steps' => $steps, 'error' => 'Could not seed admin API key: ' . $msg];
    }

    // 8. Write config/database.php
    $writeErr = writeDbConfig($host, (int)$port, $dbname, $user, $pass, $prefix);
    if ($writeErr) {
        $steps[] = ['ok' => false, 'label' => 'Write config/database.php', 'detail' => $writeErr];
        return ['success' => false, 'steps' => $steps, 'error' => $writeErr];
    }
    $steps[] = ['ok' => true, 'label' => 'Write config/database.php', 'detail' => 'Credentials written.'];

    // 9. Optionally update dashboard admin password
    if ($adminPass !== '' && $adminUser !== '') {
        $adminErr = writeAdminConfig($adminUser, $adminPass);
        if ($adminErr) {
            $steps[] = ['ok' => false, 'label' => 'Write config/admin.php', 'detail' => $adminErr];
            return ['success' => false, 'steps' => $steps, 'error' => $adminErr];
        }
        $steps[] = ['ok' => true, 'label' => 'Write config/admin.php', 'detail' => "Dashboard admin set to `{$adminUser}`."];
    } else {
        $steps[] = ['ok' => true, 'label' => 'Write config/admin.php', 'detail' => 'Skipped — no admin password provided. Default admin/admin123 remains.'];
    }

    return [
        'success' => true,
        'steps' => $steps,
        'adminKey' => $adminKey,
        'adminUser' => $adminUser ?: 'admin',
        'appUrl' => $appUrl,
        'prefix' => $prefix,
        'dbname' => $dbname,
    ];
}

function friendlyPdoMessage(PDOException $ex): string {
    $code = $ex->getCode();
    $msg = $ex->getMessage();
    // PDO MySQL error codes
    if ($code === '2002') return 'Could not reach MySQL server at the given host/port (connection refused / unknown host).';
    if ($code === '1045') return 'Access denied — check the username and password.';
    if ($code === '1044') return 'Access denied — the user lacks permission for this database.';
    if ($code === '1049') return 'Unknown database.';
    if ($code === '42000') return 'Permission denied — the user lacks a required privilege (e.g. CREATE).';
    // Strip the DSN/password leakage from the message if present
    $msg = preg_replace('/SQLSTATE\[[^\]]+\]:?\s*/', '', $msg);
    return $msg . ($code ? " (code {$code})" : '');
}

/**
 * Execute a .sql file with optional table prefixing and idempotency transforms.
 */
function runSqlFile(PDO $pdo, string $path, string $prefix, bool $idempotent, bool $foreignKeyChecksOff): array {
    $errors = [];
    if (!is_readable($path)) {
        $errors[] = "Cannot read SQL file: {$path}";
        return ['errors' => $errors];
    }

    $sql = file_get_contents($path);

    // Strip -- comment lines
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);

    // Apply table prefix (same approach as AppManager::executeSchema)
    if ($prefix !== '') {
        $sql = preg_replace(
            '/`(admin_users|users|api_keys|data_collections|data_items|storage_files|storage_folders|notifications|api_key_collection_access|api_key_folder_access)`/',
            "`{$prefix}\$1`",
            $sql
        );
        $sql = preg_replace('/CONSTRAINT `([^`]+)_ibfk_(\d+)`/', "CONSTRAINT `{$prefix}\$1_ibfk_\$2`", $sql);
        $sql = preg_replace('/UNIQUE KEY `([^`]+)`/', "UNIQUE KEY `{$prefix}\$1`", $sql);
        $sql = preg_replace('/(?<!UNIQUE )KEY `((?!idx_)[^`]+)`/', "KEY `{$prefix}\$1`", $sql);
    }

    // Idempotency transforms
    if ($idempotent) {
        $sql = preg_replace('/\bCREATE TABLE\s+`/', 'CREATE TABLE IF NOT EXISTS `', $sql);
        $sql = preg_replace('/\bINSERT INTO\s+`/', 'INSERT IGNORE INTO `', $sql);
    }

    if ($foreignKeyChecksOff) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    }

    // Split into statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if ($stmt === '') continue;
        // Skip statements we handle ourselves or that are no-ops / problematic here
        if (preg_match('/^(CREATE\s+DATABASE|USE\s+|COMMIT\b|SET\s+SQL_MODE|SET\s+time_zone)/i', $stmt)) {
            continue;
        }
        try {
            $pdo->exec($stmt);
        } catch (PDOException $ex) {
            // Ignore "already exists" on idempotent runs
            $code = $ex->getCode();
            $msg = $ex->getMessage();
            if ($code === '42S01' || stripos($msg, 'already exists') !== false) {
                continue;
            }
            $snippet = substr(preg_replace('/\s+/', ' ', $stmt), 0, 120);
            $errors[] = $msg . ' — statement: "' . $snippet . '..."';
        }
    }

    if ($foreignKeyChecksOff) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    return ['errors' => $errors];
}

function writeDbConfig(string $host, int $port, string $dbname, string $user, string $pass, string $prefix): ?string {
    $content = "<?php\n";
    $content .= "/**\n * Database Connection Configuration\n *\n * Generated by setup.php. Edit manually or re-run setup.php to change.\n * Environment variables override these values when set.\n */\n\n";
    $content .= "return [\n";
    $content .= "    'host' => getenv('DB_HOST') ?: " . var_export($host, true) . ",\n";
    $content .= "    'port' => getenv('DB_PORT') ?: " . var_export($port, true) . ",\n";
    $content .= "    'database' => getenv('DB_NAME') ?: " . var_export($dbname, true) . ",\n";
    $content .= "    'username' => getenv('DB_USER') ?: " . var_export($user, true) . ",\n";
    $content .= "    'password' => getenv('DB_PASS') ?: " . var_export($pass, true) . ",\n";
    $content .= "    'table_prefix' => getenv('DB_PREFIX') ?: " . var_export($prefix, true) . ",\n";
    $content .= "];\n";

    if (!is_writable(DB_CONFIG_PATH)) {
        return 'config/database.php is not writable by PHP. Fix file permissions (chmod 666 or make it owner-writable) and re-run.';
    }
    $written = file_put_contents(DB_CONFIG_PATH, $content);
    if ($written === false) {
        return 'Failed to write config/database.php — unknown file write error.';
    }
    return null;
}

function writeAdminConfig(string $username, string $password): ?string {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    if ($hash === false) {
        return 'Could not generate bcrypt hash for the admin password.';
    }
    $content = "<?php\n";
    $content .= "/**\n * Admin Configuration\n *\n * Generated by setup.php.\n */\n\n";
    $content .= "return [\n";
    $content .= "    'username' => " . var_export($username, true) . ",\n";
    $content .= "    'password_hash' => " . var_export($hash, true) . ",\n";
    $content .= "];\n";

    if (!is_writable(ADMIN_CONFIG_PATH)) {
        return 'config/admin.php is not writable by PHP. Fix file permissions and re-run.';
    }
    $written = file_put_contents(ADMIN_CONFIG_PATH, $content);
    if ($written === false) {
        return 'Failed to write config/admin.php — unknown file write error.';
    }
    return null;
}

/* ------------------------------------------------------------------ *
 * View
 * ------------------------------------------------------------------ */
$deletedFlag = isset($_GET['deleted']);
$allOk = true;
foreach ($preflight as $c) {
    if (!$c['ok'] && empty($c['warning'])) {
        $allOk = false;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lite_Shelf — Setup</title>
<style>
:root {
    --bg: #0f172a; --card: #1e293b; --card2: #273449; --border: #334155;
    --text: #e2e8f0; --muted: #94a3b8; --primary: #6366f1; --primary2: #818cf8;
    --ok: #22c55e; --err: #ef4444; --warn: #f59e0b; --code-bg: #0b1220;
}
* { box-sizing: border-box; }
body {
    margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
    color: var(--text); min-height: 100vh; padding: 32px 16px;
}
.wrap { max-width: 820px; margin: 0 auto; }
header { text-align: center; margin-bottom: 28px; }
header h1 { font-size: 28px; margin: 0 0 6px; letter-spacing: -0.5px; }
header h1 span { color: var(--primary2); }
header p { color: var(--muted); margin: 0; font-size: 14px; }
.card {
    background: var(--card); border: 1px solid var(--border); border-radius: 14px;
    padding: 24px; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.25);
}
.card h2 { margin: 0 0 16px; font-size: 18px; }
.row { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 8px; background: var(--card2); margin-bottom: 8px; }
.row:last-child { margin-bottom: 0; }
.badge { width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; }
.badge.ok { background: var(--ok); color: #052e16; }
.badge.err { background: var(--err); color: #450a0a; }
.badge.warn { background: var(--warn); color: #451a03; }
.row .label { font-weight: 600; font-size: 14px; }
.row .detail { color: var(--muted); font-size: 12px; margin-left: auto; word-break: break-all; text-align: right; }
form .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
form .full { grid-column: 1 / -1; }
.field { display: flex; flex-direction: column; gap: 6px; }
.field label { font-size: 13px; font-weight: 600; color: var(--text); }
.field input { background: var(--card2); border: 1px solid var(--border); color: var(--text); border-radius: 8px; padding: 10px 12px; font-size: 14px; outline: none; transition: border-color .15s; }
.field input:focus { border-color: var(--primary); }
.field .hint { font-size: 11px; color: var(--muted); }
button.btn {
    background: var(--primary); color: white; border: none; border-radius: 10px;
    padding: 12px 22px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background .15s, transform .05s;
}
button.btn:hover { background: var(--primary2); }
button.btn:active { transform: translateY(1px); }
button.btn.secondary { background: var(--card2); border: 1px solid var(--border); }
button.btn.secondary:hover { background: var(--border); }
button.btn.danger { background: var(--err); }
button.btn.danger:hover { background: #dc2626; }
.actions { display: flex; gap: 12px; margin-top: 18px; flex-wrap: wrap; }
.alert { padding: 14px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 14px; }
.alert.err { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.4); color: #fecaca; }
.alert.ok { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.4); color: #bbf7d0; }
.alert.warn { background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.4); color: #fde68a; }
code, .mono { font-family: "SF Mono", Monaco, Consolas, "Courier New", monospace; }
.codebox {
    background: var(--code-bg); border: 1px solid var(--border); border-radius: 10px;
    padding: 14px 16px; font-size: 13px; word-break: break-all; position: relative;
}
.codebox .copy { position: absolute; top: 8px; right: 8px; background: var(--card2); border: 1px solid var(--border); color: var(--muted); border-radius: 6px; padding: 4px 10px; font-size: 12px; cursor: pointer; }
.codebox .copy:hover { color: var(--text); }
.summary-row { display: flex; gap: 10px; align-items: flex-start; padding: 8px 0; border-bottom: 1px solid var(--border); }
.summary-row:last-child { border-bottom: none; }
.summary-row .badge { margin-top: 2px; }
.summary-row .txt { font-size: 14px; }
.summary-row .txt .l { font-weight: 600; }
.summary-row .txt .d { color: var(--muted); font-size: 12px; display: block; margin-top: 2px; }
.notice { font-size: 13px; color: var(--muted); margin-top: 14px; line-height: 1.5; }
a { color: var(--primary2); }
@media (max-width: 640px) { form .grid { grid-template-columns: 1fr; } .row .detail { display: none; } }
</style>
</head>
<body>
<div class="wrap">
    <header>
        <h1>Lite<span>_Shelf</span> Setup</h1>
        <p>Database installer &amp; configuration</p>
    </header>

<?php if ($deletedFlag): ?>
    <div class="card">
        <div class="alert ok">setup.php has been deleted. Your installation is secure.</div>
        <div class="actions"><a class="btn secondary" href="../">Go to site &rarr;</a></div>
    </div>
<?php endif; ?>

<?php if ($deleteError): ?>
    <div class="alert err"><?= e($deleteError) ?></div>
<?php endif; ?>

<?php if ($installResult && !$installResult['success']): ?>
    <div class="card">
        <h2>Installation failed</h2>
        <div class="alert err"><?= e($installResult['error'] ?? 'Unknown error') ?></div>
        <?php if (!empty($installResult['steps'])): ?>
            <h2 style="font-size:15px; margin-top:18px;">Step log</h2>
            <?php foreach ($installResult['steps'] as $s): ?>
                <div class="summary-row">
                    <div class="badge <?= $s['ok'] ? 'ok' : 'err' ?>"><?= $s['ok'] ? '&#10003;' : '&#10007;' ?></div>
                    <div class="txt"><span class="l"><?= e($s['label']) ?></span><span class="d"><?= e($s['detail']) ?></span></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div class="actions">
            <a class="btn secondary" href="<?= e(strtok($_SERVER['REQUEST_URI'], '?')) ?>">Back to form</a>
        </div>
    </div>
<?php elseif ($installResult && $installResult['success']): ?>
    <div class="card">
        <h2>Installation complete</h2>
        <div class="alert ok">Lite_Shelf was installed successfully.</div>

        <h2 style="font-size:15px;">Step log</h2>
        <?php foreach ($installResult['steps'] as $s): ?>
            <div class="summary-row">
                <div class="badge <?= $s['ok'] ? 'ok' : 'err' ?>"><?= $s['ok'] ? '&#10003;' : '&#10007;' ?></div>
                <div class="txt"><span class="l"><?= e($s['label']) ?></span><span class="d"><?= e($s['detail']) ?></span></div>
            </div>
        <?php endforeach; ?>

        <?php if (!empty($installResult['adminKey'])): ?>
            <h2 style="font-size:15px; margin-top:20px;">Initial admin API key <span style="color:var(--warn); font-size:12px;">(shown once — save it now)</span></h2>
            <div class="codebox">
                <button class="copy" onclick="copyKey(this)">Copy</button>
                <span id="apikey" class="mono"><?= e($installResult['adminKey']) ?></span>
            </div>
            <p class="notice">Use this key in the <code>X-API-Key</code> header for API requests. It has admin privileges.</p>
        <?php endif; ?>

        <h2 style="font-size:15px; margin-top:20px;">Next steps</h2>
        <div class="codebox">
            <div>Dashboard login: <a href="<?= e(($installResult['appUrl'] ?: '.') . '/dashboard/login.php') ?>"><?= e(($installResult['appUrl'] ?: '') . '/dashboard/login.php') ?></a></div>
            <div style="margin-top:6px;">Dashboard user: <span class="mono"><?= e($installResult['adminUser']) ?></span></div>
            <div style="margin-top:6px;">Default app API: <a href="<?= e(($installResult['appUrl'] ?: '.') . '/template/lite-shelf/') ?>"><?= e(($installResult['appUrl'] ?: '') . '/template/lite-shelf/') ?></a></div>
            <div style="margin-top:6px;">Serve an asset: <span class="mono">storage/serve/{filename}</span></div>
        </div>

        <div class="alert warn" style="margin-top:18px;">
            <strong>Security:</strong> delete <code>setup.php</code> now to prevent reconfiguration by others.
        </div>
        <div class="actions">
            <a class="btn" href="<?= e(($installResult['appUrl'] ?: '.') . '/dashboard/login.php') ?>">Open dashboard</a>
            <form method="post" onsubmit="return confirm('Delete setup.php now? This cannot be undone.');">
                <input type="hidden" name="action" value="delete_self">
                <button type="submit" class="btn danger">Delete setup.php</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <h2>Pre-flight checks</h2>
        <?php foreach ($preflight as $c): ?>
            <div class="row">
                <div class="badge <?= $c['ok'] ? 'ok' : (isset($c['warning']) && $c['warning'] ? 'warn' : 'err') ?>">
                    <?= $c['ok'] ? '&#10003;' : (isset($c['warning']) && $c['warning'] ? '!' : '&#10007;') ?>
                </div>
                <div class="label"><?= e($c['label']) ?></div>
                <div class="detail"><?= e($c['detail']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h2>Database &amp; admin configuration</h2>
        <?php if (!$allOk): ?>
            <div class="alert warn">Some pre-flight checks failed. Fix them (e.g. file permissions) before installing. The form below is still available but installation may not succeed.</div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <input type="hidden" name="action" value="install">
            <div class="grid">
                <div class="field full">
                    <label for="db_host">DB Host / Domain</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                    <span class="hint">MySQL server hostname (e.g. localhost, 127.0.0.1, or your DB domain).</span>
                </div>
                <div class="field">
                    <label for="db_port">DB Port</label>
                    <input type="number" id="db_port" name="db_port" value="3306" required>
                </div>
                <div class="field">
                    <label for="db_name">DB Name</label>
                    <input type="text" id="db_name" name="db_name" value="lite_shelf" required>
                </div>
                <div class="field">
                    <label for="db_user">DB Username</label>
                    <input type="text" id="db_user" name="db_user" value="root" required>
                </div>
                <div class="field">
                    <label for="db_pass">DB Password</label>
                    <input type="password" id="db_pass" name="db_pass" value="" autocomplete="new-password">
                </div>
                <div class="field full">
                    <label for="db_prefix">Table Prefix (optional)</label>
                    <input type="text" id="db_prefix" name="db_prefix" value="" placeholder="e.g. myapp_">
                    <span class="hint">For the default app's tables. Leave empty for no prefix.</span>
                </div>
                <div class="field">
                    <label for="admin_user">Dashboard Admin Username</label>
                    <input type="text" id="admin_user" name="admin_user" value="admin">
                </div>
                <div class="field">
                    <label for="admin_pass">Dashboard Admin Password</label>
                    <input type="password" id="admin_pass" name="admin_pass" value="" autocomplete="new-password">
                    <span class="hint">Leave blank to keep default (admin123).</span>
                </div>
                <div class="field full">
                    <label for="admin_pass2">Confirm Admin Password</label>
                    <input type="password" id="admin_pass2" name="admin_pass2" value="" autocomplete="new-password">
                </div>
                <div class="field full">
                    <label for="app_url">App URL (optional)</label>
                    <input type="text" id="app_url" name="app_url" value="" placeholder="https://example.com">
                    <span class="hint">Used to build clickable links on the success screen only.</span>
                </div>
            </div>
            <div class="actions">
                <button type="submit" class="btn">Install</button>
            </div>
        </form>
        <p class="notice">Installation is idempotent and non-destructive: existing tables are kept (<code>CREATE TABLE IF NOT EXISTS</code>), the dashboard seed row is not duplicated, and an existing initial admin key is re-used. It is safe to re-run.</p>
    </div>
<?php endif; ?>

</div>
<script>
function copyKey(btn){
    var el=document.getElementById('apikey');
    if(!el)return;
    var t=el.innerText;
    if(navigator.clipboard&&navigator.clipboard.writeText){
        navigator.clipboard.writeText(t).then(function(){btn.innerText='Copied!';setTimeout(function(){btn.innerText='Copy';},1500);});
    }else{
        var r=document.createRange();r.selectNode(el);window.getSelection().removeAllRanges();window.getSelection().addRange(r);
        try{document.execCommand('copy');btn.innerText='Copied!';setTimeout(function(){btn.innerText='Copy';},1500);}catch(e){}
        window.getSelection().removeAllRanges();
    }
}
</script>
</body>
</html>
