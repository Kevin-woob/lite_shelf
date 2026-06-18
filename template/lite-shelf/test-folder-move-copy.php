<?php
/**
 * Test script for folder move/copy functionality
 */

require_once __DIR__ . '/lib/Database.php';
require_once __DIR__ . '/lib/StorageManager.php';

echo "=== Folder Move/Copy Test Suite ===\n\n";

$db = Database::getInstance();
$storageManager = new StorageManager();
$config = require __DIR__ . '/config/settings.php';
$uploadsBase = $config['uploads_base_path'];

$testTimestamp = date('YmdHis');
$pass = 0;
$fail = 0;

function testResult($label, $condition) {
    global $pass, $fail;
    if ($condition) {
        echo "  ✓ {$label}\n";
        $pass++;
    } else {
        echo "  ✗ FAIL: {$label}\n";
        $fail++;
    }
}

function createTestFolder($storageManager, $name, $parentPath) {
    return $storageManager->createFolder($name, $parentPath);
}

function createTestFileRecord($db, $folderPath, $filename) {
    $storedName = $folderPath . uniqid() . '_' . $filename;
    $db->insert('storage_files', [
        'filename_original' => $filename,
        'filename_stored' => $storedName,
        'folder_path' => $folderPath,
        'mime_type' => 'text/plain',
        'size_bytes' => 100,
        'uploaded_by_key_id' => null,
    ]);
    return $storedName;
}

function cleanupFolder($db, $path) {
    // Delete files
    $db->query("DELETE FROM storage_files WHERE folder_path LIKE ?", [$path . '%']);
    // Delete folders
    $db->query("DELETE FROM storage_folders WHERE path LIKE ?", [$path . '%']);
    // Delete filesystem
    $config = require __DIR__ . '/config/settings.php';
    $fsPath = $config['uploads_base_path'] . $path;
    if (is_dir($fsPath)) {
        rrmdir($fsPath);
    }
}

function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != '.' && $object != '..') {
                $path = $dir . DIRECTORY_SEPARATOR . $object;
                if (is_dir($path)) { rrmdir($path); } else { unlink($path); }
            }
        }
        rmdir($dir);
    }
}

// ============================================================
echo "TEST 1: Move folder to new parent\n";
echo str_repeat("-", 50) . "\n";

$srcFolder = "test-move-src-{$testTimestamp}";
$destFolder = "test-move-dest-{$testTimestamp}";

// Create source folder with subfolder and files
createTestFolder($storageManager, $srcFolder, '');
createTestFolder($storageManager, 'subfolder', $srcFolder);
$file1 = createTestFileRecord($db, $srcFolder . '/', 'file1.txt');
$file2 = createTestFileRecord($db, $srcFolder . '/subfolder/', 'file2.txt');

// Create filesystem directories
@mkdir($uploadsBase . $srcFolder, 0755, true);
@mkdir($uploadsBase . $srcFolder . '/subfolder', 0755, true);
file_put_contents($uploadsBase . $file1, 'test1');
file_put_contents($uploadsBase . $file2, 'test2');

// Create destination folder
createTestFolder($storageManager, $destFolder, '');
@mkdir($uploadsBase . $destFolder, 0755, true);

// Move
$result = $storageManager->moveFolder($srcFolder . '/', $destFolder . '/');
testResult('Move returned success', isset($result['success']) && $result['success']);

$newPath = $destFolder . '/' . $srcFolder . '/';
$folder = $db->fetchOne("SELECT * FROM storage_folders WHERE path = ?", [$newPath]);
testResult('Folder exists at new path', $folder !== null);

$oldFolder = $db->fetchOne("SELECT * FROM storage_folders WHERE path = ?", [$srcFolder . '/']);
testResult('Folder removed from old path', $oldFolder === null);

$subfolder = $db->fetchOne("SELECT * FROM storage_folders WHERE path = ?", [$newPath . 'subfolder/']);
testResult('Subfolder moved correctly', $subfolder !== null);

$files = $db->fetchAll("SELECT * FROM storage_files WHERE folder_path LIKE ?", [$newPath . '%']);
testResult('Files moved (' . count($files) . ' found, expected 2)', count($files) === 2);

testResult('Filesystem moved', is_dir($uploadsBase . $newPath));

cleanupFolder($db, $destFolder);

// ============================================================
echo "\nTEST 2: Move folder to root\n";
echo str_repeat("-", 50) . "\n";

$srcFolder2 = "test-move-root-{$testTimestamp}";
createTestFolder($storageManager, $srcFolder2, '');
@mkdir($uploadsBase . $srcFolder2, 0755, true);
createTestFileRecord($db, $srcFolder2 . '/', 'file.txt');

// Move to a different parent first (not root, since it's already at root)
$destFolder2 = "test-move-root-dest-{$testTimestamp}";
createTestFolder($storageManager, $destFolder2, '');
@mkdir($uploadsBase . $destFolder2, 0755, true);

$result = $storageManager->moveFolder($srcFolder2 . '/', $destFolder2 . '/');
testResult('Move to different parent succeeded', isset($result['success']) && $result['success']);

// Now move back to root
$moveBackPath = $destFolder2 . '/' . $srcFolder2 . '/';
$result2 = $storageManager->moveFolder($moveBackPath, '');
testResult('Move back to root succeeded', isset($result2['success']) && $result2['success']);

$folder = $db->fetchOne("SELECT * FROM storage_folders WHERE path = ?", [$srcFolder2 . '/']);
testResult('Folder exists at root', $folder !== null);

cleanupFolder($db, $srcFolder2);
cleanupFolder($db, $destFolder2);

// ============================================================
echo "\nTEST 3: Copy folder to new parent\n";
echo str_repeat("-", 50) . "\n";

