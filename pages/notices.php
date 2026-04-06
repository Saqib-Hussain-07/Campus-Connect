<?php
// pages/notices.php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Notice Board';
$db = getDB();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$cat    = $_GET['cat'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$page   = max(1,(int)($_GET['page']??1));
$perPage= 10;
$offset = ($page-1)*$perPage;

$where  = ["(n.expires_at IS NULL OR n.expires_at > NOW())"];
$params = [];
if ($cat !== 'all') { $where[] = 'n.category=?'; $params[] = $cat; }
if ($search !== '') { $where[] = '(n.title LIKE ? OR n.body LIKE ? OR n.tags LIKE ?)'; $l='%'.$search.'%'; $params[]=$l;$params[]=$l;$params[]=$l; }
$whereSQL = implode(' AND ',$where);

$stCount = $db->prepare("SELECT COUNT(*) FROM notices n WHERE $whereSQL");
$stCount->execute($params);
$total = (int)$stCount->fetchColumn();
$totalPages = (int)ceil($total/$perPage);

$stNotices = $db->prepare(
    "SELECT n.*, u.name AS author_name, u.department AS author_dept
     FROM notices n JOIN users u ON u.id=n.user_id
     WHERE $whereSQL ORDER BY n.is_pinned DESC, n.created_at DESC LIMIT $perPage OFFSET $offset"
);
$stNotices->execute($params);
$notices = $stNotices->fetchAll();

$catLabels = ['all'=>'All','opportunity'=>'Opportunity','academic'=>'Academic','internship'=>'Internship',
              'placement'=>'Placement','general'=>'General','urgent'=>'Urgent'];
$catColors = ['opportunity'=>'var(--moss)','academic'=>'var(--sky)','internship'=>'var(--rust)',
              'placement'=>'var(--gold)','general'=>'#888','urgent'=>'#dc3545'];
$catIcons  = ['opportunity'=>'fa-star','academic'=>'fa-book','internship'=>'fa-briefcase',
              'placement'=>'fa-building','general'=>'fa-bullhorn','urgent'=>'fa-triangle-exclamation'];

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:92px;background:var(--paper);min-height:100vh;">

  <div style="background:var(--ink);padding:64px 0 50px;">
    <div class="container">
      <div class="row align-items-end g-4">
        <div class="col-lg-7">
          <div class="cc-section-label white-lbl reveal">Campus Updates</div>
          <h1 class="cc-heading on-dark reveal d1">Notice <em>Board</em></h1>
          <p class="reveal d2" style="color:rgba(255,255,255,.4);max-width:480px;margin-top:12px;font-size:.95rem;line-height:1.65;">
            Internships, academic news, opportunities, and campus announcements — all in one place.
          </p>
        </div>
        <?php if (isLoggedIn()): ?>
        <div class="col-lg-5 text-lg-end reveal d2">
          <button data-bs-toggle="modal" data-bs-target="#postNoticeModal" class="cc-btn-lg-dark">
            <span>Post a Notice</span><i class="fas fa-plus"></i>
          </button>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="container py-5">
    <!-- Search -->
    <form method="GET" class="mb-4 reveal">
      <div class="row g-2 align-items-end">
        <div class="col-md-6"><label class="cc-form-label">Search Notices</label>
          <input type="text" name="q" value="<?= e($search) ?>" class="cc-form-input" placeholder="Keywords, tags...">
          <input type="hidden" name="cat" value="<?= e($cat) ?>"></div>
        <div class="col-md-2"><button type="submit" class="cc-form-submit w-100">Search</button></div>
        <?php if ($search||$cat!=='all'): ?><div class="col-auto"><a href="notices.php" style="font-size:.84rem;color:#888;display:flex;align-items:center;height:44px;">✕ Clear</a></div><?php endif; ?>
      </div>
    </form>

    <!-- Category chips -->
    <div class="d-flex flex-wrap gap-2 mb-5 reveal">
      <?php foreach ($catLabels as $k=>$v): ?>
      <a href="?cat=<?= $k ?>&q=<?= urlencode($search) ?>"
         style="padding:5px 16px;border:1.5px solid <?= $cat===$k?($k==='all'?'var(--ink)':$catColors[$k]):'var(--cream)' ?>;
                background:<?= $cat===$k?($k==='all'?'var(--ink)':$catColors[$k]):'transparent' ?>;
                color:<?= $cat===$k?'#fff':'#888' ?>;
                font-family:var(--font-mono);font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;
                display:inline-flex;align-items:center;gap:6px;transition:all .2s;">
        <?php if($k!=='all'): ?><i class="fas <?= $catIcons[$k] ?>"></i><?php endif; ?><?= $v ?>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Notices list -->
    <?php if ($notices): ?>
    <div class="d-flex flex-column gap-3 mb-4">
      <?php foreach ($notices as $i=>$n):
        $clr  = $catColors[$n['category']] ?? '#888';
        $icon = $catIcons[$n['category']]  ?? 'fa-bullhorn';
        $tags = array_filter(array_map('trim', explode(',',$n['tags']??'')));
      ?>
      <div class="reveal d<?= ($i%3)+1 ?>"
           style="border:1.5px solid <?= $n['is_pinned']?'var(--rust)':'var(--ink)' ?>;
                  background:var(--white);overflow:hidden;
                  transition:box-shadow .25s;"
           onmouseover="this.style.boxShadow='4px 4px 0 var(--ink)'"
           onmouseout="this.style.boxShadow=''">
        <div class="d-flex">
          <!-- Category stripe -->
          <div style="width:6px;background:<?= $clr ?>;flex-shrink:0;"></div>

          <div style="padding:22px 24px;flex:1;">
            <div class="d-flex align-items-start gap-3 flex-wrap">
              <!-- Icon -->
              <div style="width:40px;height:40px;background:<?= $clr ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas <?= $icon ?>" style="color:#fff;font-size:14px;"></i>
              </div>
              <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                  <?php if ($n['is_pinned']): ?>
                  <span style="font-family:var(--font-mono);font-size:.58rem;background:var(--rust);color:#fff;
                               padding:1px 7px;text-transform:uppercase;letter-spacing:.06em;">📌 Pinned</span>
                  <?php endif; ?>
                  <span style="font-family:var(--font-mono);font-size:.62rem;border:1px solid <?= $clr ?>;
                               color:<?= $clr ?>;padding:1px 8px;text-transform:uppercase;letter-spacing:.06em;">
                    <?= e($catLabels[$n['category']]??'') ?>
                  </span>
                  <span style="font-family:var(--font-mono);font-size:.62rem;color:#ccc;">
                    <?= date('M j, Y', strtotime($n['created_at'])) ?>
                  </span>
                </div>
                <h3 style="font-weight:700;font-size:1rem;color:var(--ink);margin-bottom:8px;line-height:1.3;">
                  <?= e($n['title']) ?>
                </h3>
                <p style="font-size:.86rem;line-height:1.65;color:#555;margin-bottom:12px;">
                  <?= nl2br(e(mb_strimwidth($n['body'],0,200,'…'))) ?>
                </p>
                <!-- Tags -->
                <?php if ($tags): ?>
                <div class="d-flex flex-wrap gap-1 mb-2">
                  <?php foreach ($tags as $tag): ?>
                  <span style="padding:2px 8px;font-family:var(--font-mono);font-size:.6rem;
                               border:1px solid var(--cream);color:#aaa;text-transform:lowercase;">
                    #<?= e($tag) ?>
                  </span>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <!-- Author -->
                <div class="d-flex align-items-center gap-2 mt-2">
                  <img src="https://picsum.photos/seed/<?= e($n['author_name']) ?>/40/40"
                       style="width:24px;height:24px;object-fit:cover;border:1px solid var(--ink);" alt="">
                  <span style="font-size:.74rem;color:#888;">Posted by <strong><?= e($n['author_name']) ?></strong></span>
                  <?php if ($n['expires_at']): ?>
                  <span style="font-family:var(--font-mono);font-size:.62rem;color:var(--rust);margin-left:auto;">
                    <i class="fas fa-clock me-1"></i>Expires <?= date('M j', strtotime($n['expires_at'])) ?>
                  </span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Views -->
              <div style="text-align:right;flex-shrink:0;">
                <div style="font-family:var(--font-mono);font-size:.62rem;color:#ccc;">
                  <i class="fas fa-eye"></i> <?= (int)$n['views'] ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages>1): ?>
    <nav class="d-flex justify-content-center gap-1 mt-4">
      <?php for($p=1;$p<=$totalPages;$p++): $q=http_build_query(array_merge($_GET,['page'=>$p])); ?>
      <a href="?<?= $q ?>" style="padding:8px 16px;border:1.5px solid <?= $p===$page?'var(--ink)':'var(--cream)' ?>;
         background:<?= $p===$page?'var(--ink)':'transparent' ?>;color:<?= $p===$page?'var(--paper)':'var(--ink)' ?>;
         font-family:var(--font-mono);font-size:.75rem;"><?= $p ?></a>
      <?php endfor; ?>
    </nav>
    <?php endif; ?>

    <?php else: ?>
    <div class="text-center py-5" style="border:1.5px dashed var(--cream);">
      <i class="fas fa-bullhorn fa-3x mb-3" style="color:#ccc;"></i>
      <p style="color:#aaa;font-size:.9rem;">No notices found.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Post Notice Modal -->
