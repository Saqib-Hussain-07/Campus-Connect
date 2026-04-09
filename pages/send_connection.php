<?php
// pages/send_connection.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: '.SITE_URL.'/pages/students.php'); exit; }
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    setFlash('danger', 'Invalid request.'); header('Location: '.SITE_URL.'/pages/students.php'); exit;
}

$toUser = (int)($_POST['to_user'] ?? 0);
$me     = (int)$_SESSION['user_id'];

if ($toUser < 1 || $toUser === $me) {
    setFlash('danger', 'Invalid request.'); header('Location: '.SITE_URL.'/pages/students.php'); exit;
}

try {
    $db = getDB();
    $st = $db->prepare('INSERT IGNORE INTO connections (from_user, to_user, status) VALUES (?,?,?)');
    $st->execute([$me, $toUser, 'pending']);
    setFlash('success', 'Connection request sent!');
} catch (Exception $e) {
    setFlash('warning', 'Could not send request. You may have already sent one.');
}

$ref = $_SERVER['HTTP_REFERER'] ?? SITE_URL.'/pages/students.php';
header('Location: ' . $ref);
exit;
