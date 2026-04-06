<?php
// pages/rsvp_event.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD']!=='POST'||!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')) {
    header('Location: '.SITE_URL.'/pages/events.php'); exit;
}
$evId   = (int)($_POST['event_id']??0);
$status = $_POST['status']??'going';
$me     = (int)$_SESSION['user_id'];
if (!in_array($status,['going','interested','not_going'])) $status='going';
$db = getDB();
$ex = $db->prepare('SELECT id,status FROM event_rsvps WHERE event_id=? AND user_id=?');
$ex->execute([$evId,$me]);
$row = $ex->fetch();
if ($row && $row['status']===$status) {
    $db->prepare('DELETE FROM event_rsvps WHERE event_id=? AND user_id=?')->execute([$evId,$me]);
    setFlash('success','RSVP removed.');
} else {
    $db->prepare('INSERT INTO event_rsvps (event_id,user_id,status) VALUES (?,?,?) ON DUPLICATE KEY UPDATE status=?')
       ->execute([$evId,$me,$status,$status]);
    setFlash('success','RSVP updated — you are '.str_replace('_',' ',$status).'!');
}
header('Location: '.SITE_URL.'/pages/events.php'); exit;
