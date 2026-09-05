<?php
// Chemin : public/dashboard/delete-listing.php

require_once __DIR__ . '/../../src/middleware/auth.php';
require_once __DIR__ . '/../../src/models/Listing.php';
require_once __DIR__ . '/../../src/models/Media.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    $listingId = (int) ($_POST['listing_id'] ?? 0);
    if ($listingId > 0) {
        $mediaFiles = Media::findByListing($listingId);

        if (Listing::delete($listingId, currentUserId())) {
            // Nettoie les fichiers physiques (la BDD supprime déjà les lignes via ON DELETE CASCADE)
            foreach ($mediaFiles as $media) {
                $filePath = __DIR__ . '/../' . $media['file_path'];
                if (is_file($filePath)) {
                    unlink($filePath);
                }
            }
            setFlash('success', 'Annonce supprimée.');
        }
    }
}

redirect('index.php');
?>
