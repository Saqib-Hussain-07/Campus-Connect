<?php
// pages/like_resource.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD']!=='POST'||!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')) {
    header('Location: '.SITE_URL.'/pages/resources.php'); exit;
}
$resId = (int)($_POST['resource_id']??0);
$me    = (int)$_SESSION['user_id'];
$db    = getDB();
$ex = $db->prepare('SELECT id FROM resource_likes WHERE resource_id=? AND user_id=?');
$ex->execute([$resId,$me]);
if ($ex->fetch()) {
    $db->prepare('DELETE FROM resource_likes WHERE resource_id=? AND user_id=?')->execute([$resId,$me]);
    $db->prepare('UPDATE resources SET likes=GREATEST(0,likes-1) WHERE id=?')->execute([$resId]);
} else {
    $db->prepare('INSERT IGNORE INTO resource_likes (resource_id,user_id) VALUES (?,?)')->execute([$resId,$me]);
    $db->prepare('UPDATE resources SET likes=likes+1 WHERE id=?')->execute([$resId]);
}
$redirect = urldecode($_POST['redirect'] ?? SITE_URL.'/pages/resources.php');
header('Location: '.$redirect); exit;
