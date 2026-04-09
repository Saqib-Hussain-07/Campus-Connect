<?php
// pages/join_group.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: '.SITE_URL.'/pages/groups.php'); exit; }
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    setFlash('danger','Invalid request.'); header('Location: '.SITE_URL.'/pages/groups.php'); exit;
}

$groupId = (int)($_POST['group_id'] ?? 0);
$me      = (int)$_SESSION['user_id'];

if ($groupId < 1) {
    setFlash('danger','Invalid group.'); header('Location: '.SITE_URL.'/pages/groups.php'); exit;
}

try {
    $db = getDB();
    $db->prepare('INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?,?)')->execute([$groupId, $me]);
    setFlash('success','You have joined the group!');
} catch (Exception $e) {
    setFlash('warning','Could not join group.');
}

$ref = $_SERVER['HTTP_REFERER'] ?? SITE_URL.'/pages/groups.php';
header('Location: ' . $ref);
exit;
