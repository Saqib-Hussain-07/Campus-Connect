<?php
// pages/newsletter.php
require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: '.SITE_URL); exit; }

$email = trim($_POST['email'] ?? '');
if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    try {
        getDB()->prepare('INSERT IGNORE INTO newsletter (email) VALUES (?)')->execute([$email]);
        setFlash('success', 'You are subscribed to our newsletter!');
    } catch (Exception $e) {
        setFlash('warning', 'Could not subscribe. Please try again.');
    }
} else {
    setFlash('danger', 'Please enter a valid email address.');
}

$ref = $_SERVER['HTTP_REFERER'] ?? SITE_URL;
header('Location: ' . $ref);
exit;
