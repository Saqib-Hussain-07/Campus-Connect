<?php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Groups';
$db = getDB();

$type   = $_GET['type'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$where  = ['1=1'];
$params = [];
if ($type !== 'all') { $where[] = 'g.type = ?'; $params[] = $type; }
if ($search !== '')  { $where[] = '(g.name LIKE ? OR g.description LIKE ?)'; $like = '%'.$search.'%'; $params[] = $like; $params[] = $like; }

$whereSQL = implode(' AND ', $where);
$stGroups = $db->prepare(
    "SELECT g.*, COUNT(gm.user_id) AS member_count
     FROM groups_list g
     LEFT JOIN group_members gm ON g.id = gm.group_id
     WHERE $whereSQL
     GROUP BY g.id ORDER BY member_count DESC"
);
$stGroups->execute($params);
$groups = $stGroups->fetchAll();

// Check which groups the current user has joined
$myGroupIds = [];
if (isLoggedIn()) {
    $st = $db->prepare('SELECT group_id FROM group_members WHERE user_id=?');
    $st->execute([$_SESSION['user_id']]);
    $myGroupIds = array_column($st->fetchAll(), 'group_id');
}

$bannerSeeds = ['group1','group2','group3','group4','group5','group6','group7','group8'];
$avatarSets  = [['g1','g2','g3'],['g4','g5','g6'],['g7','g8','g9'],['g10','g11'],['g12','g13'],['g14','g15'],['g1','g4'],['g7','g10']];

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:92px;background:var(--white);min-height:100vh;">

  <!-- Header -->
  <div style="background:var(--ink);padding:60px 0;">
    <div class="container">
      <div class="cc-section-label white-lbl reveal">Communities</div>
      <h1 class="cc-heading on-dark reveal d1">Collaboration &amp; <em>Groups</em></h1>
      <p class="reveal d2" style="color:rgba(255,255,255,.4);max-width:480px;margin-top:12px;font-size:.95rem;line-height:1.65;">
        Join study circles, form project teams, or engage in open discussions.
      </p>
    </div>
  </div>

  <div class="container py-5">

    <!-- Search + Tabs -->
    <form method="GET" class="mb-4 reveal">
      <div class="row g-2 align-items-end">
        <div class="col-md-6">
          <label class="cc-form-label">Search Groups</label>
          <input type="text" name="q" value="<?= e($search) ?>" class="cc-form-input" placeholder="Name or keyword...">
          <input type="hidden" name="type" value="<?= e($type) ?>">
        </div>
        <div class="col-md-2">
          <button type="submit" class="cc-form-submit w-100">Search</button>
        </div>
        <?php if (isLoggedIn()): ?>
        <div class="col-md-4 text-md-end">
          <button type="button" data-bs-toggle="modal" data-bs-target="#createGroupModal"
                  class="cc-btn-lg-dark"><span>Create Group</span><i class="fas fa-plus"></i></button>
        </div>
        <?php endif; ?>
      </div>
    </form>

    <!-- Type tabs -->
    <div class="cc-tab-strip mb-4 reveal">
      <?php foreach (['all'=>'All Groups','study'=>'Study','project'=>'Projects','forum'=>'Forums'] as $k => $label): ?>
      <a href="?type=<?= $k ?>&q=<?= urlencode($search) ?>"
         class="cc-tab-btn <?= $type === $k ? 'active' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>

    <!-- Grid -->
    <?php if ($groups): ?>
    <div class="row g-3">
      <?php foreach ($groups as $gi => $grp):
        $seed   = $bannerSeeds[$gi % count($bannerSeeds)];
        $avSeeds= $avatarSets[$gi % count($avatarSets)];
        $joined = in_array($grp['id'], $myGroupIds);
        $statusLabel = ['active'=>'● Active','recruiting'=>'◈ Recruiting','open'=>'○ Open'];
      ?>
      <div class="col-lg-4 col-md-6">
        <div class="cc-group-card reveal d<?= ($gi % 3) + 1 ?>" data-type="<?= e($grp['type']) ?>">
          <div class="cc-group-banner">
            <img src="https://picsum.photos/seed/<?= $seed ?>/600/300" alt="">
            <span class="cc-group-type-badge"><?= ucfirst(e($grp['type'])) ?></span>
            <div class="cc-group-banner-text"><?= e($grp['name']) ?></div>
          </div>
          <div class="cc-group-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="cc-group-status <?= e($grp['status']) ?>"><?= $statusLabel[$grp['status']] ?? '' ?></span>
              <span class="cc-group-members"><i class="fas fa-users me-1"></i><?= (int)$grp['member_count'] ?> members</span>
            </div>
            <p class="cc-group-desc mb-3"><?= e($grp['description']) ?></p>
            <div class="d-flex justify-content-between align-items-center">
              <div class="d-flex">
                <?php foreach ($avSeeds as $av): ?>
                <img class="cc-group-avatar" src="https://picsum.photos/seed/<?= $av ?>/40/40" alt="">
                <?php endforeach; ?>
              </div>
              <?php if ($joined): ?>
              <span style="padding:7px 16px;border:1.5px solid var(--moss);color:var(--moss);font-size:.73rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">
                ✓ Joined
              </span>
              <?php elseif (isLoggedIn()): ?>
              <form method="POST" action="join_group.php">
                <input type="hidden" name="group_id" value="<?= (int)$grp['id'] ?>">
                <input type="hidden" name="csrf"     value="<?= e($_SESSION['csrf'] ?? '') ?>">
                <button type="submit" class="cc-group-join">
                  <?= $grp['type'] === 'project' ? 'Apply Now' : 'Join Group' ?>
                </button>
              </form>
              <?php else: ?>
              <a href="login.php" class="cc-group-join">Join</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="text-center py-5" style="border:1.5px dashed var(--cream);">
      <i class="fas fa-layer-group fa-2x mb-3" style="color:#ccc;"></i>
      <p style="color:#aaa;font-size:.9rem;">No groups found.</p>
      <a href="groups.php" style="color:var(--rust);font-size:.84rem;">Clear filters</a>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Create Group Modal -->
<?php if (isLoggedIn()): ?>
<div class="modal fade" id="createGroupModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content cc-modal">
      <div class="modal-header cc-modal-header">
        <h5 class="modal-title">Create a New Group</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <form method="POST" action="create_group.php">
          <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf'] ?? '') ?>">
          <div class="mb-3">
            <label class="cc-form-label">Group Name *</label>
            <input type="text" name="name" required class="cc-form-input" placeholder="e.g. DBMS Study Circle">
          </div>
          <div class="mb-3">
            <label class="cc-form-label">Type *</label>
            <select name="type" required class="cc-form-input">
              <option value="study">Study Group</option>
              <option value="project">Project Team</option>
              <option value="forum">Discussion Forum</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="cc-form-label">Status</label>
            <select name="status" class="cc-form-input">
              <option value="active">Active</option>
              <option value="recruiting">Recruiting</option>
              <option value="open">Open</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="cc-form-label">Description</label>
            <textarea name="description" rows="3" class="cc-form-input" style="resize:vertical;" placeholder="What is this group about?"></textarea>
          </div>
          <button type="submit" class="cc-form-submit">Create Group <i class="fas fa-arrow-right ms-2"></i></button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
