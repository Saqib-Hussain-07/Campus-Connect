<?php
// pages/logout.php
require_once __DIR__ . '/../includes/config.php';
if (isLoggedIn()) {
    getDB()->prepare('UPDATE users SET is_online=0 WHERE id=?')->execute([$_SESSION['user_id']]);
}
session_unset();     // Clear all session variables first
session_destroy();   // Then destroy the session
header('Location: ' . SITE_URL);
exit;
