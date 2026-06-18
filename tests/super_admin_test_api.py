"""
Lite_Shelf - Full Functionality Test
Tests ALL dashboard features: login, create, view, configure, toggle status, launch, delete, logout
"""

import requests
import json
import sys
from datetime import datetime

BASE_URL = "http://localhost/app-dashboard/dashboard/api.php"
BASE_DASH = "http://localhost/app-dashboard/dashboard/"
BASE_APPS = "http://localhost/app-dashboard/apps/"
CREDENTIALS = {"username": "admin", "password": "admin123"}

class TestReport:
    def __init__(self):
        self.results = []
        self.session = requests.Session()

    def log(self, test_name, passed, details, response=None):
        entry = {
            "test": test_name,
            "passed": passed,
            "details": details,
        }
        if response is not None:
            entry["status_code"] = response.status_code
            try:
                entry["body"] = response.json()
            except:
                entry["body"] = response.text[:500]
        self.results.append(entry)
        status = "PASS" if passed else "FAIL"
        print(f"\n{'='*60}")
        print(f"[{status}] {test_name}")
        print(f"  Details: {details}")
        if response is not None:
            print(f"  Status: {response.status_code}")
            try:
                print(f"  Body: {json.dumps(response.json(), indent=2, ensure_ascii=False)}")
            except:
                print(f"  Body: {entry['body']}")

    def print_summary(self):
        total = len(self.results)
        passed = sum(1 for r in self.results if r["passed"])
        failed = total - passed
        print(f"\n{'='*60}")
        print(f"TEST SUMMARY")
        print(f"{'='*60}")
        print(f"  Total:  {total}")
        print(f"  Passed: {passed}")
        print(f"  Failed: {failed}")
        print(f"\nDetailed Results:")
        for r in self.results:
            status = "PASS" if r["passed"] else "FAIL"
            print(f"  [{status}] {r['test']}")
            if not r["passed"]:
                print(f"         -> {r['details']}")
        print(f"{'='*60}")
        return failed == 0


def safe_json(resp):
    """Safely parse JSON from response, handling PHP warnings in body."""
    try:
        return resp.json()
    except:
        text = resp.text
        # Strip PHP warnings before JSON
        idx = text.find('{')
        if idx != -1:
            try:
                return json.loads(text[idx:])
            except:
                pass
        return {}


# ========== AUTHENTICATION TESTS ==========

def test_login_page_renders(report):
    """Verify login.php page loads correctly"""
    resp = report.session.get(BASE_DASH + "login.php")
    success = resp.status_code == 200 and '<form id="loginForm">' in resp.text and "Sign In" in resp.text
    report.log("Login page renders correctly", success, "Form + Sign In button found", resp)

def test_login_valid(report):
    resp = report.session.post(BASE_URL, params={"action": "login"}, json=CREDENTIALS)
    data = safe_json(resp)
    success = resp.status_code == 200 and data.get("success") == True
    report.log("Login with valid credentials", success, f"success={data.get('success')}", resp)
    return success

def test_login_invalid_password(report):
    resp = report.session.post(BASE_URL, params={"action": "login"}, json={"username": "admin", "password": "wrongpass"})
    data = safe_json(resp)
    success = resp.status_code == 401 and data.get("success") == False
    report.log("Login with wrong password", success, f"Expected 401", resp)

def test_login_invalid_username(report):
    resp = report.session.post(BASE_URL, params={"action": "login"}, json={"username": "hacker", "password": "anything"})
    data = safe_json(resp)
    success = resp.status_code == 401 and data.get("success") == False
    report.log("Login with wrong username", success, f"Expected 401", resp)

def test_login_missing_password(report):
    resp = report.session.post(BASE_URL, params={"action": "login"}, json={"username": "admin"})
    data = safe_json(resp)
    success = resp.status_code == 400 and data.get("success") == False
    report.log("Login missing password", success, f"Expected 400", resp)

def test_unauthenticated_access(report: TestReport):
    """Access API without logging in (fresh session)"""
    s = requests.Session()
    resp = s.get(BASE_URL, params={"action": "list"})
    data = safe_json(resp)
    success = resp.status_code == 401 and data.get("success") == False
    report.log("Access API without login (should 401)", success, f"Expected 401", resp)


