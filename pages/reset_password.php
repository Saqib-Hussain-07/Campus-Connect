<?php
// pages/reset_password.php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Reset Password';

if (isLoggedIn()) { header('Location: '.SITE_URL.'/pages/dashboard.php'); exit; }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$token = trim($_GET['token'] ?? '');
$email = trim($_GET['email'] ?? '');
$errors = [];
$done   = false;

// Validate token
$db      = getDB();
$tokenOk = false;
$userId  = 0;
if ($token && $email) {
    $st = $db->prepare('SELECT id FROM users WHERE email=? AND reset_token=? AND reset_expires > NOW()');
    $st->execute([$email, $token]);
    $row = $st->fetch();
    if ($row) { $tokenOk = true; $userId = (int)$row['id']; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenOk) {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $errors['csrf'] = 'Invalid request.';
    } else {
        $new     = $_POST['password']         ?? '';
        $confirm = $_POST['password_confirm'] ?? '';
        if (strlen($new) < 8)       $errors['password'] = 'Password must be at least 8 characters.';
        if ($new !== $confirm)      $errors['confirm']  = 'Passwords do not match.';

        if (empty($errors)) {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $db->prepare('UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?')
               ->execute([$hash, $userId]);
            $done = true;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:92px;background:var(--paper);min-height:100vh;">
  <div style="background:var(--ink);padding:60px 0;">
    <div class="container">
      <div class="cc-section-label white-lbl">Account</div>
      <h1 class="cc-heading on-dark">New <em>Password</em></h1>
    </div>
  </div>

  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-5">

        <?php if ($done): ?>
        <div style="border:2px solid var(--moss);background:var(--white);padding:40px;text-align:center;">
          <div style="width:64px;height:64px;background:var(--moss);margin:0 auto 20px;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-check" style="font-size:26px;color:#fff;"></i>
          </div>
          <h3 style="font-family:var(--font-display);font-size:1.8rem;color:var(--ink);margin-bottom:12px;">Password Updated!</h3>
          <p style="color:#666;font-size:.9rem;margin-bottom:24px;">Your password has been reset. You can now log in with your new password.</p>
          <a href="login.php" class="cc-btn-lg-dark"><span>Go to Login</span><i class="fas fa-arrow-right"></i></a>
        </div>

        <?php elseif (!$tokenOk): ?>
        <div style="border:2px solid var(--rust);background:var(--white);padding:40px;text-align:center;">
          <div style="width:64px;height:64px;background:var(--rust);margin:0 auto 20px;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-times" style="font-size:26px;color:#fff;"></i>
          </div>
          <h3 style="font-family:var(--font-display);font-size:1.8rem;color:var(--ink);margin-bottom:12px;">Link Expired</h3>
          <p style="color:#666;font-size:.9rem;margin-bottom:24px;">This reset link is invalid or has expired (links are valid for 1 hour). Please request a new one.</p>
          <a href="forgot_password.php" class="cc-btn-lg-dark"><span>Request New Link</span><i class="fas fa-arrow-right"></i></a>
        </div>

        <?php else: ?>
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:40px;" class="reveal">
          <div style="font-family:var(--font-mono);font-size:.62rem;letter-spacing:.1em;text-transform:uppercase;color:#aaa;margin-bottom:8px;">Account Recovery</div>
          <h3 style="font-family:var(--font-display);font-size:2rem;color:var(--ink);line-height:.95;margin-bottom:24px;">Set New Password</h3>

          <?php if (isset($errors['csrf'])): ?>
          <div class="alert alert-danger cc-alert"><?= e($errors['csrf']) ?></div>
          <?php endif; ?>

          <form method="POST" novalidate>
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <div class="mb-3">
              <label class="cc-form-label">New Password <span style="font-size:.65rem;color:#aaa;">(min 8 chars)</span></label>
              <div class="position-relative">
                <input type="password" name="password" id="rPwd"
                       class="cc-form-input w-100 <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                       placeholder="Min 8 characters" autocomplete="new-password">
                <button type="button" data-pwd-toggle="#rPwd"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#999;cursor:pointer;">
                  <i class="fas fa-eye-slash"></i>
                </button>
              </div>
              <?php if (isset($errors['password'])): ?><div class="invalid-feedback d-block"><?= e($errors['password']) ?></div><?php endif; ?>
            </div>
            <div class="mb-4">
              <label class="cc-form-label">Confirm New Password</label>
              <input type="password" name="password_confirm"
                     class="cc-form-input <?= isset($errors['confirm']) ? 'is-invalid' : '' ?>"
                     placeholder="Repeat password" autocomplete="new-password">
              <?php if (isset($errors['confirm'])): ?><div class="invalid-feedback d-block"><?= e($errors['confirm']) ?></div><?php endif; ?>
            </div>
            <button type="submit" class="cc-form-submit">Update Password <i class="fas fa-check ms-2"></i></button>
          </form>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
