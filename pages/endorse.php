<?php
// pages/endorse.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD']!=='POST'||!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')) {
    header('Location: '.SITE_URL.'/pages/students.php'); exit;
}
$endorsedId = (int)($_POST['endorsed_id']??0);
$skill      = trim($_POST['skill']??'');
$me         = (int)$_SESSION['user_id'];
if ($endorsedId<1||$endorsedId===$me||empty($skill)) {
    setFlash('danger','Invalid endorsement.'); $ref=$_SERVER['HTTP_REFERER']??SITE_URL.'/pages/students.php';
    header('Location: '.$ref); exit;
}
$db = getDB();
// Remove if already endorsed this skill, otherwise add
$ex = $db->prepare('SELECT id FROM endorsements WHERE endorsed_id=? AND endorser_id=? AND skill=?');
$ex->execute([$endorsedId,$me,$skill]);
if ($ex->fetch()) {
    $db->prepare('DELETE FROM endorsements WHERE endorsed_id=? AND endorser_id=? AND skill=?')->execute([$endorsedId,$me,$skill]);
    setFlash('success','Endorsement removed.');
} else {
    $db->prepare('INSERT IGNORE INTO endorsements (endorsed_id,endorser_id,skill) VALUES (?,?,?)')->execute([$endorsedId,$me,$skill]);
    // Notify
    $actor = currentUser();
    $db->prepare("INSERT INTO notifications (user_id,actor_id,type,ref_id,message) VALUES (?,?,?,?,?)")
       ->execute([$endorsedId,$me,'endorsement',$me,$actor['name'].' endorsed you for '.$skill]);
    setFlash('success','Endorsement added!');
}
$ref = $_SERVER['HTTP_REFERER']??SITE_URL.'/pages/students.php';
header('Location: '.$ref); exit;
