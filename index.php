<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Home';

// Fetch live counts from DB
$db = getDB();
$userCount  = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$groupCount = $db->query('SELECT COUNT(*) FROM groups_list')->fetchColumn();
$connCount  = $db->query('SELECT COUNT(*) FROM connections WHERE status="accepted"')->fetchColumn();

// Fetch sample students for hero section (6 most recent verified)
$stmtStudents = $db->query(
    'SELECT id, name, department, semester, university, skills, avatar, is_online
     FROM users WHERE is_verified=1 ORDER BY id LIMIT 6'
);
$students = $stmtStudents->fetchAll();

// Fetch groups
$stmtGroups = $db->query(
    'SELECT g.*, COUNT(gm.user_id) AS member_count
     FROM groups_list g
     LEFT JOIN group_members gm ON g.id = gm.group_id
     GROUP BY g.id ORDER BY g.id LIMIT 6'
);
$groups = $stmtGroups->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- ===================================================
     HERO
=================================================== -->
<section id="home" class="cc-hero">
  <div class="row g-0">

    <!-- Left -->
    <div class="col-lg-6">
      <div class="cc-hero-left">
        <div class="cc-hero-eyebrow reveal">Trusted by 50+ Universities</div>
        <h1 class="cc-hero-title reveal d1">
          Connect.<br>
          <span class="italic">Collaborate.</span><br>
          Grow.
        </h1>
        <p class="cc-hero-subtitle mt-4 mb-5 reveal d2">
          Join a secure, verified student network to find peers with the right skills,
          collaborate on real projects, and build academic relationships that matter.
        </p>
        <div class="d-flex gap-3 flex-wrap reveal d3">
          <a href="pages/register.php" class="cc-btn-lg-dark">
            <span>Get Started Free</span><i class="fas fa-arrow-right"></i>
          </a>
          <a href="pages/students.php" class="cc-btn-lg-ghost">
            Browse Students <i class="fas fa-arrow-right"></i>
          </a>
        </div>

        <!-- Mini stats -->
        <div class="d-flex align-items-center gap-0 mt-5 pt-4 reveal d4" style="border-top:1px solid var(--cream);">
          <div class="cc-hero-stat me-4 pe-4" style="border-right:1px solid var(--cream);">
            <div class="cc-hero-stat-num">
              <span class="cc-counter" data-target="<?= (int)$userCount ?>" data-suffix="+">0+</span>
            </div>
            <div class="cc-hero-stat-label">Students</div>
          </div>
          <div class="cc-hero-stat me-4 pe-4" style="border-right:1px solid var(--cream);">
            <div class="cc-hero-stat-num">
              <span class="cc-counter" data-target="<?= (int)$groupCount ?>" data-suffix="+">0+</span>
            </div>
            <div class="cc-hero-stat-label">Groups</div>
          </div>
          <div class="cc-hero-stat">
            <div class="cc-hero-stat-num">50<em>+</em></div>
            <div class="cc-hero-stat-label">Universities</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right: photo collage -->
    <div class="col-lg-6 cc-hero-right">
      <div class="cc-hero-grid">
        <?php
        $seeds = ['campus1','campus3','campus5','campus7'];
        foreach ($seeds as $s):
        ?>
        <div class="cc-hero-cell">
          <img src="https://picsum.photos/seed/<?= $s ?>/600/500" alt="">
        </div>
        <?php endforeach; ?>
      </div>
      <div class="cc-hero-badge"><span>Join<br>Free</span></div>
      <!-- Live student card overlay -->
      <?php if ($students): $s = $students[0]; ?>
      <div class="cc-hero-overlay d-flex align-items-center gap-3">
        <img class="cc-hero-avatar"
             src="https://picsum.photos/seed/<?= e($s['name']) ?>/100/100" alt="<?= e($s['name']) ?>">
        <div class="flex-grow-1">
          <div style="font-weight:700;font-size:.9rem;color:var(--ink);"><?= e($s['name']) ?></div>
          <div style="font-family:var(--font-mono);font-size:.7rem;color:#666;"><?= e($s['department']) ?> · <?= e($s['university'] ?? '') ?></div>
          <div class="d-flex flex-wrap gap-1 mt-2">
            <?php
            $skills = array_slice(explode(',', $s['skills'] ?? ''), 0, 3);
            foreach ($skills as $i => $sk):
            ?>
            <span class="cc-pill <?= $i === 0 ? 'accent' : '' ?>"><?= e(trim($sk)) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="text-end">
          <div style="font-family:var(--font-mono);font-size:.62rem;color:#22c55e;display:flex;align-items:center;gap:4px;">
            <span style="width:6px;height:6px;background:#22c55e;border-radius:50%;"></span> Online
          </div>
          <a href="pages/students.php" style="margin-top:8px;display:block;padding:6px 14px;background:var(--ink);color:var(--paper);font-family:var(--font-body);font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Connect</a>
        </div>
      </div>
      <?php endif; ?>
    </div>

  </div>
