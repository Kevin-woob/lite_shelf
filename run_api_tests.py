import requests
import json
import datetime
import random
from pathlib import Path

BASE_URL = "http://localhost/app-dashboard/apps/test9"
ADMIN_API = f"{BASE_URL}/admin/api.php"
MAIN_API = f"{BASE_URL}/index.php"
FUNCTIONS_API = f"{BASE_URL}/functions/index.php"

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

def safe_json(resp):
    try:
        return resp.json()
    except:
        return {}

print("=" * 60)
print("  App Dashboard - API Test Suite")
print(f"  Base URL: {BASE_URL}")
print("=" * 60)

# 1. Health Check
print("\n=== 1. Health Check ===")
resp = requests.get(f"{BASE_URL}/")
log("main", "health-check", resp)

resp = requests.get(f"{BASE_URL}/health")
log("main", "health-endpoint", resp)

# 2. Authentication
print("\n=== 2. Authentication ===")
resp = requests.post(f"{ADMIN_API}?route=/validate-admin-key", json={"api_key": ADMIN_API_KEY})
log("admin", "validate-admin-key", resp, {"api_key": "***"})

# Session-based login
login_session = requests.Session()
resp = login_session.post(f"{ADMIN_API}?route=/login", json={"api_key": ADMIN_API_KEY})
log("admin", "login", resp, {"api_key": "***"})

resp = login_session.get(f"{ADMIN_API}?route=/session-key")
log("admin", "session-key", resp)

# 3. Admin Stats
print("\n=== 3. Admin Stats ===")
resp = requests.get(f"{ADMIN_API}?route=/stats", headers={"X-API-Key": ADMIN_API_KEY})
log("admin", "stats", resp)

# 4. Admin Users
print("\n=== 4. Admin Users ===")
resp = requests.get(f"{ADMIN_API}?route=/users", headers={"X-API-Key": ADMIN_API_KEY})
log("admin", "list-users", resp)

email = f"testuser_{ts}@example.com"
resp = requests.post(f"{ADMIN_API}?route=/users",
    headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
    json={"email": email, "password": "testpass123", "display_name": "Test User"})
log("admin", "create-user", resp, {"email": email})

user_id = None
if safe_json(resp).get("success"):
    d = safe_json(resp).get("data", {})
    user_id = d.get("id")

if user_id:
    resp = requests.delete(f"{ADMIN_API}?route=/users/{user_id}", headers={"X-API-Key": ADMIN_API_KEY})
    log("admin", "delete-user", resp, {"user_id": user_id})

# 5. Admin API Keys
print("\n=== 5. Admin API Keys ===")
resp = requests.get(f"{ADMIN_API}?route=/api-keys", headers={"X-API-Key": ADMIN_API_KEY})
log("admin", "list-api-keys", resp)

resp = requests.post(f"{ADMIN_API}?route=/api-keys",
    headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
    json={"name": "Test Key", "is_admin": False, "rate_limit": 1000})
log("admin", "create-api-key", resp, {"name": "Test Key"})

key_id = None
if safe_json(resp).get("success"):
    key_id = safe_json(resp).get("data", {}).get("id")

if key_id:
    resp = requests.post(f"{ADMIN_API}?route=/api-keys/{key_id}/revoke", headers={"X-API-Key": ADMIN_API_KEY})
    log("admin", "revoke-api-key", resp, {"key_id": key_id})

# 6. Admin Collections CRUD
print("\n=== 6. Admin Collections ===")
coll_name = f"test_products_{ts}"

resp = requests.get(f"{ADMIN_API}?route=/collections", headers={"X-API-Key": ADMIN_API_KEY})
log("admin", "list-collections", resp)

resp = requests.post(f"{ADMIN_API}?route=/collections",
    headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
    json={"name": coll_name, "description": "Test catalog"})
log("admin", "create-collection", resp, {"name": coll_name})

resp = requests.get(f"{ADMIN_API}?route=/collections/{coll_name}", headers={"X-API-Key": ADMIN_API_KEY})
log("admin", "get-collection", resp)

# Create document
resp = requests.post(f"{ADMIN_API}?route=/collections/{coll_name}/documents",
    headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
    json={"title": "Widget", "price": 9.99, "in_stock": True})
log("admin", "create-document", resp, {"title": "Widget"})

doc_id = None
if safe_json(resp).get("success"):
    doc_id = safe_json(resp).get("data", {}).get("id")

