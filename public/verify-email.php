<?php

require_once __DIR__ . '/../src/includes/functions.php';
require_once __DIR__ . '/../src/models/User.php';

$userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$token = $_GET['token'] ?? '';

if (!$userId || !is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
    setFlash('error', 'Le lien de vérification est invalide.');
    redirect('login.php');
}

if (User::activateFromVerificationToken($userId, hash('sha256', $token))) {
    setFlash('success', 'Ton compte est activé. Tu peux maintenant te connecter.');
} else {
    setFlash('error', 'Ce lien est invalide, expiré ou a déjà été utilisé.');
}

redirect('login.php');
