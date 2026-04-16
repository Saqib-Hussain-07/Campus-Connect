<?php
// pages/mark_read.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

// Require POST + CSRF — GET requests must not modify data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    $ref = $_SERVER['HTTP_REFERER'] ?? SITE_URL . '/pages/notifications.php';
    if (!$ref || strpos($ref, SITE_URL) !== 0) {
        $ref = SITE_URL . '/pages/notifications.php';
    }
    header('Location: ' . $ref);
    exit;
}

getDB()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$_SESSION['user_id']]);

// Validate referer to prevent open redirect
$ref = $_SERVER['HTTP_REFERER'] ?? '';
if (!$ref || strpos($ref, SITE_URL) !== 0) {
    $ref = SITE_URL . '/pages/notifications.php';
}
header('Location: ' . $ref);
exit;
