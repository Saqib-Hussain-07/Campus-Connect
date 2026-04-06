<?php
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { header('Location: '.SITE_URL.'/pages/students.php'); exit; }

$st = $db->prepare('SELECT * FROM users WHERE id=? AND is_verified=1');
$st->execute([$id]); $profile = $st->fetch();
if (!$profile) { header('Location: '.SITE_URL.'/pages/students.php'); exit; }
$pageTitle = $profile['name'].' — Profile';

// Groups
$stGroups = $db->prepare('SELECT g.id,g.name,g.type,g.status FROM groups_list g JOIN group_members gm ON g.id=gm.group_id WHERE gm.user_id=? LIMIT 6');
$stGroups->execute([$id]); $theirGroups = $stGroups->fetchAll();

// Projects by this student
$theirProjects = [];
try {
    $stProj = $db->prepare('SELECT p.id,p.title,p.category,p.status,p.likes,p.views FROM projects p WHERE p.user_id=? ORDER BY p.likes DESC LIMIT 4');
    $stProj->execute([$id]); $theirProjects = $stProj->fetchAll();
} catch(Exception $e){}

// Connection status
$connStatus = null;
if (isLoggedIn() && $_SESSION['user_id'] !== $id) {
    $stConn = $db->prepare('SELECT id,status FROM connections WHERE (from_user=? AND to_user=?) OR (from_user=? AND to_user=?)');
    $stConn->execute([$_SESSION['user_id'],$id,$id,$_SESSION['user_id']]);
    $row = $stConn->fetch();
    if ($row) $connStatus = $row['status'];
}

// Endorsements grouped by skill
$endorsements = [];
try {
    $stEnd = $db->prepare('SELECT skill, COUNT(*) AS cnt FROM endorsements WHERE endorsed_id=? GROUP BY skill ORDER BY cnt DESC LIMIT 10');
    $stEnd->execute([$id]); $endorsements = $stEnd->fetchAll();
} catch(Exception $e){}

// Which skills the viewer has already endorsed
$myEndorsed = [];
if (isLoggedIn() && $_SESSION['user_id'] !== $id) {
    try {
        $stMe = $db->prepare('SELECT skill FROM endorsements WHERE endorsed_id=? AND endorser_id=?');
        $stMe->execute([$id,$_SESSION['user_id']]); $myEndorsed = array_column($stMe->fetchAll(),'skill');
    } catch(Exception $e){}
}

// Mutual connections
$mutualCount = 0;
if (isLoggedIn()) {
    try {
        $stMut = $db->prepare('SELECT COUNT(*) FROM connections c1 JOIN connections c2 ON (CASE WHEN c1.from_user=? THEN c1.to_user ELSE c1.from_user END = CASE WHEN c2.from_user=? THEN c2.to_user ELSE c2.from_user END) WHERE (c1.from_user=? OR c1.to_user=?) AND c1.status="accepted" AND (c2.from_user=? OR c2.to_user=?) AND c2.status="accepted"');
        $stMut->execute([$_SESSION['user_id'],$id,$_SESSION['user_id'],$_SESSION['user_id'],$id,$id]);
        $mutualCount = (int)$stMut->fetchColumn();
    } catch(Exception $e){}
}