$srcFolder3 = "test-copy-src-{$testTimestamp}";
$destFolder3 = "test-copy-dest-{$testTimestamp}";

createTestFolder($storageManager, $srcFolder3, '');
createTestFolder($storageManager, 'sub', $srcFolder3);
$file3 = createTestFileRecord($db, $srcFolder3 . '/', 'file1.txt');
$file4 = createTestFileRecord($db, $srcFolder3 . '/sub/', 'file2.txt');

@mkdir($uploadsBase . $srcFolder3, 0755, true);
@mkdir($uploadsBase . $srcFolder3 . '/sub', 0755, true);
file_put_contents($uploadsBase . $file3, 'test1');
file_put_contents($uploadsBase . $file4, 'test2');

createTestFolder($storageManager, $destFolder3, '');
@mkdir($uploadsBase . $destFolder3, 0755, true);

$result = $storageManager->copyFolder($srcFolder3 . '/', $destFolder3 . '/');
testResult('Copy returned success', isset($result['success']) && $result['success']);

// Original should still exist
$origFolder = $db->fetchOne("SELECT * FROM storage_folders WHERE path = ?", [$srcFolder3 . '/']);
testResult('Original folder still exists', $origFolder !== null);

$origFiles = $db->fetchAll("SELECT * FROM storage_files WHERE folder_path LIKE ?", [$srcFolder3 . '/%']);
testResult('Original files still exist (' . count($origFiles) . ')', count($origFiles) === 2);

// Copy should exist
$copyPath = $destFolder3 . '/' . $srcFolder3 . '/';
$copyFolder = $db->fetchOne("SELECT * FROM storage_folders WHERE path = ?", [$copyPath]);
testResult('Copy folder exists at destination', $copyFolder !== null);

$copySubfolder = $db->fetchOne("SELECT * FROM storage_folders WHERE path = ?", [$copyPath . 'sub/']);
testResult('Copy subfolder exists', $copySubfolder !== null);

$copyFiles = $db->fetchAll("SELECT * FROM storage_files WHERE folder_path LIKE ?", [$copyPath . '%']);
testResult('Copy files exist (' . count($copyFiles) . ', expected 2)', count($copyFiles) === 2);

testResult('Filesystem copy exists', is_dir($uploadsBase . $copyPath));

cleanupFolder($db, $srcFolder3);
cleanupFolder($db, $destFolder3);

// ============================================================
echo "\nTEST 4: Copy folder to root\n";
echo str_repeat("-", 50) . "\n";

$srcFolder4 = "test-copy-root-{$testTimestamp}";
createTestFolder($storageManager, $srcFolder4, '');
@mkdir($uploadsBase . $srcFolder4, 0755, true);
createTestFileRecord($db, $srcFolder4 . '/', 'file.txt');

// Copy to a different parent (not root since already at root)
$destFolder4 = "test-copy-root-dest-{$testTimestamp}";
createTestFolder($storageManager, $destFolder4, '');
@mkdir($uploadsBase . $destFolder4, 0755, true);

$result = $storageManager->copyFolder($srcFolder4 . '/', $destFolder4 . '/');
testResult('Copy to different parent succeeded', isset($result['success']) && $result['success']);

$copyPath4 = $destFolder4 . '/' . $srcFolder4 . '/';
$copyFolder4 = $db->fetchOne("SELECT * FROM storage_folders WHERE path = ?", [$copyPath4]);
testResult('Copy exists at destination', $copyFolder4 !== null);

cleanupFolder($db, $srcFolder4);
cleanupFolder($db, $destFolder4);

// ============================================================
echo "\nTEST 5: Error - Move folder into itself\n";
echo str_repeat("-", 50) . "\n";

$srcFolder5 = "test-err-self-{$testTimestamp}";
createTestFolder($storageManager, $srcFolder5, '');
$result = $storageManager->moveFolder($srcFolder5 . '/', $srcFolder5 . '/');
testResult('Correctly prevented: ' . ($result['error'] ?? 'none'), isset($result['error']));
cleanupFolder($db, $srcFolder5);

// ============================================================
echo "\nTEST 6: Error - Move folder into its subfolder\n";
echo str_repeat("-", 50) . "\n";

$srcFolder6 = "test-err-sub-{$testTimestamp}";
createTestFolder($storageManager, $srcFolder6, '');
createTestFolder($storageManager, 'child', $srcFolder6);
$result = $storageManager->moveFolder($srcFolder6 . '/', $srcFolder6 . '/child/');
testResult('Correctly prevented: ' . ($result['error'] ?? 'none'), isset($result['error']));
cleanupFolder($db, $srcFolder6);

// ============================================================
echo "\nTEST 7: Error - Move non-existent folder\n";
echo str_repeat("-", 50) . "\n";

$result = $storageManager->moveFolder('nonexistent-' . $testTimestamp . '/', '');
testResult('Correctly rejected: ' . ($result['error'] ?? 'none'), isset($result['error']));

// ============================================================
echo "\nTEST 8: Error - Copy folder into itself\n";
echo str_repeat("-", 50) . "\n";

$srcFolder8 = "test-err-copy-{$testTimestamp}";
createTestFolder($storageManager, $srcFolder8, '');
$result = $storageManager->copyFolder($srcFolder8 . '/', $srcFolder8 . '/');
testResult('Correctly prevented: ' . ($result['error'] ?? 'none'), isset($result['error']));
cleanupFolder($db, $srcFolder8);

// ============================================================
echo "\n" . str_repeat("=", 50) . "\n";
echo "Results: {$pass} passed, {$fail} failed\n";

if ($fail > 0) {
    echo "\n✗ Some tests failed!\n";
    exit(1);
} else {
    echo "\n✓ All tests passed!\n";
    exit(0);
}
