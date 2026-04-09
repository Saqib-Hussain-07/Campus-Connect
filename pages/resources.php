<?php
// pages/resources.php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Study Resources';
$db = getDB();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$type   = $_GET['type'] ?? 'all';
$dept   = $_GET['dept'] ?? '';
$sem    = (int)($_GET['sem']  ?? 0);
$search = trim($_GET['q']    ?? '');
$sort   = $_GET['sort']      ?? 'newest';

$where  = ['1=1'];
$params = [];
if ($type !== 'all') { $where[] = 'r.type=?'; $params[] = $type; }
if ($dept !== '')    { $where[] = 'r.department=?'; $params[] = $dept; }
if ($sem  > 0)       { $where[] = 'r.semester=?'; $params[] = $sem; }
if ($search !== '')  { $where[] = '(r.title LIKE ? OR r.subject LIKE ? OR r.description LIKE ?)'; $l='%'.$search.'%'; $params[]=$l;$params[]=$l;$params[]=$l; }
$whereSQL = implode(' AND ',$where);
$orderSQL = $sort==='popular' ? 'r.likes DESC' : 'r.created_at DESC';

$stRes = $db->prepare(
    "SELECT r.*, u.name AS author_name, u.department AS author_dept,
            (SELECT COUNT(*) FROM resource_likes rl WHERE rl.resource_id=r.id) AS like_count
     FROM resources r JOIN users u ON u.id=r.user_id
     WHERE $whereSQL ORDER BY $orderSQL"
);
$stRes->execute($params);
$resources = $stRes->fetchAll();

// Liked IDs
$likedIds = [];
if (isLoggedIn()) {
    $stl = $db->prepare('SELECT resource_id FROM resource_likes WHERE user_id=?');
    $stl->execute([$_SESSION['user_id']]);
    $likedIds = array_column($stl->fetchAll(),'resource_id');
}

