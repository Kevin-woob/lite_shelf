#!/usr/bin/env python3
"""
Superadmin Dashboard — Create App & Run Test Suite

This script:
  1. Logs into the superadmin dashboard
  2. Creates a new app via the dashboard API
  3. Retrieves the new app's admin API key
  4. Runs apps/test9/test_api.py against the newly created app
  5. Optionally cleans up (deletes the app) after tests

Usage:
  python test_new_app.py [--cleanup] [--name my-test-app]
"""

import requests
import subprocess
import sys
import os
import argparse
import time
from pathlib import Path

# ==================== CONFIG ====================
DASHBOARD_BASE = "http://localhost/app-dashboard/dashboard/api.php"
DASHBOARD_USERNAME = "admin"
DASHBOARD_PASSWORD = "admin123"
APPS_BASE_URL = None  # derived from DASHBOARD_BASE if None
TEST_SCRIPT = Path(__file__).parent.parent / "apps" / "test9" / "test_api.py"

PASSED = 0
FAILED = 0


def print_header(title: str):
    print(f"\n{'='*60}")
    print(f"  {title}")
    print(f"{'='*60}\n")


def print_step(step: str):
    print(f"  [STEP] {step}...", end=" ")


def print_ok(detail: str = ""):
    global PASSED
    PASSED += 1
    print(f"\033[92mOK\033[0m")
    if detail:
        print(f"        {detail}")


def print_fail(detail: str = ""):
    global FAILED
    FAILED += 1
    print(f"\033[91mFAIL\033[0m - {detail}")


def safe_json(resp):
    """Safely parse JSON from response, handling PHP warnings in body."""
    try:
        return resp.json()
    except Exception:
        text = resp.text
        idx = text.find('{')
        if idx != -1:
            try:
                import json
                return json.loads(text[idx:])
            except Exception:
                pass
        return {}


# ==================== DASHBOARD API HELPERS ====================

def dashboard_login(session: requests.Session) -> bool:
    """Login to the superadmin dashboard."""
    print_step("Logging into dashboard")
    resp = session.post(
        DASHBOARD_BASE,
        params={"action": "login"},
        data={"username": DASHBOARD_USERNAME, "password": DASHBOARD_PASSWORD}
    )
    data = safe_json(resp)
    if data.get("success"):
        print_ok("Authenticated")
        return True
    print_fail(data.get("error", {}).get("message", "Unknown error"))
    return False


def dashboard_create_app(session: requests.Session, app_name: str) -> dict | None:
    """Create a new app via the dashboard API."""
    print_step(f"Creating app '{app_name}'")
    resp = session.post(
        DASHBOARD_BASE,
        params={"action": "create"},
        json={
            "name": app_name,
            "config": {"description": f"Auto-created for test suite run at {time.strftime('%H:%M:%S')}"}
        }
    )
    data = safe_json(resp)
    if data.get("success"):
        app = data["data"]
        provision_error = data.get("meta", {}).get("provision_error", "")
        if provision_error:
            print_fail(f"App created but provisioning failed: {provision_error}")
            print(f"        App id={app['id']}, status={app['status']}")
        else:
            print_ok(f"App created with id={app['id']}, status={app['status']}")
        return app
    print_fail(data.get("error", {}).get("message", "Unknown error"))
    return None


def dashboard_get_app(session: requests.Session, app_id: int) -> dict | None:
    """Get details of a specific app."""
    print_step(f"Getting app details (id={app_id})")
    resp = session.get(
        DASHBOARD_BASE,
        params={"action": "get", "id": app_id}
    )
    data = safe_json(resp)
    if data.get("success"):
        app = data["data"]
        print_ok(f"App: {app['name']}, status={app['status']}, folder={app['folder_path']}")
        return app
    print_fail(data.get("error", {}).get("message", "Unknown error"))
    return None


