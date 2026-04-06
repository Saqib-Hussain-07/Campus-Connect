<?php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Sign Up';

// Redirect if already logged in
if (isLoggedIn()) { header('Location: '.SITE_URL.'/pages/dashboard.php'); exit; }

// Generate CSRF
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$errors = [];
$values = ['name'=>'','email'=>'','department'=>'','semester'=>'','university'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $errors['csrf'] = 'Invalid form submission.';
    } else {
        $values['name']       = trim($_POST['name']       ?? '');
        $values['email']      = trim($_POST['email']      ?? '');
        $values['department'] = trim($_POST['department'] ?? '');
        $values['semester']   = (int)($_POST['semester']  ?? 0);
        $values['university'] = trim($_POST['university'] ?? '');
        $password             = $_POST['password']         ?? '';
        $confirm              = $_POST['password_confirm'] ?? '';
        $skills               = trim($_POST['skills']      ?? '');
        $bio                  = trim($_POST['bio']         ?? '');

        // Validate
        if (strlen($values['name']) < 2)         $errors['name']     = 'Name must be at least 2 characters.';
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL))
                                                  $errors['email']    = 'Enter a valid email address.';
        if (strlen($password) < 6)               $errors['password'] = 'Password must be at least 6 characters.';
        if ($password !== $confirm)              $errors['confirm']  = 'Passwords do not match.';
        if (empty($values['department']))        $errors['dept']     = 'Please select your department.';

        // Check duplicate email
        if (empty($errors)) {
            $db = getDB();
            $chk = $db->prepare('SELECT id FROM users WHERE email = ?');
            $chk->execute([$values['email']]);
            if ($chk->fetch()) $errors['email'] = 'This email is already registered.';
        }

        if (empty($errors)) {
            $db   = getDB();
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $st   = $db->prepare(
                'INSERT INTO users (name, email, password, department, semester, university, skills, bio, is_verified)
                 VALUES (?,?,?,?,?,?,?,?,1)'
            );
            $st->execute([
                $values['name'], $values['email'], $hash,
                $values['department'], $values['semester'], $values['university'],
                $skills, $bio
            ]);
            $_SESSION['user_id'] = $db->lastInsertId();
            setFlash('success', 'Welcome to CampusConnect, '.$values['name'].'! Your account is ready.');
            header('Location: '.SITE_URL.'/pages/dashboard.php');
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="cc-auth-wrap">
  <!-- Left panel -->
  <div class="col-lg-5 cc-auth-left d-none d-lg-flex">
    <div class="text-center">
      <div class="cc-brand-mark mx-auto mb-4" style="width:56px;height:56px;">
        <i class="fas fa-graduation-cap" style="font-size:20px;"></i>
      </div>
      <h2 style="font-family:var(--font-display);font-size:3.5rem;color:var(--paper);line-height:.9;letter-spacing:.02em;">
        Start Your<br><span style="color:var(--rust);">Journey</span>
      </h2>
      <p class="mt-4" style="color:rgba(255,255,255,.4);max-width:280px;font-size:.9rem;line-height:1.6;">
        Join 25,000+ verified students finding study partners, building projects, and growing their network.
      </p>
      <div class="mt-5 d-flex flex-column gap-3">
        <?php foreach(['Verified university accounts only','End-to-end encrypted messages','Free forever for students'] as $f): ?>
        <div class="d-flex align-items-center gap-3 text-start" style="color:rgba(255,255,255,.55);font-size:.84rem;">
          <i class="fas fa-check-circle" style="color:var(--rust);"></i><?= e($f) ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Right panel: form -->
  <div class="col-lg-7 cc-auth-right">
    <div class="cc-auth-form-card">
      <h1 class="cc-auth-heading mb-1">Create Account</h1>
      <p class="mb-4" style="font-size:.88rem;color:#888;">Already have an account? <a href="login.php" style="color:var(--rust);">Login</a></p>

      <?php if (isset($errors['csrf'])): ?>
      <div class="alert alert-danger cc-alert"><?= e($errors['csrf']) ?></div>
      <?php endif; ?>

      <form method="POST" action="" novalidate>
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

        <div class="row g-3">
          <!-- Full name -->
          <div class="col-12">
            <label class="cc-form-label">Full Name *</label>
            <input type="text" name="name" value="<?= e($values['name']) ?>"
                   class="cc-form-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                   placeholder="e.g. Priya Sharma">
            <?php if (isset($errors['name'])): ?><div class="invalid-feedback d-block"><?= e($errors['name']) ?></div><?php endif; ?>
          </div>

          <!-- Email -->
          <div class="col-12">
            <label class="cc-form-label">University Email *</label>
            <input type="email" name="email" value="<?= e($values['email']) ?>"
                   class="cc-form-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                   placeholder="you@university.edu">
            <?php if (isset($errors['email'])): ?><div class="invalid-feedback d-block"><?= e($errors['email']) ?></div><?php endif; ?>
          </div>

          <!-- Department -->
          <div class="col-md-6">
            <label class="cc-form-label">Department *</label>
            <select name="department" class="cc-form-input <?= isset($errors['dept']) ? 'is-invalid' : '' ?>">
              <option value="">Select department</option>
              <?php
              $depts = ['Computer Science','Mechanical Engineering','Business Administration','UX Design','Electrical Engineering','Civil Engineering','Data Science','Information Technology','Electronics','Chemical Engineering'];
              foreach ($depts as $d):
              ?>
              <option value="<?= e($d) ?>" <?= $values['department'] === $d ? 'selected' : '' ?>><?= e($d) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['dept'])): ?><div class="invalid-feedback d-block"><?= e($errors['dept']) ?></div><?php endif; ?>
          </div>

          <!-- Semester -->
          <div class="col-md-6">
            <label class="cc-form-label">Semester</label>
            <select name="semester" class="cc-form-input">
              <?php for ($s=1;$s<=10;$s++): ?>
              <option value="<?= $s ?>" <?= $values['semester'] == $s ? 'selected' : '' ?>>Semester <?= $s ?></option>
              <?php endfor; ?>
            </select>
          </div>

          <!-- University -->
          <div class="col-12">
            <label class="cc-form-label">University / College</label>
            <input type="text" name="university" value="<?= e($values['university']) ?>"
                   class="cc-form-input" placeholder="e.g. IIT Mumbai">
          </div>

          <!-- Skills -->
          <div class="col-12">
            <label class="cc-form-label">Skills (comma-separated)</label>
            <input type="text" name="skills" class="cc-form-input"
                   placeholder="e.g. Python, React, MATLAB, Figma">
          </div>

          <!-- Bio -->
          <div class="col-12">
            <label class="cc-form-label">Short Bio</label>
            <textarea name="bio" rows="2" class="cc-form-input" style="resize:vertical;"
                      placeholder="Tell others what you're working on or looking for..."></textarea>
          </div>

          <!-- Password -->
          <div class="col-md-6">
            <label class="cc-form-label">Password *</label>
            <div class="d-flex position-relative">
              <input type="password" name="password" id="pwd1"
                     class="cc-form-input flex-grow-1 <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                     placeholder="Min 6 characters">
              <button type="button" data-pwd-toggle="#pwd1"
                      style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#999;cursor:pointer;">
                <i class="fas fa-eye-slash"></i>
              </button>
            </div>
            <?php if (isset($errors['password'])): ?><div class="invalid-feedback d-block"><?= e($errors['password']) ?></div><?php endif; ?>
          </div>

          <!-- Confirm password -->
          <div class="col-md-6">
            <label class="cc-form-label">Confirm Password *</label>
            <input type="password" name="password_confirm"
                   class="cc-form-input <?= isset($errors['confirm']) ? 'is-invalid' : '' ?>"
                   placeholder="Repeat password">
            <?php if (isset($errors['confirm'])): ?><div class="invalid-feedback d-block"><?= e($errors['confirm']) ?></div><?php endif; ?>
          </div>

          <!-- Submit -->
          <div class="col-12 mt-2">
            <button type="submit" class="cc-form-submit">Create Account <i class="fas fa-arrow-right ms-2"></i></button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