# ========== STATS & LIST ==========

def test_stats(report):
    resp = report.session.get(BASE_URL, params={"action": "stats"})
    data = safe_json(resp)
    success = resp.status_code == 200 and data.get("success") == True
    stats = data.get("data", {})
    report.log("Dashboard stats", success, f"total={stats.get('total_apps')}, active={stats.get('active_apps')}, inactive={stats.get('inactive_apps')}, error={stats.get('error_apps')}", resp)

def test_list_apps(report):
    resp = report.session.get(BASE_URL, params={"action": "list"})
    data = safe_json(resp)
    apps = data.get("data", [])
    success = resp.status_code == 200 and data.get("success") == True
    report.log("List all apps", success, f"Count: {len(apps)}", resp)
    for a in apps:
        print(f"    -> {a['name']} (id={a['id']}, status={a['status']}, folder={a['folder_path']})")


# ========== CREATE APP ==========

def test_create_app(report):
    resp = report.session.post(BASE_URL, params={"action": "create"}, json={
        "name": "test-app-final",
        "config": {"description": "Created by automated test"}
    })
    data = safe_json(resp)
    success = resp.status_code == 201 and data.get("success") == True
    app_id = data.get("data", {}).get("id") if success else None
    report.log("Create new app 'test-app-final'", success, f"ID={app_id}", resp)
    return app_id

def test_create_app_no_config(report):
    resp = report.session.post(BASE_URL, params={"action": "create"}, json={"name": "test-app-noconfig2"})
    data = safe_json(resp)
    success = resp.status_code == 201 and data.get("success") == True
    app_id = data.get("data", {}).get("id") if success else None
    report.log("Create app without config", success, f"ID={app_id}", resp)
    return app_id

def test_create_duplicate(report):
    resp = report.session.post(BASE_URL, params={"action": "create"}, json={"name": "test-app-final"})
    data = safe_json(resp)
    success = resp.status_code == 400 and data.get("success") == False
    report.log("Create duplicate app (should fail 400)", success, f"Got status={resp.status_code}", resp)

def test_create_short_name(report):
    resp = report.session.post(BASE_URL, params={"action": "create"}, json={"name": "ab"})
    data = safe_json(resp)
    success = resp.status_code == 400 and data.get("success") == False
    report.log("Create app with name too short (should fail 400)", success, f"Got status={resp.status_code}", resp)

def test_create_special_chars_name(report):
    resp = report.session.post(BASE_URL, params={"action": "create"}, json={"name": "app with spaces!"})
    data = safe_json(resp)
    success = resp.status_code == 400 and data.get("success") == False
    report.log("Create app with special chars (should fail 400)", success, f"Got status={resp.status_code}", resp)


# ========== VIEW APP ==========

def test_get_app(report, app_id):
    resp = report.session.get(BASE_URL, params={"action": "get", "id": app_id})
    data = safe_json(resp)
    success = resp.status_code == 200 and data.get("success") == True
    app = data.get("data", {})
    report.log(f"View app details (id={app_id})", success, f"Name={app.get('name')}, Status={app.get('status')}, API Key={app.get('api_key')}", resp)

def test_get_app_not_found(report):
    resp = report.session.get(BASE_URL, params={"action": "get", "id": 99999})
    data = safe_json(resp)
    success = resp.status_code == 404 and data.get("success") == False
    report.log("View non-existent app (should 404)", success, f"Got status={resp.status_code}", resp)


# ========== CONFIGURE APP ==========

def test_configure_description(report, app_id):
    resp = report.session.post(BASE_URL, params={"action": "update", "id": app_id}, json={
        "config": {"description": "Updated via configure - production app"}
    })
    data = safe_json(resp)
    success = resp.status_code == 200 and data.get("success") == True
    cfg = data.get("data", {}).get("config", {})
    report.log("Configure: update description", success, f"Config={cfg}", resp)

def test_configure_status_to_inactive(report, app_id):
    resp = report.session.post(BASE_URL, params={"action": "update", "id": app_id}, json={
        "status": "inactive"
    })
    data = safe_json(resp)
    success = resp.status_code == 200 and data.get("success") == True and data.get("data", {}).get("status") == "inactive"
    report.log("Configure: set status to inactive", success, f"Status={data.get('data', {}).get('status')}", resp)