def dashboard_list_apps(session: requests.Session) -> list:
    """List all apps to verify creation."""
    print_step("Listing all apps")
    resp = session.get(
        DASHBOARD_BASE,
        params={"action": "list"}
    )
    data = safe_json(resp)
    if data.get("success"):
        apps = data["data"]
        print_ok(f"Total apps: {len(apps)}")
        for a in apps:
            print(f"        #{a['id']}  {a['name']}  [{a['status']}]  folder={a['folder_path']}")
        return apps
    print_fail(data.get("error", {}).get("message", "Unknown error"))
    return []


def dashboard_delete_app(session: requests.Session, app_id: int) -> bool:
    """Delete an app via the dashboard API."""
    print_step(f"Deleting app (id={app_id})")
    resp = session.post(
        DASHBOARD_BASE,
        params={"action": "delete", "id": app_id}
    )
    data = safe_json(resp)
    if data.get("success"):
        print_ok("App deleted")
        return True
    print_fail(data.get("error", {}).get("message", "Unknown error"))
    return False


def dashboard_get_stats(session: requests.Session) -> dict | None:
    """Get dashboard stats."""
    print_step("Getting dashboard stats")
    resp = session.get(
        DASHBOARD_BASE,
        params={"action": "stats"}
    )
    data = safe_json(resp)
    if data.get("success"):
        stats = data["data"]
        print_ok(f"Total={stats['total_apps']}, Active={stats['active_apps']}, "
                 f"Inactive={stats['inactive_apps']}, Error={stats['error_apps']}")
        return stats
    print_fail(data.get("error", {}).get("message", "Unknown error"))
    return None


# ==================== TEST RUNNER ====================

def run_test_script(app: dict) -> int:
    """
    Run test_api.py against the newly created app.
    Patches BASE_URL and ADMIN_API_KEY via environment variables / inline modification.
    """
    app_name = app["name"]
    folder_path = app["folder_path"]
    api_key = app["api_key"]
    apps_base = APPS_BASE_URL or f"http://localhost/app-dashboard/apps/"
    app_base_url = f"{apps_base}{folder_path}"

    print_header(f"Running Test Suite Against New App")
    print(f"  App name:     {app_name}")
    print(f"  Folder:       {folder_path}")
    print(f"  Base URL:     {app_base_url}")
    print(f"  API Key:      {api_key[:20]}...")
    print(f"  Test script:  {TEST_SCRIPT}")
    print()

    if not TEST_SCRIPT.exists():
        print(f"\033[91mERROR:\033[0m Test script not found at {TEST_SCRIPT}")
        return 1

    # We create a temporary copy of the test script with patched values
    # to avoid modifying the original file.
    import shutil
    import tempfile

    test_dir = TEST_SCRIPT.parent
    tmp_test = Path(tempfile.gettempdir()) / f"test_api_{folder_path}_{int(time.time())}.py"
    shutil.copy2(TEST_SCRIPT, tmp_test)

    try:
        # Read and patch the temp script
        content = tmp_test.read_text(encoding="utf-8")

        # Replace BASE_URL
        content = content.replace(
            f'BASE_URL = "http://localhost/app-dashboard/apps/test9"',
            f'BASE_URL = "{app_base_url}"'
        )

        # Replace ADMIN_API_KEY with the new app's key
        # Find the line and replace the value
        import re
        content = re.sub(
            r'ADMIN_API_KEY = "app_[a-f0-9]+"',
            f'ADMIN_API_KEY = "{api_key}"',
            content
        )

        # Replace REGULAR_API_KEY to use the same admin key (new app has no separate regular key)
        content = content.replace(
            'REGULAR_API_KEY = "your_regular_api_key_here"',
            f'REGULAR_API_KEY = "{api_key}"'
        )

        tmp_test.write_text(content, encoding="utf-8")

        # Run the test script
        print(f"  Executing: python {tmp_test}\n")
        result = subprocess.run(
            [sys.executable, str(tmp_test)],
            cwd=str(test_dir),
            capture_output=False,
            text=True
        )

        return result.returncode

    finally:
        # Clean up temp file
        if tmp_test.exists():
            tmp_test.unlink()


# ==================== MAIN ====================