$skills = array_filter(array_map('trim', explode(',', $profile['skills']??'')));
$catColors=['web'=>'var(--sky)','mobile'=>'var(--moss)','ml'=>'var(--rust)','hardware'=>'var(--gold)','research'=>'#7c3aed','other'=>'#888'];
$catIcons=['web'=>'fa-globe','mobile'=>'fa-mobile-screen','ml'=>'fa-brain','hardware'=>'fa-microchip','research'=>'fa-flask','other'=>'fa-code'];
$typeIcon=['study'=>'fa-book-open','project'=>'fa-code-branch','forum'=>'fa-comments'];
$statusColors=['active'=>'var(--moss)','recruiting'=>'var(--rust)','open'=>'var(--sky)'];

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:92px;background:var(--paper);min-height:100vh;">

  <!-- Hero banner -->
  <div style="height:200px;background:var(--ink);position:relative;overflow:hidden;">
    <img src="https://picsum.photos/seed/banner<?= $id ?>/1400/400" style="width:100%;height:100%;object-fit:cover;filter:brightness(.2) saturate(.4);" alt="">
    <div style="position:absolute;bottom:-50px;right:60px;font-family:var(--font-display);font-size:14rem;color:rgba(255,255,255,.03);line-height:1;pointer-events:none;"><?= strtoupper(e(substr($profile['name'],0,1))) ?></div>
  </div>

  <div class="container" style="position:relative;">
    <div class="d-flex align-items-end gap-4 flex-wrap" style="margin-top:-52px;padding-bottom:28px;border-bottom:1.5px solid var(--ink);">
      <img src="https://picsum.photos/seed/<?= e($profile['name']) ?>/200/200"
           style="width:100px;height:100px;object-fit:cover;border:4px solid var(--paper);background:var(--ink);flex-shrink:0;" alt="<?= e($profile['name']) ?>">
      <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <h1 style="font-family:var(--font-display);font-size:2.2rem;color:var(--ink);line-height:.95;margin:0;"><?= e($profile['name']) ?></h1>
          <span style="font-family:var(--font-mono);font-size:.62rem;color:var(--moss);border:1px solid var(--moss);padding:2px 10px;text-transform:uppercase;letter-spacing:.08em;"><i class="fas fa-check-circle me-1"></i>Verified</span>
        </div>
        <div style="font-family:var(--font-mono);font-size:.72rem;color:#888;margin-top:6px;">
          <?= e($profile['department']??'') ?><?php if($profile['semester']): ?> · Sem <?= (int)$profile['semester'] ?><?php endif; ?><?php if($profile['university']): ?> · <?= e($profile['university']) ?><?php endif; ?>
        </div>
        <div class="d-flex gap-3 mt-2" style="font-family:var(--font-mono);font-size:.65rem;color:#aaa;">
          <?php if($mutualCount>0): ?><span><i class="fas fa-user-friends me-1" style="color:var(--rust);"></i><?= $mutualCount ?> mutual</span><?php endif; ?>
          <span><i class="fas fa-code me-1" style="color:var(--rust);"></i><?= count($theirProjects) ?> project<?= count($theirProjects)!==1?'s':'' ?></span>
          <span><i class="fas fa-layer-group me-1" style="color:var(--rust);"></i><?= count($theirGroups) ?> group<?= count($theirGroups)!==1?'s':'' ?></span>
          <span><i class="fas fa-circle me-1" style="color:<?= $profile['is_online']?'#22c55e':'#94a3b8' ?>;font-size:.5rem;"></i><?= $profile['is_online']?'Online':'Offline' ?></span>
        </div>
      </div>
      <!-- Actions -->
      <div class="d-flex gap-2 flex-wrap">
        <?php if (!isLoggedIn()): ?>
          <a href="login.php" class="cc-btn-lg-dark" style="padding:10px 22px;font-size:.8rem;"><span>Connect</span><i class="fas fa-paper-plane"></i></a>
        <?php elseif ((int)$_SESSION['user_id'] === $id): ?>
          <a href="profile.php" class="cc-btn-lg-dark" style="padding:10px 22px;font-size:.8rem;"><span>Edit Profile</span><i class="fas fa-user-pen"></i></a>
        <?php elseif ($connStatus === 'accepted'): ?>
          <a href="messages.php?with=<?= $id ?>" class="cc-btn-lg-dark" style="padding:10px 22px;font-size:.8rem;"><span>Message</span><i class="fas fa-comment-dots"></i></a>
          <span style="padding:10px 20px;border:2px solid var(--moss);color:var(--moss);font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;display:inline-flex;align-items:center;gap:6px;"><i class="fas fa-check"></i>Connected</span>
        <?php elseif ($connStatus === 'pending'): ?>
          <span style="padding:10px 20px;border:2px solid rgba(0,0,0,.15);color:#aaa;font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Request Pending…</span>
        <?php else: ?>
          <form method="POST" action="send_connection.php">
            <input type="hidden" name="to_user" value="<?= $id ?>">
            <input type="hidden" name="csrf"    value="<?= e($_SESSION['csrf']) ?>">
            <button type="submit" class="cc-btn-lg-dark" style="padding:10px 22px;font-size:.8rem;"><span>Connect</span><i class="fas fa-paper-plane"></i></button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Body -->
  <div class="container py-5">
    <div class="row g-4">

      <!-- LEFT -->
      <div class="col-lg-8">

        <!-- Bio -->
        <?php if ($profile['bio']): ?>
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:28px;margin-bottom:20px;" class="reveal">
          <div style="font-family:var(--font-mono);font-size:.62rem;letter-spacing:.12em;text-transform:uppercase;color:#aaa;margin-bottom:12px;">About</div>
          <p style="font-size:.95rem;line-height:1.8;color:#444;margin:0;"><?= nl2br(e($profile['bio'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- Skills + Endorsements -->
        <?php if ($skills): ?>
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:28px;margin-bottom:20px;" class="reveal d1">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div style="font-family:var(--font-mono);font-size:.62rem;letter-spacing:.12em;text-transform:uppercase;color:#aaa;">Skills & Endorsements</div>
            <?php if (isLoggedIn() && (int)$_SESSION['user_id'] !== $id): ?>
            <div style="font-family:var(--font-mono);font-size:.62rem;color:#aaa;">Click a skill to endorse</div>
            <?php endif; ?>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($skills as $sk):
              $sk = trim($sk);
              $endorsed = in_array($sk, $myEndorsed);
              $endCount = 0;
              foreach ($endorsements as $e2) { if ($e2['skill'] === $sk) { $endCount = (int)$e2['cnt']; break; } }
            ?>
            <?php if (isLoggedIn() && (int)$_SESSION['user_id'] !== $id): ?>
            <form method="POST" action="endorse.php" style="margin:0;">
              <input type="hidden" name="endorsed_id" value="<?= $id ?>">
              <input type="hidden" name="skill"       value="<?= e($sk) ?>">
              <input type="hidden" name="csrf"        value="<?= e($_SESSION['csrf']) ?>">
              <button type="submit" class="cc-endorse-skill <?= $endorsed?'endorsed':'' ?>"
                      title="<?= $endorsed?'Remove endorsement':'Endorse '.$sk ?>">
                <?= e($sk) ?>
                <?php if ($endCount > 0): ?>
                <span class="cc-endorse-count" style="background:<?= $endorsed?'var(--rust)':'#aaa' ?>;color:#fff;font-size:.55rem;padding:1px 5px;border-radius:8px;margin-left:2px;"><?= $endCount ?></span>
                <?php endif; ?>
              </button>
            </form>
            <?php else: ?>
            <span class="cc-pill <?= $endCount>0?'accent':'' ?>">
              <?= e($sk) ?><?php if($endCount>0): ?> <span style="opacity:.7;font-size:.65em;"><?= $endCount ?>✓</span><?php endif; ?>
            </span>
            <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Projects -->
        <?php if ($theirProjects): ?>
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:28px;margin-bottom:20px;" class="reveal d1">
          <div style="font-family:var(--font-mono);font-size:.62rem;letter-spacing:.12em;text-transform:uppercase;color:#aaa;margin-bottom:16px;">Projects (<?= count($theirProjects) ?>)</div>
          <div class="row g-2">
            <?php foreach ($theirProjects as $proj): ?>
            <div class="col-md-6">
              <a href="view_project.php?id=<?= $proj['id'] ?>" style="display:block;border:1px solid var(--cream);padding:14px;background:var(--paper);text-decoration:none;transition:all .2s;" onmouseover="this.style.borderColor='var(--ink)';this.style.boxShadow='3px 3px 0 var(--ink)'" onmouseout="this.style.borderColor='var(--cream)';this.style.boxShadow=''">
                <div class="d-flex align-items-start gap-2 mb-2">
                  <div style="width:28px;height:28px;background:<?= $catColors[$proj['category']]??'#888' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas <?= $catIcons[$proj['category']]??'fa-code' ?>" style="color:#fff;font-size:10px;"></i>
                  </div>
                  <div>
                    <div style="font-weight:700;font-size:.82rem;color:var(--ink);line-height:1.2;"><?= e($proj['title']) ?></div>
                    <div style="font-family:var(--font-mono);font-size:.58rem;color:#aaa;text-transform:uppercase;"><?= str_replace('_',' ',$proj['status']) ?></div>
                  </div>
                </div>
                <div style="font-family:var(--font-mono);font-size:.62rem;color:#aaa;display:flex;gap:10px;">
                  <span><i class="fas fa-heart me-1" style="color:var(--rust);"></i><?= (int)$proj['likes'] ?></span>
                  <span><i class="fas fa-eye me-1"></i><?= (int)$proj['views'] ?></span>
                </div>
              </a>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Groups -->
        <?php if ($theirGroups): ?>
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:28px;" class="reveal d2">
          <div style="font-family:var(--font-mono);font-size:.62rem;letter-spacing:.12em;text-transform:uppercase;color:#aaa;margin-bottom:16px;">Groups (<?= count($theirGroups) ?>)</div>
          <div class="d-flex flex-column gap-2">
            <?php foreach ($theirGroups as $g): ?>
            <div class="d-flex align-items-center gap-3" style="padding:12px;border:1px solid var(--cream);background:var(--paper);">
              <div style="width:32px;height:32px;background:var(--rust);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas <?= $typeIcon[$g['type']]??'fa-users' ?>" style="color:#fff;font-size:11px;"></i>
              </div>
              <div class="flex-grow-1">
                <div style="font-weight:700;font-size:.84rem;color:var(--ink);"><?= e($g['name']) ?></div>
                <div style="font-size:.66rem;font-family:var(--font-mono);color:#aaa;text-transform:uppercase;letter-spacing:.04em;"><?= e($g['type']) ?></div>
              </div>
              <span style="font-family:var(--font-mono);font-size:.6rem;border:1px solid <?= $statusColors[$g['status']]??'#aaa' ?>;color:<?= $statusColors[$g['status']]??'#aaa' ?>;padding:2px 8px;text-transform:uppercase;">
                <?= ucfirst(e($g['status'])) ?>
              </span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

      </div><!-- /left -->

      <!-- RIGHT sidebar -->
      <div class="col-lg-4">
        <!-- Profile info -->
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:24px;margin-bottom:16px;" class="reveal">
          <div style="font-family:var(--font-mono);font-size:.62rem;letter-spacing:.12em;text-transform:uppercase;color:#aaa;margin-bottom:14px;">Profile Info</div>
          <?php
          $info = [['fa-graduation-cap','Department',$profile['department']??'Not set'],['fa-calendar-alt','Semester',$profile['semester']?'Semester '.(int)$profile['semester']:'Not set'],['fa-university','University',$profile['university']??'Not set'],['fa-clock','Member Since',date('M Y',strtotime($profile['created_at']))]];
          foreach ($info as $inf): ?>
          <div class="d-flex gap-3 align-items-center mb-3">
            <i class="fas <?= $inf[0] ?>" style="color:var(--rust);width:14px;font-size:12px;flex-shrink:0;"></i>
            <div>
              <div style="font-size:.6rem;font-family:var(--font-mono);color:#aaa;text-transform:uppercase;letter-spacing:.05em;"><?= $inf[1] ?></div>
              <div style="font-weight:600;font-size:.84rem;color:var(--ink);"><?= e($inf[2]) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Endorsement summary -->
        <?php if ($endorsements): ?>
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:24px;margin-bottom:16px;" class="reveal d1">
          <div style="font-family:var(--font-mono);font-size:.62rem;letter-spacing:.12em;text-transform:uppercase;color:#aaa;margin-bottom:14px;">Top Endorsements</div>
          <?php foreach ($endorsements as $e2): ?>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span style="font-size:.8rem;font-weight:600;"><?= e($e2['skill']) ?></span>
            <span style="font-family:var(--font-display);font-size:1.1rem;color:var(--rust);"><?= (int)$e2['cnt'] ?></span>
          </div>
          <div style="height:3px;background:var(--cream);margin-bottom:10px;position:relative;">
            <div style="position:absolute;left:0;top:0;height:100%;width:<?= min(100, (int)$e2['cnt'] * 20) ?>%;background:var(--rust);transition:width .8s var(--ease-out);"></div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Quick actions -->
        <?php if (isLoggedIn() && (int)$_SESSION['user_id'] !== $id): ?>
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:24px;" class="reveal d2">
          <div style="font-family:var(--font-mono);font-size:.62rem;letter-spacing:.12em;text-transform:uppercase;color:#aaa;margin-bottom:14px;">Quick Actions</div>
          <div class="d-flex flex-column gap-2">
            <a href="messages.php?with=<?= $id ?>" class="cc-student-btn"><i class="fas fa-comment-dots"></i> Send Message</a>
            <?php if ($connStatus !== 'accepted'): ?>
            <form method="POST" action="send_connection.php">
              <input type="hidden" name="to_user" value="<?= $id ?>">
              <input type="hidden" name="csrf"    value="<?= e($_SESSION['csrf']) ?>">
              <button type="submit" class="cc-student-btn w-100" <?= $connStatus==='pending'?'disabled style="opacity:.5;cursor:not-allowed;"':'' ?>>
                <i class="fas fa-user-plus"></i> <?= $connStatus==='pending'?'Request Sent':'Connect' ?>
              </button>
            </form>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
