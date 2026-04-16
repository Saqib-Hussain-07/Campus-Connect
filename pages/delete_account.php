<?php
// pages/delete_account.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

// CSRF + POST required — GET-based deletion is a CSRF vulnerability
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/pages/profile.php');
    exit;
}
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    setFlash('danger', 'Invalid request.');
    header('Location: ' . SITE_URL . '/pages/profile.php');
    exit;
}

$db = getDB();
$id = (int)$_SESSION['user_id'];

// Cascade deletes are handled by FK ON DELETE CASCADE
$db->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
session_unset();
session_destroy();
header('Location: ' . SITE_URL . '/?deleted=1');
exit;
