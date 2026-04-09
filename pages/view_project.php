<?php
// pages/view_project.php
require_once __DIR__ . '/../includes/config.php';
$db = getDB();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { header('Location: projects.php'); exit; }

$stProj = $db->prepare(
    'SELECT p.*, u.name AS author_name, u.department AS author_dept, u.id AS author_id,
            (SELECT COUNT(*) FROM project_likes pl WHERE pl.project_id=p.id) AS like_count
     FROM projects p JOIN users u ON u.id=p.user_id WHERE p.id=?'
);
$stProj->execute([$id]);
$proj = $stProj->fetch();
if (!$proj) { header('Location: projects.php'); exit; }

$pageTitle = $proj['title'];

// Increment views
$db->prepare('UPDATE projects SET views=views+1 WHERE id=?')->execute([$id]);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    if (!hash_equals($_SESSION['csrf']??'', $_POST['csrf']??'')) {
        setFlash('danger','Invalid request.'); header('Location: view_project.php?id='.$id); exit;
    }
    $action = $_POST['action'] ?? '';
    $me = (int)$_SESSION['user_id'];

    if ($action === 'comment') {
        $body = trim($_POST['body'] ?? '');
        if ($body) {
            $db->prepare('INSERT INTO project_comments (project_id,user_id,body) VALUES (?,?,?)')->execute([$id,$me,$body]);
            $db->prepare("INSERT INTO notifications (user_id,actor_id,type,ref_id,message) VALUES (?,?,?,?,?)")
               ->execute([$proj['author_id'],$me,'project_comment',$id,currentUser()['name'].' commented on your project.']);
        }
    } elseif ($action === 'join_request') {
        $msg = trim($_POST['message'] ?? '');
        $db->prepare('INSERT IGNORE INTO project_requests (project_id,user_id,message) VALUES (?,?,?)')->execute([$id,$me,$msg]);
        setFlash('success','Your request to join the team has been sent!');
    }
    header('Location: view_project.php?id='.$id); exit;
}

// Fetch comments
$stComments = $db->prepare(
    'SELECT pc.*, u.name, u.department FROM project_comments pc
     JOIN users u ON u.id=pc.user_id WHERE pc.project_id=? ORDER BY pc.created_at ASC'
);
$stComments->execute([$id]);
$comments = $stComments->fetchAll();

// Like status
$liked = false;
$joinRequested = false;
if (isLoggedIn()) {
    $me = (int)$_SESSION['user_id'];
    $stl = $db->prepare('SELECT id FROM project_likes WHERE project_id=? AND user_id=?');
    $stl->execute([$id,$me]); $liked = (bool)$stl->fetch();
    $stj = $db->prepare('SELECT id FROM project_requests WHERE project_id=? AND user_id=?');
    $stj->execute([$id,$me]); $joinRequested = (bool)$stj->fetch();
}

