<?php
/**
 * Storage Manager
 * 
 * Handles file upload, storage, and retrieval operations
 * Supports folder-based organization with nested subfolders
 */

class StorageManager {
    private mixed $db;
    private array $config;
    
    // Constraints for path length safety
    private const MAX_FOLDER_NAME_LENGTH = 50;
    private const MAX_TOTAL_PATH_LENGTH = 200;
    private const MAX_FOLDER_DEPTH = 10;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../config/settings.php';
    }
    
    // ========== PATH VALIDATION ==========
    
    /**
     * Validate and sanitize a folder path
     */
    public static function validateFolderPath(string $path): array {
        // Empty path is valid (root)
        if ($path === '') {
            return ['valid' => true, 'path' => ''];
        }
        
        // Remove leading/trailing whitespace
        $path = trim($path);
        
        // Remove leading slash if present
        $path = ltrim($path, '/');
        
        // Ensure trailing slash
        if (!str_ends_with($path, '/')) {
            $path .= '/';
        }
        
        // Check total length
        if (strlen($path) > self::MAX_TOTAL_PATH_LENGTH) {
            return ['valid' => false, 'error' => 'Path too long (max ' . self::MAX_TOTAL_PATH_LENGTH . ' chars)'];
        }
        
        // Check folder name components
        $parts = explode('/', rtrim($path, '/'));
        if (count($parts) > self::MAX_FOLDER_DEPTH) {
            return ['valid' => false, 'error' => 'Too many folder levels (max ' . self::MAX_FOLDER_DEPTH . ')'];
        }
        
        foreach ($parts as $part) {
            if (strlen($part) > self::MAX_FOLDER_NAME_LENGTH) {
                return ['valid' => false, 'error' => 'Folder name too long: "' . $part . '" (max ' . self::MAX_FOLDER_NAME_LENGTH . ' chars)'];
            }
            // Only allow alphanumeric, hyphens, underscores, spaces
            if (!preg_match('/^[a-zA-Z0-9 _\-]+$/', $part)) {
                return ['valid' => false, 'error' => 'Invalid characters in folder name: "' . $part . '"'];
            }
            // No empty segments
            if ($part === '') {
                return ['valid' => false, 'error' => 'Empty folder name in path'];
            }
        }
        
        return ['valid' => true, 'path' => $path];
    }
    
    /**
     * Validate a folder name (single level)
     */
    public static function validateFolderName(string $name): array {
        if (trim($name) === '') {
            return ['valid' => false, 'error' => 'Folder name cannot be empty'];
        }
        if (strlen(trim($name)) > self::MAX_FOLDER_NAME_LENGTH) {
            return ['valid' => false, 'error' => 'Folder name too long (max ' . self::MAX_FOLDER_NAME_LENGTH . ' chars)'];
        }
        if (!preg_match('/^[a-zA-Z0-9 _\-]+$/', trim($name))) {
            return ['valid' => false, 'error' => 'Invalid characters in folder name (alphanumeric, hyphens, underscores, spaces only)'];
        }
        return ['valid' => true, 'name' => trim($name)];
    }
    
    // ========== FILE OPERATIONS ==========
    
    /**
     * Upload a file
     */
    public function upload(array $file, ?string $folderPath = null, ?int $uploadedByKeyId = null): ?array {
        // Validate file
        if (!isset($file['tmp_name']) || !isset($file['name'])) {
            return ['error' => 'No file provided'];
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'File upload error: ' . $file['error']];
        }
        
        // Check file size
        if ($file['size'] > $this->config['max_file_size_bytes']) {
            return ['error' => 'File too large'];
        }
        
        // Validate file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($extension, $this->config['blocked_extensions'])) {
            return ['error' => 'File type not allowed'];
        }
        
        if (!empty($this->config['allowed_extensions']) && 
            !in_array($extension, $this->config['allowed_extensions'])) {
            return ['error' => 'File type not allowed'];
        }
        
        // Validate folder path
        $folderPath = $folderPath ?? '';
        $validation = self::validateFolderPath($folderPath);
        if (!$validation['valid']) {
            return ['error' => $validation['error']];
        }
        $folderPath = $validation['path'];
        
        // Generate unique filename with folder path
        $filenameStored = $this->generateUniqueFilename($extension, $folderPath);
        $targetPath = $this->config['uploads_base_path'] . $filenameStored;
        
        // Create directory if it doesn't exist
        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['error' => 'Failed to save file'];
        }
        
        // Store in database
        $data = [
            'filename_original' => $file['name'],
            'filename_stored' => $filenameStored,
            'folder_path' => $folderPath,
            'mime_type' => $file['type'] ?? mime_content_type($targetPath),
            'size_bytes' => $file['size'],
            'uploaded_by_key_id' => $uploadedByKeyId
        ];
        
        $id = $this->db->insert('storage_files', $data);
        
        return [
            'id' => $id,
            'filename' => $filenameStored,
            'folder_path' => $folderPath,
            'url' => $this->config['uploads_url_prefix'] . $filenameStored,
            'size' => $file['size'],
            'mime_type' => $data['mime_type']
        ];
    }
    
    /**
     * Save text/content to storage
     */
    public function saveFile(string $filename, string $content): bool {
        $filePath = $this->config['uploads_base_path'] . $filename;
        
        // Create directory if it doesn't exist
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        return file_put_contents($filePath, $content) !== false;
    }
    
    /**
     * Get content from storage
     */
    public function getFile(string $filename): ?string {
        $filePath = $this->config['uploads_base_path'] . $filename;
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        return file_get_contents($filePath);
    }
    
    /**
     * List files in a folder (or root)
     */
    public function listFiles(string $folderPath = ''): array {
        $folderPath = rtrim($folderPath, '/') . ($folderPath ? '/' : '');
        $directory = new DirectoryIterator($this->config['uploads_base_path'] . $folderPath);
        
        $files = [];
        foreach ($directory as $file) {
            if ($file->isFile()) {
                $files[] = $file->getFilename();
            }
        }
        
        return $files;
    }
    
    /**
     * List files from database with folder filter
     */
    public function listFilesFromDb(string $folderPath = ''): array {
        $folderPath = rtrim($folderPath, '/') . ($folderPath ? '/' : '');
        
        if ($folderPath === '') {
            return $this->db->fetchAll("SELECT * FROM storage_files WHERE folder_path = '' ORDER BY created_at DESC");
        }
        
        return $this->db->fetchAll(
            "SELECT * FROM storage_files WHERE folder_path = ? ORDER BY created_at DESC",
            [$folderPath]
        );
    }
    
    /**
     * Delete a file
     */
    public function deleteFile(string $filename): bool {
        $filePath = $this->config['uploads_base_path'] . $filename;
        
        if (!file_exists($filePath)) {
            return false;
        }
        
        // Delete from filesystem
        unlink($filePath);
        
        // Delete from database
        $this->db->delete('storage_files', 'filename_stored = ?', [$filename]);
        
        // Try to clean up empty parent directories
        $this->cleanupEmptyDirectories(dirname($filePath));
        
        return true;
    }
    
    /**
     * Download a file
     */
    public function download(int $fileId): void {
        $file = $this->db->fetchOne("SELECT * FROM storage_files WHERE id = ?", [$fileId]);
        
        if (!$file) {
            Response::notFound('File');
            return;
        }
        
        $filePath = $this->config['uploads_base_path'] . $file['filename_stored'];
        
        if (!file_exists($filePath)) {
            Response::notFound('File');
            return;
        }
        
        // Send file
        header('Content-Type: ' . $file['mime_type']);
        header('Content-Length: ' . filesize($filePath));
        header('Content-Disposition: attachment; filename="' . $file['filename_original'] . '"');
        readfile($filePath);
        exit;
    }
    
    /**
     * Get file info
     */
    public function getFileInfo(int $fileId): ?array {
        return $this->db->fetchOne("SELECT * FROM storage_files WHERE id = ?", [$fileId]);
    }
    
    /**
     * Move a file to a different folder
     */
    public function moveFile(int $fileId, string $destFolderPath): array {
        $file = $this->getFileInfo($fileId);
        if (!$file) {
            return ['error' => 'File not found'];
        }
        
        // Validate destination path
        $validation = self::validateFolderPath($destFolderPath);
        if (!$validation['valid']) {
            return ['error' => $validation['error']];
        }
        $destFolderPath = $validation['path'];
        
        $oldStoredPath = $file['filename_stored'];
        $oldBasename = basename($oldStoredPath);
        $newStoredPath = $destFolderPath . $oldBasename;
        $oldFsPath = $this->config['uploads_base_path'] . $oldStoredPath;
        $newFsPath = $this->config['uploads_base_path'] . $newStoredPath;
        
        // Create destination directory if needed
        $newDir = dirname($newFsPath);
        if (!is_dir($newDir)) {
            mkdir($newDir, 0755, true);
            // Auto-create folder record in database
            $this->ensureFolderRecordExists($destFolderPath);
        }
        
        // Move on filesystem
        if (!rename($oldFsPath, $newFsPath)) {
            return ['error' => 'Failed to move file on filesystem'];
        }
        
        // Update database
        $this->db->update('storage_files', [
            'filename_stored' => $newStoredPath,
            'folder_path' => $destFolderPath
        ], 'id = ?', [$fileId]);
        
        // Clean up old empty directories
        $this->cleanupEmptyDirectories(dirname($oldFsPath));
        
        return [
            'success' => true,
            'id' => $fileId,
            'filename_stored' => $newStoredPath,
            'folder_path' => $destFolderPath
        ];
    }
    
    /**
     * Copy a file to a different folder
     */
    public function copyFile(int $fileId, string $destFolderPath): array {
        $file = $this->getFileInfo($fileId);
        if (!$file) {
            return ['error' => 'File not found'];
        }
        
        // Validate destination path
        $validation = self::validateFolderPath($destFolderPath);
        if (!$validation['valid']) {
            return ['error' => $validation['error']];
        }
        $destFolderPath = $validation['path'];
        
        // Generate new unique filename in destination folder
        $extension = pathinfo($file['filename_original'], PATHINFO_EXTENSION);
        $newStoredPath = $this->generateUniqueFilename($extension, $destFolderPath);
        $oldFsPath = $this->config['uploads_base_path'] . $file['filename_stored'];
        $newFsPath = $this->config['uploads_base_path'] . $newStoredPath;
        
        // Create destination directory if needed
        $newDir = dirname($newFsPath);
        if (!is_dir($newDir)) {
            mkdir($newDir, 0755, true);
            // Auto-create folder record in database
            $this->ensureFolderRecordExists($destFolderPath);
        }
        
        // Copy on filesystem
        if (!copy($oldFsPath, $newFsPath)) {
            return ['error' => 'Failed to copy file on filesystem'];
        }
        
        // Insert new database record
        $data = [
            'filename_original' => $file['filename_original'],
            'filename_stored' => $newStoredPath,
            'folder_path' => $destFolderPath,
            'mime_type' => $file['mime_type'],
            'size_bytes' => $file['size_bytes'],
            'uploaded_by_key_id' => $file['uploaded_by_key_id']
        ];
        
        $newId = $this->db->insert('storage_files', $data);
        
        return [
            'success' => true,
            'new_id' => $newId,
            'filename_stored' => $newStoredPath,
            'folder_path' => $destFolderPath
        ];
    }
    
    /**
     * Rename a file
     */
    public function renameFile(int $fileId, string $newName): array {
        $file = $this->getFileInfo($fileId);
        if (!$file) {
            return ['error' => 'File not found'];
        }

        if (trim($newName) === '') {
            return ['error' => 'New name cannot be empty'];
        }

        $oldStoredPath = $file['filename_stored'];
        $oldFsPath = $this->config['uploads_base_path'] . $oldStoredPath;
        $folderPath = dirname($oldStoredPath);
        $extension = pathinfo($newName, PATHINFO_EXTENSION);
        $oldExtension = pathinfo($oldStoredPath, PATHINFO_EXTENSION);

        // If no extension provided, use the original one
        if ($extension === '' && $oldExtension !== '') {
            $newName .= '.' . $oldExtension;
        }

        // Sanitize filename
        $newName = preg_replace('/[^a-zA-Z0-9 _\-\.]/', '', trim($newName));

        if ($newName === '') {
            return ['error' => 'Invalid filename'];
        }

        $newStoredPath = ($folderPath !== '.' ? $folderPath . '/' : '') . $newName;
        $newFsPath = $this->config['uploads_base_path'] . $newStoredPath;

        // Check if destination already exists
        if (file_exists($newFsPath) && $oldFsPath !== $newFsPath) {
            return ['error' => 'A file with this name already exists'];
        }

        // Rename on filesystem
        if (!rename($oldFsPath, $newFsPath)) {
            return ['error' => 'Failed to rename file on filesystem'];
        }

        // Update database
        $this->db->update('storage_files', [
            'filename_stored' => $newStoredPath,
            'filename_original' => $newName
        ], 'id = ?', [$fileId]);

        return [
            'success' => true,
            'id' => $fileId,
            'filename_stored' => $newStoredPath,
            'filename_original' => $newName
        ];
    }
    
    // ========== FOLDER OPERATIONS ==========
    
    /**
     * List subfolders at a given parent path
     */
    public function listFolders(string $parentPath = ''): array {
        $parentPath = rtrim($parentPath, '/') . ($parentPath ? '/' : '');
        
        if ($parentPath === '') {
            return $this->db->fetchAll(
                "SELECT * FROM storage_folders WHERE parent_path = '' ORDER BY name ASC"
            );
        }
        
        return $this->db->fetchAll(
            "SELECT * FROM storage_folders WHERE parent_path = ? ORDER BY name ASC",
            [$parentPath]
        );
    }
    
    /**
     * Create a folder
     */
    public function createFolder(string $name, string $parentPath = '', ?int $createdByKeyId = null): array {
        // Validate name
        $nameValidation = self::validateFolderName($name);
        if (!$nameValidation['valid']) {
            return ['error' => $nameValidation['error']];
        }
        $name = $nameValidation['name'];
        
        // Build full path
        $parentPath = rtrim($parentPath, '/') . ($parentPath ? '/' : '');
        $fullPath = $parentPath . $name . '/';
        
        // Check total path length
        if (strlen($fullPath) > self::MAX_TOTAL_PATH_LENGTH) {
            return ['error' => 'Full path too long (max ' . self::MAX_TOTAL_PATH_LENGTH . ' chars)'];
        }
        
        // Check depth
        $depth = substr_count(rtrim($fullPath, '/'), '/') + 1;
        if ($depth > self::MAX_FOLDER_DEPTH) {
            return ['error' => 'Max folder depth reached (' . self::MAX_FOLDER_DEPTH . ' levels)'];
        }
        
        // Check if folder already exists
        $existing = $this->db->fetchOne("SELECT id FROM storage_folders WHERE path = ?", [$fullPath]);
        if ($existing) {
            return ['error' => 'Folder already exists'];
        }
        
        // Create filesystem directory
        $fsPath = $this->config['uploads_base_path'] . $fullPath;
        if (!is_dir($fsPath)) {
            mkdir($fsPath, 0755, true);
        }
        
        // Insert into database
        $data = [
            'name' => $name,
            'path' => $fullPath,
            'parent_path' => $parentPath,
            'created_by_key_id' => $createdByKeyId
        ];
        
        $id = $this->db->insert('storage_folders', $data);
        
        return [
            'success' => true,
            'id' => $id,
            'name' => $name,
            'path' => $fullPath,
            'parent_path' => $parentPath
        ];
    }
    
    /**
     * Delete a folder recursively (all subfolders and files)
     */
    public function deleteFolder(string $path): array {
        $path = rtrim($path, '/') . ($path ? '/' : '');
        if ($path === '') {
            return ['error' => 'Cannot delete root'];
        }
        
        // Get all subfolders recursively
        $subfolders = $this->db->fetchAll(
            "SELECT path FROM storage_folders WHERE path LIKE ? ORDER BY CHAR_LENGTH(path) DESC",
            [$path . '%']
        );
        
        // Get all files in this folder and subfolders
        $files = $this->db->fetchAll(
            "SELECT filename_stored FROM storage_files WHERE folder_path LIKE ?",
            [$path . '%']
        );
        
        // Delete files from filesystem
        foreach ($files as $file) {
            $fsPath = $this->config['uploads_base_path'] . $file['filename_stored'];
            if (file_exists($fsPath)) {
                unlink($fsPath);
            }
        }
        
        // Delete files from database
        $this->db->delete('storage_files', 'folder_path LIKE ?', [$path . '%']);
        
        // Delete folders from database (subfolders first, then parent)
        foreach ($subfolders as $folder) {
            $this->db->delete('storage_folders', 'path = ?', [$folder['path']]);
        }
        
        // Delete this folder from database
        $this->db->delete('storage_folders', 'path = ?', [$path]);
        
        // Clean up filesystem directories
        $this->cleanupEmptyDirectories($this->config['uploads_base_path'] . $path);
        
        return ['success' => true];
    }
    
    /**
     * Rename a folder
     */
    public function renameFolder(string $path, string $newName): array {
        $path = rtrim($path, '/') . ($path ? '/' : '');
        if ($path === '') {
            return ['error' => 'Cannot rename root'];
        }
        
        // Validate new name
        $nameValidation = self::validateFolderName($newName);
        if (!$nameValidation['valid']) {
            return ['error' => $nameValidation['error']];
        }
        $newName = $nameValidation['name'];
        
        // Get folder info
        $folder = $this->db->fetchOne("SELECT * FROM storage_folders WHERE path = ?", [$path]);
        if (!$folder) {
            return ['error' => 'Folder not found'];
        }
        
        $parentPath = $folder['parent_path'];
        $newPath = $parentPath . $newName . '/';
        
        // Check if new path already exists
        $existing = $this->db->fetchOne("SELECT id FROM storage_folders WHERE path = ?", [$newPath]);
        if ($existing) {
            return ['error' => 'A folder with this name already exists at this level'];
        }
        
        // Check total path length for the new path
        if (strlen($newPath) > self::MAX_TOTAL_PATH_LENGTH) {
            return ['error' => 'New path too long'];
        }
        
        // Get all affected subfolders and files (exclude main folder from subfolders query)
        $subfolders = $this->db->fetchAll(
            "SELECT id, path, name, parent_path FROM storage_folders WHERE path LIKE ? AND path != ? ORDER BY CHAR_LENGTH(path) ASC",
            [$path . '%', $path]
        );
        
        $files = $this->db->fetchAll(
            "SELECT id, filename_stored, folder_path FROM storage_files WHERE folder_path LIKE ?",
            [$path . '%']
        );
        
        // Rename on filesystem (old path to new path)
        $oldFsBase = $this->config['uploads_base_path'] . $path;
        $newFsBase = $this->config['uploads_base_path'] . $newPath;
        
        if (file_exists($oldFsBase)) {
            if (file_exists($newFsBase)) {
                // Remove new path if it exists (shouldn't happen if DB check passed)
                $this->rrmdir($newFsBase);
            }
            rename($oldFsBase, $newFsBase);
        }
        
        // Update subfolder paths in database
        foreach ($subfolders as $sf) {
            $oldSubPath = $sf['path'];
            $newSubPath = $newPath . substr($oldSubPath, strlen($path));
            $newSubParent = $newPath . substr($sf['parent_path'], strlen($path));
            
            $this->db->update('storage_folders', [
                'path' => $newSubPath,
                'parent_path' => $newSubParent
            ], 'id = ?', [$sf['id']]);
            
            // Rename subfolder on filesystem
            $oldSubFs = $this->config['uploads_base_path'] . $oldSubPath;
            $newSubFs = $this->config['uploads_base_path'] . $newSubPath;
            if (file_exists($oldSubFs)) {
                rename($oldSubFs, $newSubFs);
            }
        }
        
        // Update file paths in database
        foreach ($files as $file) {
            $oldFileFolder = $file['folder_path'];
            $newFileFolder = $newPath . substr($oldFileFolder, strlen($path));
            $oldFileStored = $file['filename_stored'];
            $newFileStored = $newPath . substr($oldFileStored, strlen($path));
            
            $this->db->update('storage_files', [
                'folder_path' => $newFileFolder,
                'filename_stored' => $newFileStored
            ], 'id = ?', [$file['id']]);
            
            // Move file on filesystem
            $oldFileFs = $this->config['uploads_base_path'] . $oldFileStored;
            $newFileFs = $this->config['uploads_base_path'] . $newFileStored;
            if (file_exists($oldFileFs)) {
                $newFileDir = dirname($newFileFs);
                if (!is_dir($newFileDir)) {
                    mkdir($newFileDir, 0755, true);
                }
                rename($oldFileFs, $newFileFs);
            }
        }
        
        // Update the main folder record
        $this->db->update('storage_folders', [
            'name' => $newName,
            'path' => $newPath
        ], 'path = ?', [$path]);
        
        return [
            'success' => true,
            'old_path' => $path,
            'new_path' => $newPath,
            'name' => $newName
        ];
    }
    
    /**
     * Move a folder to a different parent path
     */
    public function moveFolder(string $path, string $newParentPath): array {
        $path = rtrim($path, '/') . ($path ? '/' : '');
        if ($path === '') {
            return ['error' => 'Cannot move root'];
        }
        
        // Validate and normalize destination parent path
        $validation = self::validateFolderPath($newParentPath);
        if (!$validation['valid']) {
            return ['error' => $validation['error']];
        }
        $newParentPath = $validation['path'];
        
        // Get folder info
        $folder = $this->db->fetchOne("SELECT * FROM storage_folders WHERE path = ?", [$path]);
        if (!$folder) {
            return ['error' => 'Folder not found'];
        }
        
        $folderName = $folder['name'];
        $newPath = $newParentPath . $folderName . '/';
        
        // Cannot move folder into itself or its subfolders
        if (strpos($newParentPath, $path) === 0 || $newParentPath === $path) {
            return ['error' => 'Cannot move a folder into itself or its subfolders'];
        }
        
        // Check if new path already exists
        $existing = $this->db->fetchOne("SELECT id FROM storage_folders WHERE path = ?", [$newPath]);
        if ($existing) {
            return ['error' => 'A folder with this name already exists at the destination'];
        }
        
        // Check total path length for the new path and all subfolders
        $newPathLen = strlen($newPath);
        if ($newPathLen > self::MAX_TOTAL_PATH_LENGTH) {
            return ['error' => 'New path too long'];
        }
        
        // Get all affected subfolders and files
        $subfolders = $this->db->fetchAll(
            "SELECT id, path, name, parent_path FROM storage_folders WHERE path LIKE ? AND path != ? ORDER BY CHAR_LENGTH(path) ASC",
            [$path . '%', $path]
        );
        
        $files = $this->db->fetchAll(
            "SELECT id, filename_stored, folder_path FROM storage_files WHERE folder_path LIKE ?",
            [$path . '%']
        );
        
        // Move on filesystem (old path to new path)
        $oldFsBase = $this->config['uploads_base_path'] . $path;
        $newFsBase = $this->config['uploads_base_path'] . $newPath;
        
        if (file_exists($oldFsBase)) {
            if (file_exists($newFsBase)) {
                return ['error' => 'Destination already exists on filesystem'];
            }
            // Ensure parent directory exists
            $newParentDir = dirname($newFsBase);
            if (!is_dir($newParentDir)) {
                mkdir($newParentDir, 0755, true);
            }
            rename($oldFsBase, $newFsBase);
        }
        
        // Update subfolder paths in database
        foreach ($subfolders as $sf) {
            $oldSubPath = $sf['path'];
            $newSubPath = $newPath . substr($oldSubPath, strlen($path));
            $newSubParent = $newPath . substr($sf['parent_path'], strlen($path));
            
            $this->db->update('storage_folders', [
                'path' => $newSubPath,
                'parent_path' => $newSubParent
            ], 'id = ?', [$sf['id']]);
        }
        
        // Update file paths in database
        foreach ($files as $file) {
            $oldFileFolder = $file['folder_path'];
            $newFileFolder = $newPath . substr($oldFileFolder, strlen($path));
            $oldFileStored = $file['filename_stored'];
            $newFileStored = $newPath . substr($oldFileStored, strlen($path));
            
            $this->db->update('storage_files', [
                'folder_path' => $newFileFolder,
                'filename_stored' => $newFileStored
            ], 'id = ?', [$file['id']]);
        }
        
        // Update the main folder record
        $this->db->update('storage_folders', [
            'path' => $newPath,
            'parent_path' => $newParentPath
        ], 'path = ?', [$path]);
        
        return [
            'success' => true,
            'old_path' => $path,
            'new_path' => $newPath,
            'name' => $folderName
        ];
    }
    
    /**
     * Copy a folder to a different parent path
     */
    public function copyFolder(string $path, string $destParentPath): array {
        $path = rtrim($path, '/') . ($path ? '/' : '');
        if ($path === '') {
            return ['error' => 'Cannot copy root'];
        }

        // Validate destination parent path
        $validation = self::validateFolderPath($destParentPath);
        if (!$validation['valid']) {
            return ['error' => $validation['error']];
        }
        $destParentPath = $validation['path'];

        // Get folder info
        $folder = $this->db->fetchOne("SELECT * FROM storage_folders WHERE path = ?", [$path]);
        if (!$folder) {
            return ['error' => 'Folder not found'];
        }

        $folderName = $folder['name'];
        $newPath = $destParentPath . $folderName . '/';

        // Cannot copy folder into itself
        if (strpos($destParentPath, $path) === 0 || $destParentPath === $path) {
            return ['error' => 'Cannot copy a folder into itself or its subfolders'];
        }

        // Handle name conflicts - append (copy) if exists
        $conflictCheck = $newPath;
        $counter = 1;
        while (true) {
            $existing = $this->db->fetchOne("SELECT id FROM storage_folders WHERE path = ?", [$conflictCheck]);
            if (!$existing) {
                break;
            }
            $counter++;
            $conflictCheck = $destParentPath . $folderName . " (copy" . ($counter > 1 ? " $counter" : "") . ")/";
        }
        $newPath = $conflictCheck;
        $newFolderName = rtrim(basename(rtrim($newPath, '/')), '/');

        // Copy on filesystem
        $oldFsBase = $this->config['uploads_base_path'] . $path;
        $newFsBase = $this->config['uploads_base_path'] . $newPath;

        if (file_exists($oldFsBase)) {
            if (file_exists($newFsBase)) {
                $this->rrmdir($newFsBase);
            }
            $this->recursiveCopy($oldFsBase, $newFsBase);
        }

        // Get all subfolders
        $subfolders = $this->db->fetchAll(
            "SELECT id, path, name, parent_path FROM storage_folders WHERE path LIKE ? AND path != ? ORDER BY CHAR_LENGTH(path) ASC",
            [$path . '%', $path]
        );

        // Get all files
        $files = $this->db->fetchAll(
            "SELECT id, filename_stored, folder_path, filename_original, mime_type, size_bytes, uploaded_by_key_id FROM storage_files WHERE folder_path LIKE ?",
            [$path . '%']
        );

        // Insert new folder record
        $newParentPath = $destParentPath;
        $this->db->insert('storage_folders', [
            'name' => $newFolderName,
            'path' => $newPath,
            'parent_path' => $newParentPath,
            'created_by_key_id' => $folder['created_by_key_id']
        ]);

        // Insert subfolder records
        foreach ($subfolders as $sf) {
            $oldSubPath = $sf['path'];
            $newSubPath = $newPath . substr($oldSubPath, strlen($path));
            $newSubParent = $newPath . substr($sf['parent_path'], strlen($path));

            $this->db->insert('storage_folders', [
                'name' => $sf['name'],
                'path' => $newSubPath,
                'parent_path' => $newSubParent,
                'created_by_key_id' => $sf['created_by_key_id']
            ]);
        }

        // Copy file records
        foreach ($files as $file) {
            $oldFileFolder = $file['folder_path'];
            $newFileFolder = $newPath . substr($oldFileFolder, strlen($path));
            $oldFileStored = $file['filename_stored'];
            $newFileStored = $newPath . substr($oldFileStored, strlen($path));

            $this->db->insert('storage_files', [
                'filename_original' => $file['filename_original'],
                'filename_stored' => $newFileStored,
                'folder_path' => $newFileFolder,
                'mime_type' => $file['mime_type'],
                'size_bytes' => $file['size_bytes'],
                'uploaded_by_key_id' => $file['uploaded_by_key_id']
            ]);
        }

        return [
            'success' => true,
            'original_path' => $path,
            'new_path' => $newPath,
            'name' => $newFolderName
        ];
    }
    
    /**
     * Get files and subfolders in a given path
     */
    public function getFolderPath(string $path): array {
        $path = rtrim($path, '/') . ($path ? '/' : '');
        
        $folders = $this->listFolders($path);
        $files = $this->listFilesFromDb($path);
        
        return [
            'path' => $path,
            'folders' => $folders,
            'files' => $files
        ];
    }
    
    // ========== PRIVATE HELPERS ==========
    
    /**
     * Ensure folder record (and all parent records) exist in storage_folders table.
     * Creates any missing folder hierarchy entries.
     */
    private function ensureFolderRecordExists(string $folderPath): void {
        $folderPath = rtrim($folderPath, '/') . ($folderPath ? '/' : '');
        if ($folderPath === '') {
            return; // Root doesn't need a record
        }
        
        // Build list of all folder paths in the hierarchy
        $parts = array_filter(explode('/', rtrim($folderPath, '/')));
        $builtPath = '';
        $pathsToEnsure = [];
        foreach ($parts as $part) {
            $builtPath .= $part . '/';
            $pathsToEnsure[] = $builtPath;
        }
        
        // For each level, check if record exists; if not, create it
        foreach ($pathsToEnsure as $fullPath) {
            $existing = $this->db->fetchOne("SELECT id FROM storage_folders WHERE path = ?", [$fullPath]);
            if (!$existing) {
                $parentPath = implode('/', array_slice(explode('/', rtrim($fullPath, '/')), 0, -1));
                $parentPath = $parentPath ? $parentPath . '/' : '';
                $name = basename(rtrim($fullPath, '/'));
                
                $this->db->insert('storage_folders', [
                    'name' => $name,
                    'path' => $fullPath,
                    'parent_path' => $parentPath,
                    'created_by_key_id' => null
                ]);
            }
        }
    }
    
    /**
     * Generate unique filename
     */
    private function generateUniqueFilename(string $extension, string $folderPath = ''): string {
        $folderPath = rtrim($folderPath, '/') . ($folderPath ? '/' : '');
        $base = $this->config['uploads_base_path'] . $folderPath;
        
        do {
            $filename = $folderPath . uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        } while (file_exists($this->config['uploads_base_path'] . $filename));
        
        return $filename;
    }
    
    /**
     * Recursively remove empty parent directories
     */
    private function cleanupEmptyDirectories(string $dirPath): void {
        $basePath = rtrim($this->config['uploads_base_path'], DIRECTORY_SEPARATOR);
        
        while ($dirPath !== $basePath && is_dir($dirPath)) {
            $files = scandir($dirPath);
            if (count($files) <= 2) { // Only . and ..
                rmdir($dirPath);
                $dirPath = dirname($dirPath);
            } else {
                break;
            }
        }
    }
    
    /**
     * Recursively remove a directory and all its contents
     */
    private function rrmdir(string $dir): void {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    $path = $dir . DIRECTORY_SEPARATOR . $object;
                    if (is_dir($path)) {
                        $this->rrmdir($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            rmdir($dir);
        }
    }
    
    /**
     * Recursively copy a directory and all its contents
     */
    private function recursiveCopy(string $src, string $dest): void {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        if (is_dir($src)) {
            $dir = opendir($src);
            while (false !== ($file = readdir($dir))) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($src . DIRECTORY_SEPARATOR . $file)) {
                        $this->recursiveCopy($src . DIRECTORY_SEPARATOR . $file, $dest . DIRECTORY_SEPARATOR . $file);
                    } else {
                        copy($src . DIRECTORY_SEPARATOR . $file, $dest . DIRECTORY_SEPARATOR . $file);
                    }
                }
            }
            closedir($dir);
        }
    }
}
