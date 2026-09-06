<?php

// Point d'entrée Vercel : seul ce fichier est exécuté comme fonction PHP.
// Les pages publiques restent dans public/ pour conserver la structure locale XAMPP.
$path = trim((string) ($_GET['path'] ?? ''), '/');
if ($path === '') {
    $path = 'index.php';
}

$pages = [
    'index.php',
    'annonce.php',
    'login.php',
    'logout.php',
    'register.php',
    'verify-email.php',
    'dashboard/index.php',
    'dashboard/add-listing.php',
    'dashboard/delete-listing.php',
    'dashboard/edit-listing.php',
    'dashboard/mark-sold.php',
    'admin/index.php',
    'admin/delete-listing.php',
    'admin/users.php',
];

if (!in_array($path, $pages, true)) {
    http_response_code(404);
    exit('Page introuvable.');
}

require __DIR__ . '/../public/' . $path;
