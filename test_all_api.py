#!/usr/bin/env python3
"""
Comprehensive API test script for App Management Dashboard (Test9).
Tests all endpoints and records actual responses.
"""
import requests
import json
import time
import os
from pathlib import Path

BASE_URL = "http://localhost/app-dashboard/apps/test9"
ADMIN_API = f"{BASE_URL}/admin/api.php"
MAIN_API = f"{BASE_URL}/index.php"
FUNCTIONS_API = f"{BASE_URL}/functions/index.php"

# Test9 admin API key from existing test script
ADMIN_API_KEY = "app_15b2daba18cb2b1f5ae8d0d6a3f79c61159e649a81f9d778ea8533749d11c1ee"

# Store all results
results = {}

def log(section, endpoint, response, data=None):
    """Log a test result."""
    try:
        resp_content = response.json()
    except:
        resp_content = response.text[:500]
    results[f"{section}_{endpoint}"] = {
        "url": response.url,
        "status_code": response.status_code,
        "method": response.request.method,
        "request_data": data,
        "response": resp_content
    }
    print(f"  ✓ {section}/{endpoint}: HTTP {response.status_code}")

def test_admin_login():
    """Test admin login and get API key."""
    print("\n=== Testing Admin Login ===")
    
    # Login to get/create admin API key
    resp = requests.post(
        f"{ADMIN_API}?route=/login",
        json={"api_key": ADMIN_API_KEY}
    )
    log("admin", "login", resp, {"api_key": "***"})
    try:
        return resp.json()
    except:
        return {}

def test_validate_admin_key():
    """Test API key validation."""
    print("\n=== Testing Validate Admin Key ===")
    
    resp = requests.post(
        f"{ADMIN_API}?route=/validate-admin-key",
        json={"api_key": ADMIN_API_KEY}
    )
    log("admin", "validate-key", resp, {"api_key": ADMIN_API_KEY})
    return resp.json()

def test_admin_session_key():
    """Test session key check."""
    print("\n=== Testing Session Key ===")
    
    # First login to establish session
    login_session = requests.Session()
    login_session.post(f"{ADMIN_API}?route=/login", json={"api_key": ADMIN_API_KEY})
    
    resp = login_session.get(f"{ADMIN_API}?route=/session-key")
    log("admin", "session-key", resp)
    return resp.json()

def test_admin_stats():
    """Test dashboard stats."""
    print("\n=== Testing Admin Stats ===")
    
    resp = requests.get(
        f"{ADMIN_API}?route=/stats",
        headers={"X-API-Key": ADMIN_API_KEY}
    )
    log("admin", "stats", resp)
    return resp.json()

def test_admin_users():
    """Test user management."""
    print("\n=== Testing Admin Users ===")
    
    # List users
    resp = requests.get(
        f"{ADMIN_API}?route=/users",
        headers={"X-API-Key": ADMIN_API_KEY}
    )
    log("admin", "list-users", resp)
    
    # Create user
    resp = requests.post(
        f"{ADMIN_API}?route=/users",
        headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
        json={"email": "testuser@example.com", "password": "testpass123", "display_name": "Test User"}
    )
    log("admin", "create-user", resp, {"email": "testuser@example.com"})
    
    # List users after creation
    resp = requests.get(
        f"{ADMIN_API}?route=/users",
        headers={"X-API-Key": ADMIN_API_KEY}
    )
    log("admin", "list-users-after-create", resp)
    
    # Get user ID for deletion
    users_data = resp.json()
    user_id = None
    if "data" in users_data and isinstance(users_data["data"], list):
        for user in users_data["data"]:
            if user.get("email") == "testuser@example.com":
                user_id = user.get("id")
                break
    
    if user_id:
        # Delete user
        resp = requests.delete(
            f"{ADMIN_API}?route=/users/{user_id}",
            headers={"X-API-Key": ADMIN_API_KEY}
        )
        log("admin", "delete-user", resp, {"user_id": user_id})
    
    return users_data

