<?php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Explore Students';
$db = getDB();

// Filters
$search = trim($_GET['q']    ?? '');
$dept   = trim($_GET['dept'] ?? '');
$sem    = (int)($_GET['sem'] ?? 0);
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage= 12;
$offset = ($page - 1) * $perPage;

// Build query
$where  = ['u.is_verified = 1'];
$params = [];

if ($search !== '') {
    $where[]  = '(u.name LIKE ? OR u.skills LIKE ? OR u.bio LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($dept !== '') {
    $where[]  = 'u.department = ?';
    $params[] = $dept;
}
if ($sem > 0) {
    $where[]  = 'u.semester = ?';
    $params[] = $sem;
}

// Exclude self
if (isLoggedIn()) {
    $where[]  = 'u.id != ?';
    $params[] = $_SESSION['user_id'];
}

$whereSQL = implode(' AND ', $where);

// Count
$stCount = $db->prepare("SELECT COUNT(*) FROM users u WHERE $whereSQL");
$stCount->execute($params);
$total = (int)$stCount->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

// Fetch
$stFetch = $db->prepare("SELECT * FROM users u WHERE $whereSQL ORDER BY u.is_online DESC, u.id DESC LIMIT $perPage OFFSET $offset");
$stFetch->execute($params);
$students = $stFetch->fetchAll();

// Departments list for filter
$depts = $db->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);