if doc_id:
    resp = requests.get(f"{ADMIN_API}?route=/collections/{coll_name}/documents/{doc_id}", headers={"X-API-Key": ADMIN_API_KEY})
    log("admin", "get-document", resp)
    
    resp = requests.patch(f"{ADMIN_API}?route=/collections/{coll_name}/documents/{doc_id}",
        headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
        json={"price": 12.99})
    log("admin", "update-document", resp, {"price": 12.99})
    
    resp = requests.delete(f"{ADMIN_API}?route=/collections/{coll_name}/documents/{doc_id}", headers={"X-API-Key": ADMIN_API_KEY})
    log("admin", "delete-document", resp)

# Copy collection
resp = requests.post(f"{ADMIN_API}?route=/collections/{coll_name}/copy",
    headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
    json={"new_name": f"{coll_name}_backup"})
log("admin", "copy-collection", resp, {"new_name": f"{coll_name}_backup"})

# Rename collection
renamed = f"{coll_name}_renamed"
resp = requests.patch(f"{ADMIN_API}?route=/collections/{coll_name}",
    headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
    json={"name": renamed})
log("admin", "rename-collection", resp, {"name": renamed})

# Delete collections
resp = requests.delete(f"{ADMIN_API}?route=/collections/{renamed}", headers={"X-API-Key": ADMIN_API_KEY})
log("admin", "delete-collection-renamed", resp)

resp = requests.delete(f"{ADMIN_API}?route=/collections/{coll_name}_backup", headers={"X-API-Key": ADMIN_API_KEY})
log("admin", "delete-collection-backup", resp)

# 7. Main API - Collections
print("\n=== 7. Main API Collections ===")
main_coll = f"main_test_{ts}"
requests.post(f"{ADMIN_API}?route=/collections",
    headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
    json={"name": main_coll})

resp = requests.get(f"{BASE_URL}/collections/{main_coll}/documents", headers={"X-API-Key": ADMIN_API_KEY})
log("main", "list-documents", resp)

resp = requests.post(f"{BASE_URL}/collections/{main_coll}/documents",
    headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
    json={"name": "John Doe", "email": "john@example.com", "age": 30})
log("main", "create-document", resp, {"name": "John Doe"})

doc_id = None
if safe_json(resp).get("success"):
    doc_id = safe_json(resp).get("data", {}).get("id")

if doc_id:
    resp = requests.get(f"{BASE_URL}/collections/{main_coll}/documents/{doc_id}", headers={"X-API-Key": ADMIN_API_KEY})
    log("main", "get-document", resp)
    
    resp = requests.patch(f"{BASE_URL}/collections/{main_coll}/documents/{doc_id}",
        headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
        json={"age": 31})
    log("main", "update-document", resp, {"age": 31})
    
    resp = requests.put(f"{BASE_URL}/collections/{main_coll}/documents/{doc_id}",
        headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
        json={"name": "Jane Doe"})
    log("main", "replace-document", resp, {"name": "Jane Doe"})
    
    resp = requests.get(f"{BASE_URL}/collections/{main_coll}/documents?field=age&operator=>&value=25", headers={"X-API-Key": ADMIN_API_KEY})
    log("main", "query-documents", resp)
    
    resp = requests.delete(f"{BASE_URL}/collections/{main_coll}/documents/{doc_id}", headers={"X-API-Key": ADMIN_API_KEY})
    log("main", "delete-document", resp)

requests.delete(f"{ADMIN_API}?route=/collections/{main_coll}", headers={"X-API-Key": ADMIN_API_KEY})

# 8. Storage API
print("\n=== 8. Storage API ===")
temp_file = Path("test_upload.txt")
temp_file.write_text("Hello, this is a test file content!")

with open(temp_file, "rb") as f:
    resp = requests.post(f"{BASE_URL}/storage/upload",
        headers={"X-API-Key": ADMIN_API_KEY},
        files={"file": ("test_upload.txt", f)},
        data={"folder_path": ""})
log("storage", "upload-file", resp)

filename = None
if safe_json(resp).get("success"):
    filename = safe_json(resp).get("data", {}).get("filename")

if filename:
    base_fn = filename.split("/")[-1]
    resp = requests.get(f"{BASE_URL}/storage/files/{base_fn}")
    log("storage", "get-file", resp)
    
    resp = requests.delete(f"{BASE_URL}/storage/files/{base_fn}", headers={"X-API-Key": ADMIN_API_KEY})
    log("storage", "delete-file", resp)