def test_admin_api_keys():
    """Test API key management."""
    print("\n=== Testing Admin API Keys ===")
    
    # List API keys
    resp = requests.get(
        f"{ADMIN_API}?route=/api-keys",
        headers={"X-API-Key": ADMIN_API_KEY}
    )
    log("admin", "list-api-keys", resp)
    
    # Create API key
    resp = requests.post(
        f"{ADMIN_API}?route=/api-keys",
        headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
        json={"name": "Test App Key", "is_admin": False, "rate_limit": 1000}
    )
    log("admin", "create-api-key", resp, {"name": "Test App Key"})
    
    # Get the created key ID and actual key value
    key_id = None
    actual_key = None
    if resp.json().get("success"):
        key_data = resp.json().get("data", {})
        key_id = key_data.get("id")
        actual_key = key_data.get("key")  # The plain-text key
    
    if key_id:
        # Revoke key
        resp = requests.post(
            f"{ADMIN_API}?route=/api-keys/{key_id}/revoke",
            headers={"X-API-Key": ADMIN_API_KEY}
        )
        log("admin", "revoke-api-key", resp, {"key_id": key_id})
    
    return actual_key

def test_admin_collections():
    """Test collection management."""
    print("\n=== Testing Admin Collections ===")
    
    # List collections
    resp = requests.get(
        f"{ADMIN_API}?route=/collections",
        headers={"X-API-Key": ADMIN_API_KEY}
    )
    log("admin", "list-collections", resp)
    
    # Create collection
    resp = requests.post(
        f"{ADMIN_API}?route=/collections",
        headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
        json={"name": "test_collection", "description": "Test collection"}
    )
    log("admin", "create-collection", resp, {"name": "test_collection"})
    
    # Get collection items
    resp = requests.get(
        f"{ADMIN_API}?route=/collections/test_collection",
        headers={"X-API-Key": ADMIN_API_KEY}
    )
    log("admin", "get-collection", resp)
    
    # Create document in collection
    resp = requests.post(
        f"{ADMIN_API}?route=/collections/test_collection/documents",
        headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
        json={"title": "Test Document", "content": "This is a test", "status": "draft"}
    )
    log("admin", "create-document", resp, {"title": "Test Document"})
    
    # Get document
    doc_id = None
    if resp.json().get("success"):
        doc_data = resp.json().get("data", {})
        doc_id = doc_data.get("id")
    
    if doc_id:
        # Get single document
        resp = requests.get(
            f"{ADMIN_API}?route=/collections/test_collection/documents/{doc_id}",
            headers={"X-API-Key": ADMIN_API_KEY}
        )
        log("admin", "get-document", resp)
        
        # Update document
        resp = requests.patch(
            f"{ADMIN_API}?route=/collections/test_collection/documents/{doc_id}",
            headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
            json={"status": "published", "updated": True}
        )
        log("admin", "update-document", resp, {"status": "published"})
        
        # Delete document
        resp = requests.delete(
            f"{ADMIN_API}?route=/collections/test_collection/documents/{doc_id}",
            headers={"X-API-Key": ADMIN_API_KEY}
        )
        log("admin", "delete-document", resp)
    
    # Copy collection
    resp = requests.post(
        f"{ADMIN_API}?route=/collections/test_collection/copy",
        headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
        json={"new_name": "test_collection_copy"}
    )
    log("admin", "copy-collection", resp, {"new_name": "test_collection_copy"})
    
    # Rename collection
    resp = requests.patch(
        f"{ADMIN_API}?route=/collections/test_collection_copy",
        headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
        json={"name": "test_collection_renamed"}
    )
    log("admin", "rename-collection", resp, {"name": "test_collection_renamed"})
    
    # Delete collection
    resp = requests.delete(
        f"{ADMIN_API}?route=/collections/test_collection_renamed",
        headers={"X-API-Key": ADMIN_API_KEY}
    )
    log("admin", "delete-collection", resp)
    
    # Delete original collection
    resp = requests.delete(
        f"{ADMIN_API}?route=/collections/test_collection",
        headers={"X-API-Key": ADMIN_API_KEY}
    )
    log("admin", "delete-original-collection", resp)
    
    return resp.json()