<?php if (isLoggedIn()): ?>
<div class="modal fade" id="postNoticeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content cc-modal">
      <div class="modal-header cc-modal-header">
        <h5 class="modal-title">Post a Notice</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <form method="POST" action="post_notice.php">
          <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
          <div class="row g-3">
            <div class="col-12"><label class="cc-form-label">Title *</label>
              <input type="text" name="title" required class="cc-form-input" placeholder="e.g. Internship at TechCorp — Apply by Oct 30"></div>
            <div class="col-md-6"><label class="cc-form-label">Category</label>
              <select name="category" class="cc-form-input">
                <?php foreach ($catLabels as $k=>$v): if($k==='all')continue; ?>
                <option value="<?= $k ?>"><?= $v ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="col-md-6"><label class="cc-form-label">Expires On (optional)</label>
              <input type="date" name="expires_at" class="cc-form-input"></div>
            <div class="col-12"><label class="cc-form-label">Message *</label>
              <textarea name="body" rows="4" required class="cc-form-input" style="resize:vertical;"
                        placeholder="Write your notice here..."></textarea></div>
            <div class="col-12"><label class="cc-form-label">Tags <span style="color:#aaa;font-size:.65rem;">(comma-separated)</span></label>
              <input type="text" name="tags" class="cc-form-input" placeholder="e.g. internship, tech, paid, apply"></div>
            <div class="col-12"><button type="submit" class="cc-form-submit">Post Notice <i class="fas fa-arrow-right ms-2"></i></button></div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