</section>


<!-- ===================================================
     FEATURES
=================================================== -->
<section id="features" class="cc-features">
  <div class="container">
    <div class="row g-5 align-items-end mb-5">
      <div class="col-lg-6">
        <div class="cc-section-label reveal">Platform Features</div>
        <h2 class="cc-heading reveal d1">Everything You Need to <em>Succeed Together</em></h2>
      </div>
      <div class="col-lg-6">
        <p class="reveal d2" style="font-size:1.02rem;line-height:1.7;color:#555;">
          Campus Connect gives every student the tools to find the right people, collaborate on what matters, and build a network that goes beyond graduation.
        </p>
      </div>
    </div>

    <?php
    $features = [
      ['01','fa-id-card',         'Rich Student Profiles', 'Build a detailed academic identity — your skills, courses, availability, and project interests all in one verified page.'],
      ['02','fa-sliders',         'Smart Filtering',       'Search by department, skills, semester, or active courses. Find exactly who you need in under a minute.'],
      ['03','fa-comment-dots',    'Real-Time Messaging',   'Encrypted direct messages with read receipts, file sharing, and group threads — no third-party apps needed.'],
      ['04','fa-users-rectangle', 'Study Groups',          'Create subject-specific groups. Schedule sessions, share notes, and track collective progress toward exams.'],
      ['05','fa-handshake',       'Project Partner Finder','Match with students who complement your skillset for hackathons, assignments, and research projects.'],
      ['06','fa-shield-halved',   'Verified Accounts Only','Every account is verified via university email. A trusted, safe environment — no bots, no strangers.'],
    ];
    ?>
    <div class="cc-features-border-wrap">
      <div class="row g-0">
        <?php foreach ($features as $i => $f):
          $delay = 'd' . (($i % 3) + 1);
        ?>
        <div class="col-lg-4 col-md-6">
          <div class="cc-feature-card reveal <?= $delay ?>">
            <div class="fc-num"><?= $f[0] ?> / Feature</div>
            <div class="fc-icon"><i class="fas <?= $f[1] ?>"></i></div>
            <div class="fc-title"><?= e($f[2]) ?></div>
            <div class="fc-desc"><?= e($f[3]) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>


<!-- ===================================================
     HOW IT WORKS
=================================================== -->
<section id="how" class="cc-how">
  <div class="container">
    <div class="cc-section-label reveal">Simple Process</div>
    <h2 class="cc-heading on-dark reveal d1">Four Steps to <em>Your Network</em></h2>

    <div class="cc-how-border">
      <div class="row g-0">
        <?php
        $steps = [
          ['fa-envelope-open-text','Sign Up with University Email', 'Register using your official .edu email for instant verification and access to your campus network.'],
          ['fa-user-pen',          'Build Your Profile',           'Add skills, current courses, interests, and what you are looking to collaborate on. Make yourself discoverable.'],
          ['fa-compass',           'Discover & Filter',            'Browse students across departments, search by skill, join study groups, or post a project listing.'],
          ['fa-link',              'Connect & Collaborate',        'Send a request, start a conversation, and begin building something meaningful together on campus.'],
        ];
        foreach ($steps as $i => $step):
        ?>
        <div class="col-lg-3 col-md-6">
          <div class="cc-how-step reveal d<?= $i + 1 ?>">
            <div class="cc-step-num"><?= str_pad($i+1, 2, '0', STR_PAD_LEFT) ?></div>
            <div class="cc-step-icon"><i class="fas <?= $step[0] ?>"></i></div>
            <div class="cc-step-title"><?= e($step[1]) ?></div>
            <div class="cc-step-desc"><?= e($step[2]) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>


