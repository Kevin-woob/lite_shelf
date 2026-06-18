# Checklist

## Backend Implementation
- [x] StorageManager::moveFolder() method implemented
- [x] StorageManager::copyFolder() method implemented
- [x] Both methods validate source folder exists
- [x] Both methods validate destination parent path
- [x] Both methods prevent moving/copying folder into itself or subfolders
- [x] moveFolder() updates filesystem structure correctly
- [x] moveFolder() updates all subfolder paths in database
- [x] moveFolder() updates all file paths in database
- [x] copyFolder() creates filesystem copy correctly
- [x] copyFolder() creates new folder records in database
- [x] copyFolder() copies all file records in database

## API Routes
- [x] POST /storage/folders/{path}/move route added
- [x] POST /storage/folders/{path}/copy route added
- [x] handleMoveFolder() function implemented with access control
- [x] handleCopyFolder() function implemented with access control
- [x] Both handlers validate dest_parent_path in request body
- [x] Both handlers call StorageManager methods correctly
- [x] Both handlers return proper JSON responses

## UI Components
- [x] Move Folder modal HTML added to admin/index.php
- [x] Copy Folder modal HTML added to admin/index.php
- [x] moveFolderPrompt() JavaScript function implemented
- [x] moveFolder() async function implemented
- [x] copyFolderPrompt() JavaScript function implemented
- [x] copyFolder() async function implemented
- [x] Move button added to folder action rows
- [x] Copy button added to folder action rows

## Testing
- [x] Move folder to new parent works correctly
- [x] Move folder to root works correctly
- [x] Subfolder paths updated correctly after move
- [x] File paths updated correctly after move
- [x] Filesystem structure matches database after move
- [x] Copy folder to new parent works correctly
- [x] Copy folder to root works correctly
- [x] All files and subfolders copied correctly
- [x] Original folder unchanged after copy
- [x] Error: move/copy folder into itself returns error
- [x] Error: move/copy folder into subfolder returns error
- [x] Error: move/copy non-existent folder returns error