def test_main_collections(api_key=None):
    """Test main API collections."""
    print("\n=== Testing Main API Collections ===")
    
    headers = {"X-API-Key": api_key or ADMIN_API_KEY}
    
    # Create collection first via admin
    requests.post(
        f"{ADMIN_API}?route=/collections",
        headers={"X-API-Key": ADMIN_API_KEY, "Content-Type": "application/json"},
        json={"name": "main_test", "description": "Main API test"}
    )
    
    # List documents (empty)
    resp = requests.get(
        f"{BASE_URL}/collections/main_test/documents",
        headers=headers
    )
    log("main", "list-documents-empty", resp)
    
    # Create document
    resp = requests.post(
        f"{BASE_URL}/collections/main_test/documents",
        headers={**headers, "Content-Type": "application/json"},
        json={"name": "John Doe", "email": "john@example.com", "age": 30}
    )
    log("main", "create-document", resp, {"name": "John Doe"})
    
    doc_id = None
    if resp.json().get("success"):
        doc_data = resp.json().get("data", {})
        doc_id = doc_data.get("id")
    
    if doc_id:
        # Get document
        resp = requests.get(
            f"{BASE_URL}/collections/main_test/documents/{doc_id}",
            headers=headers
        )
        log("main", "get-document", resp)
        
        # Update document
        resp = requests.patch(
            f"{BASE_URL}/collections/main_test/documents/{doc_id}",
            headers={**headers, "Content-Type": "application/json"},
            json={"age": 31, "status": "active"}
        )
        log("main", "update-document", resp, {"age": 31})
        
        # Replace document
        resp = requests.put(
            f"{BASE_URL}/collections/main_test/documents/{doc_id}",
            headers={**headers, "Content-Type": "application/json"},
            json={"name": "Jane Doe", "email": "jane@example.com"}
        )
        log("main", "replace-document", resp, {"name": "Jane Doe"})
        
        # List documents with query
        resp = requests.get(
            f"{BASE_URL}/collections/main_test/documents?field=age&operator=>&value=25",
            headers=headers
        )
        log("main", "query-documents", resp)
        
        # Delete document
        resp = requests.delete(
            f"{BASE_URL}/collections/main_test/documents/{doc_id}",
            headers=headers
        )
        log("main", "delete-document", resp)
    
    # Clean up
    requests.delete(
        f"{ADMIN_API}?route=/collections/main_test",
        headers={"X-API-Key": ADMIN_API_KEY}
    )

def test_storage(api_key=None):
    """Test storage API."""
    print("\n=== Testing Storage API ===")
    
    headers = {"X-API-Key": api_key or ADMIN_API_KEY}
    
    # Create a test file
    test_file = Path("test_upload.txt")
    test_file.write_text("This is a test file content for API testing.")
    
    # Upload file
    with open(test_file, "rb") as f:
        resp = requests.post(
            f"{BASE_URL}/storage/upload",
            headers=headers,
            files={"file": ("test_upload.txt", f, "text/plain")},
            data={"folder_path": "test_folder/"}
        )
    log("storage", "upload-file", resp)
    
    filename = None
    if resp.json().get("success"):
        file_data = resp.json().get("data", {})
        filename = file_data.get("filename")
    
    if filename:
        # Get file
        resp = requests.get(f"{BASE_URL}/storage/files/{filename}")
        log("storage", "get-file", resp)
        
        # Delete file
        resp = requests.delete(
            f"{BASE_URL}/storage/files/{filename}",
            headers=headers
        )
        log("storage", "delete-file", resp)
    
    # Clean up test file
    if test_file.exists():
        test_file.unlink()

