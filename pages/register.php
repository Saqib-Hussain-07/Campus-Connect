<?php
// pages/register.php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Sign Up';

if (isLoggedIn()) { header('Location: '.SITE_URL.'/pages/dashboard.php'); exit; }

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$errors = [];
$values = ['name'=>'','email'=>'','department'=>'','semester'=>'','university'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        if (strlen($values['name']) < 2)          $errors['name']     = 'Name must be at least 2 characters.';
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL))
                                                  $errors['email']    = 'Enter a valid email address.';
        if (strlen($password) < 8)                $errors['password'] = 'Password must be at least 8 characters.';
        if ($password !== $confirm)               $errors['confirm']  = 'Passwords do not match.';
        if (empty($values['department']))         $errors['dept']     = 'Please select your department.';

        // Check duplicate email
        if (empty($errors)) {
            $db = getDB();
            $chk = $db->prepare('SELECT id FROM users WHERE email = ?');
            $chk->execute([$values['email']]);
            if ($chk->fetch()) $errors['email'] = 'This email is already registered.';
        }

        if (empty($errors)) {
            $db    = getDB();
            $hash  = password_hash($password, PASSWORD_BCRYPT);
            $token = bin2hex(random_bytes(32)); // email verification token

            $st = $db->prepare(
                'INSERT INTO users (name, email, password, department, semester, university, skills, bio, is_verified, verify_token)
                 VALUES (?,?,?,?,?,?,?,?,0,?)'
            );
            $st->execute([
                $values['name'], $values['email'], $hash,
                $values['department'], $values['semester'], $values['university'],
                $skills, $bio, $token
            ]);

            // Build verification URL
            $verifyUrl = SITE_URL . '/pages/verify.php?token=' . $token . '&email=' . urlencode($values['email']);

            // Attempt to send email (works if XAMPP mail/hMailServer configured)
            $subject = 'Verify your CampusConnect account';
            $body    = "Hello {$values['name']},\n\nPlease click the link below to verify your account:\n\n$verifyUrl\n\nThis link does not expire.\n\nCampusConnect Team";
            $headers = "From: noreply@campusconnect.local\r\nContent-Type: text/plain; charset=UTF-8";
            @mail($values['email'], $subject, $body, $headers);

            // Show verification link directly (dev-friendly fallback)
            setFlash('success', 'Account created! <strong>Please verify your email.</strong> <a href="'.e($verifyUrl).'" style="color:var(--rust);text-decoration:underline;">Click here to verify now &rarr;</a> (or check your inbox)');
            header('Location: '.SITE_URL.'/pages/login.php');
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

          <!-- Cascade: Department → Course → Semester in one row -->
          <!-- Step 1: Department group (UI-only, not submitted) -->
          <div class="col-md-4">
            <label class="cc-form-label">
              Department *
              <span style="font-size:.58rem;color:#bbb;font-family:var(--font-mono);font-weight:400;margin-left:4px;">① Choose first</span>
            </label>
            <select id="regDeptGroup" class="cc-form-input <?= isset($errors['dept']) ? 'is-invalid' : '' ?>"
                    onchange="onDeptChange('regDeptGroup','regCourse','regSem','regCourseInfo')">
              <option value="">— Select Department —</option>
              <?php foreach (array_keys(DEPT_COURSES_PHP) as $g): ?>
              <option value="<?= e($g) ?>"><?= e($g) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['dept'])): ?><div class="invalid-feedback d-block"><?= e($errors['dept']) ?></div><?php endif; ?>
          </div>

          <!-- Step 2: Specific course (submitted as 'department') -->
          <div class="col-md-5">
            <label class="cc-form-label">
              Course / Programme *
              <span style="font-size:.58rem;color:#bbb;font-family:var(--font-mono);font-weight:400;margin-left:4px;">② Then pick course</span>
            </label>
            <select id="regCourse" name="department" class="cc-form-input" disabled
                    onchange="onCourseChange('regCourse','regSem','regCourseInfo')">
              <option value="">— Select department first —</option>
            </select>
          </div>

          <!-- Step 3: Semester (auto-updated by JS) -->
          <div class="col-md-3">
            <label class="cc-form-label">
              Semester
              <span style="font-size:.58rem;color:#bbb;font-family:var(--font-mono);font-weight:400;margin-left:4px;">③ Auto-set</span>
            </label>
            <select name="semester" id="regSem" class="cc-form-input" disabled>
              <option value="">—</option>
            </select>
            <div id="regCourseInfo" style="font-size:.64rem;color:var(--rust);font-family:var(--font-mono);margin-top:4px;letter-spacing:.02em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"></div>
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
            <label class="cc-form-label">Password * <span style="font-size:.65rem;color:#aaa;">(min 8 chars)</span></label>
            <div class="d-flex position-relative">
              <input type="password" name="password" id="pwd1"
                     class="cc-form-input flex-grow-1 <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                     placeholder="Min 8 characters" autocomplete="new-password">
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
                   placeholder="Repeat password" autocomplete="new-password">
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

