<?php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Dashboard';
requireLogin();
$user = currentUser();
$db   = getDB();
$me   = (int)$user['id'];
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

// Core stats
$r = $db->prepare('SELECT COUNT(*) FROM group_members WHERE user_id=?');   $r->execute([$me]); $grpCount    =(int)$r->fetchColumn();
$r = $db->prepare('SELECT COUNT(*) FROM connections WHERE (from_user=? OR to_user=?) AND status="accepted"'); $r->execute([$me,$me]); $connCount=(int)$r->fetchColumn();
$r = $db->prepare('SELECT COUNT(*) FROM connections WHERE to_user=? AND status="pending"'); $r->execute([$me]); $pendingCount=(int)$r->fetchColumn();
$r = $db->prepare('SELECT COUNT(*) FROM messages WHERE to_user=? AND is_read=0'); $r->execute([$me]); $unreadCount=(int)$r->fetchColumn();
$myProjectCount=0; $myLikesTotal=0; $myEndorseCount=0;
try {
    $r=$db->prepare('SELECT COUNT(*) FROM projects WHERE user_id=?'); $r->execute([$me]); $myProjectCount=(int)$r->fetchColumn();
    $r=$db->prepare('SELECT COALESCE(SUM(likes),0) FROM projects WHERE user_id=?'); $r->execute([$me]); $myLikesTotal=(int)$r->fetchColumn();
    $r=$db->prepare('SELECT COUNT(*) FROM endorsements WHERE endorsed_id=?'); $r->execute([$me]); $myEndorseCount=(int)$r->fetchColumn();
} catch(Exception $e){}

// Pending requests
$stR=$db->prepare('SELECT c.id,u.name,u.department,u.skills FROM connections c JOIN users u ON u.id=c.from_user WHERE c.to_user=? AND c.status="pending" ORDER BY c.created_at DESC LIMIT 5');
$stR->execute([$me]); $requests=$stR->fetchAll();

// My groups
$stG=$db->prepare('SELECT g.id,g.name,g.type,g.status FROM groups_list g JOIN group_members gm ON g.id=gm.group_id WHERE gm.user_id=? ORDER BY gm.joined_at DESC LIMIT 5');
$stG->execute([$me]); $myGroupsData=$stG->fetchAll();

// My projects
$myProjects=[];
try {
    $stP=$db->prepare('SELECT p.id,p.title,p.category,p.status,p.likes,p.views,(SELECT COUNT(*) FROM project_requests pr WHERE pr.project_id=p.id AND pr.status="pending") AS join_requests FROM projects p WHERE p.user_id=? ORDER BY p.created_at DESC LIMIT 4');
    $stP->execute([$me]); $myProjects=$stP->fetchAll();
} catch(Exception $e){}

// Activity feed
$stF=$db->prepare('SELECT af.*,u.name AS actor_name FROM activity_feed af JOIN users u ON u.id=af.user_id ORDER BY af.created_at DESC LIMIT 10');
$stF->execute(); $feed=$stF->fetchAll();

// Suggested connections
$stS=$db->prepare('SELECT u.id,u.name,u.department,u.skills,u.is_online FROM users u WHERE u.id!=? AND u.is_verified=1 AND u.id NOT IN (SELECT CASE WHEN from_user=? THEN to_user ELSE from_user END FROM connections WHERE from_user=? OR to_user=?) ORDER BY u.is_online DESC,RAND() LIMIT 4');
$stS->execute([$me,$me,$me,$me]); $suggestions=$stS->fetchAll();

// Upcoming events
$stEvents=[];
try {
    $stE=$db->prepare("SELECT e.title,e.event_date,e.category FROM events e JOIN event_rsvps r ON r.event_id=e.id WHERE r.user_id=? AND r.status='going' AND e.event_date>=NOW() ORDER BY e.event_date ASC LIMIT 3");
    $stE->execute([$me]); $stEvents=$stE->fetchAll();
} catch(Exception $e){}

// Recent notices
$recentNotices=[];
try {
    $stN=$db->prepare("SELECT title,category,created_at FROM notices WHERE (expires_at IS NULL OR expires_at>NOW()) ORDER BY is_pinned DESC,created_at DESC LIMIT 3");
    $stN->execute(); $recentNotices=$stN->fetchAll();
} catch(Exception $e){}

