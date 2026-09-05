<?php
// Chemin : src/models/Listing.php

require_once __DIR__ . '/../config/database.php';

class Listing
{
    public static function getLocalities(): array
    {
        $stmt = Database::getConnection()->query('SELECT id, name FROM localities_immo ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO listings_immo
                (user_id, locality_id, listing_type, category, title, description, price, address_detail, phone_call, whatsapp_number)
             VALUES
                (:user_id, :locality_id, :listing_type, :category, :title, :description, :price, :address_detail, :phone_call, :whatsapp_number)'
        );
        $stmt->execute([
            'user_id'         => $data['user_id'],
            'locality_id'     => $data['locality_id'],
            'listing_type'    => $data['listing_type'],
            'category'        => $data['category'],
            'title'           => $data['title'],
            'description'     => $data['description'],
            'price'           => $data['price'],
            'address_detail'  => $data['address_detail'] !== '' ? $data['address_detail'] : null,
            'phone_call'      => $data['phone_call'],
            'whatsapp_number' => $data['whatsapp_number'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function findByUser(int $userId): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT l.*, loc.name AS locality_name,
                    (SELECT file_path FROM listing_media_immo m WHERE m.listing_id = l.id AND m.media_type = "image" ORDER BY m.position ASC LIMIT 1) AS cover_image
             FROM listings_immo l
             JOIN localities_immo loc ON loc.id = l.locality_id
             WHERE l.user_id = :user_id
             ORDER BY l.created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT l.*, loc.name AS locality_name
             FROM listings_immo l
             JOIN localities_immo loc ON loc.id = l.locality_id
             JOIN users_immo u ON u.id = l.user_id
             WHERE l.id = :id AND u.is_active = 1
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $listing = $stmt->fetch();
        return $listing ?: null;
    }

    // Comme findById, mais vérifie que l'annonce appartient bien à cet utilisateur
    public static function findByIdForUser(int $id, int $userId): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT l.*, loc.name AS locality_name
             FROM listings_immo l
             JOIN localities_immo loc ON loc.id = l.locality_id
             WHERE l.id = :id AND l.user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $listing = $stmt->fetch();
        return $listing ?: null;
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE listings_immo SET
                locality_id = :locality_id,
                listing_type = :listing_type,
                category = :category,
                title = :title,
                description = :description,
                price = :price,
                address_detail = :address_detail,
                phone_call = :phone_call,
                whatsapp_number = :whatsapp_number
             WHERE id = :id AND user_id = :user_id'
        );
        return $stmt->execute([
            'locality_id'     => $data['locality_id'],
            'listing_type'    => $data['listing_type'],
            'category'        => $data['category'],
            'title'           => $data['title'],
            'description'     => $data['description'],
            'price'           => $data['price'],
            'address_detail'  => $data['address_detail'] !== '' ? $data['address_detail'] : null,
            'phone_call'      => $data['phone_call'],
            'whatsapp_number' => $data['whatsapp_number'],
            'id'              => $id,
            'user_id'         => $userId,
        ]);
    }

    public static function markSoldRented(int $id, int $userId): bool
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE listings_immo SET status = 'sold_rented', sold_at = NOW(), last_reminder_at = NOW()
             WHERE id = :id AND user_id = :user_id"
        );
        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    public static function delete(int $id, int $userId): bool
    {
        $stmt = Database::getConnection()->prepare('DELETE FROM listings_immo WHERE id = :id AND user_id = :user_id');
        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    public static function touchReminder(int $id): void
    {
        $stmt = Database::getConnection()->prepare('UPDATE listings_immo SET last_reminder_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    // Vrai si l'annonce est vendue/louée ET n'a pas eu de relance depuis 3 jours
    public static function needsReminder(array $listing): bool
    {
        if ($listing['status'] !== 'sold_rented') {
            return false;
        }
        if ($listing['last_reminder_at'] === null) {
            return true;
        }
        $last = new DateTime($listing['last_reminder_at']);
        $now  = new DateTime();
        return $now->diff($last)->days >= 3;
    }

    // Recherche publique paginée avec filtres (page d'accueil)
    public static function searchPage(array $filters, int $page = 1, int $perPage = 12): array
    {
        $from = " FROM listings_immo l
                  JOIN localities_immo loc ON loc.id = l.locality_id
                  JOIN users_immo u ON u.id = l.user_id
                  WHERE l.status = 'active' AND u.is_active = 1";
        $params = [];

        if (!empty($filters['type'])) {
            $from .= ' AND l.listing_type = :type';
            $params['type'] = $filters['type'];
        }
        if (!empty($filters['category'])) {
            $from .= ' AND l.category = :category';
            $params['category'] = $filters['category'];
        }
        if (!empty($filters['locality_id'])) {
            $from .= ' AND l.locality_id = :locality_id';
            $params['locality_id'] = $filters['locality_id'];
        }
        if (!empty($filters['budget_max'])) {
            $from .= ' AND l.price <= :budget_max';
            $params['budget_max'] = $filters['budget_max'];
        }

        $orderBy = match ($filters['sort'] ?? 'recent') {
            'price_asc'  => ' ORDER BY l.price ASC',
            'price_desc' => ' ORDER BY l.price DESC',
            default      => ' ORDER BY l.created_at DESC',
        };

        $pdo = Database::getConnection();
        $countStmt = $pdo->prepare('SELECT COUNT(*)' . $from);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $perPage = max(1, min($perPage, 24));
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT l.*, loc.name AS locality_name' . $from . $orderBy . " LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $listings = $stmt->fetchAll();

        if ($listings) {
            self::attachMedia($listings);
        }

        return [
            'items' => $listings,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    // Compatibilité avec les éventuels appels existants hors de la page d'accueil.
    public static function search(array $filters): array
    {
        return self::searchPage($filters, 1, 24)['items'];
    }

    // Récupère jusqu'à 3 médias par annonce (images et vidéos) en une seule requête.
    private static function attachMedia(array &$listings): void
    {
        $ids = array_column($listings, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = Database::getConnection()->prepare(
            "SELECT listing_id, file_path, media_type FROM listing_media_immo
             WHERE listing_id IN ($placeholders)
             ORDER BY listing_id, position ASC"
        );
        $stmt->execute($ids);

        $mediaByListing = [];
        foreach ($stmt->fetchAll() as $row) {
            $mediaByListing[$row['listing_id']][] = $row;
        }

        foreach ($listings as &$listing) {
            $listing['media'] = array_slice($mediaByListing[$listing['id']] ?? [], 0, 3);
        }
    }

    public static function incrementViews(int $id): void
    {
        $stmt = Database::getConnection()->prepare('UPDATE listings_immo SET views_count = views_count + 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    // Toutes les annonces, tous statuts et tous vendeurs confondus (panel admin)
    public static function findAllForAdmin(): array
    {
        $stmt = Database::getConnection()->query(
            "SELECT l.*, loc.name AS locality_name, u.email AS seller_email, u.phone AS seller_phone,
                    (SELECT file_path FROM listing_media_immo m WHERE m.listing_id = l.id AND m.media_type = 'image' ORDER BY m.position ASC LIMIT 1) AS cover_image
             FROM listings_immo l
             JOIN localities_immo loc ON loc.id = l.locality_id
             JOIN users_immo u ON u.id = l.user_id
             ORDER BY l.created_at DESC"
        );
        return $stmt->fetchAll();
    }

    // Suppression sans vérification de propriétaire — réservée à l'admin
    public static function deleteAsAdmin(int $id): bool
    {
        $stmt = Database::getConnection()->prepare('DELETE FROM listings_immo WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
