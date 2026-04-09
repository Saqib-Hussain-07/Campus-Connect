<?php
// pages/search.php — Global search
require_once __DIR__ . '/../includes/config.php';
$q         = trim($_GET['q'] ?? '');
$pageTitle = $q ? 'Search: ' . $q : 'Search';
$db        = getDB();

$students  = [];
$projects  = [];
$events    = [];
$notices   = [];
$resources = [];

if ($q !== '') {
    $like = '%' . $q . '%';

    // Students
    $st = $db->prepare('SELECT id, name, department, semester, university, skills, is_online FROM users WHERE is_verified=1 AND (name LIKE ? OR skills LIKE ? OR bio LIKE ? OR department LIKE ?) LIMIT 6');
    $st->execute([$like, $like, $like, $like]);
    $students = $st->fetchAll();

    // Projects
    try {
        $st = $db->prepare('SELECT p.id, p.title, p.category, p.status, p.likes, u.name AS author FROM projects p JOIN users u ON u.id=p.user_id WHERE p.title LIKE ? OR p.description LIKE ? OR p.tech_stack LIKE ? LIMIT 6');
        $st->execute([$like, $like, $like]);
        $projects = $st->fetchAll();
    } catch (Exception $e) {}

    // Events
    try {
        $st = $db->prepare('SELECT id, title, category, event_date, venue FROM events WHERE title LIKE ? OR description LIKE ? ORDER BY event_date DESC LIMIT 4');
        $st->execute([$like, $like]);
        $events = $st->fetchAll();
    } catch (Exception $e) {}

    // Notices
    try {
        $st = $db->prepare('SELECT id, title, category, created_at FROM notices WHERE (expires_at IS NULL OR expires_at > NOW()) AND (title LIKE ? OR body LIKE ? OR tags LIKE ?) ORDER BY created_at DESC LIMIT 4');
        $st->execute([$like, $like, $like]);
        $notices = $st->fetchAll();
    } catch (Exception $e) {}

    // Resources
    try {
        $st = $db->prepare('SELECT id, title, subject, type FROM resources WHERE title LIKE ? OR description LIKE ? OR subject LIKE ? LIMIT 4');
        $st->execute([$like, $like, $like]);
        $resources = $st->fetchAll();
    } catch (Exception $e) {}
}

