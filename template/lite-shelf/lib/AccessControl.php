<?php
/**
 * Access Control
 *
 * Handles resource-level access control for API keys.
 * Admin keys have unlimited access; non-admin keys must be explicitly granted access.
 */

class AccessControl {
    private static mixed $db = null;

    private static function getDb(): mixed {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }

    /**
     * Check if an API key is admin (unlimited access)
     */
    public static function isAdmin(array $apiKeyData): bool {
        return !empty($apiKeyData['is_admin']);
    }

    /**
     * Check if key has access to a specific collection
     * Admin keys always have access
     */
    public static function hasCollectionAccess(int $keyId, int $collectionId, string $action = 'read'): bool {
        $db = self::getDb();

        // Check if key is admin
        $apiKeysTable = $db->resolveTable('api_keys');
        $keyData = $db->fetchOne("SELECT is_admin FROM {$apiKeysTable} WHERE id = ?", [$keyId]);
        if ($keyData && $keyData['is_admin']) {
            return true;
        }

        // Map action to required access level
        $requiredLevel = match ($action) {
            'read' => 'read',
            'write' => 'write',
            'full' => 'full',
            default => 'read'
        };

        // Check if key has granted access to this collection
        $accessTable = $db->resolveTable('api_key_collection_access');
        $access = $db->fetchOne(
            "SELECT access_level FROM {$accessTable} WHERE key_id = ? AND collection_id = ?",
            [$keyId, $collectionId]
        );

        if (!$access) {
            return false;
        }

        // Compare access levels
        $levels = ['read' => 1, 'write' => 2, 'full' => 3];
        $granted = $levels[$access['access_level']] ?? 0;
        $needed = $levels[$requiredLevel] ?? 0;

        return $granted >= $needed;
    }