<!-- ===================================================
     STUDENTS
=================================================== -->
<section id="students-section" class="cc-students">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
      <div>
        <div class="cc-section-label reveal">Student Network</div>
        <h2 class="cc-heading reveal d1">Explore <em>Peers</em></h2>
      </div>
      <a href="pages/students.php" class="cc-btn-lg-dark reveal d2">
        <span>Browse All</span><i class="fas fa-arrow-right"></i>
      </a>
    </div>

    <!-- Filter strip -->
    <div class="cc-filter-strip mb-2 reveal d1">
      <button class="cc-filter-btn active" data-filter="all">All</button>
      <button class="cc-filter-btn" data-filter="cs">Computer Science</button>
      <button class="cc-filter-btn" data-filter="mech">Mechanical</button>
      <button class="cc-filter-btn" data-filter="biz">Business</button>
      <button class="cc-filter-btn" data-filter="design">Design</button>
    </div>

    <!-- Department map for filter keys -->
    <?php
    $deptMap = [
      'Computer Science'       => 'cs',
      'Mechanical Engineering' => 'mech',
      'Business Administration'=> 'biz',
      'UX Design'              => 'design',
    ];
    ?>

    <div class="cc-students-grid">
      <?php foreach ($students as $i => $stu):
        $dept  = $stu['department'] ?? '';
        $key   = $deptMap[$dept] ?? 'other';
        $skills= array_slice(explode(',', $stu['skills'] ?? ''), 0, 3);
        $statusClass = $stu['is_online'] ? 'online' : 'offline';
      ?>
      <div class="cc-student-card reveal d<?= ($i % 3) + 1 ?>" data-dept="<?= e($key) ?>">
        <div class="d-flex gap-3 align-items-start mb-3">
          <div class="position-relative flex-shrink-0">
            <img class="cc-student-avatar"
                 src="https://picsum.photos/seed/<?= e($stu['name']) ?>/120/120" alt="<?= e($stu['name']) ?>">
            <span class="cc-status-dot <?= $statusClass ?>"></span>
          </div>
          <div>
            <div class="cc-student-name"><?= e($stu['name']) ?></div>
            <div class="cc-student-dept"><?= e($dept) ?> · Sem <?= (int)$stu['semester'] ?></div>
            <div class="cc-student-verified"><i class="fas fa-check-circle"></i> Verified</div>
          </div>
        </div>
        <div class="d-flex flex-wrap gap-1 mb-3">
          <?php foreach ($skills as $j => $sk): ?>
          <span class="cc-pill <?= $j === 0 ? 'accent' : '' ?>"><?= e(trim($sk)) ?></span>
          <?php endforeach; ?>
        </div>
        <a href="pages/students.php?id=<?= (int)$stu['id'] ?>" class="cc-student-btn">
          View Profile <i class="fas fa-arrow-right"></i>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<!-- ===================================================
     GROUPS
