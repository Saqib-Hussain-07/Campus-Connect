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

    // Check if already a member
    $chk = $db->prepare('SELECT 1 FROM group_members WHERE group_id=? AND user_id=?');
    $chk->execute([$groupId, $me]);
    if ($chk->fetch()) {
        setFlash('info', 'You are already a member of this group.');
    } else {
        $db->prepare('INSERT INTO group_members (group_id, user_id) VALUES (?,?)')->execute([$groupId, $me]);
        setFlash('success','You have joined the group!');
    }
} catch (Exception $e) {
    setFlash('warning','Could not join group.');
}

// Validate referer to prevent open redirect
$ref = $_SERVER['HTTP_REFERER'] ?? '';
if (!$ref || strpos($ref, SITE_URL) !== 0) {
    $ref = SITE_URL . '/pages/groups.php';
}
header('Location: ' . $ref);
exit;
