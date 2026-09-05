<?php
// Chemin : src/models/User.php

require_once __DIR__ . '/../config/database.php';

class User
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM users_immo WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByPhone(string $phone): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM users_immo WHERE phone = :phone LIMIT 1');
        $stmt->execute(['phone' => $phone]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM users_immo WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(string $email, string $phone, string $password): int
    {
        $pdo  = Database::getConnection();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO users_immo (email, phone, password_hash, role, is_active) VALUES (:email, :phone, :hash, :role, 0)'
        );
        $stmt->execute([
            'email' => $email,
            'phone' => $phone,
            'hash'  => $hash,
            'role'  => 'user',
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function setVerificationToken(int $id, string $tokenHash, DateTimeImmutable $expiresAt): bool
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE users_immo
             SET verification_token_hash = :token_hash, verification_expires_at = :expires_at
             WHERE id = :id'
        );

        return $stmt->execute([
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'id' => $id,
        ]);
    }

    public static function activateFromVerificationToken(int $id, string $tokenHash): bool
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE users_immo
             SET is_active = 1,
                 email_verified_at = NOW(),
                 verification_token_hash = NULL,
                 verification_expires_at = NULL
             WHERE id = :id
               AND is_active = 0
               AND verification_token_hash = :token_hash
               AND verification_expires_at > NOW()'
        );

        return $stmt->execute(['id' => $id, 'token_hash' => $tokenHash]) && $stmt->rowCount() === 1;
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::getConnection()->prepare('DELETE FROM users_immo WHERE id = :id AND is_active = 0');
        return $stmt->execute(['id' => $id]);
    }

    public static function verifyPassword(array $user, string $password): bool
    {
        return password_verify($password, $user['password_hash']);
    }

    // Tous les utilisateurs, avec leur nombre d'annonces (panel admin)
    public static function findAll(): array
    {
        $stmt = Database::getConnection()->query(
            "SELECT u.*, (SELECT COUNT(*) FROM listings_immo l WHERE l.user_id = u.id) AS listing_count
             FROM users_immo u
             ORDER BY u.created_at DESC"
        );
        return $stmt->fetchAll();
    }

    public static function setActive(int $id, bool $active): bool
    {
        $stmt = Database::getConnection()->prepare('UPDATE users_immo SET is_active = :active WHERE id = :id');
        return $stmt->execute(['active' => $active ? 1 : 0, 'id' => $id]);
    }
}