=================================================== -->
<section id="groups-section" class="cc-groups">
  <div class="container">
    <div class="row g-5 align-items-end mb-4">
      <div class="col-lg-6">
        <div class="cc-section-label reveal">Communities</div>
        <h2 class="cc-heading reveal d1">Collaboration &amp; <em>Groups</em></h2>
      </div>
      <div class="col-lg-6">
        <p class="reveal d2" style="font-size:1rem;line-height:1.7;color:#555;">
          Join study circles, form project teams, or engage in open discussions — all in one place.
        </p>
      </div>
    </div>

    <div class="cc-tab-strip mb-4 reveal">
      <button class="cc-tab-btn active" data-tab="all">All Groups</button>
      <button class="cc-tab-btn" data-tab="study">Study</button>
      <button class="cc-tab-btn" data-tab="project">Projects</button>
      <button class="cc-tab-btn" data-tab="forum">Forums</button>
    </div>

    <div class="row g-3">
      <?php
      $bannerSeeds = ['group1','group2','group3','group4','group5','group6'];
      $avatarSeeds = [['g1','g2','g3'],['g4','g5','g6'],['g7','g8','g9'],['g10','g11'],['g12','g13'],['g14','g15']];
      foreach ($groups as $gi => $grp):
        $statusLabel = ['active' => '● Active', 'recruiting' => '◈ Recruiting', 'open' => '○ Open'];
      ?>
      <div class="col-lg-4 col-md-6">
        <div class="cc-group-card reveal d<?= ($gi % 3) + 1 ?>" data-type="<?= e($grp['type']) ?>">
          <div class="cc-group-banner">
            <img src="https://picsum.photos/seed/<?= $bannerSeeds[$gi] ?>/600/300" alt="">
            <span class="cc-group-type-badge"><?= ucfirst(e($grp['type'])) ?></span>
            <div class="cc-group-banner-text"><?= e($grp['name']) ?></div>
          </div>
          <div class="cc-group-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="cc-group-status <?= e($grp['status']) ?>"><?= $statusLabel[$grp['status']] ?? '' ?></span>
              <span class="cc-group-members"><i class="fas fa-users me-1"></i><?= (int)$grp['member_count'] ?> members</span>
            </div>
            <p class="cc-group-desc mb-3"><?= e($grp['description']) ?></p>
            <div class="d-flex justify-content-between align-items-center">
              <div class="d-flex">
                <?php foreach (($avatarSeeds[$gi] ?? []) as $av): ?>
                <img class="cc-group-avatar" src="https://picsum.photos/seed/<?= $av ?>/40/40" alt="">
                <?php endforeach; ?>
              </div>
              <?php if (isLoggedIn()): ?>
              <form method="POST" action="pages/join_group.php">
                <input type="hidden" name="group_id" value="<?= (int)$grp['id'] ?>">
                <input type="hidden" name="csrf"     value="<?= $_SESSION['csrf'] ?? '' ?>">
                <button type="submit" class="cc-group-join">
                  <?= $grp['type'] === 'project' ? 'Apply Now' : 'Join Group' ?>
                </button>
              </form>
              <?php else: ?>
              <a href="pages/login.php" class="cc-group-join">Join Group</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<!-- ===================================================
     SECURITY
=================================================== -->
<section id="security" class="cc-security">
  <div class="container">
    <div class="row g-5 align-items-center">
      <!-- Visual -->
      <div class="col-lg-5 d-flex justify-content-center reveal from-left">
        <div class="cc-sec-float-wrap">
          <div class="cc-sec-ring">
            <div class="cc-sec-ring-inner">
              <div class="cc-sec-center"><i class="fas fa-shield-halved"></i></div>
            </div>
          </div>
          <div class="cc-sec-float" style="top:10px;left:50%;transform:translateX(-50%);">
            <i class="fas fa-lock"></i><span>End-to-End Encrypted</span>
          </div>
          <div class="cc-sec-float" style="bottom:30px;left:0;">
            <i class="fas fa-fingerprint"></i><span>Biometric Auth</span>
          </div>
          <div class="cc-sec-float" style="bottom:100px;right:0;">
            <i class="fas fa-user-check"></i><span>Verified Accounts</span>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div class="col-lg-7 reveal from-right">
        <div class="cc-section-label light">Security First</div>
        <h2 class="cc-heading on-dark mb-4">Your Safety Is Our <em>Priority</em></h2>
        <div class="d-flex flex-column gap-4">
          <?php
          $points = [
            ['fa-envelope-circle-check','University Email Verification','Every account is verified through an official .edu email, ensuring only genuine students join.'],
            ['fa-lock',                 'End-to-End Encryption',        'All messages and data are encrypted in transit and at rest using industry-standard protocols.'],
            ['fa-sliders',              'Granular Privacy Controls',    'Control who sees your profile, who can message you, and manage data visibility on your terms.'],
          ];
          foreach ($points as $pt):
          ?>
          <div class="cc-sec-point">
            <div class="cc-sec-point-icon"><i class="fas <?= $pt[0] ?>"></i></div>
            <div>
              <div class="cc-sec-point-title"><?= e($pt[1]) ?></div>
              <div class="cc-sec-point-desc"><?= e($pt[2]) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- ===================================================
     STATS  (live from DB)
