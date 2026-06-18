# Tasks

## Task 1: Implement StorageManager Backend Methods
- [ ] Task 1.1: Add `moveFolder(string $sourcePath, string $destParentPath)` method to StorageManager
  - Validate source folder exists
  - Validate destination parent path
  - Prevent moving folder into itself or its subfolders
  - Move folder on filesystem
  - Update all subfolder paths in database (parent_path and path fields)
  - Update all file paths in database (folder_path and filename_stored fields)
  - Return success with new path
- [ ] Task 1.2: Add `copyFolder(string $sourcePath, string $destParentPath)` method to StorageManager
  - Validate source folder exists
  - Validate destination parent path
  - Prevent copying folder into itself or its subfolders
  - Recursively copy folder on filesystem (all subfolders and files)
  - Create new folder records in database for destination
  - Copy all file records in database with new paths
  - Return success with new path

## Task 2: Implement API Routes
- [ ] Task 2.1: Add route handlers in admin/api.php
  - Add `POST /storage/folders/{path}/move` route
  - Add `POST /storage/folders/{path}/copy` route
  - Create `handleMoveFolder()` function with access control checks
  - Create `handleCopyFolder()` function with access control checks
  - Validate request body contains dest_parent_path
  - Call StorageManager methods and return JSON response

## Task 3: Implement UI Components
- [ ] Task 3.1: Add Move Folder modal to admin/index.php
  - Create modal with form containing hidden source path and destination parent path input
  - Add modal HTML after existing folder modals
- [ ] Task 3.2: Add Copy Folder modal to admin/index.php
  - Create modal with form containing hidden source path and destination parent path input
  - Add modal HTML after existing folder modals
- [ ] Task 3.3: Add JavaScript functions for folder move/copy
  - Add `moveFolderPrompt(path)` function to open move modal
  - Add `moveFolder()` async function to call API and handle response
  - Add `copyFolderPrompt(path)` function to open copy modal
  - Add `copyFolder()` async function to call API and handle response
- [ ] Task 3.4: Add Move and Copy buttons to folder action rows
  - Update folder row rendering in `renderStorage()` function
  - Add Move button with onclick="moveFolderPrompt('${f.path}')"
  - Add Copy button with onclick="copyFolderPrompt('${f.path}')"

## Task 4: Testing
- [ ] Task 4.1: Test move folder functionality
  - Move folder to new parent
  - Move folder to root
  - Verify subfolder paths updated correctly
  - Verify file paths updated correctly
  - Verify filesystem structure matches database
- [ ] Task 4.2: Test copy folder functionality
  - Copy folder to new parent
  - Copy folder to root
  - Verify all files and subfolders copied
  - Verify original folder unchanged
  - Verify new folder has correct structure
- [ ] Task 4.3: Test error cases
  - Attempt to move/copy folder into itself
  - Attempt to move/copy folder into its subfolder
  - Attempt to move/copy non-existent folder
  - Verify appropriate error messages returned
