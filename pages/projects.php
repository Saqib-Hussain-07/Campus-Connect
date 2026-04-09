<?php
// pages/projects.php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Projects Showcase';
$db = getDB();

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

// Filters
$cat    = $_GET['cat']    ?? 'all';
$status = $_GET['status'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$sort   = $_GET['sort']   ?? 'newest';
$page   = max(1,(int)($_GET['page'] ?? 1));
$perPage = 9;
$offset  = ($page-1)*$perPage;

$where  = ['1=1'];
$params = [];
if ($cat    !== 'all') { $where[] = 'p.category=?'; $params[] = $cat; }
if ($status !== 'all') { $where[] = 'p.status=?';   $params[] = $status; }
if ($search !== '') {
    $where[]  = '(p.title LIKE ? OR p.description LIKE ? OR p.tech_stack LIKE ?)';
    $l = '%'.$search.'%'; $params[] = $l; $params[] = $l; $params[] = $l;
}
$whereSQL = implode(' AND ', $where);
$orderSQL = match($sort) {
    'popular' => 'p.likes DESC, p.views DESC',
    'views'   => 'p.views DESC',
    default   => 'p.created_at DESC'
};

// Total
$stCount = $db->prepare("SELECT COUNT(*) FROM projects p WHERE $whereSQL");
$stCount->execute($params);
$total = (int)$stCount->fetchColumn();
$totalPages = (int)ceil($total/$perPage);

// Fetch
$stProj = $db->prepare(
    "SELECT p.*, u.name AS author_name, u.department AS author_dept,
            (SELECT COUNT(*) FROM project_likes pl WHERE pl.project_id=p.id) AS like_count,
            (SELECT COUNT(*) FROM project_comments pc WHERE pc.project_id=p.id) AS comment_count
     FROM projects p JOIN users u ON u.id=p.user_id
     WHERE $whereSQL ORDER BY $orderSQL LIMIT $perPage OFFSET $offset"
);
$stProj->execute($params);
$projects = $stProj->fetchAll();

// Liked project IDs for current user
$likedIds = [];
if (isLoggedIn()) {
    $stLiked = $db->prepare('SELECT project_id FROM project_likes WHERE user_id=?');
    $stLiked->execute([$_SESSION['user_id']]);
    $likedIds = array_column($stLiked->fetchAll(), 'project_id');
}

$catLabels   = ['all'=>'All','web'=>'Web','mobile'=>'Mobile','ml'=>'AI / ML','hardware'=>'Hardware','research'=>'Research','other'=>'Other'];
$statusLabels= ['all'=>'All','in_progress'=>'In Progress','completed'=>'Completed','looking_for_team'=>'Looking for Team'];
$catColors   = ['web'=>'var(--sky)','mobile'=>'var(--moss)','ml'=>'var(--rust)','hardware'=>'var(--gold)','research'=>'#7c3aed','other'=>'#888'];
$catIcons    = ['web'=>'fa-globe','mobile'=>'fa-mobile-screen','ml'=>'fa-brain','hardware'=>'fa-microchip','research'=>'fa-flask','other'=>'fa-code'];

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:92px;background:var(--paper);min-height:100vh;">

  <!-- Page header -->
  <div style="background:var(--ink);padding:64px 0 50px;">
    <div class="container">
      <div class="row align-items-end g-4">
        <div class="col-lg-7">
          <div class="cc-section-label white-lbl reveal">Student Work</div>
          <h1 class="cc-heading on-dark reveal d1">Projects <em>Showcase</em></h1>
          <p class="reveal d2" style="color:rgba(255,255,255,.4);max-width:500px;margin-top:12px;font-size:.95rem;line-height:1.65;">
            Discover what students are building — from AI tools to hardware robots. Like, comment, and request to join a team.
          </p>
        </div>
        <div class="col-lg-5 text-lg-end reveal d2">
          <?php if (isLoggedIn()): ?>
          <a href="add_project.php" class="cc-btn-lg-dark">
            <span>Post Your Project</span><i class="fas fa-plus"></i>
          </a>
          <?php else: ?>
          <a href="login.php" class="cc-btn-lg-dark">
            <span>Login to Post</span><i class="fas fa-arrow-right"></i>
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="container py-5">

    <!-- Filters row -->
    <form method="GET" class="mb-4 reveal">
      <div class="row g-2 align-items-end">
        <div class="col-lg-4 col-md-5">
          <label class="cc-form-label">Search Projects</label>
          <input type="text" name="q" value="<?= e($search) ?>" class="cc-form-input" placeholder="Title, tech stack, keyword...">
        </div>
        <div class="col-lg-2 col-md-3">
          <label class="cc-form-label">Category</label>
          <select name="cat" class="cc-form-input">
            <?php foreach ($catLabels as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $cat===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-lg-2 col-md-2">
          <label class="cc-form-label">Status</label>
          <select name="status" class="cc-form-input">
            <?php foreach ($statusLabels as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $status===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-lg-2 col-md-2">
          <label class="cc-form-label">Sort By</label>
          <select name="sort" class="cc-form-input">
            <option value="newest"  <?= $sort==='newest'?'selected':'' ?>>Newest</option>
            <option value="popular" <?= $sort==='popular'?'selected':'' ?>>Most Liked</option>
            <option value="views"   <?= $sort==='views'?'selected':'' ?>>Most Viewed</option>
          </select>
        </div>
        <div class="col-lg-2 col-md-2 d-flex gap-2">
          <button type="submit" class="cc-form-submit flex-grow-1">Filter</button>
          <?php if ($search||$cat!=='all'||$status!=='all'||$sort!=='newest'): ?>
          <a href="projects.php" style="padding:11px 12px;border:1.5px solid #ccc;color:#888;font-size:.84rem;display:flex;align-items:center;">✕</a>
          <?php endif; ?>
        </div>
      </div>
    </form>

    <!-- Category chips -->
    <div class="d-flex flex-wrap gap-2 mb-4 reveal">
      <?php foreach ($catLabels as $k => $v): if($k==='all')continue; ?>
      <a href="?cat=<?= $k ?>"
         style="padding:5px 14px;border:1.5px solid <?= $cat===$k ? $catColors[$k] : 'var(--cream)' ?>;
                background:<?= $cat===$k ? $catColors[$k] : 'transparent' ?>;
                color:<?= $cat===$k ? '#fff' : '#888' ?>;
                font-family:var(--font-mono);font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;
                transition:all .2s;display:inline-flex;align-items:center;gap:6px;">
        <i class="fas <?= $catIcons[$k] ?>"></i><?= $v ?>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Results count -->
    <div style="font-family:var(--font-mono);font-size:.68rem;color:#aaa;letter-spacing:.06em;text-transform:uppercase;margin-bottom:20px;">
      Showing <?= count($projects) ?> of <?= $total ?> projects
    </div>

    <!-- Project grid -->
    <?php if ($projects): ?>
    <div class="row g-4">
      <?php foreach ($projects as $i => $proj):
        $tech   = array_slice(explode(',',$proj['tech_stack']??''),0,4);
        $liked  = in_array($proj['id'],$likedIds);
        $clr    = $catColors[$proj['category']] ?? '#888';
        $icon   = $catIcons[$proj['category']]  ?? 'fa-code';
        $statusLabels2 = ['in_progress'=>'In Progress','completed'=>'Completed','looking_for_team'=>'Looking for Team'];
        $statusColors2 = ['in_progress'=>'var(--gold)','completed'=>'var(--moss)','looking_for_team'=>'var(--rust)'];
      ?>
      <div class="col-lg-4 col-md-6">
        <div class="reveal d<?= ($i%3)+1 ?>"
             style="border:1.5px solid var(--ink);background:var(--white);overflow:hidden;
                    transition:transform .3s var(--ease-bounce),box-shadow .3s;height:100%;display:flex;flex-direction:column;"
             onmouseover="this.style.transform='translateY(-6px)';this.style.boxShadow='5px 5px 0 var(--ink)'"
             onmouseout="this.style.transform='';this.style.boxShadow=''">

          <!-- Banner -->
          <div style="height:140px;background:var(--ink);position:relative;overflow:hidden;flex-shrink:0;">
            <img src="https://picsum.photos/seed/proj<?= $proj['id'] ?>/600/280"
                 style="width:100%;height:100%;object-fit:cover;filter:brightness(.35) saturate(.4);" alt="">
            <!-- Category badge -->
            <div style="position:absolute;top:12px;left:12px;padding:3px 10px;background:<?= $clr ?>;
                        font-family:var(--font-mono);font-size:.6rem;color:#fff;text-transform:uppercase;letter-spacing:.08em;display:flex;align-items:center;gap:5px;">
              <i class="fas <?= $icon ?>"></i><?= e($catLabels[$proj['category']] ?? '') ?>
            </div>
            <!-- Status badge -->
            <div style="position:absolute;top:12px;right:12px;padding:3px 10px;
                        border:1px solid <?= $statusColors2[$proj['status']] ?? '#aaa' ?>;
                        color:<?= $statusColors2[$proj['status']] ?? '#aaa' ?>;
                        font-family:var(--font-mono);font-size:.58rem;text-transform:uppercase;letter-spacing:.06em;">
              <?= e($statusLabels2[$proj['status']] ?? '') ?>
            </div>
            <!-- Project title overlay -->
            <div style="position:absolute;bottom:0;left:0;right:0;padding:16px;
                        background:linear-gradient(transparent,rgba(0,0,0,.8));">
              <div style="font-family:var(--font-display);font-size:1.3rem;color:#fff;line-height:1;letter-spacing:.02em;">
                <?= e($proj['title']) ?>
              </div>
            </div>
          </div>

          <!-- Body -->
          <div style="padding:20px;flex:1;display:flex;flex-direction:column;">
            <!-- Author -->
            <div class="d-flex align-items-center gap-2 mb-3">
              <img src="https://picsum.photos/seed/<?= e($proj['author_name']) ?>/60/60"
                   style="width:28px;height:28px;border:1.5px solid var(--ink);object-fit:cover;" alt="">
              <a href="view_student.php?id=<?= $proj['user_id'] ?>"
                 style="font-size:.78rem;font-weight:600;color:var(--ink);"><?= e($proj['author_name']) ?></a>
              <span style="font-size:.68rem;color:#aaa;font-family:var(--font-mono);">· <?= e($proj['author_dept']) ?></span>
            </div>

            <!-- Description -->
            <p style="font-size:.84rem;line-height:1.65;color:#555;flex:1;margin-bottom:14px;">
              <?= e(mb_strimwidth($proj['description']??'',0,110,'…')) ?>
            </p>

            <!-- Tech stack pills -->
            <div class="d-flex flex-wrap gap-1 mb-4">
              <?php foreach ($tech as $t): ?>
              <span style="padding:2px 8px;font-size:.62rem;font-family:var(--font-mono);
                           border:1px solid var(--ink);text-transform:uppercase;letter-spacing:.03em;">
                <?= e(trim($t)) ?>
              </span>
              <?php endforeach; ?>
            </div>

            <!-- Actions row -->
            <div class="d-flex align-items-center justify-content-between">
              <div class="d-flex gap-3 align-items-center">
                <!-- Like button -->
                <?php if (isLoggedIn()): ?>
                <form method="POST" action="like_project.php" style="margin:0;">
                  <input type="hidden" name="project_id" value="<?= $proj['id'] ?>">
                  <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                  <input type="hidden" name="redirect" value="<?= urlencode($_SERVER['REQUEST_URI']) ?>">
                  <button type="submit"
                          style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:5px;
                                 font-family:var(--font-mono);font-size:.72rem;
                                 color:<?= $liked ? 'var(--rust)' : '#aaa' ?>;padding:0;transition:color .2s;"
                          title="<?= $liked ? 'Unlike' : 'Like' ?>">
                    <i class="fas fa-heart" style="font-size:14px;"></i><?= (int)$proj['like_count'] ?>
                  </button>
                </form>
                <?php else: ?>
                <span style="display:flex;align-items:center;gap:5px;font-family:var(--font-mono);font-size:.72rem;color:#aaa;">
                  <i class="fas fa-heart"></i><?= (int)$proj['like_count'] ?>
                </span>
                <?php endif; ?>

                <span style="display:flex;align-items:center;gap:5px;font-family:var(--font-mono);font-size:.72rem;color:#aaa;">
                  <i class="fas fa-comment"></i><?= (int)$proj['comment_count'] ?>
                </span>
                <span style="display:flex;align-items:center;gap:5px;font-family:var(--font-mono);font-size:.72rem;color:#aaa;">
                  <i class="fas fa-eye"></i><?= (int)$proj['views'] ?>
                </span>
              </div>
              <div class="d-flex gap-2">
                <?php if ($proj['github_url']): ?>
                <a href="<?= e($proj['github_url']) ?>" target="_blank" rel="noopener"
                   style="padding:6px 12px;border:1.5px solid var(--ink);font-size:.72rem;color:var(--ink);
                          display:flex;align-items:center;gap:5px;transition:all .2s;"
                   onmouseover="this.style.background='var(--ink)';this.style.color='var(--paper)'"
                   onmouseout="this.style.background='';this.style.color='var(--ink)'">
                  <i class="fab fa-github"></i>
                </a>
                <?php endif; ?>
                <?php if ($proj['live_url']): ?>
                <a href="<?= e($proj['live_url']) ?>" target="_blank" rel="noopener"
                   style="padding:6px 12px;border:1.5px solid var(--moss);font-size:.72rem;color:var(--moss);
                          display:flex;align-items:center;gap:5px;transition:all .2s;"
                   onmouseover="this.style.background='var(--moss)';this.style.color='#fff'"
                   onmouseout="this.style.background='';this.style.color='var(--moss)'">
                  <i class="fas fa-external-link-alt"></i>
                </a>
                <?php endif; ?>
                <a href="view_project.php?id=<?= $proj['id'] ?>"
                   style="padding:6px 16px;background:var(--ink);border:1.5px solid var(--ink);font-size:.72rem;
                          color:var(--paper);font-family:var(--font-body);font-weight:600;text-transform:uppercase;
                          letter-spacing:.04em;transition:all .2s;"
                   onmouseover="this.style.background='var(--rust)';this.style.borderColor='var(--rust)'"
                   onmouseout="this.style.background='var(--ink)';this.style.borderColor='var(--ink)'">
                  View
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav class="d-flex justify-content-center gap-1 mt-5">
      <?php for ($p=1;$p<=$totalPages;$p++): $q=http_build_query(array_merge($_GET,['page'=>$p])); ?>
      <a href="?<?= $q ?>"
         style="padding:8px 16px;border:1.5px solid <?= $p===$page?'var(--ink)':'var(--cream)' ?>;
                background:<?= $p===$page?'var(--ink)':'transparent' ?>;
                color:<?= $p===$page?'var(--paper)':'var(--ink)' ?>;
                font-family:var(--font-mono);font-size:.75rem;"><?= $p ?></a>
      <?php endfor; ?>
    </nav>
    <?php endif; ?>

    <?php else: ?>
    <div class="text-center py-5" style="border:1.5px dashed var(--cream);">
      <i class="fas fa-folder-open fa-3x mb-3" style="color:#ccc;"></i>
      <p style="color:#aaa;font-size:.9rem;margin-bottom:12px;">No projects found matching your search.</p>
      <a href="projects.php" style="color:var(--rust);font-size:.84rem;">Clear filters</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