=================================================== -->
<section id="stats" class="cc-stats">
  <div class="container">
    <div class="cc-section-label white-lbl reveal">By The Numbers</div>
    <h2 class="cc-heading on-dark reveal d1">Growing <em style="color:rgba(255,255,255,.4);">Every Day</em></h2>
    <div class="cc-stats-grid row g-0 mt-4 reveal d2">
      <?php
      $connTotal = $db->query('SELECT COUNT(*) FROM connections')->fetchColumn();
      $msgTotal  = $db->query('SELECT COUNT(*) FROM messages')->fetchColumn();
      $statsData = [
        [(int)$userCount,  '+', 'Total Students'],
        [(int)$connTotal,  '+', 'Connections Made'],
        [(int)$groupCount, '+', 'Groups Created'],
        [(int)$msgTotal,   '+', 'Messages Exchanged'],
      ];
      foreach ($statsData as $sd):
      ?>
      <div class="col-lg-3 col-6">
        <div class="cc-stat-item">
          <div class="cc-stat-big">
            <span class="cc-counter" data-target="<?= $sd[0] ?>" data-suffix="<?= e($sd[1]) ?>">0</span>
          </div>
          <div class="cc-stat-lbl"><?= e($sd[2]) ?></div>
          <div class="cc-stat-bar"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<!-- ===================================================
     TESTIMONIALS (static for now)
=================================================== -->
<section id="testimonials" class="cc-testimonials">
  <div class="container">
    <div class="cc-section-label reveal">Student Stories</div>
    <h2 class="cc-heading reveal d1 mb-5">What <em>Students</em> Say</h2>

    <div class="cc-testi-grid">
      <?php
      $testis = [
        ['Ananya Gupta','IT Engineering · Pune','test1',5,
         'Campus Connect helped me find a study partner for Data Structures. We went from struggling to topping the class. Incredibly easy to find someone with the right skills.'],
        ['Rohan Mehta','Computer Science · Mumbai','test2',5,
         'I found my hackathon team through the Project Partner Finder. We won the inter-college competition! Verified profiles gave me full confidence.'],
        ['Divya Krishnan','Electronics · Chennai','test3',4.5,
         'As a first-year student, I felt lost. Study groups helped me integrate quickly. Career Connect gave me internship leads I would never have found otherwise.'],
      ];
      foreach ($testis as $i => $t):
      ?>
      <div class="cc-testi-item reveal d<?= $i + 1 ?>">
        <span class="cc-testi-quote">"</span>
        <div class="cc-testi-star mb-2">
          <?php for($s=1;$s<=5;$s++): ?>
            <?php if($s <= floor($t[3])): ?>
              <i class="fas fa-star"></i>
            <?php elseif($t[3] - floor($t[3]) > 0): ?>
              <i class="fas fa-star-half-stroke"></i><?php $t[3]=0; ?>
            <?php endif; ?>
          <?php endfor; ?>
        </div>
        <p class="cc-testi-text"><?= e($t[4]) ?></p>
        <div class="d-flex align-items-center gap-3">
          <img class="cc-testi-avatar"
               src="https://picsum.photos/seed/<?= $t[2] ?>/80/80" alt="<?= e($t[0]) ?>">
          <div>
            <div class="cc-testi-name"><?= e($t[0]) ?></div>
            <div class="cc-testi-role"><?= e($t[1]) ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<!-- ===================================================
     CTA
=================================================== -->
<section class="cc-cta">
  <div class="cc-cta-bg-text">JOIN</div>
  <div class="container position-relative" style="z-index:1;">
    <div class="cc-section-label reveal justify-content-center">Ready?</div>
    <h2 class="cc-cta-heading reveal d1 mb-4">Join Your<br>Campus<br><em>Network</em></h2>
    <p class="cc-cta-sub mx-auto reveal d2">
      Start connecting with students, join study groups, and unlock new academic and career opportunities — completely free.
    </p>
    <div class="d-flex gap-3 justify-content-center flex-wrap mt-4 reveal d3">
      <a href="pages/register.php" class="cc-btn-cta-fill">Sign Up Free <i class="fas fa-arrow-right"></i></a>
      <a href="pages/login.php"    class="cc-btn-cta-ghost">Login</a>
    </div>
    <p class="cc-footer-copy mt-4 reveal d4" style="color:rgba(255,255,255,.25);">Free forever for students · No credit card required</p>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