def test_admin_storage():
    """Test admin storage operations."""
    print("\n=== Testing Admin Storage ===")
    
    headers = {"X-API-Key": ADMIN_API_KEY}
    
    # Create test file
    test_file = Path("admin_test_upload.txt")
    test_file.write_text("Admin test file content.")
    
    # Upload file (admin)
    with open(test_file, "rb") as f:
        resp = requests.post(
            f"{ADMIN_API}?route=/storage/upload",
            headers=headers,
            files={"file": ("admin_test.txt", f, "text/plain")},
            data={"folder_path": "admin_test/"}
        )
    log("admin-storage", "upload-file", resp)
    
    file_id = None
    if resp.json().get("success"):
        file_data = resp.json().get("data", {})
        file_id = file_data.get("id")
    
    # List files
    resp = requests.get(
        f"{ADMIN_API}?route=/storage/files&folder_path=admin_test/",
        headers=headers
    )
    log("admin-storage", "list-files", resp)
    
    if file_id:
        # Download file
        resp = requests.get(
            f"{ADMIN_API}?route=/storage/files/{file_id}/download",
            headers=headers
        )
        log("admin-storage", "download-file", resp)
        
        # Rename file
        resp = requests.post(
            f"{ADMIN_API}?route=/storage/files/{file_id}/rename",
            headers={**headers, "Content-Type": "application/json"},
            json={"name": "renamed_test.txt"}
        )
        log("admin-storage", "rename-file", resp, {"name": "renamed_test.txt"})
        
        # Move file
        resp = requests.post(
            f"{ADMIN_API}?route=/storage/files/{file_id}/move",
            headers={**headers, "Content-Type": "application/json"},
            json={"folder_path": "admin_test/moved/"}
        )
        log("admin-storage", "move-file", resp, {"folder_path": "admin_test/moved/"})
        
        # Copy file
        resp = requests.post(
            f"{ADMIN_API}?route=/storage/files/{file_id}/copy",
            headers={**headers, "Content-Type": "application/json"},
            json={"folder_path": "admin_test/copied/"}
        )
        log("admin-storage", "copy-file", resp, {"folder_path": "admin_test/copied/"})
        
        # Delete file
        resp = requests.delete(
            f"{ADMIN_API}?route=/storage/files/{file_id}",
            headers=headers
        )
        log("admin-storage", "delete-file", resp)
    
    # Clean up
    if test_file.exists():
        test_file.unlink()

def test_storage_folders():
    """Test storage folder operations."""
    print("\n=== Testing Storage Folders ===")
    
    headers = {"X-API-Key": ADMIN_API_KEY}
    
    # List folders (root)
    resp = requests.get(
        f"{ADMIN_API}?route=/storage/folders&parent_path=",
        headers=headers
    )
    log("folders", "list-folders", resp)
    
    # Create folder
    resp = requests.post(
        f"{ADMIN_API}?route=/storage/folders",
        headers={**headers, "Content-Type": "application/json"},
        json={"name": "test_folder_api", "parent_path": ""}
    )
    log("folders", "create-folder", resp, {"name": "test_folder_api"})
    
    # Create subfolder
    resp = requests.post(
        f"{ADMIN_API}?route=/storage/folders",
        headers={**headers, "Content-Type": "application/json"},
        json={"name": "subfolder", "parent_path": "test_folder_api/"}
    )
    log("folders", "create-subfolder", resp, {"name": "subfolder"})
    
    # List folders at path
    resp = requests.get(
        f"{ADMIN_API}?route=/storage/folders&parent_path=test_folder_api/",
        headers=headers
    )
    log("folders", "list-subfolders", resp)
    
    # Rename folder
    resp = requests.patch(
        f"{ADMIN_API}?route=/storage/folders/test_folder_api/",
        headers={**headers, "Content-Type": "application/json"},
        json={"name": "renamed_folder"}
    )
    log("folders", "rename-folder", resp, {"name": "renamed_folder"})
    
    # Move folder
    resp = requests.post(
        f"{ADMIN_API}?route=/storage/folders/renamed_folder/move",
        headers={**headers, "Content-Type": "application/json"},
        json={"parent_path": ""}
    )
    log("folders", "move-folder", resp)
    
    # Copy folder
    resp = requests.post(
        f"{ADMIN_API}?route=/storage/folders/renamed_folder/copy",
        headers={**headers, "Content-Type": "application/json"},
        json={"parent_path": ""}
    )
    log("folders", "copy-folder", resp)
    
    # List all paths
    resp = requests.get(
        f"{ADMIN_API}?route=/storage/folders/all-paths",
        headers=headers
    )
    log("folders", "list-all-paths", resp)
    
    # Delete copied folder
    resp = requests.delete(
        f"{ADMIN_API}?route=/storage/folders/renamed_folder%20copy/",
        headers=headers
    )
    log("folders", "delete-copied-folder", resp)
    
    # Delete renamed folder
    resp = requests.delete(
        f"{ADMIN_API}?route=/storage/folders/renamed_folder/",
        headers=headers
    )
    log("folders", "delete-folder", resp)

