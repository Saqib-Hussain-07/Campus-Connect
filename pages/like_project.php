<?php
// pages/like_project.php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD']!=='POST'||!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')) {
    header('Location: '.SITE_URL.'/pages/projects.php'); exit;
}
$projId = (int)($_POST['project_id']??0);
$me     = (int)$_SESSION['user_id'];
$db     = getDB();
$existing = $db->prepare('SELECT id FROM project_likes WHERE project_id=? AND user_id=?');
$existing->execute([$projId,$me]);
if ($existing->fetch()) {
    $db->prepare('DELETE FROM project_likes WHERE project_id=? AND user_id=?')->execute([$projId,$me]);
    $db->prepare('UPDATE projects SET likes=GREATEST(0,likes-1) WHERE id=?')->execute([$projId]);
} else {
    $db->prepare('INSERT IGNORE INTO project_likes (project_id,user_id) VALUES (?,?)')->execute([$projId,$me]);
    $db->prepare('UPDATE projects SET likes=likes+1 WHERE id=?')->execute([$projId]);
}
$redirect = urldecode($_POST['redirect'] ?? SITE_URL.'/pages/projects.php');
header('Location: '.$redirect); exit;
