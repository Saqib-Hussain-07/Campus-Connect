<?php
// pages/create_event.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD']!=='POST'||!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')) {
    header('Location: '.SITE_URL.'/pages/events.php'); exit;
}
$title    = trim($_POST['title']??'');
$desc     = trim($_POST['description']??'');
$cat      = $_POST['category']??'other';
$venue    = trim($_POST['venue']??'');
$evDate   = $_POST['event_date']??'';
$regDead  = $_POST['registration_deadline']??null;
$maxAtt   = (int)($_POST['max_attendees']??0);
$online   = (int)($_POST['is_online']??0);
$regLink  = trim($_POST['registration_link']??'');
$me       = (int)$_SESSION['user_id'];

if (strlen($title)<3||empty($evDate)) { setFlash('danger','Please fill all required fields.'); header('Location: '.SITE_URL.'/pages/events.php'); exit; }
if (!in_array($cat,['hackathon','seminar','workshop','cultural','sports','other'])) $cat='other';

$db = getDB();
$seed = 'ev'.rand(1,9);
$db->prepare('INSERT INTO events (user_id,title,description,category,venue,event_date,registration_deadline,max_attendees,banner_seed,is_online,registration_link)
              VALUES (?,?,?,?,?,?,?,?,?,?,?)')
   ->execute([$me,$title,$desc,$cat,$venue,$evDate,($regDead?:null),$maxAtt,$seed,$online,$regLink]);
$db->prepare("INSERT INTO activity_feed (user_id,type,ref_id,ref_title) VALUES (?,?,?,?)")
   ->execute([$me,'event_created',$db->lastInsertId(),$title]);
setFlash('success','Event created successfully!');
header('Location: '.SITE_URL.'/pages/events.php'); exit;
