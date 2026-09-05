<?php
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    setFlash('error', 'Connecte-toi pour accéder à cette page.');
    redirect(APP_URL . '/login.php');
}
?>