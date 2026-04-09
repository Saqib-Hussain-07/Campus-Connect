<?php
// pages/post_resource.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD']!=='POST'||!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')) {
    header('Location: '.SITE_URL.'/pages/resources.php'); exit;
}
$title   = trim($_POST['title']??'');
$desc    = trim($_POST['description']??'');
$subject = trim($_POST['subject']??'');
$type    = $_POST['type']??'other';
$url     = trim($_POST['url']??'');
$dept    = trim($_POST['department']??'');
$sem     = (int)($_POST['semester']??0);
$me      = (int)$_SESSION['user_id'];
if (strlen($title)<3||empty($url)) { setFlash('danger','Title and URL are required.'); header('Location: '.SITE_URL.'/pages/resources.php'); exit; }
if (!in_array($type,['notes','video','book','article','tool','other'])) $type='other';
$db = getDB();
$db->prepare('INSERT INTO resources (user_id,title,description,subject,type,url,department,semester) VALUES (?,?,?,?,?,?,?,?)')
   ->execute([$me,$title,$desc,$subject,$type,$url,$dept,$sem?:null]);
$db->prepare("INSERT INTO activity_feed (user_id,type,ref_id,ref_title) VALUES (?,?,?,?)")
   ->execute([$me,'resource_shared',$db->lastInsertId(),$title]);
setFlash('success','Resource shared — thanks for contributing!');
header('Location: '.SITE_URL.'/pages/resources.php'); exit;