$depts   = $db->query("SELECT DISTINCT department FROM resources WHERE department IS NOT NULL ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
$typeLabels = ['all'=>'All','notes'=>'Notes','video'=>'Video','book'=>'Book','article'=>'Article','tool'=>'Tool','other'=>'Other'];
$typeColors = ['notes'=>'var(--sky)','video'=>'var(--rust)','book'=>'var(--moss)','article'=>'var(--gold)','tool'=>'#7c3aed','other'=>'#888'];
$typeIcons  = ['notes'=>'fa-file-lines','video'=>'fa-play-circle','book'=>'fa-book','article'=>'fa-newspaper','tool'=>'fa-wrench','other'=>'fa-link'];

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:92px;background:var(--paper);min-height:100vh;">

  <div style="background:var(--ink);padding:64px 0 50px;">
    <div class="container">
      <div class="row align-items-end g-4">
        <div class="col-lg-7">
          <div class="cc-section-label white-lbl reveal">Learning Hub</div>
          <h1 class="cc-heading on-dark reveal d1">Study <em>Resources</em></h1>
          <p class="reveal d2" style="color:rgba(255,255,255,.4);max-width:480px;margin-top:12px;font-size:.95rem;line-height:1.65;">
            Notes, videos, books, tools — shared by students for students. Like the ones you find useful.
          </p>
        </div>
        <?php if (isLoggedIn()): ?>
        <div class="col-lg-5 text-lg-end reveal d2">
          <button data-bs-toggle="modal" data-bs-target="#shareResourceModal" class="cc-btn-lg-dark">
            <span>Share a Resource</span><i class="fas fa-share-from-square"></i>
          </button>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="container py-5">
    <!-- Filters -->
    <form method="GET" class="mb-4 reveal">
      <div class="row g-2 align-items-end">
        <div class="col-lg-3 col-md-4">
          <label class="cc-form-label">Search</label>
          <input type="text" name="q" value="<?= e($search) ?>" class="cc-form-input" placeholder="Topic, subject...">
        </div>
        <div class="col-lg-2 col-md-3">
          <label class="cc-form-label">Department</label>
          <select name="dept" class="cc-form-input">
            <option value="">All Depts</option>
            <?php foreach ($depts as $d): ?>
            <option value="<?= e($d) ?>" <?= $dept===$d?'selected':'' ?>><?= e($d) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-lg-1 col-md-2">
          <label class="cc-form-label">Sem</label>
          <select name="sem" class="cc-form-input">
            <option value="0">Any</option>
            <?php for($s=1;$s<=10;$s++): ?><option value="<?= $s ?>" <?= $sem===$s?'selected':'' ?>>Sem <?= $s ?></option><?php endfor; ?>
          </select>
        </div>
        <div class="col-lg-2 col-md-2">
          <label class="cc-form-label">Sort</label>
          <select name="sort" class="cc-form-input">
            <option value="newest"  <?= $sort==='newest'?'selected':'' ?>>Newest</option>
            <option value="popular" <?= $sort==='popular'?'selected':'' ?>>Most Liked</option>
          </select>
        </div>
        <input type="hidden" name="type" value="<?= e($type) ?>">
        <div class="col-lg-2 col-md-3 d-flex gap-2">
          <button type="submit" class="cc-form-submit flex-grow-1">Filter</button>
          <?php if($search||$dept||$sem||$type!=='all'||$sort!=='newest'): ?>
          <a href="resources.php" style="padding:11px 12px;border:1.5px solid #ccc;color:#888;font-size:.84rem;display:flex;align-items:center;">✕</a>
          <?php endif; ?>
        </div>
      </div>
    </form>

    <!-- Type tabs -->
    <div class="cc-tab-strip mb-4 reveal">
      <?php foreach ($typeLabels as $k=>$v): ?>
      <a href="?type=<?= $k ?>&q=<?= urlencode($search) ?>&dept=<?= urlencode($dept) ?>&sem=<?= $sem ?>&sort=<?= $sort ?>"
         class="cc-tab-btn <?= $type===$k?'active':'' ?>"><?= $v ?></a>
      <?php endforeach; ?>
    </div>

    <div style="font-family:var(--font-mono);font-size:.65rem;color:#aaa;letter-spacing:.06em;text-transform:uppercase;margin-bottom:16px;">
      <?= count($resources) ?> resource<?= count($resources)!==1?'s':'' ?> found
    </div>

    <!-- Resources grid -->
    <?php if ($resources): ?>
    <div class="row g-3">
      <?php foreach ($resources as $i=>$res):
        $clr  = $typeColors[$res['type']] ?? '#888';
        $icon = $typeIcons[$res['type']]  ?? 'fa-link';
        $liked = in_array($res['id'],$likedIds);
      ?>
      <div class="col-lg-4 col-md-6">
        <div class="reveal d<?= ($i%3)+1 ?>"
             style="border:1.5px solid var(--ink);background:var(--white);padding:24px;height:100%;
                    display:flex;flex-direction:column;transition:transform .25s,box-shadow .25s;"
             onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='4px 4px 0 var(--ink)'"
             onmouseout="this.style.transform='';this.style.boxShadow=''">

          <!-- Type + subject header -->
          <div class="d-flex align-items-center gap-2 mb-3">
            <div style="width:36px;height:36px;background:<?= $clr ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fas <?= $icon ?>" style="color:#fff;font-size:13px;"></i>
            </div>
            <div>
              <div style="font-family:var(--font-mono);font-size:.6rem;color:<?= $clr ?>;text-transform:uppercase;letter-spacing:.06em;"><?= e($typeLabels[$res['type']]??'') ?></div>
              <?php if ($res['subject']): ?>
              <div style="font-size:.72rem;font-weight:600;color:var(--ink);"><?= e($res['subject']) ?></div>
              <?php endif; ?>
            </div>
            <?php if ($res['semester']): ?>
            <span style="margin-left:auto;font-family:var(--font-mono);font-size:.6rem;border:1px solid var(--cream);
                         color:#aaa;padding:2px 7px;text-transform:uppercase;">Sem <?= (int)$res['semester'] ?></span>
            <?php endif; ?>
          </div>

          <h3 style="font-weight:700;font-size:.96rem;color:var(--ink);margin-bottom:8px;line-height:1.3;flex:1;">
            <?= e($res['title']) ?>
          </h3>
          <?php if ($res['description']): ?>
          <p style="font-size:.8rem;line-height:1.6;color:#666;margin-bottom:14px;">
            <?= e(mb_strimwidth($res['description'],0,90,'…')) ?>
          </p>
          <?php endif; ?>

          <?php if ($res['department']): ?>
          <div style="font-family:var(--font-mono);font-size:.62rem;color:#aaa;margin-bottom:14px;">
            <i class="fas fa-graduation-cap me-1"></i><?= e($res['department']) ?>
          </div>
          <?php endif; ?>

          <!-- Footer: author + actions -->
          <div class="d-flex align-items-center justify-content-between mt-auto pt-2" style="border-top:1px solid var(--cream);">
            <div class="d-flex align-items-center gap-2">
              <img src="https://picsum.photos/seed/<?= e($res['author_name']) ?>/40/40"
                   style="width:24px;height:24px;object-fit:cover;border:1px solid var(--ink);" alt="">
              <span style="font-size:.72rem;color:#888;"><?= e($res['author_name']) ?></span>
            </div>
            <div class="d-flex gap-2 align-items-center">
              <!-- Like -->
              <?php if (isLoggedIn()): ?>
              <form method="POST" action="like_resource.php" style="margin:0;">
                <input type="hidden" name="resource_id" value="<?= $res['id'] ?>">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                <input type="hidden" name="redirect" value="<?= urlencode($_SERVER['REQUEST_URI']) ?>">
                <button type="submit" style="background:none;border:none;cursor:pointer;padding:0;
                  font-family:var(--font-mono);font-size:.68rem;color:<?= $liked?'var(--rust)':'#aaa' ?>;
                  display:flex;align-items:center;gap:4px;">
                  <i class="fas fa-heart"></i><?= (int)$res['like_count'] ?>
                </button>
              </form>
              <?php else: ?>
              <span style="font-family:var(--font-mono);font-size:.68rem;color:#aaa;display:flex;align-items:center;gap:4px;">
                <i class="fas fa-heart"></i><?= (int)$res['like_count'] ?>
              </span>
              <?php endif; ?>
              <!-- Visit link -->
              <?php if ($res['url']): ?>
              <a href="<?= e($res['url']) ?>" target="_blank" rel="noopener"
                 style="padding:5px 12px;background:var(--ink);border:1.5px solid var(--ink);
                        color:var(--paper);font-size:.68rem;font-weight:600;text-transform:uppercase;
                        letter-spacing:.04em;transition:all .2s;"
                 onmouseover="this.style.background='var(--rust)';this.style.borderColor='var(--rust)'"
                 onmouseout="this.style.background='var(--ink)';this.style.borderColor='var(--ink)'">
                Open <i class="fas fa-external-link-alt ms-1"></i>
              </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="text-center py-5" style="border:1.5px dashed var(--cream);">
      <i class="fas fa-book-open fa-3x mb-3" style="color:#ccc;"></i>
      <p style="color:#aaa;font-size:.9rem;">No resources found.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Share Resource Modal -->
<?php if (isLoggedIn()): ?>
<div class="modal fade" id="shareResourceModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content cc-modal">
      <div class="modal-header cc-modal-header">
        <h5 class="modal-title">Share a Resource</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <form method="POST" action="post_resource.php">
          <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
          <div class="row g-3">
            <div class="col-12"><label class="cc-form-label">Title *</label>
              <input type="text" name="title" required class="cc-form-input" placeholder="e.g. Complete DBMS Notes — IIT Pattern"></div>
            <div class="col-md-4"><label class="cc-form-label">Type</label>
              <select name="type" class="cc-form-input">
                <?php foreach ($typeLabels as $k=>$v): if($k==='all')continue; ?>
                <option value="<?= $k ?>"><?= $v ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="col-md-4"><label class="cc-form-label">Department</label>
              <input type="text" name="department" class="cc-form-input" placeholder="e.g. Computer Science"></div>
            <div class="col-md-4"><label class="cc-form-label">Semester</label>
              <select name="semester" class="cc-form-input">
                <option value="0">Any</option>
                <?php for($s=1;$s<=10;$s++): ?><option value="<?= $s ?>">Sem <?= $s ?></option><?php endfor; ?>
              </select></div>
            <div class="col-md-8"><label class="cc-form-label">Subject</label>
              <input type="text" name="subject" class="cc-form-input" placeholder="e.g. Database Management Systems"></div>
            <div class="col-12"><label class="cc-form-label">Resource URL *</label>
              <input type="url" name="url" required class="cc-form-input" placeholder="https://drive.google.com/..."></div>
            <div class="col-12"><label class="cc-form-label">Description</label>
              <textarea name="description" rows="2" class="cc-form-input" style="resize:none;"
                        placeholder="What's in this resource? Why is it useful?"></textarea></div>
            <div class="col-12"><button type="submit" class="cc-form-submit">Share Resource <i class="fas fa-arrow-right ms-2"></i></button></div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
