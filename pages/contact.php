<?php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Contact Us';
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$errors = []; $sent = false;
$vals = ['name'=>'','email'=>'','subject'=>'','message'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf']??'', $_POST['csrf']??'')) {
        $errors['csrf'] = 'Invalid form submission.';
    } else {
        foreach (['name','email','subject','message'] as $f) $vals[$f] = trim($_POST[$f]??'');
        if (strlen($vals['name']) < 2)          $errors['name']    = 'Please enter your name.';
        if (!filter_var($vals['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email.';
        if (strlen($vals['subject']) < 3)       $errors['subject'] = 'Please enter a subject.';
        if (strlen($vals['message']) < 10)      $errors['message'] = 'Message must be at least 10 characters.';

        if (empty($errors)) {
            $db = getDB();
            $db->prepare('INSERT INTO contact_messages (name,email,subject,message) VALUES (?,?,?,?)')
               ->execute([$vals['name'],$vals['email'],$vals['subject'],$vals['message']]);
            $sent = true;
            $vals = ['name'=>'','email'=>'','subject'=>'','message'=>''];
            setFlash('success', 'Your message has been sent! We\'ll reply within 24 hours.');
        }
    }
}
include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:92px;background:var(--paper);min-height:100vh;">

  <!-- Header -->
  <div style="background:var(--ink);padding:64px 0 50px;">
    <div class="container">
      <div class="cc-section-label white-lbl reveal">Get In Touch</div>
      <h1 class="cc-heading on-dark reveal d1">Contact <em>Us</em></h1>
      <p class="reveal d2" style="color:rgba(255,255,255,.4);max-width:460px;margin-top:12px;font-size:.95rem;line-height:1.65;">
        Questions, feedback, or need help? We're here and happy to respond.
      </p>
    </div>
  </div>

  <div class="container py-5">
    <div class="row g-5">

      <!-- Contact methods -->
      <div class="col-lg-4">
        <div class="d-flex flex-column gap-3 mb-5 reveal">
          <?php
          $contacts = [
            ['fa-envelope','Email Us','hello@campusconnect.edu','For general queries & feedback','var(--rust)'],
            ['fa-phone','Call Us','+91 98765 43210','Mon–Fri, 9 AM – 6 PM IST','var(--moss)'],
            ['fa-location-dot','Visit Us','Mumbai, India','Andheri East, Mumbai 400093','var(--sky)'],
            ['fa-shield-halved','Report Abuse','abuse@campusconnect.edu','For safety & harassment issues','var(--gold)'],
          ];
          foreach ($contacts as $c):
          ?>
          <div class="cc-contact-card reveal">
            <div class="cc-contact-icon" style="background:<?= $c[4] ?>;">
              <i class="fas <?= $c[0] ?>" style="color:#fff;font-size:20px;"></i>
            </div>
            <h4 style="font-family:var(--font-body);font-weight:700;font-size:.96rem;color:var(--ink);margin-bottom:4px;"><?= $c[1] ?></h4>
            <div style="font-weight:600;font-size:.88rem;color:<?= $c[4] ?>;margin-bottom:4px;"><?= e($c[2]) ?></div>
            <div style="font-size:.78rem;color:#888;"><?= $c[3] ?></div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Social -->
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:24px;" class="reveal">
          <div style="font-family:var(--font-mono);font-size:.62rem;letter-spacing:.1em;text-transform:uppercase;color:#aaa;margin-bottom:16px;">Follow Us</div>
          <div class="d-flex gap-2 flex-wrap">
            <?php foreach ([['fab fa-x-twitter','X / Twitter','#000'],['fab fa-linkedin-in','LinkedIn','#0077b5'],['fab fa-instagram','Instagram','#e1306c'],['fab fa-github','GitHub','#333'],['fab fa-youtube','YouTube','#ff0000']] as $s): ?>
            <a href="#" style="width:40px;height:40px;border:1.5px solid var(--cream);display:flex;align-items:center;justify-content:center;color:#888;font-size:14px;transition:all .2s;"
               title="<?= $s[1] ?>"
               onmouseover="this.style.borderColor='<?= $s[2] ?>';this.style.color='<?= $s[2] ?>'"
               onmouseout="this.style.borderColor='var(--cream)';this.style.color='#888'">
              <i class="<?= $s[0] ?>"></i>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Contact form -->
      <div class="col-lg-8">
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:40px;" class="reveal d1">
          <h3 style="font-family:var(--font-display);font-size:2rem;color:var(--ink);margin-bottom:6px;line-height:.95;">Send a Message</h3>
          <p style="font-size:.84rem;color:#888;margin-bottom:28px;">We read every message and respond within 24 hours on weekdays.</p>

          <?php if (isset($errors['csrf'])): ?>
          <div class="alert alert-danger cc-alert"><?= e($errors['csrf']) ?></div>
          <?php endif; ?>

          <form method="POST" novalidate>
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="cc-form-label">Your Name *</label>
                <input type="text" name="name" value="<?= e($vals['name']) ?>"
                       class="cc-form-input <?= isset($errors['name'])?'is-invalid':'' ?>" placeholder="Priya Sharma">
                <?php if (isset($errors['name'])): ?><div class="invalid-feedback d-block"><?= e($errors['name']) ?></div><?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="cc-form-label">Email Address *</label>
                <input type="email" name="email" value="<?= e($vals['email']) ?>"
                       class="cc-form-input <?= isset($errors['email'])?'is-invalid':'' ?>" placeholder="you@university.edu">
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback d-block"><?= e($errors['email']) ?></div><?php endif; ?>
              </div>
              <div class="col-12">
                <label class="cc-form-label">Subject *</label>
                <select name="subject" class="cc-form-input <?= isset($errors['subject'])?'is-invalid':'' ?>">
                  <option value="">Choose a subject...</option>
                  <?php foreach(['General Enquiry','Report a Bug','Feature Request','Account Issue','Safety & Abuse','Partnership','Press / Media','Other'] as $opt): ?>
                  <option value="<?= e($opt) ?>" <?= $vals['subject']===$opt?'selected':'' ?>><?= $opt ?></option>
                  <?php endforeach; ?>
                </select>
                <?php if (isset($errors['subject'])): ?><div class="invalid-feedback d-block"><?= e($errors['subject']) ?></div><?php endif; ?>
              </div>
              <div class="col-12">
                <label class="cc-form-label">Message *</label>
                <textarea name="message" rows="6" class="cc-form-input <?= isset($errors['message'])?'is-invalid':'' ?>"
                          style="resize:vertical;" placeholder="Tell us how we can help..."><?= e($vals['message']) ?></textarea>
                <?php if (isset($errors['message'])): ?><div class="invalid-feedback d-block"><?= e($errors['message']) ?></div><?php endif; ?>
              </div>
              <div class="col-12">
                <button type="submit" class="cc-form-submit" style="width:auto;padding:13px 40px;">
                  Send Message <i class="fas fa-paper-plane ms-2"></i>
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- FAQ -->
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:36px;margin-top:20px;" class="reveal d2">
          <h4 style="font-family:var(--font-display);font-size:1.8rem;color:var(--ink);margin-bottom:20px;">Frequently Asked Questions</h4>
          <div class="accordion" id="faqAccordion">
            <?php
            $faqs = [
              ['Is CampusConnect free?','Yes — CampusConnect is completely free for students. We will never charge you for core features.'],
              ['Who can join CampusConnect?','Any student with a valid university or college email address. We verify each account before granting access.'],
              ['How do I find study partners?','Use the Students page to search by department, semester, and skills. Send a connection request and start chatting.'],
              ['Can I post my projects?','Absolutely! Go to Projects > Post Your Project and fill in the details. You can also mark projects as "Looking for Team" to recruit collaborators.'],
              ['Is my data safe?','Yes. All messages are end-to-end encrypted, and we never sell your data. Read our Privacy Policy for full details.'],
              ['How do I report someone?','Use the "Report Abuse" option on any profile, or email abuse@campusconnect.edu. We take safety seriously.'],
            ];
            foreach ($faqs as $i => [$q,$a]):
            ?>
            <div class="accordion-item" style="border:1px solid var(--cream);border-radius:0;margin-bottom:6px;">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#faq<?= $i ?>"
                        style="font-family:var(--font-body);font-weight:700;font-size:.88rem;color:var(--ink);background:var(--paper);border-radius:0;box-shadow:none;">
                  <?= e($q) ?>
                </button>
              </h2>
              <div id="faq<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body" style="font-size:.84rem;line-height:1.7;color:#555;background:var(--white);">
                  <?= e($a) ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
