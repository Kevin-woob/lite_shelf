# Complete Workflow Examples

Full end-to-end scripts showing the typical lifecycle: login → create → list → update → stats → delete → logout.

## Python

```python
import requests

BASE = 'http://your_site/dashboard/api.php'
s = requests.Session()

# 1. Login
r = s.post(BASE, params={'action': 'login'},
           json={'username': 'admin', 'password': 'admin123'})
assert r.json()['success'], 'Login failed'

# 2. Create an app
r = s.post(BASE, params={'action': 'create'},
           json={'name': 'demo-app', 'config': {'description': 'Demo'}})
app = r.json()['data']
app_id = app['id']
print(f'Created app #{app_id}: {app["name"]}')

# 3. List all apps
r = s.get(BASE, params={'action': 'list'})
for a in r.json()['data']:
    print(f'  #{a["id"]}  {a["name"]}  [{a["status"]}]')

# 4. Get single app
r = s.get(BASE, params={'action': 'get', 'id': app_id})
print('Single app:', r.json()['data']['name'])

# 5. Update — deactivate
r = s.post(BASE, params={'action': 'update', 'id': app_id},
           json={'status': 'inactive'})
print('Updated status:', r.json()['data']['status'])

# 6. Update — reactivate with new config
r = s.post(BASE, params={'action': 'update', 'id': app_id},
           json={'status': 'active', 'config': {'description': 'Updated demo'}})
print('Reactivated:', r.json()['data']['status'])

# 7. Stats
r = s.get(BASE, params={'action': 'stats'})
print('Stats:', r.json()['data'])

# 8. Delete
r = s.post(BASE, params={'action': 'delete', 'id': app_id})
print('Deleted:', r.json()['meta']['message'])

# 9. Logout
r = s.post(BASE, params={'action': 'logout'})
print('Logout:', r.json()['meta']['message'])
```

## PHP (CLI)

```php
<?php
$base = 'http://your_site/dashboard/api.php';
$cookie = '/tmp/dashboard_cookies.txt';

function api($action, $id = null, $body = null, $method = null) {
    global $base, $cookie;
    $url = "$base?action=$action";
    if ($id !== null) $url .= "&id=$id";
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_COOKIEJAR => $cookie,
    ];
    if ($body !== null || $method === 'POST') {
        $opts[CURLOPT_POST] = true;
        if ($body) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body);
            $opts[CURLOPT_HTTPHEADER] = ['Content-Type: application/json'];
        }
    }
    curl_setopt_array($ch, $opts);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
}

// 1. Login
$r = api('login', null, ['username' => 'admin', 'password' => 'admin123']);
echo "Login: " . ($r['success'] ? 'OK' : 'FAIL') . "\n";

// 2. Create
$r = api('create', null, ['name' => 'demo-app', 'config' => ['description' => 'Demo']]);
$appId = $r['data']['id'];
echo "Created app #$appId\n";

// 3. List
$r = api('list');
foreach ($r['data'] as $a) {
    echo "  #{$a['id']}  {$a['name']}  [{$a['status']}]\n";
}

// 4. Get
$r = api('get', $appId);
echo "Single: {$r['data']['name']}\n";

// 5. Update
$r = api('update', $appId, ['status' => 'inactive']);
echo "Status: {$r['data']['status']}\n";

// 6. Stats
$r = api('stats');
print_r($r['data']);

// 7. Delete
$r = api('delete', $appId);
echo "Deleted: {$r['meta']['message']}\n";

// 8. Logout
$r = api('logout');
echo "Logout: {$r['meta']['message']}\n";
```

## JavaScript (Node.js with node-fetch)

```javascript
// npm install node-fetch tough-cookie tough-cookie-fetch
import fetch, { FetchError } from 'node-fetch';
import { CookieJar } from 'tough-cookie';
import { wrapper } from 'tough-cookie-fetch';

const BASE = 'http://your_site/dashboard/api.php';
const jar = new CookieJar();
const fetchAuth = wrapper(fetch, jar);

async function api(action, id = null, body = null) {
    let url = `${BASE}?action=${action}`;
    if (id !== null) url += `&id=${id}`;
    const opts = { method: body ? 'POST' : 'GET' };
    if (body) {
        opts.headers = { 'Content-Type': 'application/json' };
        opts.body = JSON.stringify(body);
    }
    const res = await fetchAuth(url, opts);
    return res.json();
}

(async () => {
    // 1. Login
    let r = await api('login', null, { username: 'admin', password: 'admin123' });
    console.log('Login:', r.success ? 'OK' : 'FAIL');

    // 2. Create
    r = await api('create', null, { name: 'demo-app', config: { description: 'Demo' } });
    const appId = r.data.id;
    console.log(`Created app #${appId}`);

    // 3. List
    r = await api('list');
    r.data.forEach(a => console.log(`  #${a.id}  ${a.name}  [${a.status}]`));

    // 4. Get
    r = await api('get', appId);
    console.log('Single:', r.data.name);

    // 5. Update
    r = await api('update', appId, { status: 'inactive' });
    console.log('Status:', r.data.status);

    // 6. Stats
    r = await api('stats');
    console.log('Stats:', r.data);

    // 7. Delete
    r = await api('delete', appId);
    console.log('Deleted:', r.meta.message);

    // 8. Logout
    r = await api('logout');
    console.log('Logout:', r.meta.message);
})();
```

---

[← Back to Index](./index.md)
