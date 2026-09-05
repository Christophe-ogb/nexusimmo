ALTER TABLE users
    ADD COLUMN verification_token_hash CHAR(64) NULL AFTER is_active,
    ADD COLUMN verification_expires_at DATETIME NULL AFTER verification_token_hash,
    ADD COLUMN email_verified_at DATETIME NULL AFTER verification_expires_at;
