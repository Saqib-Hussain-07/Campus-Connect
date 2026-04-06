<?php
// pages/create_group.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: '.SITE_URL.'/pages/groups.php'); exit; }
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    setFlash('danger','Invalid request.'); header('Location: '.SITE_URL.'/pages/groups.php'); exit;
}

$name   = trim($_POST['name']        ?? '');
$type   = $_POST['type']              ?? 'study';
$status = $_POST['status']            ?? 'active';
$desc   = trim($_POST['description'] ?? '');
$me     = (int)$_SESSION['user_id'];

if (strlen($name) < 2) {
    setFlash('danger','Group name is required.'); header('Location: '.SITE_URL.'/pages/groups.php'); exit;
}
if (!in_array($type, ['study','project','forum'])) $type = 'study';
if (!in_array($status, ['active','recruiting','open'])) $status = 'active';

$db = getDB();
$st = $db->prepare('INSERT INTO groups_list (name, description, type, status, created_by) VALUES (?,?,?,?,?)');
$st->execute([$name, $desc, $type, $status, $me]);
$newId = $db->lastInsertId();

// Auto-join creator
$db->prepare('INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?,?)')->execute([$newId, $me]);

setFlash('success', 'Group "'.$name.'" created successfully!');
header('Location: '.SITE_URL.'/pages/groups.php');
exit;