$totalResults = count($students) + count($projects) + count($events) + count($notices) + count($resources);
$catColor = ['web'=>'var(--sky)','mobile'=>'var(--moss)','ml'=>'var(--rust)','hardware'=>'var(--gold)','research'=>'#7c3aed','other'=>'#888'];
$catIcon  = ['web'=>'fa-globe','mobile'=>'fa-mobile-screen','ml'=>'fa-brain','hardware'=>'fa-microchip','research'=>'fa-flask','other'=>'fa-code'];
$noticeClr= ['opportunity'=>'var(--moss)','academic'=>'var(--sky)','internship'=>'var(--rust)','placement'=>'var(--gold)','general'=>'#888','urgent'=>'#dc3545'];

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:92px;background:var(--paper);min-height:100vh;">
  <!-- Header -->
  <div style="background:var(--ink);padding:60px 0;">
    <div class="container">
      <div class="cc-section-label white-lbl">Discover</div>
      <h1 class="cc-heading on-dark">Global <em>Search</em></h1>
      <form method="GET" class="mt-4" style="max-width:600px;">
        <div class="d-flex gap-0">
          <input type="text" name="q" value="<?= e($q) ?>"
                 class="cc-form-input flex-grow-1"
                 style="background:rgba(255,255,255,.07);border-color:rgba(255,255,255,.2);color:var(--paper);"
                 placeholder="Search students, projects, events, notices, resources…"
                 autofocus>
          <button type="submit" style="padding:12px 24px;background:var(--rust);border:1.5px solid var(--rust);color:#fff;font-weight:700;font-size:.84rem;text-transform:uppercase;letter-spacing:.05em;cursor:pointer;white-space:nowrap;">
            <i class="fas fa-search"></i>
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="container py-5">

    <?php if ($q === ''): ?>
    <!-- Empty state -->
    <div class="text-center py-5" style="color:#aaa;">
      <i class="fas fa-search fa-3x mb-4" style="color:#ddd;"></i>
      <h3 style="font-family:var(--font-display);font-size:2rem;color:var(--ink);">Search CampusConnect</h3>
      <p style="max-width:400px;margin:12px auto;font-size:.9rem;line-height:1.7;">
        Search across students, projects, events, notices, and study resources — all in one place.
      </p>
    </div>

    <?php elseif ($totalResults === 0): ?>
    <div class="text-center py-5" style="border:1.5px dashed var(--cream);">
      <i class="fas fa-search fa-2x mb-3" style="color:#ddd;"></i>
      <p style="color:#999;font-size:.95rem;">No results found for <strong>"<?= e($q) ?>"</strong></p>
      <p style="font-size:.82rem;color:#bbb;">Try different keywords or browse individual sections.</p>
    </div>

    <?php else: ?>
    <div style="font-family:var(--font-mono);font-size:.7rem;color:#aaa;margin-bottom:32px;letter-spacing:.08em;text-transform:uppercase;">
      <?= $totalResults ?> result<?= $totalResults !== 1 ? 's' : '' ?> for "<?= e($q) ?>"
    </div>

    <!-- STUDENTS -->
    <?php if ($students): ?>
    <div class="mb-5">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid var(--ink);">
        <div style="width:32px;height:32px;background:var(--rust);display:flex;align-items:center;justify-content:center;">
          <i class="fas fa-users" style="color:#fff;font-size:13px;"></i>
        </div>
        <div style="font-family:var(--font-display);font-size:1.4rem;color:var(--ink);">Students</div>
        <div style="font-family:var(--font-mono);font-size:.65rem;color:#aaa;margin-left:auto;"><?= count($students) ?> found</div>
      </div>
      <div class="row g-3">
        <?php foreach ($students as $s): ?>
        <div class="col-lg-4 col-md-6">
          <a href="view_student.php?id=<?= (int)$s['id'] ?>" class="d-block text-decoration-none"
             style="border:1.5px solid var(--cream);padding:20px;background:var(--white);transition:border-color .2s;"
             onmouseover="this.style.borderColor='var(--ink)'" onmouseout="this.style.borderColor='var(--cream)'">
            <div class="d-flex gap-3 align-items-center">
              <div class="position-relative flex-shrink-0">
                <img src="<?= e(avatarUrl($s)) ?>" style="width:44px;height:44px;object-fit:cover;border:1.5px solid var(--ink);" alt="">
                <span style="position:absolute;bottom:-1px;right:-1px;width:10px;height:10px;background:<?= $s['is_online'] ? '#22c55e' : '#94a3b8' ?>;border-radius:50%;border:2px solid var(--white);"></span>
              </div>
              <div>
                <div style="font-weight:700;font-size:.88rem;color:var(--ink);"><?= e($s['name']) ?></div>
                <div style="font-family:var(--font-mono);font-size:.65rem;color:#aaa;"><?= e($s['department'] ?? '') ?></div>
                <?php if ($s['skills']): ?>
                <div style="margin-top:4px;display:flex;flex-wrap:wrap;gap:4px;">
                  <?php foreach (array_slice(explode(',', $s['skills']), 0, 3) as $sk): ?>
                  <span class="cc-pill" style="font-size:.58rem;padding:2px 6px;"><?= e(trim($sk)) ?></span>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="mt-3"><a href="students.php?q=<?= urlencode($q) ?>" style="font-family:var(--font-mono);font-size:.7rem;color:var(--rust);">View all matching students →</a></div>
    </div>
    <?php endif; ?>

    <!-- PROJECTS -->
    <?php if ($projects): ?>
    <div class="mb-5">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid var(--ink);">
        <div style="width:32px;height:32px;background:var(--sky);display:flex;align-items:center;justify-content:center;">
          <i class="fas fa-code" style="color:#fff;font-size:13px;"></i>
        </div>
        <div style="font-family:var(--font-display);font-size:1.4rem;color:var(--ink);">Projects</div>
        <div style="font-family:var(--font-mono);font-size:.65rem;color:#aaa;margin-left:auto;"><?= count($projects) ?> found</div>
      </div>
      <div class="row g-3">
        <?php foreach ($projects as $p): ?>
        <div class="col-md-6">
          <a href="view_project.php?id=<?= (int)$p['id'] ?>" class="d-block text-decoration-none"
             style="border:1.5px solid var(--cream);padding:18px;background:var(--white);transition:border-color .2s;"
             onmouseover="this.style.borderColor='var(--ink)'" onmouseout="this.style.borderColor='var(--cream)'">
            <div class="d-flex align-items-start gap-3">
              <div style="width:36px;height:36px;background:<?= $catColor[$p['category']] ?? '#888' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas <?= $catIcon[$p['category']] ?? 'fa-code' ?>" style="color:#fff;font-size:13px;"></i>
              </div>
              <div>
                <div style="font-weight:700;font-size:.86rem;color:var(--ink);"><?= e($p['title']) ?></div>
                <div style="font-family:var(--font-mono);font-size:.62rem;color:#aaa;">by <?= e($p['author']) ?> · <?= str_replace('_',' ',e($p['status'])) ?></div>
              </div>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="mt-3"><a href="projects.php?q=<?= urlencode($q) ?>" style="font-family:var(--font-mono);font-size:.7rem;color:var(--rust);">View all matching projects →</a></div>
    </div>
    <?php endif; ?>

    <!-- EVENTS -->
    <?php if ($events): ?>
    <div class="mb-5">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid var(--ink);">
        <div style="width:32px;height:32px;background:var(--moss);display:flex;align-items:center;justify-content:center;">
          <i class="fas fa-calendar-days" style="color:#fff;font-size:13px;"></i>
        </div>
        <div style="font-family:var(--font-display);font-size:1.4rem;color:var(--ink);">Events</div>
        <div style="font-family:var(--font-mono);font-size:.65rem;color:#aaa;margin-left:auto;"><?= count($events) ?> found</div>
      </div>
      <div class="row g-3">
        <?php foreach ($events as $ev): ?>
        <div class="col-md-6">
          <a href="events.php" class="d-block text-decoration-none"
             style="border:1.5px solid var(--cream);padding:16px;background:var(--white);display:flex;gap:16px;align-items:center;transition:border-color .2s;"
             onmouseover="this.style.borderColor='var(--ink)'" onmouseout="this.style.borderColor='var(--cream)'">
            <div style="width:44px;height:44px;background:var(--rust);display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;">
              <div style="font-family:var(--font-display);font-size:.95rem;color:#fff;line-height:1;"><?= date('d', strtotime($ev['event_date'])) ?></div>
              <div style="font-family:var(--font-mono);font-size:.48rem;color:rgba(255,255,255,.7);text-transform:uppercase;"><?= date('M', strtotime($ev['event_date'])) ?></div>
            </div>
            <div>
              <div style="font-weight:700;font-size:.86rem;color:var(--ink);"><?= e($ev['title']) ?></div>
              <div style="font-family:var(--font-mono);font-size:.62rem;color:#aaa;"><?= ucfirst(e($ev['category'])) ?> · <?= e($ev['venue'] ?? '') ?></div>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="mt-3"><a href="events.php" style="font-family:var(--font-mono);font-size:.7rem;color:var(--rust);">View all events →</a></div>
    </div>
    <?php endif; ?>

    <!-- NOTICES -->
    <?php if ($notices): ?>
    <div class="mb-5">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid var(--ink);">
        <div style="width:32px;height:32px;background:var(--gold);display:flex;align-items:center;justify-content:center;">
          <i class="fas fa-bullhorn" style="color:#fff;font-size:13px;"></i>
        </div>
        <div style="font-family:var(--font-display);font-size:1.4rem;color:var(--ink);">Notices</div>
        <div style="font-family:var(--font-mono);font-size:.65rem;color:#aaa;margin-left:auto;"><?= count($notices) ?> found</div>
      </div>
      <div class="row g-3">
        <?php foreach ($notices as $n): ?>
        <div class="col-md-6">
          <a href="notices.php" class="d-block text-decoration-none"
             style="border:1.5px solid var(--cream);border-left:4px solid <?= $noticeClr[$n['category']] ?? '#888' ?>;padding:14px 16px;background:var(--white);transition:border-color .2s;"
             onmouseover="this.style.borderColor='var(--ink)'" onmouseout="this.style.borderColor='var(--cream)'">
            <div style="font-weight:700;font-size:.84rem;color:var(--ink);"><?= e($n['title']) ?></div>
            <div style="font-family:var(--font-mono);font-size:.62rem;color:#aaa;margin-top:4px;"><?= ucfirst(e($n['category'])) ?> · <?= date('M j', strtotime($n['created_at'])) ?></div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="mt-3"><a href="notices.php" style="font-family:var(--font-mono);font-size:.7rem;color:var(--rust);">View all notices →</a></div>
    </div>
    <?php endif; ?>

    <!-- RESOURCES -->
    <?php if ($resources): ?>
    <div class="mb-5">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid var(--ink);">
        <div style="width:32px;height:32px;background:#7c3aed;display:flex;align-items:center;justify-content:center;">
          <i class="fas fa-book-open" style="color:#fff;font-size:13px;"></i>
        </div>
        <div style="font-family:var(--font-display);font-size:1.4rem;color:var(--ink);">Resources</div>
        <div style="font-family:var(--font-mono);font-size:.65rem;color:#aaa;margin-left:auto;"><?= count($resources) ?> found</div>
      </div>
      <div class="row g-3">
        <?php foreach ($resources as $r): ?>
        <div class="col-md-6">
          <a href="resources.php" class="d-block text-decoration-none"
             style="border:1.5px solid var(--cream);padding:14px 16px;background:var(--white);transition:border-color .2s;"
             onmouseover="this.style.borderColor='var(--ink)'" onmouseout="this.style.borderColor='var(--cream)'">
            <div style="font-weight:700;font-size:.84rem;color:var(--ink);"><?= e($r['title']) ?></div>
            <div style="font-family:var(--font-mono);font-size:.62rem;color:#aaa;margin-top:4px;"><?= ucfirst(e($r['type'])) ?> · <?= e($r['subject'] ?? '') ?></div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="mt-3"><a href="resources.php" style="font-family:var(--font-mono);font-size:.7rem;color:var(--rust);">View all resources →</a></div>
    </div>
    <?php endif; ?>

    <?php endif; // end results ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
