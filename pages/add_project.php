<?php
// pages/add_project.php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Post a Project';
requireLogin();
$db = getDB();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$errors = [];
$vals   = ['title'=>'','description'=>'','tech_stack'=>'','github_url'=>'','live_url'=>'',
           'category'=>'web','status'=>'in_progress','team_size'=>1];

// Edit mode
$editId = (int)($_GET['edit'] ?? 0);
$editProj = null;
if ($editId > 0) {
    $st = $db->prepare('SELECT * FROM projects WHERE id=? AND user_id=?');
    $st->execute([$editId, $_SESSION['user_id']]);
    $editProj = $st->fetch();
    if ($editProj) { $vals = array_merge($vals, $editProj); $pageTitle = 'Edit Project'; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf']??'', $_POST['csrf']??'')) {
        $errors['csrf'] = 'Invalid submission.';
    } else {
        foreach (['title','description','tech_stack','github_url','live_url','category','status'] as $f)
            $vals[$f] = trim($_POST[$f] ?? '');
        $vals['team_size'] = max(1,(int)($_POST['team_size']??1));

        if (strlen($vals['title']) < 3) $errors['title'] = 'Title must be at least 3 characters.';
        if (empty($vals['description']))  $errors['description'] = 'Please describe your project.';
        if (!in_array($vals['category'],['web','mobile','ml','hardware','research','other'])) $vals['category']='other';
        if (!in_array($vals['status'],['in_progress','completed','looking_for_team'])) $vals['status']='in_progress';

        if (empty($errors)) {
            $me = (int)$_SESSION['user_id'];
            if ($editProj) {
                $db->prepare('UPDATE projects SET title=?,description=?,tech_stack=?,github_url=?,live_url=?,
                              category=?,status=?,team_size=?,updated_at=NOW() WHERE id=? AND user_id=?')
                   ->execute([$vals['title'],$vals['description'],$vals['tech_stack'],$vals['github_url'],
                              $vals['live_url'],$vals['category'],$vals['status'],$vals['team_size'],$editId,$me]);
                setFlash('success','Project updated!');
                header('Location: '.SITE_URL.'/pages/view_project.php?id='.$editId); exit;
            } else {
                $db->prepare('INSERT INTO projects (user_id,title,description,tech_stack,github_url,live_url,category,status,team_size)
                              VALUES (?,?,?,?,?,?,?,?,?)')
                   ->execute([$me,$vals['title'],$vals['description'],$vals['tech_stack'],$vals['github_url'],
                              $vals['live_url'],$vals['category'],$vals['status'],$vals['team_size']]);
                $newId = $db->lastInsertId();
                $db->prepare("INSERT INTO activity_feed (user_id,type,ref_id,ref_title) VALUES (?,?,?,?)")
                   ->execute([$me,'project_added',$newId,$vals['title']]);
                setFlash('success','Project posted successfully!');
                header('Location: '.SITE_URL.'/pages/view_project.php?id='.$newId); exit;
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:92px;background:var(--paper);min-height:100vh;">
  <div style="background:var(--ink);padding:56px 0 44px;">
    <div class="container">
      <div class="cc-section-label white-lbl reveal">Projects</div>
      <h1 class="cc-heading on-dark reveal d1"><?= $editProj ? 'Edit' : 'Post a' ?> <em>Project</em></h1>
    </div>
  </div>

  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:40px;" class="reveal">
          <?php if (isset($errors['csrf'])): ?>
          <div class="alert alert-danger cc-alert"><?= e($errors['csrf']) ?></div>
          <?php endif; ?>
          <form method="POST" novalidate>
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <div class="row g-3">
              <div class="col-12">
                <label class="cc-form-label">Project Title *</label>
                <input type="text" name="title" value="<?= e($vals['title']) ?>"
                       class="cc-form-input <?= isset($errors['title'])?'is-invalid':'' ?>"
                       placeholder="e.g. Campus Lost & Found App">
                <?php if (isset($errors['title'])): ?><div class="invalid-feedback d-block"><?= e($errors['title']) ?></div><?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="cc-form-label">Category</label>
                <select name="category" class="cc-form-input">
                  <?php foreach (['web'=>'Web App','mobile'=>'Mobile App','ml'=>'AI / ML','hardware'=>'Hardware','research'=>'Research','other'=>'Other'] as $k=>$v): ?>
                  <option value="<?= $k ?>" <?= $vals['category']===$k?'selected':'' ?>><?= $v ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="cc-form-label">Status</label>
                <select name="status" class="cc-form-input">
                  <option value="in_progress"      <?= $vals['status']==='in_progress'?'selected':'' ?>>In Progress</option>
                  <option value="completed"         <?= $vals['status']==='completed'?'selected':'' ?>>Completed</option>
                  <option value="looking_for_team"  <?= $vals['status']==='looking_for_team'?'selected':'' ?>>Looking for Team</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="cc-form-label">Team Size</label>
                <input type="number" name="team_size" value="<?= (int)$vals['team_size'] ?>"
                       min="1" max="20" class="cc-form-input">
              </div>
              <div class="col-12">
                <label class="cc-form-label">Description *</label>
                <textarea name="description" rows="5" class="cc-form-input <?= isset($errors['description'])?'is-invalid':'' ?>"
                          style="resize:vertical;" placeholder="Describe what your project does, the problem it solves, and what you learned..."><?= e($vals['description']) ?></textarea>
                <?php if (isset($errors['description'])): ?><div class="invalid-feedback d-block"><?= e($errors['description']) ?></div><?php endif; ?>
              </div>
              <div class="col-12">
                <label class="cc-form-label">Tech Stack <span style="color:#aaa;font-size:.65rem;">(comma-separated)</span></label>
                <input type="text" name="tech_stack" value="<?= e($vals['tech_stack']) ?>"
                       class="cc-form-input" placeholder="e.g. React, Node.js, MongoDB, Python">
              </div>
              <div class="col-md-6">
                <label class="cc-form-label">GitHub URL</label>
                <input type="url" name="github_url" value="<?= e($vals['github_url']) ?>"
                       class="cc-form-input" placeholder="https://github.com/username/repo">
              </div>
              <div class="col-md-6">
                <label class="cc-form-label">Live Demo URL</label>
                <input type="url" name="live_url" value="<?= e($vals['live_url']) ?>"
                       class="cc-form-input" placeholder="https://yourproject.com">
              </div>
              <div class="col-12 d-flex gap-3 mt-2">
                <button type="submit" class="cc-form-submit" style="width:auto;padding:12px 36px;">
                  <?= $editProj?'Update Project':'Post Project' ?> <i class="fas fa-arrow-right ms-2"></i>
                </button>
                <a href="projects.php" style="font-size:.82rem;color:#888;display:flex;align-items:center;">Cancel</a>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
