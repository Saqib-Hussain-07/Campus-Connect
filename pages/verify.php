<?php
// pages/verify.php  — Email verification handler
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Verify Email';

$token = trim($_GET['token'] ?? '');
$email = trim($_GET['email'] ?? '');

$success = false;
$message = '';

if ($token && $email) {
    $db = getDB();
    $st = $db->prepare('SELECT id, is_verified FROM users WHERE email = ? AND verify_token = ?');
    $st->execute([$email, $token]);
    $user = $st->fetch();

    if ($user) {
        if ($user['is_verified']) {
            $message = 'Your account is already verified. You can log in.';
            $success = true;
        } else {
            $db->prepare('UPDATE users SET is_verified=1, verify_token=NULL WHERE id=?')
               ->execute([$user['id']]);
            $success = true;
            $message = 'Your email has been verified successfully! You can now log in.';
        }
    } else {
        $message = 'Invalid or expired verification link. Please register again or request a new link.';
    }
} else {
    $message = 'Missing verification parameters.';
}

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:92px;background:var(--paper);min-height:100vh;">
  <div style="background:var(--ink);padding:60px 0;">
    <div class="container">
      <div class="cc-section-label white-lbl">Account</div>
      <h1 class="cc-heading on-dark">Email <em>Verification</em></h1>
    </div>
  </div>

  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-6">
        <div style="border:2px solid <?= $success ? 'var(--moss)' : 'var(--rust)' ?>;background:var(--white);padding:48px;text-align:center;">
          <div style="width:72px;height:72px;background:<?= $success ? 'var(--moss)' : 'var(--rust)' ?>;margin:0 auto 24px;display:flex;align-items:center;justify-content:center;">
            <i class="fas <?= $success ? 'fa-check' : 'fa-times' ?>" style="font-size:28px;color:#fff;"></i>
          </div>
          <h2 style="font-family:var(--font-display);font-size:2rem;color:var(--ink);margin-bottom:16px;">
            <?= $success ? 'Verified!' : 'Verification Failed' ?>
          </h2>
          <p style="font-size:.94rem;color:#666;line-height:1.7;margin-bottom:28px;">
            <?= e($message) ?>
          </p>
          <?php if ($success): ?>
          <a href="login.php" class="cc-btn-lg-dark">
            <span>Go to Login</span><i class="fas fa-arrow-right"></i>
          </a>
          <?php else: ?>
          <a href="register.php" class="cc-btn-lg-dark">
            <span>Register Again</span><i class="fas fa-arrow-right"></i>
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
