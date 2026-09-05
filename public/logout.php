<?php
require_once __DIR__ . '/../src/includes/functions.php';

$_SESSION = [];
session_destroy();
redirect('login.php');
?>