<?php
// pages/login.php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Login';

if (isLoggedIn()) { header('Location: '.SITE_URL.'/pages/dashboard.php'); exit; }

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$errors = [];
$email  = '';
$rlKey  = 'login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $errors['csrf'] = 'Invalid form submission. Please try again.';
    } else {
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']       ?? '';

        // Rate-limit check
        if (!checkRateLimit($rlKey)) {
            $secs = rateLimitSecondsLeft($rlKey);
            $errors['rate'] = 'Too many failed attempts. Please wait ' . ceil($secs / 60) . ' minute(s) before trying again.';
        } else {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email.';
            if (empty($password))                           $errors['pass']  = 'Password is required.';

            if (empty($errors)) {
                $db = getDB();
                $st = $db->prepare('SELECT * FROM users WHERE email = ?');
                $st->execute([$email]);
                $user = $st->fetch();

                if (!$user || !password_verify($password, $user['password'])) {
                    incrementRateLimit($rlKey);
                    $remaining = LOGIN_MAX_ATTEMPTS - (($_SESSION['_rl'][$rlKey]['count'] ?? 0));
                    $errors['auth'] = 'Incorrect email or password.' . ($remaining > 0 ? " ($remaining attempt(s) remaining)" : '');
                } elseif (!$user['is_verified']) {
                    $errors['auth'] = 'Please verify your email before logging in. <a href="resend_verify.php?email='.urlencode($email).'" style="color:var(--rust);">Resend verification link</a>';
                } else {
                    clearRateLimit($rlKey);
                    $_SESSION['user_id'] = $user['id'];
                    unset($_SESSION['_user_cache']); // bust cache
                    // mark online
                    $db->prepare('UPDATE users SET is_online=1 WHERE id=?')->execute([$user['id']]);
                    setFlash('success', 'Welcome back, ' . $user['name'] . '!');
                    header('Location: '.SITE_URL.'/pages/dashboard.php');
                    exit;
                }
            }
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
        Welcome<br><span style="color:var(--rust);">Back</span>
      </h2>
      <p class="mt-4" style="color:rgba(255,255,255,.4);max-width:280px;font-size:.9rem;line-height:1.6;">
        Your campus network is waiting. Log in to connect, collaborate, and grow.
      </p>
      <div class="mt-5">
        <p style="font-family:var(--font-mono);font-size:.65rem;color:rgba(255,255,255,.25);letter-spacing:.08em;">DEMO CREDENTIALS</p>
        <div class="mt-2 p-3" style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);text-align:left;">
          <p style="font-family:var(--font-mono);font-size:.72rem;color:rgba(255,255,255,.5);margin:0;">
            Email: <span style="color:var(--gold);">priya@iitmumbai.edu</span><br>
            Password: <span style="color:var(--gold);">password123</span>
          </p>
        </div>
      </div>
      <div class="mt-4 text-center">
        <a href="forgot_password.php" style="font-family:var(--font-mono);font-size:.7rem;color:rgba(255,255,255,.35);letter-spacing:.05em;">Forgot your password?</a>
      </div>
    </div>
  </div>

  <!-- Right panel: form -->
  <div class="col-lg-7 cc-auth-right">
    <div class="cc-auth-form-card">
      <h1 class="cc-auth-heading mb-1">Login</h1>
      <p class="mb-4" style="font-size:.88rem;color:#888;">New here? <a href="register.php" style="color:var(--rust);">Create an account</a></p>

      <?php if (isset($errors['csrf'])): ?>
      <div class="alert alert-danger cc-alert"><?= e($errors['csrf']) ?></div>
      <?php endif; ?>
      <?php if (isset($errors['rate'])): ?>
      <div class="alert alert-warning cc-alert"><i class="fas fa-clock me-2"></i><?= e($errors['rate']) ?></div>
      <?php endif; ?>
      <?php if (isset($errors['auth'])): ?>
      <div class="alert alert-danger cc-alert"><i class="fas fa-triangle-exclamation me-2"></i><?= $errors['auth'] ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

        <div class="mb-3">
          <label class="cc-form-label">University Email</label>
          <input type="email" name="email" value="<?= e($email) ?>"
                 class="cc-form-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                 placeholder="you@university.edu" autocomplete="email">
          <?php if (isset($errors['email'])): ?><div class="invalid-feedback d-block"><?= e($errors['email']) ?></div><?php endif; ?>
        </div>

        <div class="mb-2">
          <label class="cc-form-label">Password</label>
          <div class="position-relative">
            <input type="password" name="password" id="loginPwd"
                   class="cc-form-input w-100 <?= isset($errors['pass']) ? 'is-invalid' : '' ?>"
                   placeholder="Your password" autocomplete="current-password">
            <button type="button" data-pwd-toggle="#loginPwd"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#999;cursor:pointer;">
              <i class="fas fa-eye-slash"></i>
            </button>
          </div>
          <?php if (isset($errors['pass'])): ?><div class="invalid-feedback d-block"><?= e($errors['pass']) ?></div><?php endif; ?>
        </div>

        <div class="mb-4 text-end">
          <a href="forgot_password.php" style="font-size:.78rem;color:var(--rust);">Forgot password?</a>
        </div>

        <button type="submit" class="cc-form-submit" <?= !checkRateLimit($rlKey) ? 'disabled' : '' ?>>
          Login <i class="fas fa-arrow-right ms-2"></i>
        </button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
