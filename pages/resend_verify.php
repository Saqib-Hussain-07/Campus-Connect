<?php
// pages/resend_verify.php — Resend verification email
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Resend Verification';

$email = trim($_GET['email'] ?? '');
if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $db = getDB();
    $st = $db->prepare('SELECT id, name, is_verified FROM users WHERE email = ?');
    $st->execute([$email]);
    $user = $st->fetch();
    if ($user && !$user['is_verified']) {
        $token = bin2hex(random_bytes(32));
        $db->prepare('UPDATE users SET verify_token=? WHERE id=?')->execute([$token, $user['id']]);
        $verifyUrl = SITE_URL . '/pages/verify.php?token=' . $token . '&email=' . urlencode($email);
        $subject = 'Verify your CampusConnect account';
        $body    = "Hello {$user['name']},\n\nPlease verify your account:\n$verifyUrl\n\nCampusConnect Team";
        @mail($email, $subject, $body, 'From: noreply@campusconnect.local');
        setFlash('success', 'Verification link sent! <a href="'.e($verifyUrl).'" style="color:var(--rust);">Click here to verify now &rarr;</a>');
    } elseif ($user && $user['is_verified']) {
        setFlash('success', 'Your account is already verified. You can log in.');
    } else {
        setFlash('danger', 'Email address not found.');
    }
}
header('Location: '.SITE_URL.'/pages/login.php');
exit;
