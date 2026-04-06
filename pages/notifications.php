<?php
// pages/notifications.php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Notifications';
requireLogin();
$db = getDB();
$me = (int)$_SESSION['user_id'];

// Mark all read when viewing page
$db->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$me]);

$stNotifs = $db->prepare(
    'SELECT n.*, u.name AS actor_name FROM notifications n
     LEFT JOIN users u ON u.id=n.actor_id
     WHERE n.user_id=? ORDER BY n.created_at DESC LIMIT 50'
);
$stNotifs->execute([$me]);
$notifs = $stNotifs->fetchAll();

$notifIcons = [
    'connection_request'   => ['fa-user-plus',   'var(--rust)', 'New connection request'],
    'connection_accepted'  => ['fa-user-check',  'var(--moss)', 'Connection accepted'],
    'project_like'         => ['fa-heart',        'var(--rust)', 'Someone liked your project'],
    'project_comment'      => ['fa-comment',      'var(--sky)',  'New comment on your project'],
    'project_join_request' => ['fa-users',        'var(--gold)', 'Team join request'],
    'endorsement'          => ['fa-award',        'var(--gold)', 'New skill endorsement'],
    'message_new'          => ['fa-comment-dots', 'var(--sky)',  'New message'],
    'notice_new'           => ['fa-bullhorn',     'var(--moss)', 'New notice'],
];

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:92px;background:var(--paper);min-height:100vh;">
  <div style="background:var(--ink);padding:52px 0 44px;">
    <div class="container">
      <div class="cc-section-label white-lbl reveal">Activity</div>
      <h1 class="cc-heading on-dark reveal d1">Notifications</h1>
    </div>
  </div>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <?php if ($notifs): ?>
        <div style="border:1.5px solid var(--ink);background:var(--white);overflow:hidden;">
          <div style="padding:16px 24px;background:var(--paper);border-bottom:1px solid var(--cream);display:flex;justify-content:space-between;align-items:center;">
            <div style="font-family:var(--font-mono);font-size:.62rem;letter-spacing:.1em;text-transform:uppercase;color:#aaa;"><?= count($notifs) ?> Notification<?= count($notifs)!==1?'s':'' ?></div>
            <a href="mark_read.php" style="font-family:var(--font-mono);font-size:.62rem;color:var(--rust);">Mark all read</a>
          </div>
          <?php foreach ($notifs as $n):
            [$nIcon,$nClr,$nLabel] = $notifIcons[$n['type']] ?? ['fa-bell','#888','Notification'];
          ?>
          <div class="cc-notif-page-item <?= $n['is_read']?'':'unread' ?>">
            <div style="width:42px;height:42px;background:<?= $nClr ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fas <?= $nIcon ?>" style="color:#fff;font-size:16px;"></i>
            </div>
            <div class="flex-grow-1">
              <div style="font-family:var(--font-mono);font-size:.62rem;color:<?= $nClr ?>;text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;"><?= $nLabel ?></div>
              <div style="font-size:.88rem;color:var(--ink);font-weight:<?= $n['is_read']?'400':'600' ?>;">
                <?= e($n['message']??'') ?>
              </div>
              <div style="font-family:var(--font-mono);font-size:.62rem;color:#aaa;margin-top:4px;">
                <?= date('D, M j Y · g:i A', strtotime($n['created_at'])) ?>
              </div>
            </div>
            <?php if (!$n['is_read']): ?>
            <div style="width:8px;height:8px;background:var(--rust);border-radius:50%;flex-shrink:0;margin-top:6px;"></div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5" style="border:1.5px dashed var(--cream);">
          <i class="fas fa-bell-slash fa-3x mb-3" style="color:#ccc;"></i>
          <p style="color:#aaa;">No notifications yet. Start connecting with students to see activity here.</p>
          <a href="students.php" class="cc-btn-lg-dark mt-3" style="padding:10px 24px;font-size:.8rem;display:inline-flex;"><span>Explore Students</span><i class="fas fa-arrow-right"></i></a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