    /**
     * Check if key has access to a specific folder path
     * Also checks parent folders for hierarchical access
     * Admin keys always have access
     */
    public static function hasFolderAccess(int $keyId, string $folderPath, string $action = 'read'): bool {
        $db = self::getDb();

        // Admin keys have unlimited access
        $apiKeysTable = $db->resolveTable('api_keys');
        $keyData = $db->fetchOne("SELECT is_admin FROM {$apiKeysTable} WHERE id = ?", [$keyId]);
        if ($keyData && $keyData['is_admin']) {
            return true;
        }

        // Root folder is accessible to all authenticated keys
        if ($folderPath === '' || $folderPath === '/') {
            return true;
        }

        // Normalize path
        $folderPath = rtrim($folderPath, '/') . ($folderPath ? '/' : '');

        // Map action to required access level
        $requiredLevel = match ($action) {
            'read' => 'read',
            'write' => 'write',
            'full' => 'full',
            default => 'read'
        };

        $levels = ['read' => 1, 'write' => 2, 'full' => 3];
        $needed = $levels[$requiredLevel] ?? 0;

        // Check direct access to this folder path
        $folderAccessTable = $db->resolveTable('api_key_folder_access');
        $access = $db->fetchOne(
            "SELECT access_level FROM {$folderAccessTable} WHERE key_id = ? AND folder_path = ?",
            [$keyId, $folderPath]
        );

        if ($access) {
            $granted = $levels[$access['access_level']] ?? 0;
            if ($granted >= $needed) {
                return true;
            }
        }

        // Check parent folders for hierarchical access
        // Build list of parent paths
        $parts = explode('/', rtrim($folderPath, '/'));
        $checkPath = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if ($checkPath === '') {
                $checkPath = $part . '/';
            } else {
                $checkPath .= $part . '/';
            }

            // Skip checking the folder itself (already checked above)
            if ($checkPath === $folderPath) {
                continue;
            }

            $parentAccess = $db->fetchOne(
                "SELECT access_level FROM {$folderAccessTable} WHERE key_id = ? AND folder_path = ?",
                [$keyId, $checkPath]
            );

            if ($parentAccess) {
                $granted = $levels[$parentAccess['access_level']] ?? 0;
                if ($granted >= $needed) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get all collections a key has access to
     * Admin keys get all collections
     */
    public static function getAccessibleCollections(int $keyId): array {
        $db = self::getDb();

        // Admin keys get all collections
        $apiKeysTable = $db->resolveTable('api_keys');
        $collectionsTable = $db->resolveTable('data_collections');
        $keyData = $db->fetchOne("SELECT is_admin FROM {$apiKeysTable} WHERE id = ?", [$keyId]);
        if ($keyData && $keyData['is_admin']) {
            return $db->fetchAll("SELECT id, name, description, created_at FROM {$collectionsTable} ORDER BY name");
        }

        // Get granted collections
        $accessTable = $db->resolveTable('api_key_collection_access');
        $ids = $db->fetchAll(
            "SELECT collection_id FROM {$accessTable} WHERE key_id = ?",
            [$keyId]
        );

        if (empty($ids)) {
            return [];
        }

        $collectionIds = array_map(fn($r) => $r['collection_id'], $ids);
        $placeholders = implode(',', array_fill(0, count($collectionIds), '?'));

        return $db->fetchAll(
            "SELECT id, name, description, created_at FROM {$collectionsTable} WHERE id IN ($placeholders) ORDER BY name",
            $collectionIds
        );
    }

    /**
     * Get all folders a key has access to at a given parent path
     * Admin keys get all folders
     */
    public static function getAccessibleFolders(int $keyId, string $parentPath = ''): array {
        $db = self::getDb();

        // Admin keys get all folders
        $apiKeysTable = $db->resolveTable('api_keys');
        $foldersTable = $db->resolveTable('storage_folders');
        $keyData = $db->fetchOne("SELECT is_admin FROM {$apiKeysTable} WHERE id = ?", [$keyId]);
        if ($keyData && $keyData['is_admin']) {
            $parentPath = rtrim($parentPath, '/') . ($parentPath ? '/' : '');
            if ($parentPath === '') {
                return $db->fetchAll("SELECT * FROM {$foldersTable} WHERE parent_path = '' ORDER BY name ASC");
            }
            return $db->fetchAll(
                "SELECT * FROM {$foldersTable} WHERE parent_path = ? ORDER BY name ASC",
                [$parentPath]
            );
        }

        // Get all folders this key has access to (at this parent or inherited)
        $folderAccessTable = $db->resolveTable('api_key_folder_access');
        $allAccess = $db->fetchAll(
            "SELECT folder_path, access_level FROM {$folderAccessTable} WHERE key_id = ?",
            [$keyId]
        );

        if (empty($allAccess)) {
            // Key also has access to root-level folders implicitly if they have any folder access
            // For now return empty - no folder access means no folders visible
            return [];
        }

        $parentPath = rtrim($parentPath, '/') . ($parentPath ? '/' : '');

        // Get direct children at this parent path
        $folders = $db->fetchAll(
            "SELECT * FROM {$foldersTable} WHERE parent_path = ? ORDER BY name ASC",
            [$parentPath]
        );

        // Filter: keep folder if key has direct access OR parent has access
        $result = [];
        foreach ($folders as $folder) {
            // Check if key has any folder access (direct or inherited)
            $hasAccess = false;

            // Check direct access
            foreach ($allAccess as $grant) {
                $grantedPath = $grant['folder_path'];

                // Exact match
                if ($grantedPath === $folder['path']) {
                    $hasAccess = true;
                    break;
                }

                // Parent path grant (key has access to a parent of this folder)
                if (str_starts_with($folder['path'], $grantedPath)) {
                    $hasAccess = true;
                    break;
                }
            }

            if ($hasAccess) {
                $result[] = $folder;
            }
        }

        return $result;
    }

    /**
     * Get granted permissions for a key (for UI display)
     */
    public static function getKeyPermissions(int $keyId): array {
        $db = self::getDb();

        $accessTable = $db->resolveTable('api_key_collection_access');
        $collectionsTable = $db->resolveTable('data_collections');
        $folderAccessTable = $db->resolveTable('api_key_folder_access');

        $collections = $db->fetchAll(
            "SELECT a.collection_id, a.access_level, c.name as collection_name
             FROM {$accessTable} a
             JOIN {$collectionsTable} c ON a.collection_id = c.id
             WHERE a.key_id = ?
             ORDER BY c.name",
            [$keyId]
        );

        $folders = $db->fetchAll(
            "SELECT folder_path, access_level
             FROM {$folderAccessTable}
             WHERE key_id = ?
             ORDER BY folder_path",
            [$keyId]
        );

        return [
            'collections' => $collections,
            'folders' => $folders
        ];
    }

    /**
     * Grant collection access to an API key
     */
    public static function grantCollectionAccess(int $keyId, int $collectionId, string $accessLevel = 'read'): bool {
        $db = self::getDb();

        // Validate access level
        if (!in_array($accessLevel, ['read', 'write', 'full'])) {
            return false;
        }

        // Check if collection exists
        $collectionsTable = $db->resolveTable('data_collections');
        $collection = $db->fetchOne("SELECT id FROM {$collectionsTable} WHERE id = ?", [$collectionId]);
        if (!$collection) {
            return false;
        }

        // Check if key exists
        $apiKeysTable = $db->resolveTable('api_keys');
        $keyData = $db->fetchOne("SELECT id FROM {$apiKeysTable} WHERE id = ?", [$keyId]);
        if (!$keyData) {
            return false;
        }

        // Insert or update
        $accessTable = $db->resolveTable('api_key_collection_access');
        $existing = $db->fetchOne(
            "SELECT id FROM {$accessTable} WHERE key_id = ? AND collection_id = ?",
            [$keyId, $collectionId]
        );

        if ($existing) {
            return $db->update(
                'api_key_collection_access',
                ['access_level' => $accessLevel],
                'key_id = ? AND collection_id = ?',
                [$keyId, $collectionId]
            ) > 0;
        }

        return $db->insert('api_key_collection_access', [
            'key_id' => $keyId,
            'collection_id' => $collectionId,
            'access_level' => $accessLevel
        ]) > 0;
    }

    /**
     * Revoke collection access for an API key
     */
    public static function revokeCollectionAccess(int $keyId, int $collectionId): bool {
        $db = self::getDb();

        return (bool) $db->delete(
            'api_key_collection_access',
            'key_id = ? AND collection_id = ?',
            [$keyId, $collectionId]
        );
    }

    /**
     * Grant folder access to an API key
     */
    public static function grantFolderAccess(int $keyId, string $folderPath, string $accessLevel = 'read'): bool {
        $db = self::getDb();

        // Validate access level
        if (!in_array($accessLevel, ['read', 'write', 'full'])) {
            return false;
        }

        // Check if key exists
        $apiKeysTable = $db->resolveTable('api_keys');
        $keyData = $db->fetchOne("SELECT id FROM {$apiKeysTable} WHERE id = ?", [$keyId]);
        if (!$keyData) {
            return false;
        }

        // Insert or update
        $folderAccessTable = $db->resolveTable('api_key_folder_access');
        $existing = $db->fetchOne(
            "SELECT id FROM {$folderAccessTable} WHERE key_id = ? AND folder_path = ?",
            [$keyId, $folderPath]
        );

        if ($existing) {
            return $db->update(
                'api_key_folder_access',
                ['access_level' => $accessLevel],
                'key_id = ? AND folder_path = ?',
                [$keyId, $folderPath]
            ) > 0;
        }

        return $db->insert('api_key_folder_access', [
            'key_id' => $keyId,
            'folder_path' => $folderPath,
            'access_level' => $accessLevel
        ]) > 0;
    }

    /**
     * Revoke folder access for an API key
     */
    public static function revokeFolderAccess(int $keyId, string $folderPath): bool {
        $db = self::getDb();

        return (bool) $db->delete(
            'api_key_folder_access',
            'key_id = ? AND folder_path = ?',
            [$keyId, $folderPath]
        );
    }
}