def main():
    global PASSED, FAILED, DASHBOARD_BASE, DASHBOARD_USERNAME, DASHBOARD_PASSWORD, APPS_BASE_URL

    parser = argparse.ArgumentParser(description="Create a new app via dashboard and run its test suite")
    parser.add_argument("--name", type=str, default=None,
                        help="App name to create (default: auto-generated with timestamp)")
    parser.add_argument("--cleanup", action="store_true",
                        help="Delete the app after tests complete")
    parser.add_argument("--skip-tests", action="store_true",
                        help="Skip running the app test suite (only create + verify)")
    parser.add_argument("--url", default=DASHBOARD_BASE, help="Dashboard API URL")
    parser.add_argument("--apps-url", default=None, help="Apps base URL (dir)")
    parser.add_argument("--user", default=DASHBOARD_USERNAME, help="Admin username")
    parser.add_argument("--pass", dest="password", default=DASHBOARD_PASSWORD, help="Admin password")
    args = parser.parse_args()

    DASHBOARD_BASE = args.url
    DASHBOARD_USERNAME = args.user
    DASHBOARD_PASSWORD = args.password
    if args.apps_url:
        APPS_BASE_URL = args.apps_url

    # Generate unique app name
    if args.name:
        app_name = args.name
    else:
        ts = time.strftime("%Y%m%d_%H%M%S")
        app_name = f"test_auto_{ts}"

    print_header("Superadmin Dashboard — App Creation & Test Suite")
    print(f"  Dashboard URL:  {DASHBOARD_BASE}")
    print(f"  App name:       {app_name}")
    print(f"  Cleanup after:  {'Yes' if args.cleanup else 'No'}")
    print(f"  Skip tests:     {'Yes' if args.skip_tests else 'No'}")

    session = requests.Session()
    # Mod_Security on remote servers blocks Python's default UA; mimic browser
    session.headers.update({
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
        'Accept': 'application/json, text/javascript, */*; q=0.01',
        'X-Requested-With': 'XMLHttpRequest',
    })

    # Step 1: Login
    print_header("Step 1: Authentication")
    if not dashboard_login(session):
        print("\n\033[91mFATAL: Cannot proceed without dashboard login.\033[0m")
        sys.exit(1)

    # Step 2: Get stats before creation
    print_header("Step 2: Dashboard Stats (Before)")
    dashboard_get_stats(session)

    # Step 3: Create app
    print_header("Step 3: Create New App")
    app = dashboard_create_app(session, app_name)
    if not app:
        print("\n\033[91mFATAL: App creation failed.\033[0m")
        sys.exit(1)

    # Step 4: Verify app details
    print_header("Step 4: Verify App Details")
    app_details = dashboard_get_app(session, app["id"])
    if not app_details:
        print("\n\033[91mFATAL: Could not retrieve app details.\033[0m")
        if args.cleanup:
            dashboard_delete_app(session, app["id"])
        sys.exit(1)

    # Step 5: List all apps
    print_header("Step 5: List All Apps (Verification)")
    dashboard_list_apps(session)

    # Step 6: Run test suite (optional)
    test_exit_code = 0
    if not args.skip_tests:
        print_header("Step 6: Run App Test Suite")
        test_exit_code = run_test_script(app)
    else:
        print_header("Step 6: Run App Test Suite (SKIPPED)")

    # Step 7: Cleanup (optional)
    if args.cleanup:
        print_header("Step 7: Cleanup — Deleting App")
        dashboard_delete_app(session, app["id"])

        print_header("Step 8: Dashboard Stats (After)")
        dashboard_get_stats(session)

    # Final summary
    print_header("Final Summary")
    print(f"  App:            {app_name} (id={app['id']})")
    print(f"  Status:         {app['status']}")
    print(f"  Test exit code: {test_exit_code}")
    print(f"  Cleanup:        {'Done' if args.cleanup else 'Skipped'}")
    print(f"  {'='*60}")

    if test_exit_code == 0:
        print(f"\n  \033[92mAll tests passed!\033[0m\n")
    else:
        print(f"\n  \033[91mSome tests failed. Review output above.\033[0m\n")

    sys.exit(test_exit_code)


if __name__ == "__main__":
    main()