<script>
// ── Department → Course → Semester cascade ─────────────────────────────────
const DEPT_COURSES = <?= json_encode(DEPT_COURSES_PHP, JSON_UNESCAPED_UNICODE) ?>;

// Reverse map: course name → { group, sem, label }
const COURSE_META = {};
for (const [grp, courses] of Object.entries(DEPT_COURSES)) {
  for (const c of courses) COURSE_META[c.name] = { group: grp, sem: c.sem, label: c.label };
}

/** Called when user changes the Department group dropdown */
function onDeptChange(deptGroupId, courseId, semId, infoId, preselectCourse) {
  const grp     = document.getElementById(deptGroupId).value;
  const cSel    = document.getElementById(courseId);
  const sSel    = document.getElementById(semId);
  const info    = document.getElementById(infoId);
  const courses = DEPT_COURSES[grp] || [];

  // Reset course
  cSel.innerHTML = '';
  if (!grp || !courses.length) {
    cSel.appendChild(new Option('— Select department first —', ''));
    cSel.disabled = true;
    sSel.innerHTML = '';
    sSel.appendChild(new Option('— Choose course first —', ''));
    sSel.disabled = true;
    if (info) info.textContent = '';
    return;
  }

  cSel.appendChild(new Option('— Select course / programme —', ''));
  for (const c of courses) {
    const opt = new Option(c.name, c.name);
    if (preselectCourse && c.name === preselectCourse) opt.selected = true;
    cSel.appendChild(opt);
  }
  cSel.disabled = false;

  // If a course is pre-selected, update semesters too
  if (preselectCourse && COURSE_META[preselectCourse]) {
    onCourseChange(courseId, semId, infoId, preselectCourse);
  } else {
    sSel.innerHTML = '';
    sSel.appendChild(new Option('— Choose course first —', ''));
    sSel.disabled = true;
    if (info) info.textContent = '';
  }
}

/** Called when user changes the Course dropdown */
function onCourseChange(courseId, semId, infoId, preselect) {
  const courseVal = preselect || document.getElementById(courseId).value;
  const sSel      = document.getElementById(semId);
  const info      = document.getElementById(infoId);
  const meta      = COURSE_META[courseVal];

  if (!meta || !courseVal) {
    sSel.innerHTML = '';
    sSel.appendChild(new Option('— Choose course first —', ''));
    sSel.disabled = true;
    if (info) info.textContent = '';
    return;
  }

  const curSem = Math.min(parseInt(sSel.value, 10) || 1, meta.sem);
  sSel.innerHTML = '';
  for (let i = 1; i <= meta.sem; i++) {
    const opt = new Option('Semester ' + i, i);
    if (i === curSem) opt.selected = true;
    sSel.appendChild(opt);
  }
  sSel.disabled = false;
  if (info) info.textContent = '📚 ' + meta.label;
}

// Pre-fill on validation error (PHP echoes saved values back)
document.addEventListener('DOMContentLoaded', function () {
  const saved = <?= json_encode($values['department'] ?? '') ?>;
  const sem   = <?= json_encode((int)($values['semester'] ?? 1)) ?>;
  if (!saved) return;
  const meta = COURSE_META[saved];
  if (!meta) return;
  document.getElementById('regDeptGroup').value = meta.group;
  onDeptChange('regDeptGroup', 'regCourse', 'regSem', 'regCourseInfo', saved);
  document.getElementById('regSem').value = Math.min(sem, meta.sem);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
