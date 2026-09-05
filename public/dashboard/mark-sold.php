<?php
// Chemin : public/dashboard/mark-sold.php

require_once __DIR__ . '/../../src/middleware/auth.php';
require_once __DIR__ . '/../../src/models/Listing.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isValidCsrfToken($_POST['csrf_token'] ?? null)) {
    $listingId = (int) ($_POST['listing_id'] ?? 0);
    if ($listingId > 0) {
        Listing::markSoldRented($listingId, currentUserId());
        setFlash('success', 'Annonce marquée comme vendue/louée et retirée de l\'affichage public.');
    }
}

redirect('index.php');
?>