temp_file.unlink(missing_ok=True)

# 9. Storage Folders
print("\n=== 9. Storage Folders ===")
folder_name = f"test_folder_api_{ts}"

resp = requests.get(f"{ADMIN_API}?route=/storage/folders&parent_path=", headers={"X-API-Key": ADMIN_API_KEY})
log("folders", "list-folders", resp)

resp = requests.post(f"{ADMIN_API}?route=/storage/folders",
    headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
    json={"name": folder_name, "parent_path": ""})
log("folders", "create-folder", resp, {"name": folder_name})

import urllib.parse
encoded = urllib.parse.quote(f"{folder_name}/", safe="")
new_name = f"renamed_{ts}"
resp = requests.patch(f"{ADMIN_API}?route=/storage/folders/{encoded}",
    headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
    json={"name": new_name})
log("folders", "rename-folder", resp, {"name": new_name})

# Move
move_target = f"move_target_{ts}"
requests.post(f"{ADMIN_API}?route=/storage/folders",
    headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
    json={"name": move_target, "parent_path": ""})

encoded_renamed = urllib.parse.quote(f"{new_name}/", safe="")
resp = requests.post(f"{ADMIN_API}?route=/storage/folders/{encoded_renamed}/move",
    headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
    json={"parent_path": f"{move_target}/"})
log("folders", "move-folder", resp, {"parent_path": f"{move_target}/"})

# Copy
encoded_moved = urllib.parse.quote(f"{move_target}/{new_name}/", safe="")
resp = requests.post(f"{ADMIN_API}?route=/storage/folders/{encoded_moved}/copy",
    headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
    json={"parent_path": ""})
log("folders", "copy-folder", resp)

# List all paths
resp = requests.get(f"{ADMIN_API}?route=/storage/folders/all-paths", headers={"X-API-Key": ADMIN_API_KEY})
log("folders", "list-all-paths", resp)

# Delete
resp = requests.delete(f"{ADMIN_API}?route=/storage/folders/{encoded_moved}", headers={"X-API-Key": ADMIN_API_KEY})
log("folders", "delete-moved-folder", resp)

encoded_copy = urllib.parse.quote(f"{new_name}/", safe="")
resp = requests.delete(f"{ADMIN_API}?route=/storage/folders/{encoded_copy}", headers={"X-API-Key": ADMIN_API_KEY})
log("folders", "delete-copied-folder", resp)

encoded_parent = urllib.parse.quote(f"{move_target}/", safe="")
resp = requests.delete(f"{ADMIN_API}?route=/storage/folders/{encoded_parent}", headers={"X-API-Key": ADMIN_API_KEY})
log("folders", "delete-parent-folder", resp)

# 10. Storage File Operations (Admin)
print("\n=== 10. Storage File Admin Operations ===")
admin_file = Path("admin_test_upload.txt")
admin_file.write_text("Admin test content")

with open(admin_file, "rb") as f:
    resp = requests.post(f"{ADMIN_API}?route=/storage/upload",
        headers={"X-API-Key": ADMIN_API_KEY},
        files={"file": ("admin_test.txt", f)},
        data={"folder_path": "admin_test/"})
log("admin-storage", "upload-file", resp)

file_id = None
if safe_json(resp).get("success"):
    file_id = safe_json(resp).get("data", {}).get("id")

if file_id:
    resp = requests.get(f"{ADMIN_API}?route=/storage/files&folder_path=admin_test/", headers={"X-API-Key": ADMIN_API_KEY})
    log("admin-storage", "list-files", resp)
    
    resp = requests.get(f"{ADMIN_API}?route=/storage/files/{file_id}/download", headers={"X-API-Key": ADMIN_API_KEY})
    log("admin-storage", "download-file", resp)
    
    resp = requests.post(f"{ADMIN_API}?route=/storage/files/{file_id}/rename",
        headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
        json={"name": "renamed_test.txt"})
    log("admin-storage", "rename-file", resp)
    
    resp = requests.post(f"{ADMIN_API}?route=/storage/files/{file_id}/move",
        headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
        json={"folder_path": ""})
    log("admin-storage", "move-file", resp)
    
    resp = requests.post(f"{ADMIN_API}?route=/storage/files/{file_id}/copy",
        headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
        json={"folder_path": ""})
    log("admin-storage", "copy-file", resp)
    
    resp = requests.delete(f"{ADMIN_API}?route=/storage/files/{file_id}", headers={"X-API-Key": ADMIN_API_KEY})
    log("admin-storage", "delete-file", resp)