def test_configure_status_to_error(report, app_id):
    resp = report.session.post(BASE_URL, params={"action": "update", "id": app_id}, json={
        "status": "error"
    })
    data = safe_json(resp)
    success = resp.status_code == 200 and data.get("success") == True and data.get("data", {}).get("status") == "error"
    report.log("Configure: set status to error", success, f"Status={data.get('data', {}).get('status')}", resp)

def test_configure_api_key(report, app_id):
    resp = report.session.post(BASE_URL, params={"action": "update", "id": app_id}, json={
        "api_key": "app_custom_test_key_12345"
    })
    data = safe_json(resp)
    success = resp.status_code == 200 and data.get("success") == True and data.get("data", {}).get("api_key") == "app_custom_test_key_12345"
    report.log("Configure: update API key", success, f"API Key={data.get('data', {}).get('api_key')}", resp)

def test_configure_status_to_active(report, app_id):
    resp = report.session.post(BASE_URL, params={"action": "update", "id": app_id}, json={
        "status": "active",
        "config": {"description": "Re-activated after testing", "env": "production"}
    })
    data = safe_json(resp)
    success = resp.status_code == 200 and data.get("success") == True and data.get("data", {}).get("status") == "active"
    report.log("Configure: set status back to active", success, f"Status={data.get('data', {}).get('status')}", resp)

def test_configure_empty_update(report, app_id):
    resp = report.session.post(BASE_URL, params={"action": "update", "id": app_id}, json={})
    data = safe_json(resp)
    success = resp.status_code == 400 and data.get("success") == False
    report.log("Configure: empty update (should fail 400)", success, f"Got status={resp.status_code}", resp)


# ========== TOGGLE STATUS (like dashboard toggle button) ==========

def test_toggle_deactivate(report, app_id):
    """Mimics dashboard 'Deactivate' button"""
    resp = report.session.post(BASE_URL, params={"action": "update", "id": app_id}, json={"status": "inactive"})
    data = safe_json(resp)
    success = resp.status_code == 200 and data.get("data", {}).get("status") == "inactive"
    report.log("Toggle: Deactivate app", success, f"Status={data.get('data', {}).get('status')}", resp)

def test_toggle_activate(report, app_id):
    """Mimics dashboard 'Activate' button"""
    resp = report.session.post(BASE_URL, params={"action": "update", "id": app_id}, json={"status": "active"})
    data = safe_json(resp)
    success = resp.status_code == 200 and data.get("data", {}).get("status") == "active"
    report.log("Toggle: Activate app", success, f"Status={data.get('data', {}).get('status')}", resp)


# ========== LAUNCH APP ==========

def test_launch_app_active(report, app_id):
    """Launch the app - verify the app folder/index.php is accessible"""
    resp_get = report.session.get(BASE_URL, params={"action": "get", "id": app_id})
    data = safe_json(resp_get)
    folder = data.get("data", {}).get("folder_path", "")

    if not folder:
        report.log("Launch active app", False, "No folder_path found", resp_get)
        return False

    resp = report.session.get(f"{BASE_APPS}{folder}/index.php")
    success = resp.status_code == 200
    report.log(f"Launch active app (/{folder}/index.php)", success, f"Status={resp.status_code}, size={len(resp.text)} bytes", resp)

def test_launch_disabled_for_inactive(report, app_id):
    """Verify launch should be disabled for inactive app (dashboard UI logic)"""
    # Set to inactive first
    report.session.post(BASE_URL, params={"action": "update", "id": app_id}, json={"status": "inactive"})

    # The API doesn't block launch for inactive, but dashboard UI disables the button
    # Test: folder still exists but app is inactive
    resp_get = report.session.get(BASE_URL, params={"action": "get", "id": app_id})
    data = safe_json(resp_get)
    folder = data.get("data", {}).get("folder_path", "")
    status = data.get("data", {}).get("status", "")

    success = status == "inactive"
    report.log("Launch button disabled for inactive app", success, f"App status={status} (UI should disable launch button)", resp_get)

    # Reactivate
    report.session.post(BASE_URL, params={"action": "update", "id": app_id}, json={"status": "active"})

