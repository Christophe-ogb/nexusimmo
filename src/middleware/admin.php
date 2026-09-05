<?php

require_once __DIR__ . '/auth.php';

if (!isAdmin()) {
    setFlash('error', 'Accès réservé aux administrateurs.');
    redirect(APP_URL . '/login.php');
}
