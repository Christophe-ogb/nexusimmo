<?php
// Environnement : development en local, production une fois en ligne.
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_NAME', 'NEXUS Immobilier');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/nexus-immo/public');
define('MAIL_FROM_ADDRESS', 'happer880@gmail.com');
define('MAIL_FROM_NAME', 'NEXUS Immobilier');

$brevoApiKey = getenv('BREVO_API_KEY');
if (is_string($brevoApiKey) && $brevoApiKey !== '') {
    define('BREVO_API_KEY', $brevoApiKey);
} elseif (is_file(__DIR__ . '/secrets.php')) {
    require_once __DIR__ . '/secrets.php';
}

if (!defined('BREVO_API_KEY')) {
    define('BREVO_API_KEY', '');
}

// Base MySQL : valeurs locales XAMPP par défaut, variables de production facultatives.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', (int) (getenv('DB_PORT') ?: 3306));
define('DB_NAME', getenv('DB_NAME') ?: 'nexus_immo');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Limites compatibles avec les fonctions Vercel.
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024);
define('MAX_UPLOAD_FILES', 5);
define('MAX_UPLOAD_TOTAL_SIZE', 4 * 1024 * 1024);
define('UPLOAD_DIR', __DIR__ . '/../../public/uploads/');
define('UPLOAD_IMAGES_DIR', UPLOAD_DIR . 'images/');
define('UPLOAD_VIDEOS_DIR', UPLOAD_DIR . 'videos/');

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
if (APP_ENV === 'production') {
    ini_set('session.cookie_secure', 1);
}

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
