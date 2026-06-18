-- Mark the first admin API key as "initial" so it cannot be revoked
-- This prevents admins from accidentally revoking the last admin access

-- First, add the is_initial column
ALTER TABLE api_keys ADD COLUMN is_initial TINYINT(1) NOT NULL DEFAULT 0 AFTER is_admin;

-- Then mark the oldest admin key as initial (if there are active admin keys)
SET @min_id = (SELECT MIN(id) FROM (SELECT id FROM api_keys WHERE is_admin = 1 AND is_active = 1) AS tmp);
UPDATE api_keys SET is_initial = 1 WHERE id = @min_id AND @min_id IS NOT NULL;