def test_notifications(api_key=None):
    """Test notifications API."""
    print("\n=== Testing Notifications ===")
    
    headers = {"X-API-Key": api_key or ADMIN_API_KEY}
    
    # Send notification
    resp = requests.post(
        f"{BASE_URL}/notifications",
        headers={**headers, "Content-Type": "application/json"},
        json={"message": "Test notification!", "data": {"test": True, "user_id": 42}}
    )
    log("notifications", "send-notification", resp, {"message": "Test notification!"})
    
    notif_id = None
    if resp.json().get("success"):
        notif_data = resp.json().get("data", {})
        notif_id = notif_data.get("id")
    
    # List notifications
    resp = requests.get(
        f"{BASE_URL}/notifications?limit=10&offset=0",
        headers=headers
    )
    log("notifications", "list-notifications", resp)
    
    if notif_id:
        # Mark as read
        resp = requests.post(
            f"{BASE_URL}/notifications/{notif_id}/read",
            headers=headers
        )
        log("notifications", "mark-read", resp)
        
        # Delete notification
        resp = requests.delete(
            f"{BASE_URL}/notifications/{notif_id}",
            headers=headers
        )
        log("notifications", "delete-notification", resp)

def test_cloud_functions(api_key=None):
    """Test cloud functions API."""
    print("\n=== Testing Cloud Functions ===")
    
    headers = {"X-API-Key": api_key or ADMIN_API_KEY}
    
    # Test hello function (if exists)
    resp = requests.post(
        f"{FUNCTIONS_API}/hello",
        headers={**headers, "Content-Type": "application/json"},
        json={"name": "API Tester"}
    )
    log("functions", "hello-function", resp, {"name": "API Tester"})

