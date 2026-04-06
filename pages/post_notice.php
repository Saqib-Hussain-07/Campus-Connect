<?php
// pages/post_notice.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD']!=='POST'||!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')) {
    header('Location: '.SITE_URL.'/pages/notices.php'); exit;
}
$title   = trim($_POST['title']??'');
$body    = trim($_POST['body']??'');
$cat     = $_POST['category']??'general';
$tags    = trim($_POST['tags']??'');
$expires = $_POST['expires_at']??null;
$me      = (int)$_SESSION['user_id'];
if (strlen($title)<3||empty($body)) { setFlash('danger','Title and message are required.'); header('Location: '.SITE_URL.'/pages/notices.php'); exit; }
if (!in_array($cat,['opportunity','academic','internship','placement','general','urgent'])) $cat='general';
$db = getDB();
$db->prepare('INSERT INTO notices (user_id,title,body,category,tags,expires_at) VALUES (?,?,?,?,?,?)')
   ->execute([$me,$title,$body,$cat,$tags,($expires?:null)]);
$db->prepare("INSERT INTO activity_feed (user_id,type,ref_id,ref_title) VALUES (?,?,?,?)")
   ->execute([$me,'notice_posted',$db->lastInsertId(),$title]);
setFlash('success','Notice posted!');
header('Location: '.SITE_URL.'/pages/notices.php'); exit;
