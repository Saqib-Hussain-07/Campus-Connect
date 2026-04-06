<?php
// pages/profile.php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'My Profile';
requireLogin();

$db   = getDB();
$user = currentUser();

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $errors['csrf'] = 'Invalid form submission.';
    } else {
        $name       = trim($_POST['name']       ?? '');
        $department = trim($_POST['department'] ?? '');
        $semester   = (int)($_POST['semester']  ?? 0);
        $university = trim($_POST['university'] ?? '');
        $skills     = trim($_POST['skills']     ?? '');
        $bio        = trim($_POST['bio']        ?? '');

        if (strlen($name) < 2) $errors['name'] = 'Name must be at least 2 characters.';

        if (empty($errors)) {
            $st = $db->prepare(
                'UPDATE users SET name=?, department=?, semester=?, university=?, skills=?, bio=? WHERE id=?'
            );
            $st->execute([$name, $department, $semester, $university, $skills, $bio, $user['id']]);
            $user    = currentUser(); // re-fetch
            $success = true;
            setFlash('success', 'Profile updated successfully!');
        }
    }
}

$depts = ['Computer Science','Mechanical Engineering','Business Administration','UX Design',
          'Electrical Engineering','Civil Engineering','Data Science','Information Technology',
          'Electronics','Chemical Engineering'];

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:92px;background:var(--paper);min-height:100vh;">
  <!-- Header bar -->
  <div style="background:var(--ink);padding:48px 0 40px;">
    <div class="container">
      <div class="cc-section-label white-lbl reveal">Account</div>
      <h1 class="cc-heading on-dark reveal d1">My <em>Profile</em></h1>
    </div>
  </div>

  <div class="container py-5">
    <div class="row g-4">

      <!-- Sidebar: profile card -->
      <div class="col-lg-3">
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:28px;text-align:center;" class="reveal">
          <div class="position-relative d-inline-block mb-3">
            <img src="https://picsum.photos/seed/<?= e($user['name']) ?>/200/200"
                 style="width:100px;height:100px;object-fit:cover;border:3px solid var(--ink);"
                 alt="<?= e($user['name']) ?>">
            <span style="position:absolute;bottom:4px;right:4px;width:14px;height:14px;
                         background:#22c55e;border-radius:50%;border:2px solid var(--white);"></span>
          </div>
          <h4 style="font-family:var(--font-display);font-size:1.4rem;color:var(--ink);line-height:1;margin-bottom:4px;">
            <?= e($user['name']) ?>
          </h4>
          <div style="font-family:var(--font-mono);font-size:.68rem;color:var(--rust);text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;">
            <?= e($user['department'] ?? 'No department set') ?>
          </div>
          <?php if ($user['university']): ?>
          <div style="font-size:.78rem;color:#888;margin-bottom:12px;">
            <i class="fas fa-university me-1" style="color:var(--rust);"></i><?= e($user['university']) ?>
          </div>
          <?php endif; ?>
          <div style="font-family:var(--font-mono);font-size:.65rem;color:var(--moss);margin-bottom:16px;">
            <i class="fas fa-check-circle me-1"></i>Verified Account
          </div>
          <?php if ($user['bio']): ?>
          <p style="font-size:.8rem;line-height:1.6;color:#666;text-align:left;padding-top:12px;border-top:1px solid var(--cream);">
            <?= e($user['bio']) ?>
          </p>
          <?php endif; ?>
          <?php if ($user['skills']): ?>
          <div class="d-flex flex-wrap gap-1 mt-3 justify-content-center">
            <?php foreach (array_slice(explode(',', $user['skills']), 0, 5) as $sk): ?>
            <span class="cc-pill"><?= e(trim($sk)) ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <!-- Quick links -->
          <div class="mt-4 d-flex flex-column gap-2">
            <a href="students.php" class="cc-student-btn"><i class="fas fa-users"></i> Browse Students</a>
            <a href="groups.php"   class="cc-student-btn"><i class="fas fa-layer-group"></i> My Groups</a>
            <a href="dashboard.php" class="cc-student-btn"><i class="fas fa-house"></i> Dashboard</a>
          </div>
        </div>
      </div>

      <!-- Edit form -->
      <div class="col-lg-9">
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:36px;" class="reveal d1">
          <div style="font-family:var(--font-mono);font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:#aaa;margin-bottom:8px;">Edit Information</div>
          <h3 style="font-family:var(--font-display);font-size:2rem;color:var(--ink);line-height:.95;margin-bottom:28px;">Update Profile</h3>

          <?php if (isset($errors['csrf'])): ?>
          <div class="alert alert-danger cc-alert"><?= e($errors['csrf']) ?></div>
          <?php endif; ?>

          <form method="POST" novalidate>
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

            <div class="row g-3">
              <!-- Name -->
              <div class="col-md-6">
                <label class="cc-form-label">Full Name *</label>
                <input type="text" name="name" value="<?= e($user['name']) ?>"
                       class="cc-form-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>">
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback d-block"><?= e($errors['name']) ?></div><?php endif; ?>
              </div>

              <!-- Email (read-only) -->
              <div class="col-md-6">
                <label class="cc-form-label">Email (cannot change)</label>
                <input type="email" value="<?= e($user['email']) ?>" class="cc-form-input" disabled
                       style="background:#f5f5f5;cursor:not-allowed;opacity:.6;">
              </div>

              <!-- Department -->
              <div class="col-md-6">
                <label class="cc-form-label">Department</label>
                <select name="department" class="cc-form-input">
                  <option value="">Select department</option>
                  <?php foreach ($depts as $d): ?>
                  <option value="<?= e($d) ?>" <?= ($user['department'] ?? '') === $d ? 'selected' : '' ?>><?= e($d) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Semester -->
              <div class="col-md-3">
                <label class="cc-form-label">Semester</label>
                <select name="semester" class="cc-form-input">
                  <?php for ($s=1;$s<=10;$s++): ?>
                  <option value="<?= $s ?>" <?= ($user['semester'] ?? 0) == $s ? 'selected' : '' ?>>Sem <?= $s ?></option>
                  <?php endfor; ?>
                </select>
              </div>

              <!-- University -->
              <div class="col-md-9">
                <label class="cc-form-label">University / College</label>
                <input type="text" name="university" value="<?= e($user['university'] ?? '') ?>"
                       class="cc-form-input" placeholder="e.g. IIT Mumbai">
              </div>

              <!-- Skills -->
              <div class="col-12">
                <label class="cc-form-label">Skills <span style="color:#aaa;font-size:.65rem;">(comma-separated)</span></label>
                <input type="text" name="skills" value="<?= e($user['skills'] ?? '') ?>"
                       class="cc-form-input" placeholder="e.g. Python, React, MATLAB, Figma">
                <div style="font-size:.72rem;color:#aaa;margin-top:4px;font-family:var(--font-mono);">
                  These skills appear on your public profile and help others find you.
                </div>
              </div>

              <!-- Bio -->
              <div class="col-12">
                <label class="cc-form-label">Bio</label>
                <textarea name="bio" rows="4" class="cc-form-input" style="resize:vertical;"
                          placeholder="Tell others what you're working on, looking for, or interested in..."><?= e($user['bio'] ?? '') ?></textarea>
              </div>

              <!-- Submit -->
              <div class="col-12 d-flex gap-3 align-items-center">
                <button type="submit" class="cc-form-submit" style="width:auto;padding:12px 36px;">
                  Save Changes <i class="fas fa-check ms-2"></i>
                </button>
                <a href="dashboard.php" style="font-size:.82rem;color:#888;">Cancel</a>
              </div>
            </div>
          </form>
        </div>

        <!-- Change password -->
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:36px;margin-top:20px;" class="reveal d2">
          <h4 style="font-family:var(--font-display);font-size:1.6rem;color:var(--ink);margin-bottom:20px;">Change Password</h4>
          <form method="POST" action="change_password.php" novalidate>
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="cc-form-label">Current Password</label>
                <input type="password" name="current_password" class="cc-form-input" placeholder="Current password">
              </div>
              <div class="col-md-4">
                <label class="cc-form-label">New Password</label>
                <input type="password" name="new_password" class="cc-form-input" placeholder="Min 6 characters">
              </div>
              <div class="col-md-4">
                <label class="cc-form-label">Confirm New</label>
                <input type="password" name="confirm_password" class="cc-form-input" placeholder="Repeat new password">
              </div>
              <div class="col-12">
                <button type="submit" class="cc-btn-outline" style="padding:10px 28px;">
                  Update Password
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- Danger zone -->
        <div style="border:1.5px solid #dc3545;background:var(--white);padding:28px;margin-top:20px;" class="reveal d3">
          <h5 style="font-family:var(--font-body);font-weight:700;color:#dc3545;margin-bottom:8px;">
            <i class="fas fa-triangle-exclamation me-2"></i>Danger Zone
          </h5>
          <p style="font-size:.84rem;color:#888;margin-bottom:16px;">
            Deleting your account is permanent and cannot be undone. All your connections, groups and messages will be lost.
          </p>
          <button type="button" class="btn btn-outline-danger btn-sm" style="border-radius:0;font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;"
                  onclick="if(confirm('Are you sure you want to delete your account? This cannot be undone.')) window.location='delete_account.php';">
            Delete My Account
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
