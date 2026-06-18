# Folder Move & Copy Spec

## Why
The admin UI currently supports move/copy for files, and create/rename/delete for folders, but lacks the ability to move or copy an entire folder (with all its contents) to a different parent location.

## What Changes
- Add `moveFolder()` method to `StorageManager` - moves a folder and all its contents (subfolders + files) to a new parent path
- Add `copyFolder()` method to `StorageManager` - recursively copies a folder and all its contents to a new parent path
- Add API routes: `POST /storage/folders/{path}/move` and `POST /storage/folders/{path}/copy`
- Add API handler functions `handleMoveFolder()` and `handleCopyFolder()` in admin api.php
- Add Move Folder and Copy Folder modals + JS functions in admin index.php
- Add Move and Copy buttons to folder action rows in the storage UI

## Impact
- Affected specs: none (new feature)
- Affected code:
  - `template/simple-firebase-alt/lib/StorageManager.php`
  - `template/simple-firebase-alt/admin/api.php`
  - `template/simple-firebase-alt/admin/index.php`

## ADDED Requirements

### Requirement: Move Folder
The system SHALL allow moving a folder (and all its subfolders/files) to a different parent path.

#### Scenario: Move folder to new parent
- **WHEN** admin provides a source folder path and a destination parent path
- **THEN** the folder is moved to the new parent, all subfolder paths are updated, all file paths are updated on filesystem and database

#### Scenario: Move folder to root
- **WHEN** destination parent path is empty (root)
- **THEN** the folder is moved to the root level

#### Scenario: Prevent moving folder into itself
- **WHEN** destination parent path is the same as or a subpath of the source folder
- **THEN** the system returns an error

### Requirement: Copy Folder
The system SHALL allow copying a folder (and all its subfolders/files) to a different parent path.

#### Scenario: Copy folder to new parent
- **WHEN** admin provides a source folder path and a destination parent path
- **THEN** a new folder is created at the destination with all contents duplicated (filesystem + database)

#### Scenario: Copy folder to root
- **WHEN** destination parent path is empty (root)
- **THEN** a copy of the folder is created at the root level

#### Scenario: Prevent copying folder into itself
- **WHEN** destination parent path is the same as or a subpath of the source folder
- **THEN** the system returns an error
