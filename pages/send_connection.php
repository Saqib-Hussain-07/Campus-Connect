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

    // Check if a connection already exists in either direction
    $chk = $db->prepare(
        'SELECT status FROM connections
         WHERE (from_user=? AND to_user=?) OR (from_user=? AND to_user=?)'
    );
    $chk->execute([$me, $toUser, $toUser, $me]);
    $existing = $chk->fetch();

    if ($existing) {
        if ($existing['status'] === 'accepted') {
            setFlash('info', 'You are already connected with this student.');
        } else {
            setFlash('info', 'A connection request is already pending.');
        }
    } else {
        $st = $db->prepare('INSERT INTO connections (from_user, to_user, status) VALUES (?,?,?)');
        $st->execute([$me, $toUser, 'pending']);
        setFlash('success', 'Connection request sent!');
    }
} catch (Exception $e) {
    setFlash('warning', 'Could not send request. Please try again.');
}

// Validate referer to prevent open redirect
$ref = $_SERVER['HTTP_REFERER'] ?? '';
if (!$ref || strpos($ref, SITE_URL) !== 0) {
    $ref = SITE_URL . '/pages/students.php';
}
header('Location: ' . $ref);
exit;
