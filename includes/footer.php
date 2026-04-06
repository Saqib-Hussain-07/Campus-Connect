<?php // includes/footer.php ?>

<!-- ── Footer ─────────────────────────────────────────────── -->
<footer id="contact" class="cc-footer pt-5 pb-4">
  <div class="container">
    <div class="row g-5 mb-5">

      <!-- Brand -->
      <div class="col-lg-4 col-md-6">
        <div class="cc-brand d-flex align-items-center gap-2 mb-3">
          <div class="cc-brand-mark"><i class="fas fa-graduation-cap"></i></div>
          <span style="font-family:var(--font-display);font-size:1.2rem;color:var(--paper);letter-spacing:.04em;">
            Campus<span class="cc-brand-accent">Connect</span>
          </span>
        </div>
        <p class="cc-footer-about">The secure student connection portal built to help university peers discover, collaborate, and grow together — verified, safe, and free.</p>
        <div class="d-flex gap-2 mt-3">
          <a href="#" class="cc-social-btn" aria-label="X"><i class="fab fa-x-twitter"></i></a>
          <a href="#" class="cc-social-btn" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
          <a href="#" class="cc-social-btn" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
          <a href="#" class="cc-social-btn" aria-label="GitHub"><i class="fab fa-github"></i></a>
        </div>
      </div>

      <!-- Quick links -->
      <div class="col-lg-2 col-md-3 col-6">
        <div class="cc-footer-col-title">Quick Links</div>
        <ul class="list-unstyled cc-footer-links">
          <li><a href="<?= SITE_URL ?>">Home</a></li>
          <li><a href="<?= SITE_URL ?>/#features">Features</a></li>
          <li><a href="<?= SITE_URL ?>/#how">How It Works</a></li>
          <li><a href="<?= SITE_URL ?>/pages/students.php">Students</a></li>
          <li><a href="<?= SITE_URL ?>/pages/groups.php">Groups</a></li>
        </ul>
      </div>

      <!-- Support -->
      <div class="col-lg-2 col-md-3 col-6">
        <div class="cc-footer-col-title">Support</div>
        <ul class="list-unstyled cc-footer-links">
          <li><a href="#">Help Center</a></li>
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Terms of Service</a></li>
          <li><a href="#">Report Abuse</a></li>
          <li><a href="#">Contact Us</a></li>
        </ul>
      </div>

      <!-- Contact + newsletter -->
      <div class="col-lg-4 col-md-6">
        <div class="cc-footer-col-title">Get In Touch</div>
        <div class="cc-contact-item"><i class="fas fa-envelope"></i><span>hello@campusconnect.edu</span></div>
        <div class="cc-contact-item"><i class="fas fa-phone"></i><span>+91 98765 43210</span></div>
        <div class="cc-contact-item"><i class="fas fa-location-dot"></i><span>Mumbai, India</span></div>

        <div class="cc-footer-col-title mt-4 mb-2">Newsletter</div>
        <form method="POST" action="<?= SITE_URL ?>/pages/newsletter.php" class="d-flex gap-0">
          <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?? '' ?>">
          <input type="email" name="email" required placeholder="your@email.com" class="cc-newsletter-input flex-grow-1">
          <button type="submit" class="cc-newsletter-btn"><i class="fas fa-paper-plane"></i></button>
        </form>
      </div>

    </div>

    <div class="cc-footer-bottom d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3 pt-4">
      <p class="cc-footer-copy mb-0">© <?= date('Y') ?> CampusConnect. All rights reserved.</p>
      <div class="d-flex gap-4">
        <a href="#" class="cc-footer-legal-link">Privacy</a>
        <a href="#" class="cc-footer-legal-link">Terms</a>
        <a href="#" class="cc-footer-legal-link">Cookies</a>
      </div>
    </div>
  </div>
</footer>

<!-- ── Floating chat button ─────────────────────────────────── -->
<button class="cc-chat-float" data-bs-toggle="modal" data-bs-target="#chatModal" aria-label="Chat">
  <i class="fas fa-comment-dots"></i>
</button>

<!-- Chat modal -->
<div class="modal fade" id="chatModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content cc-modal">
      <div class="modal-header cc-modal-header">
        <h5 class="modal-title"><i class="fas fa-headset me-2 text-rust"></i>Live Support</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4 text-center">
        <i class="fas fa-robot fa-3x mb-3 text-rust"></i>
        <p class="mb-0" style="color:#666;">Live chat is coming soon! For now, reach us at<br><strong>hello@campusconnect.edu</strong></p>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Site JS -->
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