def test_access_control():
    """Test access control operations."""
    print("\n=== Testing Access Control ===")
    
    headers = {"X-API-Key": ADMIN_API_KEY}
    
    # Create a non-admin API key first
    resp = requests.post(
        f"{ADMIN_API}?route=/api-keys",
        headers={**headers, "Content-Type": "application/json"},
        json={"name": "Test Limited Key", "is_admin": False}
    )
    log("access", "create-limited-key", resp, {"name": "Test Limited Key"})
    
    key_id = None
    if resp.json().get("success"):
        key_data = resp.json().get("data", {})
        key_id = key_data.get("id")
    
    if key_id:
        # Create a test collection to get ID
        requests.post(
            f"{ADMIN_API}?route=/collections",
            headers={**headers, "Content-Type": "application/json"},
            json={"name": "access_test_collection"}
        )
        
        # Get collection ID
        resp = requests.get(
            f"{ADMIN_API}?route=/collections",
            headers=headers
        )
        collection_id = None
        if resp.json().get("success"):
            cols = resp.json().get("data", [])
            for col in cols:
                if col.get("name") == "access_test_collection":
                    collection_id = col.get("id")
                    break
        
        if collection_id:
            # Grant collection access
            resp = requests.post(
                f"{ADMIN_API}?route=/api-keys/{key_id}/grant-collection",
                headers={**headers, "Content-Type": "application/json"},
                json={"collection_id": collection_id, "access_level": "write"}
            )
            log("access", "grant-collection", resp, {"collection_id": collection_id})
            
            # Get permissions
            resp = requests.get(
                f"{ADMIN_API}?route=/api-keys/{key_id}/permissions",
                headers=headers
            )
            log("access", "get-permissions", resp)
            
            # Revoke collection access
            resp = requests.post(
                f"{ADMIN_API}?route=/api-keys/{key_id}/revoke-collection",
                headers={**headers, "Content-Type": "application/json"},
                json={"collection_id": collection_id}
            )
            log("access", "revoke-collection", resp, {"collection_id": collection_id})
        
        # Grant folder access
        resp = requests.post(
            f"{ADMIN_API}?route=/api-keys/{key_id}/grant-folder",
            headers={**headers, "Content-Type": "application/json"},
            json={"folder_path": "documents/reports/", "access_level": "read"}
        )
        log("access", "grant-folder", resp, {"folder_path": "documents/reports/"})
        
        # Revoke folder access
        resp = requests.post(
            f"{ADMIN_API}?route=/api-keys/{key_id}/revoke-folder",
            headers={**headers, "Content-Type": "application/json"},
            json={"folder_path": "documents/reports/"}
        )
        log("access", "revoke-folder", resp, {"folder_path": "documents/reports/"})
        
        # Revoke the key
        requests.post(
            f"{ADMIN_API}?route=/api-keys/{key_id}/revoke",
            headers=headers
        )
        
        # Clean up collection
        requests.delete(
            f"{ADMIN_API}?route=/collections/access_test_collection",
            headers=headers
        )

def test_health_check():
    """Test health check endpoint."""
    print("\n=== Testing Health Check ===")
    
    resp = requests.get(f"{BASE_URL}/")
    log("main", "health-check", resp)
    
    resp = requests.get(f"{BASE_URL}/health")
    log("main", "health-endpoint", resp)
    try:
        return resp.json()
    except:
        return {}

def main():
    """Run all tests."""
    print("=" * 60)
    print("App Management Dashboard - API Test Suite")
    print(f"Base URL: {BASE_URL}")
    print("=" * 60)
    
    # Test health first
    test_health_check()
    
    # Test admin login
    login_result = test_admin_login()
    
    # Test validate key
    test_validate_admin_key()
    
    # Test session key
    test_admin_session_key()
    
    # Test stats
    test_admin_stats()
    
    # Test users
    test_admin_users()
    
    # Test API keys
    api_key = test_admin_api_keys()
    
    # Test admin collections
    test_admin_collections()
    
    # Test main collections
    test_main_collections(api_key)
    
    # Test storage
    test_storage(api_key)
    
    # Test admin storage
    test_admin_storage()
    
    # Test storage folders
    test_storage_folders()
    
    # Test notifications
    test_notifications(api_key)
    
    # Test cloud functions
    test_cloud_functions(api_key)
    
    # Test access control
    test_access_control()
    
    # Save results
    output_file = Path("api_test_results.json")
    with open(output_file, "w") as f:
        json.dump(results, f, indent=2, ensure_ascii=False)
    
    print("\n" + "=" * 60)
    print(f"All tests completed! Results saved to {output_file}")
    print(f"Total tests: {len(results)}")
    print("=" * 60)
    
    return results

if __name__ == "__main__":
    main()