include __DIR__ . '/../includes/header.php';
$catColors=['web'=>'var(--sky)','mobile'=>'var(--moss)','ml'=>'var(--rust)','hardware'=>'var(--gold)','research'=>'#7c3aed','other'=>'#888'];
$catIcons=['web'=>'fa-globe','mobile'=>'fa-mobile-screen','ml'=>'fa-brain','hardware'=>'fa-microchip','research'=>'fa-flask','other'=>'fa-code'];
$feedIcons=['project_added'=>['fa-code','var(--rust)'],'event_created'=>['fa-calendar','var(--moss)'],'notice_posted'=>['fa-bullhorn','var(--gold)'],'resource_shared'=>['fa-book','var(--sky)'],'connected'=>['fa-user-check','var(--moss)'],'joined_group'=>['fa-layer-group','var(--rust)'],'endorsed'=>['fa-award','var(--gold)']];
$nCatClr=['opportunity'=>'var(--moss)','academic'=>'var(--sky)','internship'=>'var(--rust)','placement'=>'var(--gold)','general'=>'#888','urgent'=>'#dc3545'];
?>

<div style="margin-top:92px;background:var(--paper);min-height:100vh;">
<div class="row g-0">

<!-- Sidebar -->
<div class="col-xl-2 col-lg-3 cc-dash-sidebar d-none d-lg-flex flex-column">
  <div class="text-center mb-4 pb-4" style="border-bottom:1px solid rgba(255,255,255,.08);">
    <div class="position-relative d-inline-block mb-2">
      <img src="https://picsum.photos/seed/<?= e($user['name']) ?>/120/120" style="width:64px;height:64px;object-fit:cover;border:2px solid var(--rust);" alt="">
      <span style="position:absolute;bottom:0;right:0;width:12px;height:12px;background:#22c55e;border-radius:50%;border:2px solid var(--ink);"></span>
    </div>
    <div style="font-weight:700;font-size:.82rem;color:var(--paper);line-height:1.2;"><?= e($user['name']) ?></div>
    <div style="font-family:var(--font-mono);font-size:.58rem;color:var(--rust);margin-top:3px;">● Online</div>
  </div>
  <nav class="flex-grow-1">
    <a class="cc-dash-nav-link active" href="dashboard.php"><i class="fas fa-house"></i>Dashboard</a>
    <a class="cc-dash-nav-link" href="students.php"><i class="fas fa-users"></i>Students</a>
    <a class="cc-dash-nav-link" href="projects.php"><i class="fas fa-code"></i>Projects</a>
    <a class="cc-dash-nav-link" href="groups.php"><i class="fas fa-layer-group"></i>Groups</a>
    <a class="cc-dash-nav-link" href="events.php"><i class="fas fa-calendar-days"></i>Events</a>
    <a class="cc-dash-nav-link" href="notices.php"><i class="fas fa-bullhorn"></i>Notices</a>
    <a class="cc-dash-nav-link" href="resources.php"><i class="fas fa-book-open"></i>Resources</a>
    <a class="cc-dash-nav-link" href="leaderboard.php"><i class="fas fa-trophy"></i>Leaderboard</a>
    <div style="height:1px;background:rgba(255,255,255,.06);margin:10px 0;"></div>
    <a class="cc-dash-nav-link" href="messages.php">
      <i class="fas fa-comment-dots"></i>Messages
      <?php if($unreadCount>0):?><span style="margin-left:auto;background:var(--rust);color:#fff;font-family:var(--font-mono);font-size:.52rem;padding:1px 5px;border-radius:8px;"><?=$unreadCount?></span><?php endif;?>
    </a>
    <a class="cc-dash-nav-link" href="profile.php"><i class="fas fa-user-pen"></i>Edit Profile</a>
    <a class="cc-dash-nav-link" href="add_project.php"><i class="fas fa-plus-circle"></i>Post Project</a>
    <a class="cc-dash-nav-link" href="contact.php"><i class="fas fa-envelope"></i>Contact</a>
    <div style="height:1px;background:rgba(255,255,255,.06);margin:10px 0;"></div>
    <a class="cc-dash-nav-link" href="logout.php" style="color:rgba(220,53,69,.7);"><i class="fas fa-sign-out-alt"></i>Logout</a>
  </nav>
</div>