// Check connection status for logged-in user
$connectionStatus = [];
if (isLoggedIn()) {
    $stConns = $db->prepare(
        'SELECT CASE WHEN from_user=? THEN to_user ELSE from_user END AS other_id, status
         FROM connections WHERE from_user=? OR to_user=?'
    );
    $stConns->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    foreach ($stConns->fetchAll() as $c) {
        $connectionStatus[(int)$c['other_id']] = $c['status'];
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:92px;background:var(--paper);min-height:100vh;">

  <!-- Page header -->
  <div style="background:var(--ink);padding:60px 0;">
    <div class="container">
      <div class="cc-section-label white-lbl reveal">Student Network</div>
      <h1 class="cc-heading on-dark reveal d1">Explore <em>Peers</em></h1>
      <p class="reveal d2" style="color:rgba(255,255,255,.4);max-width:500px;margin-top:12px;font-size:.95rem;line-height:1.65;">
        <?= number_format($total) ?> verified students across <?= count($depts) ?> departments.
      </p>
    </div>
  </div>

  <div class="container py-5">

    <!-- Search & Filters -->
    <form method="GET" class="mb-4 reveal">
      <div class="row g-2 align-items-end">
        <div class="col-lg-5 col-md-4">
          <label class="cc-form-label">Search Students</label>
          <input type="text" name="q" value="<?= e($search) ?>"
                 class="cc-form-input" placeholder="Name, skill, or keyword...">
        </div>
        <div class="col-lg-3 col-md-3">
          <label class="cc-form-label">Department</label>
          <select name="dept" class="cc-form-input">
            <option value="">All Departments</option>
            <?php foreach ($depts as $d): ?>
            <option value="<?= e($d) ?>" <?= $dept === $d ? 'selected' : '' ?>><?= e($d) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-lg-2 col-md-2">
          <label class="cc-form-label">Semester</label>
          <select name="sem" class="cc-form-input">
            <option value="0">Any</option>
            <?php for ($s=1;$s<=10;$s++): ?>
            <option value="<?= $s ?>" <?= $sem === $s ? 'selected' : '' ?>>Sem <?= $s ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="col-lg-2 col-md-3 d-flex gap-2">
          <button type="submit" class="cc-form-submit flex-grow-1">Search</button>
          <?php if ($search || $dept || $sem): ?>
          <a href="students.php" style="padding:11px 14px;border:1.5px solid #ccc;background:transparent;color:#888;font-size:.84rem;text-align:center;">✕</a>
          <?php endif; ?>
        </div>
      </div>
    </form>

    <!-- Results -->
    <?php if ($students): ?>
    <div class="cc-students-grid mb-5">
      <?php
      $deptMap = [
        'Computer Science'        => 'cs',
        'Mechanical Engineering'  => 'mech',
        'Business Administration' => 'biz',
        'UX Design'               => 'design',
      ];
      foreach ($students as $i => $stu):
        $dept_key   = $deptMap[$stu['department'] ?? ''] ?? 'other';
        $skills     = array_slice(explode(',', $stu['skills'] ?? ''), 0, 3);
        $statusCls  = $stu['is_online'] ? 'online' : 'offline';
        $connStatus = $connectionStatus[(int)$stu['id']] ?? null;
      ?>
      <div class="cc-student-card reveal d<?= ($i % 3) + 1 ?>" data-dept="<?= e($dept_key) ?>">
        <div class="d-flex gap-3 align-items-start mb-3">
          <div class="position-relative flex-shrink-0">
            <img class="cc-student-avatar"
                 src="https://picsum.photos/seed/<?= e($stu['name']) ?>/120/120" alt="<?= e($stu['name']) ?>">
            <span class="cc-status-dot <?= $statusCls ?>"></span>
          </div>
          <div>
            <div class="cc-student-name"><?= e($stu['name']) ?></div>
            <div class="cc-student-dept"><?= e($stu['department']) ?> · Sem <?= (int)$stu['semester'] ?></div>
            <?php if ($stu['university']): ?>
            <div style="font-size:.68rem;color:#aaa;font-family:var(--font-mono);margin-top:2px;"><?= e($stu['university']) ?></div>
            <?php endif; ?>
            <div class="cc-student-verified"><i class="fas fa-check-circle"></i> Verified</div>
          </div>
        </div>
        <div class="d-flex flex-wrap gap-1 mb-3">
          <?php foreach ($skills as $j => $sk): ?>
          <span class="cc-pill <?= $j === 0 ? 'accent' : '' ?>"><?= e(trim($sk)) ?></span>
          <?php endforeach; ?>
        </div>
        <?php if ($stu['bio']): ?>
        <p style="font-size:.78rem;color:#777;line-height:1.55;margin-bottom:12px;"><?= e(mb_strimwidth($stu['bio'],0,90,'…')) ?></p>
        <?php endif; ?>

        <!-- View Profile link -->
        <a href="view_student.php?id=<?= (int)$stu['id'] ?>" class="cc-student-btn mb-2">
          View Profile <i class="fas fa-user"></i>
        </a>
        <?php if (isLoggedIn()): ?>
          <?php if ($connStatus === 'accepted'): ?>
          <div class="cc-student-btn" style="justify-content:center;background:var(--moss);color:#fff;border-color:var(--moss);cursor:default;">
            <i class="fas fa-check"></i> Connected
          </div>
          <?php elseif ($connStatus === 'pending'): ?>
          <div class="cc-student-btn" style="justify-content:center;cursor:default;color:#aaa;border-color:#ddd;">
            Pending…
          </div>
          <?php else: ?>
          <form method="POST" action="send_connection.php">
            <input type="hidden" name="to_user" value="<?= (int)$stu['id'] ?>">
            <input type="hidden" name="csrf"    value="<?= e($_SESSION['csrf'] ?? '') ?>">
            <button type="submit" class="cc-student-btn">
              Connect <i class="fas fa-paper-plane"></i>
            </button>
          </form>
          <?php endif; ?>
        <?php else: ?>
          <a href="login.php" class="cc-student-btn">Connect <i class="fas fa-arrow-right"></i></a>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav class="d-flex justify-content-center gap-1">
      <?php for ($p = 1; $p <= $totalPages; $p++):
        $q = http_build_query(array_merge($_GET, ['page' => $p]));
      ?>
      <a href="?<?= $q ?>" style="padding:8px 16px;border:1.5px solid <?= $p === $page ? 'var(--ink)' : 'var(--cream)' ?>;background:<?= $p === $page ? 'var(--ink)' : 'transparent' ?>;color:<?= $p === $page ? 'var(--paper)' : 'var(--ink)' ?>;font-family:var(--font-mono);font-size:.75rem;">
        <?= $p ?>
      </a>
      <?php endfor; ?>
    </nav>
    <?php endif; ?>

    <?php else: ?>
    <div class="text-center py-5" style="border:1.5px dashed var(--cream);">
      <i class="fas fa-users fa-2x mb-3" style="color:#ccc;"></i>
      <p style="color:#aaa;font-size:.9rem;">No students found matching your search.</p>
      <a href="students.php" style="color:var(--rust);font-size:.84rem;">Clear filters</a>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
