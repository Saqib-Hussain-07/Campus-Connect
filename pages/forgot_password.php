<?php
// pages/forgot_password.php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Forgot Password';

if (isLoggedIn()) { header('Location: '.SITE_URL.'/pages/dashboard.php'); exit; }
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$errors  = [];
$sent    = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $errors['csrf'] = 'Invalid request.';
    } else {
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        } else {
            $db = getDB();
            $st = $db->prepare('SELECT id, name FROM users WHERE email = ?');
            $st->execute([$email]);
            $user = $st->fetch();

            // Always show the same message to prevent email enumeration
            $sent = true;

            if ($user) {
                $token   = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
                $db->prepare('UPDATE users SET reset_token=?, reset_expires=? WHERE id=?')
                   ->execute([$token, $expires, $user['id']]);

                $resetUrl = SITE_URL . '/pages/reset_password.php?token=' . $token . '&email=' . urlencode($email);
                $subject  = 'Reset your CampusConnect password';
                $body     = "Hello {$user['name']},\n\nClick the link below to reset your password (valid for 1 hour):\n\n$resetUrl\n\nIf you did not request this, ignore this email.\n\nCampusConnect Team";
                @mail($email, $subject, $body, 'From: noreply@campusconnect.local');

                // Dev-friendly: store reset URL in session to show on page
                $_SESSION['_reset_url_dev'] = $resetUrl;
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:92px;background:var(--paper);min-height:100vh;">
  <div style="background:var(--ink);padding:60px 0;">
    <div class="container">
      <div class="cc-section-label white-lbl">Account</div>
      <h1 class="cc-heading on-dark">Reset <em>Password</em></h1>
    </div>
  </div>

  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-5">

        <?php if ($sent): ?>
        <div style="border:2px solid var(--moss);background:var(--white);padding:40px;text-align:center;">
          <div style="width:64px;height:64px;background:var(--moss);margin:0 auto 20px;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-envelope-circle-check" style="font-size:24px;color:#fff;"></i>
          </div>
          <h3 style="font-family:var(--font-display);font-size:1.8rem;color:var(--ink);margin-bottom:12px;">Check Your Inbox</h3>
          <p style="color:#666;font-size:.9rem;line-height:1.7;margin-bottom:20px;">
            If that email is registered, a password reset link has been sent. It expires in 1 hour.
          </p>
          <?php if (isset($_SESSION['_reset_url_dev'])): ?>
          <div style="background:var(--cream);border:1px solid var(--ink);padding:16px;margin-bottom:20px;text-align:left;">
            <div style="font-family:var(--font-mono);font-size:.6rem;letter-spacing:.1em;text-transform:uppercase;color:#aaa;margin-bottom:8px;">
              <i class="fas fa-code me-1"></i> Dev Mode — Direct Link
            </div>
            <a href="<?= e($_SESSION['_reset_url_dev']) ?>" style="font-size:.75rem;color:var(--rust);word-break:break-all;">
              <?= e($_SESSION['_reset_url_dev']) ?>
            </a>
          </div>
          <?php unset($_SESSION['_reset_url_dev']); ?>
          <?php endif; ?>
          <a href="login.php" class="cc-btn-lg-dark"><span>Back to Login</span><i class="fas fa-arrow-right"></i></a>
        </div>

        <?php else: ?>
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:40px;" class="reveal">
          <div style="font-family:var(--font-mono);font-size:.62rem;letter-spacing:.1em;text-transform:uppercase;color:#aaa;margin-bottom:8px;">Account Recovery</div>
          <h3 style="font-family:var(--font-display);font-size:2rem;color:var(--ink);line-height:.95;margin-bottom:8px;">Forgot Password?</h3>
          <p style="font-size:.86rem;color:#888;margin-bottom:28px;">Enter your registered email and we'll send you a reset link.</p>

          <?php if (isset($errors['csrf'])): ?>
          <div class="alert alert-danger cc-alert"><?= e($errors['csrf']) ?></div>
          <?php endif; ?>

          <form method="POST" novalidate>
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <div class="mb-4">
              <label class="cc-form-label">Email Address</label>
              <input type="email" name="email" class="cc-form-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                     placeholder="you@university.edu" autocomplete="email">
              <?php if (isset($errors['email'])): ?><div class="invalid-feedback d-block"><?= e($errors['email']) ?></div><?php endif; ?>
            </div>
            <button type="submit" class="cc-form-submit">Send Reset Link <i class="fas fa-paper-plane ms-2"></i></button>
          </form>
          <div class="mt-4 text-center">
            <a href="login.php" style="font-size:.8rem;color:#aaa;">Back to Login</a>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
