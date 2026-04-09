<?php
// pages/change_password.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: '.SITE_URL.'/pages/profile.php'); exit; }
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    setFlash('danger','Invalid request.'); header('Location: '.SITE_URL.'/pages/profile.php'); exit;
}

$current = $_POST['current_password'] ?? '';
$new     = $_POST['new_password']     ?? '';
$confirm = $_POST['confirm_password'] ?? '';
$db      = getDB();

$user = currentUser();
if (!password_verify($current, $user['password'])) {
    setFlash('danger','Current password is incorrect.'); header('Location: '.SITE_URL.'/pages/profile.php'); exit;
}
if (strlen($new) < 8) {
    setFlash('danger','New password must be at least 8 characters.'); header('Location: '.SITE_URL.'/pages/profile.php'); exit;
}
if ($new !== $confirm) {
    setFlash('danger','New passwords do not match.'); header('Location: '.SITE_URL.'/pages/profile.php'); exit;
}

$hash = password_hash($new, PASSWORD_BCRYPT);
$db->prepare('UPDATE users SET password=? WHERE id=?')->execute([$hash, $user['id']]);
unset($_SESSION['_user_cache']); // bust session cache
setFlash('success','Password updated successfully!');
header('Location: '.SITE_URL.'/pages/profile.php');
exit;
