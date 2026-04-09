<?php
// pages/handle_connection.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: '.SITE_URL.'/pages/dashboard.php'); exit; }
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    setFlash('danger','Invalid request.'); header('Location: '.SITE_URL.'/pages/dashboard.php'); exit;
}

$connId = (int)($_POST['conn_id'] ?? 0);
$action = $_POST['action'] ?? '';
$me     = (int)$_SESSION['user_id'];

if (!in_array($action, ['accept','reject']) || $connId < 1) {
    setFlash('danger','Invalid action.'); header('Location: '.SITE_URL.'/pages/dashboard.php'); exit;
}

$db = getDB();
// Make sure this user is the recipient
$st = $db->prepare('SELECT * FROM connections WHERE id=? AND to_user=?');
$st->execute([$connId, $me]);
$conn = $st->fetch();

if (!$conn) {
    setFlash('danger','Request not found.'); header('Location: '.SITE_URL.'/pages/dashboard.php'); exit;
}

$status = $action === 'accept' ? 'accepted' : 'rejected';
$db->prepare('UPDATE connections SET status=? WHERE id=?')->execute([$status, $connId]);
setFlash('success', $action === 'accept' ? 'Connection accepted!' : 'Request declined.');
header('Location: '.SITE_URL.'/pages/dashboard.php');
exit;
