<?php
// includes/header.php
$flash = getFlash();
$user  = currentUser();

$notifCount = 0;
$msgBadge   = 0;
if ($user) {
    try {
        $stNotif = getDB()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
        $stNotif->execute([$user['id']]);
        $notifCount = (int)$stNotif->fetchColumn();
    } catch (Exception $e) {}
    try {
        $stMsg = getDB()->prepare('SELECT COUNT(*) FROM messages WHERE to_user=? AND is_read=0');
        $stMsg->execute([$user['id']]);
        $msgBadge = (int)$stMsg->fetchColumn();
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? SITE_NAME) ?> — CampusConnect</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Bricolage+Grotesque:opsz,wght@12..96,300;400;500;600;700;800&family=DM+Serif+Display:ital@0;1&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="cc-ticker">
  <div class="cc-ticker-inner">
    <?php
    $ticks = ['Projects Showcase — See what students are building',
              'Hackathons and Events — RSVP now',
              'Notice Board — Internships and Opportunities',
              'Study Resources — Shared by students for students',
              'Leaderboard — Top contributors this month',
              'Verified University Accounts Only',
              'End-to-End Encrypted Messages',
              '25,000+ Students · 1,200+ Groups · 50+ Universities'];
    $all = array_merge($ticks,$ticks);
    foreach($all as $t) echo '<span>'.e($t).' <span class="cc-ticker-sep">◆</span></span>';
    ?>
  </div>
</div>

<nav class="navbar navbar-expand-xl cc-navbar sticky-top">
  <div class="container-fluid px-3 px-lg-4">
    <a class="navbar-brand cc-brand d-flex align-items-center gap-2" href="<?= SITE_URL ?>">
      <div class="cc-brand-mark"><i class="fas fa-graduation-cap"></i></div>
      Campus<span class="cc-brand-accent">Connect</span>
    </a>
    <button class="navbar-toggler cc-toggler border-0 ms-2" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-label="Menu">
      <i class="fas fa-bars"></i>
    </button>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav mx-auto align-items-center gap-0">
        <li class="nav-item"><a class="nav-link cc-nav-link" href="<?= SITE_URL ?>/pages/students.php"><i class="fas fa-users me-1 cc-nav-icon"></i>Students</a></li>
        <li class="nav-item"><a class="nav-link cc-nav-link" href="<?= SITE_URL ?>/pages/projects.php"><i class="fas fa-code me-1 cc-nav-icon"></i>Projects</a></li>
        <li class="nav-item"><a class="nav-link cc-nav-link" href="<?= SITE_URL ?>/pages/groups.php"><i class="fas fa-layer-group me-1 cc-nav-icon"></i>Groups</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link cc-nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-compass me-1 cc-nav-icon"></i>Explore
          </a>
          <ul class="dropdown-menu cc-dropdown-menu shadow-lg p-2">
            <li><a class="dropdown-item cc-dd-item" href="<?= SITE_URL ?>/pages/events.php">
              <div class="cc-dd-icon" style="background:var(--rust);"><i class="fas fa-calendar-days"></i></div>
              <div><div class="cc-dd-title">Events & Hackathons</div><div class="cc-dd-sub">RSVP to campus events</div></div>
            </a></li>
            <li><a class="dropdown-item cc-dd-item" href="<?= SITE_URL ?>/pages/notices.php">
              <div class="cc-dd-icon" style="background:var(--moss);"><i class="fas fa-bullhorn"></i></div>
              <div><div class="cc-dd-title">Notice Board</div><div class="cc-dd-sub">Internships & opportunities</div></div>
            </a></li>
            <li><a class="dropdown-item cc-dd-item" href="<?= SITE_URL ?>/pages/resources.php">
              <div class="cc-dd-icon" style="background:var(--sky);"><i class="fas fa-book-open"></i></div>
              <div><div class="cc-dd-title">Study Resources</div><div class="cc-dd-sub">Notes, videos & tools</div></div>
            </a></li>
            <li><hr class="dropdown-divider" style="border-color:var(--cream);margin:4px 8px;"></li>
            <li><a class="dropdown-item cc-dd-item" href="<?= SITE_URL ?>/pages/leaderboard.php">
              <div class="cc-dd-icon" style="background:var(--gold);"><i class="fas fa-trophy"></i></div>
              <div><div class="cc-dd-title">Leaderboard</div><div class="cc-dd-sub">Top students this month</div></div>
            </a></li>
            <li><a class="dropdown-item cc-dd-item" href="<?= SITE_URL ?>/pages/contact.php">
              <div class="cc-dd-icon" style="background:#64748b;"><i class="fas fa-envelope"></i></div>
              <div><div class="cc-dd-title">Contact Us</div><div class="cc-dd-sub">Get in touch</div></div>
            </a></li>
          </ul>
        </li>
      </ul>
      <!-- Search bar -->
      <form method="GET" action="<?= SITE_URL ?>/pages/search.php" class="d-flex gap-0 mx-2" style="min-width:180px;">
        <input type="text" name="q" placeholder="Search…"
               style="padding:7px 12px;border:1.5px solid var(--cream);background:var(--paper);font-family:var(--font-body);font-size:.8rem;color:var(--ink);outline:none;flex:1;min-width:0;"
               onfocus="this.style.borderColor='var(--ink)'" onblur="this.style.borderColor='var(--cream)'">
        <button type="submit"
                style="padding:7px 12px;background:var(--ink);border:1.5px solid var(--ink);color:var(--paper);cursor:pointer;flex-shrink:0;">
          <i class="fas fa-search" style="font-size:12px;"></i>
        </button>
      </form>
      <div class="d-flex align-items-center gap-2">
        <?php if ($user): ?>
          <a href="<?= SITE_URL ?>/pages/messages.php" class="cc-icon-btn position-relative" title="Messages">
            <i class="fas fa-comment-dots"></i>
            <?php if ($msgBadge > 0): ?><span class="cc-badge cc-msg-badge"><?= $msgBadge > 9 ? '9+' : $msgBadge ?></span><?php else: ?><span class="cc-badge cc-msg-badge" style="display:none;">0</span><?php endif; ?>
          </a>
          <div class="dropdown">
            <button class="cc-icon-btn position-relative" data-bs-toggle="dropdown" title="Notifications">
              <i class="fas fa-bell"></i>
              <?php if ($notifCount > 0): ?><span class="cc-badge cc-notif-badge"><?= $notifCount > 9 ? '9+' : $notifCount ?></span><?php else: ?><span class="cc-badge cc-notif-badge" style="display:none;">0</span><?php endif; ?>
            </button>
            <div class="dropdown-menu cc-notif-menu dropdown-menu-end shadow-lg p-0" style="min-width:300px;max-width:340px;">
              <div class="cc-notif-header">
                <span>Notifications</span>
                <?php if ($notifCount > 0): ?><form method="POST" action="<?= SITE_URL ?>/pages/mark_read.php" style="display:inline;"><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><button type="submit" style="background:none;border:none;padding:0;font-size:.62rem;color:var(--rust);font-family:var(--font-mono);cursor:pointer;">Mark all read</button></form><?php endif; ?>
              </div>
              <?php
              try {
                  $stN = getDB()->prepare('SELECT n.*, u.name AS actor_name FROM notifications n LEFT JOIN users u ON u.id=n.actor_id WHERE n.user_id=? ORDER BY n.created_at DESC LIMIT 6');
                  $stN->execute([$user['id']]);
                  $notifs = $stN->fetchAll();
              } catch(Exception $e) { $notifs = []; }
              $notifIcons = ['connection_request'=>['fa-user-plus','var(--rust)'],'connection_accepted'=>['fa-user-check','var(--moss)'],
                             'project_like'=>['fa-heart','var(--rust)'],'project_comment'=>['fa-comment','var(--sky)'],
                             'project_join_request'=>['fa-users','var(--gold)'],'endorsement'=>['fa-award','var(--gold)'],
                             'message_new'=>['fa-comment-dots','var(--sky)'],'notice_new'=>['fa-bullhorn','var(--moss)']];
              if ($notifs): foreach ($notifs as $n): [$nIcon,$nClr]=$notifIcons[$n['type']]??['fa-bell','#888']; ?>
              <a href="<?= SITE_URL ?>/pages/notifications.php" class="cc-notif-item <?= $n['is_read']?'':'cc-notif-unread' ?>">
                <div class="cc-notif-icon" style="background:<?= $nClr ?>;"><i class="fas <?= $nIcon ?>"></i></div>
                <div class="flex-grow-1"><div class="cc-notif-msg"><?= e($n['message']??'') ?></div><div class="cc-notif-time"><?= date('M j, g:i A',strtotime($n['created_at'])) ?></div></div>
                <?php if (!$n['is_read']): ?><div class="cc-notif-dot"></div><?php endif; ?>
              </a>
              <?php endforeach; else: ?>
              <div class="text-center py-4" style="color:#aaa;font-size:.82rem;"><i class="fas fa-bell-slash d-block mb-2" style="font-size:1.4rem;"></i>No notifications yet</div>
              <?php endif; ?>
              <div class="cc-notif-footer"><a href="<?= SITE_URL ?>/pages/notifications.php">View All Notifications</a></div>
            </div>
          </div>
          <div class="dropdown">
            <button class="cc-user-btn d-flex align-items-center gap-2" data-bs-toggle="dropdown">
              <img src="<?= e(avatarUrl($user)) ?>" style="width:28px;height:28px;object-fit:cover;border:1.5px solid var(--ink);" alt="">
              <span class="d-none d-xxl-inline"><?= e(explode(' ',$user['name'])[0]) ?></span>
              <i class="fas fa-chevron-down" style="font-size:.55rem;color:#888;"></i>
            </button>
            <ul class="dropdown-menu cc-dropdown-menu dropdown-menu-end shadow-lg p-2" style="min-width:210px;">
              <li class="px-3 py-2" style="border-bottom:1px solid var(--cream);">
                <div style="font-weight:700;font-size:.84rem;color:var(--ink);"><?= e($user['name']) ?></div>
                <div style="font-size:.65rem;color:#aaa;font-family:var(--font-mono);"><?= e($user['email']) ?></div>
              </li>
              <li><a class="dropdown-item cc-dd-simple" href="<?= SITE_URL ?>/pages/dashboard.php"><i class="fas fa-house me-2" style="color:var(--rust);width:16px;"></i>Dashboard</a></li>
              <li><a class="dropdown-item cc-dd-simple" href="<?= SITE_URL ?>/pages/profile.php"><i class="fas fa-user-pen me-2" style="color:var(--rust);width:16px;"></i>Edit Profile</a></li>
              <li><a class="dropdown-item cc-dd-simple" href="<?= SITE_URL ?>/pages/add_project.php"><i class="fas fa-plus-circle me-2" style="color:var(--rust);width:16px;"></i>Post Project</a></li>
              <li><a class="dropdown-item cc-dd-simple" href="<?= SITE_URL ?>/pages/messages.php"><i class="fas fa-comment-dots me-2" style="color:var(--rust);width:16px;"></i>Messages<?php if($msgBadge>0): ?> <span style="background:var(--rust);color:#fff;font-size:.58rem;padding:1px 5px;border-radius:8px;"><?= $msgBadge ?></span><?php endif; ?></a></li>
              <li><hr class="dropdown-divider" style="border-color:var(--cream);"></li>
              <li><a class="dropdown-item cc-dd-simple" href="<?= SITE_URL ?>/pages/admin.php"><i class="fas fa-gauge-high me-2" style="color:var(--sky);width:16px;"></i>Admin Panel</a></li>
              <li><hr class="dropdown-divider" style="border-color:var(--cream);"></li>
              <li><a class="dropdown-item cc-dd-simple" href="<?= SITE_URL ?>/pages/logout.php" style="color:#dc3545;"><i class="fas fa-sign-out-alt me-2" style="width:16px;"></i>Logout</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a href="<?= SITE_URL ?>/pages/login.php"    class="btn cc-btn-outline">Login</a>
          <a href="<?= SITE_URL ?>/pages/register.php" class="btn cc-btn-fill">Sign Up Free</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<div class="offcanvas offcanvas-end cc-offcanvas" tabindex="-1" id="mobileNav">
  <div class="offcanvas-header" style="border-bottom:1px solid var(--cream);">
    <div class="cc-brand d-flex align-items-center gap-2"><div class="cc-brand-mark"><i class="fas fa-graduation-cap"></i></div>Campus<span class="cc-brand-accent">Connect</span></div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body d-flex flex-column pt-3 pb-4 px-4">
    <?php if ($user): ?>
    <div class="d-flex align-items-center gap-3 mb-4 p-3" style="background:var(--cream);border:1px solid var(--ink);">
      <img src="<?= e(avatarUrl($user)) ?>" style="width:40px;height:40px;object-fit:cover;border:1.5px solid var(--ink);" alt="">
      <div><div style="font-weight:700;font-size:.88rem;"><?= e($user['name']) ?></div><div style="font-size:.66rem;color:#888;font-family:var(--font-mono);"><?= e($user['department']??'') ?></div></div>
    </div>
    <?php endif; ?>
    <!-- Mobile search -->
    <form method="GET" action="<?= SITE_URL ?>/pages/search.php" class="d-flex gap-0 mb-3">
      <input type="text" name="q" placeholder="Search everything…"
             style="padding:9px 12px;border:1.5px solid var(--ink);background:var(--paper);font-family:var(--font-body);font-size:.82rem;color:var(--ink);outline:none;flex:1;min-width:0;">
      <button type="submit"
              style="padding:9px 14px;background:var(--ink);border:1.5px solid var(--ink);color:var(--paper);cursor:pointer;">
        <i class="fas fa-search"></i>
      </button>
    </form>
    <div style="font-family:var(--font-mono);font-size:.58rem;color:#aaa;letter-spacing:.1em;text-transform:uppercase;margin-bottom:8px;">Discover</div>
    <a class="cc-mob-link" href="<?= SITE_URL ?>/pages/students.php"><i class="fas fa-users me-3" style="color:var(--rust);width:18px;"></i>Students</a>
    <a class="cc-mob-link" href="<?= SITE_URL ?>/pages/projects.php"><i class="fas fa-code me-3" style="color:var(--rust);width:18px;"></i>Projects</a>
    <a class="cc-mob-link" href="<?= SITE_URL ?>/pages/groups.php"><i class="fas fa-layer-group me-3" style="color:var(--rust);width:18px;"></i>Groups</a>
    <a class="cc-mob-link" href="<?= SITE_URL ?>/pages/events.php"><i class="fas fa-calendar-days me-3" style="color:var(--rust);width:18px;"></i>Events</a>
    <a class="cc-mob-link" href="<?= SITE_URL ?>/pages/notices.php"><i class="fas fa-bullhorn me-3" style="color:var(--rust);width:18px;"></i>Notice Board</a>
    <a class="cc-mob-link" href="<?= SITE_URL ?>/pages/resources.php"><i class="fas fa-book-open me-3" style="color:var(--rust);width:18px;"></i>Study Resources</a>
    <a class="cc-mob-link" href="<?= SITE_URL ?>/pages/leaderboard.php"><i class="fas fa-trophy me-3" style="color:var(--rust);width:18px;"></i>Leaderboard</a>
    <hr style="border-color:var(--cream);">
    <?php if ($user): ?>
      <div style="font-family:var(--font-mono);font-size:.58rem;color:#aaa;letter-spacing:.1em;text-transform:uppercase;margin-bottom:8px;">Account</div>
      <a class="cc-mob-link" href="<?= SITE_URL ?>/pages/dashboard.php"><i class="fas fa-house me-3" style="color:var(--rust);width:18px;"></i>Dashboard</a>
      <a class="cc-mob-link" href="<?= SITE_URL ?>/pages/messages.php"><i class="fas fa-comment-dots me-3" style="color:var(--rust);width:18px;"></i>Messages<?php if($msgBadge>0): ?> <span style="background:var(--rust);color:#fff;font-size:.58rem;padding:1px 5px;border-radius:8px;margin-left:4px;"><?= $msgBadge ?></span><?php endif; ?></a>
      <a class="cc-mob-link" href="<?= SITE_URL ?>/pages/notifications.php"><i class="fas fa-bell me-3" style="color:var(--rust);width:18px;"></i>Notifications<?php if($notifCount>0): ?> <span style="background:var(--rust);color:#fff;font-size:.58rem;padding:1px 5px;border-radius:8px;margin-left:4px;"><?= $notifCount ?></span><?php endif; ?></a>
      <a class="cc-mob-link" href="<?= SITE_URL ?>/pages/profile.php"><i class="fas fa-user-pen me-3" style="color:var(--rust);width:18px;"></i>Edit Profile</a>
      <div class="mt-3"><a href="<?= SITE_URL ?>/pages/logout.php" class="btn cc-btn-fill w-100">Logout</a></div>
    <?php else: ?>
      <a href="<?= SITE_URL ?>/pages/login.php"    class="btn cc-btn-outline w-100 mb-2">Login</a>
      <a href="<?= SITE_URL ?>/pages/register.php" class="btn cc-btn-fill    w-100">Sign Up Free</a>
    <?php endif; ?>
  </div>
</div>

    <?php if ($flash): ?>
<div class="px-3 pt-3">
  <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show cc-alert" role="alert">
    <i class="fas <?= $flash['type']==='success'?'fa-check-circle':'fa-triangle-exclamation' ?> me-2"></i>
    <?= $flash['msg'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
</div>
<?php endif; ?>

<?php if ($user): ?>
<script>
// ── Live badge polling (runs on every page) ───────────────────
(function () {
  const API = '<?= SITE_URL ?>/pages/api_notifications.php';
  function updateBadge(cls, count) {
    document.querySelectorAll('.' + cls).forEach(el => {
      if (count > 0) {
        el.textContent = count > 9 ? '9+' : count;
        el.style.display = '';
      } else {
        el.style.display = 'none';
      }
    });
  }
  function pollBadges() {
    fetch(API).then(r => r.json()).then(d => {
      updateBadge('cc-msg-badge',   d.unread_msgs   || 0);
      updateBadge('cc-notif-badge', d.unread_notifs || 0);
    }).catch(() => {});
  }
  // Poll every 30 seconds
  setInterval(pollBadges, 30000);
})();
</script>
<?php endif; ?>