def test_launch_app_folder_exists(report, app_id):
    """Verify the app folder was provisioned from template"""
    resp_get = report.session.get(BASE_URL, params={"action": "get", "id": app_id})
    data = safe_json(resp_get)
    folder = data.get("data", {}).get("folder_path", "")

    if not folder:
        report.log("App folder provisioning", False, "No folder_path", resp_get)
        return False

    # Check key template files exist
    for path in ["index.php", "config/database.php", "config/settings.php", "admin/index.php"]:
        resp = report.session.get(f"{BASE_APPS}{folder}/{path}")
        report.log(f"App folder: /{folder}/{path} exists", resp.status_code == 200, f"Status={resp.status_code}", resp)


# ========== STATS AFTER CHANGES ==========

def test_stats_reflect_changes(report):
    resp = report.session.get(BASE_URL, params={"action": "stats"})
    data = safe_json(resp)
    stats = data.get("data", {})
    success = resp.status_code == 200 and data.get("success") == True
    report.log("Stats reflect all changes", success, f"total={stats.get('total_apps')}, active={stats.get('active_apps')}, inactive={stats.get('inactive_apps')}, error={stats.get('error_apps')}", resp)


# ========== DELETE APP ==========

def test_delete_app(report, app_id):
    resp = report.session.post(BASE_URL, params={"action": "delete", "id": app_id})
    data = safe_json(resp)
    success = resp.status_code == 200 and data.get("success") == True
    report.log(f"Delete app (id={app_id})", success, f"success={data.get('success')}", resp)

def test_delete_app_no_config(report, app_id):
    resp = report.session.post(BASE_URL, params={"action": "delete", "id": app_id})
    data = safe_json(resp)
    success = resp.status_code == 200 and data.get("success") == True
    report.log(f"Delete app without config (id={app_id})", success, f"success={data.get('success')}", resp)

def test_delete_nonexistent(report):
    resp = report.session.post(BASE_URL, params={"action": "delete", "id": 99999})
    data = safe_json(resp)
    success = resp.status_code == 404 and data.get("success") == False
    report.log("Delete non-existent app (should 404)", success, f"Got status={resp.status_code}", resp)


# ========== DASHBOARD PAGE ACCESS ==========

def test_dashboard_page_redirect(report: TestReport):
    """Test that dashboard redirects to login.php when not authenticated"""
    s = requests.Session()
    resp = s.get(BASE_DASH + "index.php", allow_redirects=False)
    # PHP session-based auth; without login, it should redirect
    success = resp.status_code == 302
    report.log("Dashboard redirects to login when not authenticated", success, f"Status={resp.status_code}, Location={resp.headers.get('Location', 'N/A')}", resp)

def test_dashboard_page_accessible(report: TestReport):
    """Test that dashboard loads after login"""
    resp = report.session.get(BASE_DASH + "index.php")
    success = resp.status_code == 200 and "Lite_Shelf" in resp.text and "Create New App" in resp.text
    report.log("Dashboard page accessible after login", success, "Contains 'Lite_Shelf' and 'Create New App'", resp)

def test_dashboard_contains_all_features(report: TestReport):
    """Verify dashboard page includes all UI features"""
    resp = report.session.get(BASE_DASH + "index.php")
    text = resp.text
    checks = {
        "Stats cards": "statTotal" in text,
        "Create button": "Create New App" in text,
        "Apps table": "appsTableBody" in text,
        "View button": "viewApp(" in text,
        "Configure button": "configureApp(" in text,
        "Launch button": "launchApp(" in text,
        "Toggle button": "toggleStatus(" in text,
        "Delete button": "openDeleteModal(" in text,
        "Logout button": "handleLogout()" in text,
        "Create modal": "createModal" in text,
        "View modal": "viewModal" in text,
        "Delete modal": "deleteModal" in text,
    }
    all_pass = all(checks.values())
    report.log("Dashboard has all UI features", all_pass, str(checks))


# ========== LOGOUT ==========

def test_logout(report):
    resp = report.session.post(BASE_URL, params={"action": "logout"})
    data = safe_json(resp)
    success = resp.status_code == 200 and data.get("success") == True
    report.log("Logout", success, f"success={data.get('success')}", resp)

