<?php
// Chemin : src/models/Media.php

require_once __DIR__ . '/../config/database.php';

class Media
{
    public static function create(int $listingId, string $type, string $path, int $position): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO listing_media_immo (listing_id, media_type, file_path, position) VALUES (:listing_id, :type, :path, :position)'
        );
        $stmt->execute([
            'listing_id' => $listingId,
            'type'       => $type,
            'path'       => $path,
            'position'   => $position,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function findByListing(int $listingId): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM listing_media_immo WHERE listing_id = :listing_id ORDER BY position ASC'
        );
        $stmt->execute(['listing_id' => $listingId]);
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM listing_media_immo WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $media = $stmt->fetch();
        return $media ?: null;
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::getConnection()->prepare('DELETE FROM listing_media_immo WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    // Prochaine position libre, pour ajouter des fichiers à la suite des existants lors d'une modification
    public static function nextPosition(int $listingId): int
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT COALESCE(MAX(position), -1) + 1 AS next_pos FROM listing_media_immo WHERE listing_id = :listing_id'
        );
        $stmt->execute(['listing_id' => $listingId]);
        return (int) $stmt->fetch()['next_pos'];
    }
}
