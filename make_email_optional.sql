-- Make email column optional in users table
-- This allows agents to be created without requiring an email address

ALTER TABLE `users` MODIFY COLUMN `email` VARCHAR(100) NULL;

-- Optional: Update any existing users with empty email to NULL for consistency
UPDATE `users` SET `email` = NULL WHERE `email` = '';
