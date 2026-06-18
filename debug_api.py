import requests
import json
import datetime
import random
from pathlib import Path
import urllib.parse

BASE_URL = "http://localhost/app-dashboard/apps/test9"
ADMIN_API = f"{BASE_URL}/admin/api.php"
MAIN_API = f"{BASE_URL}/index.php"
FUNCTIONS_API = f"{BASE_URL}/functions/index.php"

# Test9 admin API key - might need to create a new one
ADMIN_API_KEY = "app_15b2daba18cb2b1f5ae8d0d6a3f79c61159e649a81f9d778ea8533749d11c1ee"

results = {}
ts = datetime.datetime.now().strftime("%H%M%S") + f"{random.randint(100,999)}"

def log(section, endpoint, response, data=None):
    try:
        resp_content = response.json()
    except:
        resp_content = response.text[:500]
    key = f"{section}_{endpoint}"
    results[key] = {
        "url": str(response.url),
        "status_code": response.status_code,
        "method": response.request.method,
        "request_data": data,
        "response": resp_content
    }
    status = "OK" if 200 <= response.status_code < 300 else f"FAIL({response.status_code})"
    print(f"  [{status}] {section}/{endpoint}")
    if not (200 <= response.status_code < 300):
        print(f"         Response: {resp_content}")

def safe_json(resp):
    try:
        return resp.json()
    except:
        return {}

# First, check if key is valid
print("Checking API key validity...")
resp = requests.post(f"{ADMIN_API}?route=/validate-admin-key", json={"api_key": ADMIN_API_KEY})
print(f"  Validate response: {resp.json()}")

# Try the session key endpoint first to see what key is stored
print("\nTrying session check...")
resp = requests.get(f"{ADMIN_API}?route=/session-key")
print(f"  Session response: {resp.json()}")

# List existing API keys to see what's available
print("\nListing existing API keys...")
# Try without auth to see what happens
resp = requests.get(f"{ADMIN_API}?route=/api-keys")
print(f"  No auth: {resp.status_code} - {resp.json()}")

# The validate-admin-key works, so the key is valid. Let's check if it's admin
resp = requests.post(f"{ADMIN_API}?route=/validate-admin-key", json={"api_key": ADMIN_API_KEY})
data = resp.json()
print(f"  Key validation: {data}")
print(f"  Is admin: {data.get('is_admin', 'N/A')}")

# Try login
resp = requests.post(f"{ADMIN_API}?route=/login", json={"api_key": ADMIN_API_KEY})
print(f"\nLogin response: {resp.json()}")

# Try with a different approach - maybe the key format needs the app_ prefix stripped
test_key = ADMIN_API_KEY
if ADMIN_API_KEY.startswith("app_"):
    test_key = ADMIN_API_KEY[4:]
    print(f"\nTrying without 'app_' prefix: {test_key[:20]}...")
    resp = requests.post(f"{ADMIN_API}?route=/validate-admin-key", json={"api_key": test_key})
    print(f"  Validate: {resp.json()}")
    
    resp = requests.post(f"{ADMIN_API}?route=/login", json={"api_key": test_key})
    print(f"  Login: {resp.json()}")