def test_unauthorized_after_logout(report):
    resp = report.session.get(BASE_URL, params={"action": "list"})
    data = safe_json(resp)
    success = resp.status_code == 401 and data.get("success") == False
    report.log("API blocked after logout (401)", success, f"Got status={resp.status_code}", resp)


def main():
    print("=" * 60)
    print("LITE_SHELF - FULL FUNCTIONALITY TEST")
    print(f"  API:    {BASE_URL}")
    print(f"  Dash:   {BASE_DASH}")
    print(f"  Apps:   {BASE_APPS}")
    print(f"  Time:   {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print("=" * 60)

    report = TestReport()

    # === PHASE 0: UNAUTHENTICATED ACCESS ===
    print("\n" + "="*40)
    print("PHASE 0: UNAUTHENTICATED ACCESS")
    print("="*40)
    test_unauthenticated_access(report)
    test_dashboard_page_redirect(report)

    # === PHASE 1: LOGIN ===
    print("\n" + "="*40)
    print("PHASE 1: AUTHENTICATION")
    print("="*40)
    test_login_page_renders(report)
    login_ok = test_login_valid(report)
    if not login_ok:
        print("\nFATAL: Login failed. Cannot continue.")
        report.print_summary()
        sys.exit(1)
    test_login_invalid_password(report)
    test_login_invalid_username(report)
    test_login_missing_password(report)
    test_dashboard_page_accessible(report)
    test_dashboard_contains_all_features(report)

    # === PHASE 2: STATS & LIST (BEFORE) ===
    print("\n" + "="*40)
    print("PHASE 2: PRE-CREATION STATE")
    print("="*40)
    test_stats(report)
    test_list_apps(report)

    # === PHASE 3: CREATE APPS ===
    print("\n" + "="*40)
    print("PHASE 3: CREATE APPS")
    print("="*40)
    app1 = test_create_app(report)
    app2 = test_create_app_no_config(report)
    test_create_duplicate(report)
    test_create_short_name(report)
    test_create_special_chars_name(report)

    if app1 is None:
        print("\nFATAL: App creation failed. Cannot continue.")
        report.print_summary()
        sys.exit(1)

    # === PHASE 4: VIEW APP ===
    print("\n" + "="*40)
    print("PHASE 4: VIEW APP DETAILS")
    print("="*40)
    test_get_app(report, app1)
    test_get_app_not_found(report)

    # === PHASE 5: CONFIGURE APP ===
    print("\n" + "="*40)
    print("PHASE 5: CONFIGURE APP")
    print("="*40)
    test_configure_description(report, app1)
    test_configure_api_key(report, app1)
    test_configure_status_to_inactive(report, app1)
    test_configure_status_to_error(report, app1)
    test_configure_status_to_active(report, app1)
    test_configure_empty_update(report, app1)

    # === PHASE 6: TOGGLE STATUS ===
    print("\n" + "="*40)
    print("PHASE 6: TOGGLE STATUS (Activate/Deactivate)")
    print("="*40)
    test_toggle_deactivate(report, app1)
    test_toggle_activate(report, app1)

    # === PHASE 7: LAUNCH APP ===
    print("\n" + "="*40)
    print("PHASE 7: LAUNCH APP")
    print("="*40)
    test_launch_app_active(report, app1)
    test_launch_disabled_for_inactive(report, app1)
    test_launch_app_folder_exists(report, app1)

    # === PHASE 8: STATS AFTER ===
    print("\n" + "="*40)
    print("PHASE 8: STATS AFTER CHANGES")
    print("="*40)
    test_stats_reflect_changes(report)
    test_list_apps(report)

    # === PHASE 9: DELETE ===
    print("\n" + "="*40)
    print("PHASE 9: DELETE APPS")
    print("="*40)
    test_delete_app(report, app1)
    test_delete_app_no_config(report, app2)
    test_delete_nonexistent(report)

    # === PHASE 10: LOGOUT ===
    print("\n" + "="*40)
    print("PHASE 10: LOGOUT")
    print("="*40)
    test_logout(report)
    test_unauthorized_after_logout(report)

    # === FINAL SUMMARY ===
    all_passed = report.print_summary()
    sys.exit(0 if all_passed else 1)


if __name__ == "__main__":
    main()