admin_file.unlink(missing_ok=True)

# 11. Notifications
print("\n=== 11. Notifications ===")
resp = requests.get(f"{BASE_URL}/notifications?limit=10&offset=0", headers={"X-API-Key": ADMIN_API_KEY})
log("notifications", "list-notifications", resp)

resp = requests.post(f"{BASE_URL}/notifications",
    headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
    json={"message": "Test notification!", "data": {"test": True, "user_id": 42}})
log("notifications", "send-notification", resp, {"message": "Test notification!"})

notif_id = None
if safe_json(resp).get("success"):
    notif_id = safe_json(resp).get("data", {}).get("id")

if notif_id:
    resp = requests.post(f"{BASE_URL}/notifications/{notif_id}/read", headers={"X-API-Key": ADMIN_API_KEY})
    log("notifications", "mark-read", resp)
    
    resp = requests.delete(f"{BASE_URL}/notifications/{notif_id}", headers={"X-API-Key": ADMIN_API_KEY})
    log("notifications", "delete-notification", resp)

# 12. Cloud Functions
print("\n=== 12. Cloud Functions ===")
hello_path = Path(__file__).parent / "apps" / "test9" / "functions" / "hello.php"
if hello_path.exists():
    resp = requests.post(f"{FUNCTIONS_API}/hello",
        headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
        json={"name": "API Tester"})
    log("functions", "hello", resp, {"name": "API Tester"})
else:
    print("  [SKIP] hello.php not found")

# 13. Access Control
print("\n=== 13. Access Control ===")
resp = requests.post(f"{ADMIN_API}?route=/api-keys",
    headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
    json={"name": "AC Test Key", "is_admin": False})
log("access", "create-limited-key", resp)

ac_key_id = None
if safe_json(resp).get("success"):
    ac_key_id = safe_json(resp).get("data", {}).get("id")

if ac_key_id:
    # Create test collection for access control
    ac_coll = f"ac_test_{ts}"
    requests.post(f"{ADMIN_API}?route=/collections",
        headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
        json={"name": ac_coll})
    
    # Get collection ID
    collection_id = None
    resp = requests.get(f"{ADMIN_API}?route=/collections", headers={"X-API-Key": ADMIN_API_KEY})
    if resp.status_code == 200:
        cols = safe_json(resp).get("collections", [])
        for c in cols:
            if c.get("name") == ac_coll:
                collection_id = c.get("id")
                break
    
    if collection_id:
        resp = requests.post(f"{ADMIN_API}?route=/api-keys/{ac_key_id}/grant-collection",
            headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
            json={"collection_id": collection_id, "access_level": "write"})
        log("access", "grant-collection", resp, {"collection_id": collection_id})
        
        resp = requests.get(f"{ADMIN_API}?route=/api-keys/{ac_key_id}/permissions", headers={"X-API-Key": ADMIN_API_KEY})
        log("access", "get-permissions", resp)
        
        resp = requests.post(f"{ADMIN_API}?route=/api-keys/{ac_key_id}/revoke-collection",
            headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
            json={"collection_id": collection_id})
        log("access", "revoke-collection", resp, {"collection_id": collection_id})
        
        requests.delete(f"{ADMIN_API}?route=/collections/{ac_coll}", headers={"X-API-Key": ADMIN_API_KEY})
    
    # Folder access
    resp = requests.post(f"{ADMIN_API}?route=/api-keys/{ac_key_id}/grant-folder",
        headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
        json={"folder_path": "test_folder/", "access_level": "read"})
    log("access", "grant-folder", resp)
    
    resp = requests.post(f"{ADMIN_API}?route=/api-keys/{ac_key_id}/revoke-folder",
        headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
        json={"folder_path": "test_folder/"})
    log("access", "revoke-folder", resp)
    
    requests.post(f"{ADMIN_API}?route=/api-keys/{ac_key_id}/revoke", headers={"X-API-Key": ADMIN_API_KEY})

# 14. Admin Logout
print("\n=== 14. Admin Logout ===")
resp = login_session.post(f"{ADMIN_API}?route=/logout")
log("admin", "logout", resp)

# Save results
output_file = Path("api_test_results.json")
with open(output_file, "w") as f:
    json.dump(results, f, indent=2, ensure_ascii=False)

print("\n" + "=" * 60)
print(f"All tests completed! Results saved to {output_file}")
print(f"Total tests: {len(results)}")
print("=" * 60)