<!-- Main -->
<div class="col-xl-10 col-lg-9 cc-dash-content">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-5">
    <div>
      <div style="font-family:var(--font-mono);font-size:.62rem;letter-spacing:.1em;text-transform:uppercase;color:#aaa;">Dashboard</div>
      <h2 style="font-family:var(--font-display);font-size:2.8rem;color:var(--ink);line-height:.95;margin-top:4px;">Hello, <span style="color:var(--rust);"><?= e(explode(' ',$user['name'])[0]) ?></span>.</h2>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <a href="add_project.php" class="cc-btn-lg-dark" style="padding:10px 20px;font-size:.78rem;"><span>Post Project</span><i class="fas fa-plus"></i></a>
      <a href="students.php"    class="cc-btn-lg-ghost" style="padding:10px 20px;font-size:.78rem;">Find Students <i class="fas fa-arrow-right"></i></a>
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <?php foreach ([[$connCount,'fa-user-check','Connections','var(--moss)'],[$grpCount,'fa-layer-group','Groups','var(--sky)'],[$myProjectCount,'fa-code','My Projects','var(--rust)'],[$myLikesTotal,'fa-heart','Project Likes','var(--rust)'],[$myEndorseCount,'fa-award','Endorsements','var(--gold)'],[$pendingCount,'fa-user-clock','Pending Requests','#888'],[$unreadCount,'fa-envelope','Unread Messages','var(--sky)']] as $s): ?>
    <div class="col-xl-3 col-sm-6">
      <div class="cc-dash-stat-card"><div class="d-flex justify-content-between align-items-start">
        <div><div class="cc-dash-stat-num"><?=(int)$s[0]?></div><div class="cc-dash-stat-label mt-1"><?=e($s[2])?></div></div>
        <div style="width:38px;height:38px;background:<?=$s[3]?>;display:flex;align-items:center;justify-content:center;"><i class="fas <?=$s[1]?>" style="color:#fff;font-size:14px;"></i></div>
      </div></div>
    </div>
    <?php endforeach;?>
  </div>

  <div class="row g-4">
    <!-- LEFT -->
    <div class="col-xl-8">

      <?php if($requests):?>
      <div style="border:1.5px solid var(--rust);background:var(--white);padding:24px;margin-bottom:20px;">
        <div style="font-family:var(--font-mono);font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:var(--rust);margin-bottom:14px;"><i class="fas fa-user-clock me-1"></i>Pending Requests (<?=count($requests)?>)</div>
        <?php foreach($requests as $req):?>
        <div class="d-flex align-items-center gap-3 mb-3 pb-3" style="border-bottom:1px solid var(--cream);">
          <img src="https://picsum.photos/seed/<?=e($req['name'])?>/80/80" style="width:40px;height:40px;object-fit:cover;border:1.5px solid var(--ink);" alt="">
          <div class="flex-grow-1">
            <div style="font-weight:700;font-size:.86rem;"><?=e($req['name'])?></div>
            <div style="font-size:.66rem;color:#888;font-family:var(--font-mono);"><?=e($req['department']??'')?></div>
          </div>
          <div class="d-flex gap-2">
            <form method="POST" action="handle_connection.php"><input type="hidden" name="conn_id" value="<?=(int)$req['id']?>"><input type="hidden" name="action" value="accept"><input type="hidden" name="csrf" value="<?=e($_SESSION['csrf'])?>"><button type="submit" style="padding:6px 14px;background:var(--moss);border:none;color:#fff;font-size:.7rem;font-weight:600;cursor:pointer;text-transform:uppercase;">Accept</button></form>
            <form method="POST" action="handle_connection.php"><input type="hidden" name="conn_id" value="<?=(int)$req['id']?>"><input type="hidden" name="action" value="reject"><input type="hidden" name="csrf" value="<?=e($_SESSION['csrf'])?>"><button type="submit" style="padding:6px 10px;background:transparent;border:1px solid #ccc;color:#888;font-size:.7rem;cursor:pointer;">✕</button></form>
          </div>
        </div>
        <?php endforeach;?>
      </div>
      <?php endif;?>

      <?php if($myProjects):?>
      <div style="border:1.5px solid var(--ink);background:var(--white);padding:24px;margin-bottom:20px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div style="font-family:var(--font-mono);font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:#aaa;">My Projects</div>
          <a href="add_project.php" style="font-family:var(--font-mono);font-size:.62rem;color:var(--rust);">+ New Project</a>
        </div>
        <div class="row g-2">
          <?php foreach($myProjects as $proj):?>
          <div class="col-md-6">
            <div style="border:1px solid var(--cream);padding:14px;background:var(--paper);">
              <div class="d-flex align-items-start gap-2 mb-2">
                <div style="width:28px;height:28px;background:<?=$catColors[$proj['category']]??'#888'?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas <?=$catIcons[$proj['category']]??'fa-code'?>" style="color:#fff;font-size:10px;"></i></div>
                <div class="flex-grow-1 min-w-0">
                  <div style="font-weight:700;font-size:.8rem;color:var(--ink);line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?=e($proj['title'])?></div>
                  <div style="font-family:var(--font-mono);font-size:.58rem;color:#aaa;text-transform:uppercase;"><?=str_replace('_',' ',$proj['status'])?></div>
                </div>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <div style="font-family:var(--font-mono);font-size:.6rem;color:#aaa;display:flex;gap:8px;">
                  <span><i class="fas fa-heart me-1" style="color:var(--rust);"></i><?=(int)$proj['likes']?></span>
                  <span><i class="fas fa-eye me-1"></i><?=(int)$proj['views']?></span>
                  <?php if($proj['join_requests']>0):?><span style="color:var(--rust);"><i class="fas fa-users me-1"></i><?=(int)$proj['join_requests']?> req</span><?php endif;?>
                </div>
                <div class="d-flex gap-1">
                  <a href="add_project.php?edit=<?=$proj['id']?>" style="font-size:.6rem;padding:3px 8px;border:1px solid var(--cream);color:#888;">Edit</a>
                  <a href="view_project.php?id=<?=$proj['id']?>" style="font-size:.6rem;padding:3px 8px;background:var(--ink);color:var(--paper);border:1px solid var(--ink);">View</a>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach;?>
        </div>
        <div class="mt-3"><a href="projects.php" style="font-family:var(--font-mono);font-size:.62rem;color:var(--rust);">Browse all projects →</a></div>
      </div>
      <?php endif;?>

      <!-- Activity feed -->
      <div style="border:1.5px solid var(--ink);background:var(--white);padding:24px;">
        <div style="font-family:var(--font-mono);font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:#aaa;margin-bottom:14px;">Campus Activity Feed</div>
        <?php if($feed): foreach($feed as $item): [$fIcon,$fClr]=$feedIcons[$item['type']]??['fa-circle','#888'];
        $ft=['project_added'=>e($item['actor_name']).' posted: <strong>'.e($item['ref_title']).'</strong>','event_created'=>e($item['actor_name']).' created event: <strong>'.e($item['ref_title']).'</strong>','notice_posted'=>e($item['actor_name']).' posted notice: <strong>'.e($item['ref_title']).'</strong>','resource_shared'=>e($item['actor_name']).' shared: <strong>'.e($item['ref_title']).'</strong>','connected'=>e($item['actor_name']).' made a new connection','joined_group'=>e($item['actor_name']).' joined a group','endorsed'=>e($item['actor_name']).' endorsed a skill'][$item['type']]??e($item['actor_name']).' did something';?>
        <div class="cc-activity-item">
          <div class="cc-activity-icon" style="background:<?=$fClr?>;"><i class="fas <?=$fIcon?>"></i></div>
          <div><div class="cc-activity-text"><?=$ft?></div><div class="cc-activity-time"><?=date('M j, g:i A',strtotime($item['created_at']))?></div></div>
        </div>
        <?php endforeach; else:?><p style="color:#aaa;font-size:.84rem;">No recent activity yet.</p><?php endif;?>
      </div>
    </div><!-- /left -->

    <!-- RIGHT -->
    <div class="col-xl-4">
      <!-- Quick actions -->
      <div class="mb-3">
        <div style="font-family:var(--font-mono);font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:#aaa;margin-bottom:10px;">Quick Actions</div>
        <div class="d-flex flex-column gap-2">
          <?php foreach([['add_project.php','fa-plus-circle','Post a Project','Show off what you\'re building','var(--rust)'],['events.php','fa-calendar-days','Events & Hackathons','RSVP to campus events','var(--moss)'],['notices.php','fa-bullhorn','Notice Board','Internships & opportunities','var(--gold)'],['resources.php','fa-book-open','Study Resources','Notes, videos & tools','var(--sky)'],['leaderboard.php','fa-trophy','Leaderboard','Top contributors','#7c3aed'],['contact.php','fa-envelope','Contact Support','Get help','#64748b']] as $ql):?>
          <a href="<?=$ql[0]?>" class="cc-dash-quick-link">
            <div class="cc-dash-quick-link-icon" style="background:<?=$ql[4]?>;"><i class="fas <?=$ql[1]?>"></i></div>
            <div><div class="cc-dash-quick-link-title"><?=$ql[2]?></div><div class="cc-dash-quick-link-sub"><?=$ql[3]?></div></div>
            <i class="fas fa-arrow-right ms-auto" style="font-size:.6rem;color:#ccc;"></i>
          </a>
          <?php endforeach;?>
        </div>
      </div>

      <?php if($stEvents):?>
      <div style="border:1.5px solid var(--ink);background:var(--white);padding:20px;margin-bottom:16px;">
        <div style="font-family:var(--font-mono);font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:#aaa;margin-bottom:12px;">My Upcoming Events</div>
        <?php foreach($stEvents as $ev):?>
        <div class="d-flex gap-2 align-items-start mb-3 pb-3" style="border-bottom:1px solid var(--cream);">
          <div style="width:36px;height:36px;background:var(--rust);display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;">
            <div style="font-family:var(--font-display);font-size:.9rem;color:#fff;line-height:1;"><?=date('d',strtotime($ev['event_date']))?></div>
            <div style="font-family:var(--font-mono);font-size:.48rem;color:rgba(255,255,255,.7);text-transform:uppercase;"><?=date('M',strtotime($ev['event_date']))?></div>
          </div>
          <div><div style="font-weight:700;font-size:.8rem;line-height:1.2;"><?=e($ev['title'])?></div><div style="font-family:var(--font-mono);font-size:.6rem;color:#aaa;"><?=date('g:i A',strtotime($ev['event_date']))?></div></div>
        </div>
        <?php endforeach;?>
        <a href="events.php" style="font-family:var(--font-mono);font-size:.6rem;color:var(--rust);">View all events →</a>
      </div>
      <?php endif;?>

      <?php if($recentNotices):?>
      <div style="border:1.5px solid var(--ink);background:var(--white);padding:20px;margin-bottom:16px;">
        <div style="font-family:var(--font-mono);font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:#aaa;margin-bottom:12px;">Recent Notices</div>
        <?php foreach($recentNotices as $n):?>
        <div class="d-flex gap-2 align-items-start mb-3 pb-3" style="border-bottom:1px solid var(--cream);">
          <div style="width:6px;background:<?=$nCatClr[$n['category']]??'#888'?>;min-height:40px;flex-shrink:0;"></div>
          <div><div style="font-weight:600;font-size:.78rem;line-height:1.3;"><?=e($n['title'])?></div><div style="font-family:var(--font-mono);font-size:.58rem;color:#aaa;"><?=date('M j',strtotime($n['created_at']))?></div></div>
        </div>
        <?php endforeach;?>
        <a href="notices.php" style="font-family:var(--font-mono);font-size:.6rem;color:var(--rust);">View notice board →</a>
      </div>
      <?php endif;?>

      <?php if($suggestions):?>
      <div style="border:1.5px solid var(--ink);background:var(--white);padding:20px;">
        <div style="font-family:var(--font-mono);font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:#aaa;margin-bottom:12px;">People You May Know</div>
        <?php foreach($suggestions as $sug):?>
        <div class="d-flex align-items-center gap-2 mb-3 pb-3" style="border-bottom:1px solid var(--cream);">
          <div class="position-relative flex-shrink-0">
            <img src="https://picsum.photos/seed/<?=e($sug['name'])?>/60/60" style="width:36px;height:36px;object-fit:cover;border:1.5px solid var(--ink);" alt="">
            <span style="position:absolute;bottom:-1px;right:-1px;width:9px;height:9px;background:<?=$sug['is_online']?'#22c55e':'#94a3b8'?>;border-radius:50%;border:1.5px solid var(--white);"></span>
          </div>
          <div class="flex-grow-1 min-w-0">
            <div style="font-weight:700;font-size:.76rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?=e($sug['name'])?></div>
            <div style="font-size:.62rem;color:#aaa;font-family:var(--font-mono);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?=e($sug['department']??'')?></div>
          </div>
          <form method="POST" action="send_connection.php" class="flex-shrink-0">
            <input type="hidden" name="to_user" value="<?=$sug['id']?>">
            <input type="hidden" name="csrf"    value="<?=e($_SESSION['csrf'])?>">
            <button type="submit" style="padding:4px 10px;background:transparent;border:1.5px solid var(--ink);font-size:.6rem;font-weight:600;text-transform:uppercase;cursor:pointer;color:var(--ink);transition:all .2s;" onmouseover="this.style.background='var(--ink)';this.style.color='var(--paper)'" onmouseout="this.style.background='transparent';this.style.color='var(--ink)'">Connect</button>
          </form>
        </div>
        <?php endforeach;?>
        <a href="students.php" style="font-family:var(--font-mono);font-size:.6rem;color:var(--rust);">Browse all students →</a>
      </div>
      <?php endif;?>
    </div><!-- /right -->
  </div><!-- /row -->
</div><!-- /main -->
</div><!-- /g-0 -->
</div><!-- /wrapper -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
