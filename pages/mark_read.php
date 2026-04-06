<?php
// pages/mark_read.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
getDB()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$_SESSION['user_id']]);
$ref = $_SERVER['HTTP_REFERER'] ?? SITE_URL.'/pages/notifications.php';
header('Location: '.$ref); exit;
