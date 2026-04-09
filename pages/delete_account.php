<?php
// pages/delete_account.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

$db = getDB();
$id = (int)$_SESSION['user_id'];

// Cascade deletes are handled by FK ON DELETE CASCADE
$db->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
session_destroy();
header('Location: '.SITE_URL.'/?deleted=1');
exit;