$tech = array_filter(array_map('trim', explode(',', $proj['tech_stack']??'')));
$catColors = ['web'=>'var(--sky)','mobile'=>'var(--moss)','ml'=>'var(--rust)','hardware'=>'var(--gold)','research'=>'#7c3aed','other'=>'#888'];
$catIcons  = ['web'=>'fa-globe','mobile'=>'fa-mobile-screen','ml'=>'fa-brain','hardware'=>'fa-microchip','research'=>'fa-flask','other'=>'fa-code'];

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:92px;background:var(--paper);min-height:100vh;">

  <!-- Hero banner -->
  <div style="height:280px;background:var(--ink);position:relative;overflow:hidden;">
    <img src="https://picsum.photos/seed/proj<?= $proj['id'] ?>/1200/400"
         style="width:100%;height:100%;object-fit:cover;filter:brightness(.25) saturate(.4);" alt="">
    <div style="position:absolute;inset:0;display:flex;align-items:flex-end;">
      <div class="container pb-5">
        <div style="display:inline-flex;align-items:center;gap:6px;padding:3px 12px;
                    background:<?= $catColors[$proj['category']]??'#888' ?>;
                    font-family:var(--font-mono);font-size:.65rem;color:#fff;text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px;">
          <i class="fas <?= $catIcons[$proj['category']]??'fa-code' ?>"></i>
          <?= e(ucfirst($proj['category'])) ?>
        </div>
        <h1 style="font-family:var(--font-display);font-size:clamp(2rem,5vw,3.5rem);color:#fff;line-height:.95;margin:0;">
          <?= e($proj['title']) ?>
        </h1>
      </div>
    </div>
  </div>

  <div class="container py-5">
    <div class="row g-4">

      <!-- Left: main content -->
      <div class="col-lg-8">

        <!-- Description -->
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:32px;margin-bottom:20px;" class="reveal">
          <div style="font-family:var(--font-mono);font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:#aaa;margin-bottom:14px;">About This Project</div>
          <p style="font-size:.96rem;line-height:1.8;color:#444;margin:0;"><?= nl2br(e($proj['description']??'')) ?></p>
        </div>

        <!-- Tech stack -->
        <?php if ($tech): ?>
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:28px;margin-bottom:20px;" class="reveal d1">
          <div style="font-family:var(--font-mono);font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:#aaa;margin-bottom:14px;">Tech Stack</div>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($tech as $t): ?>
            <span style="padding:6px 14px;font-family:var(--font-mono);font-size:.72rem;
                         border:1.5px solid var(--ink);text-transform:uppercase;letter-spacing:.04em;
                         background:var(--paper);color:var(--ink);">
              <?= e($t) ?>
            </span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Join team CTA (if looking for team) -->
        <?php if ($proj['status'] === 'looking_for_team' && isLoggedIn() && (int)$proj['author_id'] !== (int)$_SESSION['user_id']): ?>
        <div style="border:1.5px solid var(--rust);background:rgba(201,79,44,.04);padding:28px;margin-bottom:20px;" class="reveal d2">
          <div style="font-family:var(--font-display);font-size:1.4rem;color:var(--rust);margin-bottom:8px;">
            <i class="fas fa-users me-2"></i>This project is looking for team members!
          </div>
          <?php if ($joinRequested): ?>
          <p style="font-size:.88rem;color:var(--moss);font-weight:600;"><i class="fas fa-check-circle me-2"></i>You've already requested to join. The author will review your request.</p>
          <?php else: ?>
          <form method="POST">
            <input type="hidden" name="csrf"   value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="action" value="join_request">
            <div class="mb-3">
              <label class="cc-form-label">Why do you want to join? (optional)</label>
              <textarea name="message" rows="2" class="cc-form-input" style="resize:vertical;"
                        placeholder="Tell them what you can contribute..."></textarea>
            </div>
            <button type="submit" class="cc-btn-lg-dark" style="padding:12px 28px;font-size:.84rem;">
              <span>Request to Join Team</span><i class="fas fa-paper-plane"></i>
            </button>
          </form>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Comments -->
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:32px;" class="reveal d2">
          <div style="font-family:var(--font-mono);font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:#aaa;margin-bottom:20px;">
            Comments (<?= count($comments) ?>)
          </div>

          <?php if ($comments): ?>
          <div class="d-flex flex-column gap-4 mb-4">
            <?php foreach ($comments as $c): ?>
            <div class="d-flex gap-3">
              <img src="https://picsum.photos/seed/<?= e($c['name']) ?>/60/60"
                   style="width:38px;height:38px;object-fit:cover;border:1.5px solid var(--ink);flex-shrink:0;" alt="">
              <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <span style="font-weight:700;font-size:.86rem;color:var(--ink);"><?= e($c['name']) ?></span>
                  <span style="font-size:.65rem;color:#aaa;font-family:var(--font-mono);"><?= e($c['department']) ?></span>
                  <span style="font-size:.62rem;color:#ccc;font-family:var(--font-mono);margin-left:auto;"><?= date('M j, Y',strtotime($c['created_at'])) ?></span>
                </div>
                <div style="background:var(--paper);padding:12px 14px;border:1px solid var(--cream);font-size:.86rem;line-height:1.65;color:#444;">
                  <?= nl2br(e($c['body'])) ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <p style="color:#aaa;font-size:.84rem;margin-bottom:20px;">No comments yet. Be the first to leave feedback!</p>
          <?php endif; ?>

          <?php if (isLoggedIn()): ?>
          <form method="POST">
            <input type="hidden" name="csrf"   value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="action" value="comment">
            <div class="d-flex gap-2">
              <img src="https://picsum.photos/seed/<?= e(currentUser()['name']) ?>/60/60"
                   style="width:36px;height:36px;object-fit:cover;border:1.5px solid var(--ink);flex-shrink:0;" alt="">
              <textarea name="body" rows="2" required class="cc-form-input flex-grow-1" style="resize:none;"
                        placeholder="Leave feedback, ask a question, or offer to collaborate..."></textarea>
              <button type="submit" style="padding:8px 18px;background:var(--ink);border:1.5px solid var(--ink);color:var(--paper);font-size:13px;cursor:pointer;flex-shrink:0;transition:all .2s;"
                      onmouseover="this.style.background='var(--rust)';this.style.borderColor='var(--rust)'"
                      onmouseout="this.style.background='var(--ink)';this.style.borderColor='var(--ink)'">
                <i class="fas fa-paper-plane"></i>
              </button>
            </div>
          </form>
          <?php else: ?>
          <a href="login.php" style="font-size:.84rem;color:var(--rust);">Login to leave a comment</a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Right: sidebar -->
      <div class="col-lg-4">

        <!-- Like / stats -->
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:28px;margin-bottom:20px;text-align:center;" class="reveal">
          <?php if (isLoggedIn()): ?>
          <form method="POST" action="like_project.php">
            <input type="hidden" name="project_id" value="<?= $proj['id'] ?>">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="redirect" value="<?= urlencode('view_project.php?id='.$id) ?>">
            <button type="submit"
                    style="background:<?= $liked?'var(--rust)':'transparent' ?>;
                           border:2px solid var(--rust);color:<?= $liked?'#fff':'var(--rust)' ?>;
                           padding:12px 32px;font-family:var(--font-body);font-size:.88rem;font-weight:700;
                           text-transform:uppercase;letter-spacing:.06em;cursor:pointer;
                           width:100%;margin-bottom:12px;transition:all .2s;"
                    onmouseover="this.style.background='var(--rust)';this.style.color='#fff'"
                    onmouseout="this.style.background='<?= $liked?'var(--rust)':'transparent' ?>'; this.style.color='<?= $liked?'#fff':'var(--rust)' ?>'">
              <i class="fas fa-heart me-2"></i><?= $liked?'Liked':'Like this Project' ?>
            </button>
          </form>
          <?php endif; ?>
          <div class="d-flex justify-content-around mt-2">
            <div style="text-align:center;">
              <div style="font-family:var(--font-display);font-size:1.8rem;color:var(--rust);"><?= (int)$proj['like_count'] ?></div>
              <div style="font-family:var(--font-mono);font-size:.62rem;color:#aaa;text-transform:uppercase;">Likes</div>
            </div>
            <div style="width:1px;background:var(--cream);"></div>
            <div style="text-align:center;">
              <div style="font-family:var(--font-display);font-size:1.8rem;color:var(--ink);"><?= count($comments) ?></div>
              <div style="font-family:var(--font-mono);font-size:.62rem;color:#aaa;text-transform:uppercase;">Comments</div>
            </div>
            <div style="width:1px;background:var(--cream);"></div>
            <div style="text-align:center;">
              <div style="font-family:var(--font-display);font-size:1.8rem;color:var(--ink);"><?= (int)$proj['views'] ?></div>
              <div style="font-family:var(--font-mono);font-size:.62rem;color:#aaa;text-transform:uppercase;">Views</div>
            </div>
          </div>
        </div>

        <!-- Project info -->
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:28px;margin-bottom:20px;" class="reveal d1">
          <div style="font-family:var(--font-mono);font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:#aaa;margin-bottom:16px;">Project Details</div>
          <?php
          $details = [
            ['fa-tag','Category', ucfirst($proj['category'])],
            ['fa-circle-check','Status', str_replace('_',' ',ucfirst($proj['status']))],
            ['fa-users','Team Size', $proj['team_size'].' member'.($proj['team_size']>1?'s':'')],
            ['fa-calendar','Posted', date('M j, Y',strtotime($proj['created_at']))],
          ];
          foreach ($details as $d): ?>
          <div class="d-flex gap-3 align-items-center mb-3">
            <i class="fas <?= $d[0] ?>" style="color:var(--rust);width:16px;"></i>
            <div>
              <div style="font-family:var(--font-mono);font-size:.62rem;color:#aaa;text-transform:uppercase;letter-spacing:.05em;"><?= $d[1] ?></div>
              <div style="font-weight:600;font-size:.86rem;color:var(--ink);"><?= e($d[2]) ?></div>
            </div>
          </div>
          <?php endforeach; ?>

          <!-- Links -->
          <div class="d-flex flex-column gap-2 mt-4">
            <?php if ($proj['github_url']): ?>
            <a href="<?= e($proj['github_url']) ?>" target="_blank" rel="noopener"
               style="display:flex;align-items:center;gap:8px;padding:9px 14px;border:1.5px solid var(--ink);
                      font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--ink);transition:all .2s;"
               onmouseover="this.style.background='var(--ink)';this.style.color='var(--paper)'"
               onmouseout="this.style.background='';this.style.color='var(--ink)'">
              <i class="fab fa-github"></i> View on GitHub
            </a>
            <?php endif; ?>
            <?php if ($proj['live_url']): ?>
            <a href="<?= e($proj['live_url']) ?>" target="_blank" rel="noopener"
               style="display:flex;align-items:center;gap:8px;padding:9px 14px;border:1.5px solid var(--moss);
                      font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--moss);transition:all .2s;"
               onmouseover="this.style.background='var(--moss)';this.style.color='#fff'"
               onmouseout="this.style.background='';this.style.color='var(--moss)'">
              <i class="fas fa-external-link-alt"></i> Live Demo
            </a>
            <?php endif; ?>
          </div>
        </div>

        <!-- Author card -->
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:28px;" class="reveal d2">
          <div style="font-family:var(--font-mono);font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:#aaa;margin-bottom:16px;">Built by</div>
          <div class="d-flex gap-3 align-items-center mb-3">
            <img src="https://picsum.photos/seed/<?= e($proj['author_name']) ?>/100/100"
                 style="width:52px;height:52px;object-fit:cover;border:2px solid var(--ink);" alt="">
            <div>
              <div style="font-weight:700;font-size:.92rem;color:var(--ink);"><?= e($proj['author_name']) ?></div>
              <div style="font-size:.72rem;color:#888;font-family:var(--font-mono);"><?= e($proj['author_dept']) ?></div>
            </div>
          </div>
          <a href="view_student.php?id=<?= $proj['author_id'] ?>" class="cc-student-btn">
            View Profile <i class="fas fa-arrow-right"></i>
          </a>
        </div>

      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
