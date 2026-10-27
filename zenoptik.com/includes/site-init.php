<?php
// Ön yüz ortak başlatma
require_once __DIR__ . '/../config/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db->exec("SET time_zone = '+03:00'");
