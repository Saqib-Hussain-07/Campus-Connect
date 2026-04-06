<?php
// pages/events.php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Events & Hackathons';
$db = getDB();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$cat    = $_GET['cat'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$where  = ['1=1'];
$params = [];
if ($cat !== 'all') { $where[] = 'e.category=?'; $params[] = $cat; }
if ($search !== '') { $where[] = '(e.title LIKE ? OR e.description LIKE ?)'; $l='%'.$search.'%'; $params[]=$l; $params[]=$l; }

$whereSQL = implode(' AND ',$where);
$stEvents = $db->prepare(
    "SELECT e.*, u.name AS organiser_name,
            (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id=e.id AND r.status='going') AS going_count,
            (SELECT COUNT(*) FROM event_rsvps r WHERE r.event_id=e.id AND r.status='interested') AS interested_count
     FROM events e JOIN users u ON u.id=e.user_id
     WHERE $whereSQL ORDER BY e.event_date ASC"
);
$stEvents->execute($params);
$events = $stEvents->fetchAll();

// User's RSVP status
$myRsvps = [];
if (isLoggedIn()) {
    $stR = $db->prepare('SELECT event_id, status FROM event_rsvps WHERE user_id=?');
    $stR->execute([$_SESSION['user_id']]);
    foreach ($stR->fetchAll() as $r) $myRsvps[(int)$r['event_id']] = $r['status'];
}

$catLabels = ['all'=>'All','hackathon'=>'Hackathon','workshop'=>'Workshop','seminar'=>'Seminar','cultural'=>'Cultural','sports'=>'Sports','other'=>'Other'];
$catColors = ['hackathon'=>'var(--rust)','workshop'=>'var(--moss)','seminar'=>'var(--sky)','cultural'=>'#7c3aed','sports'=>'var(--gold)','other'=>'#888'];
$catIcons  = ['hackathon'=>'fa-code','workshop'=>'fa-screwdriver-wrench','seminar'=>'fa-chalkboard-teacher',
              'cultural'=>'fa-music','sports'=>'fa-trophy','other'=>'fa-calendar-days'];

// Split upcoming vs past
$now = date('Y-m-d H:i:s');
$upcoming = array_filter($events, fn($e) => $e['event_date'] >= $now);
$past     = array_filter($events, fn($e) => $e['event_date'] < $now);

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:92px;background:var(--paper);min-height:100vh;">

  <!-- Header -->
  <div style="background:var(--ink);padding:64px 0 50px;">
    <div class="container">
      <div class="row align-items-end g-4">
        <div class="col-lg-7">
          <div class="cc-section-label white-lbl reveal">Campus Calendar</div>
          <h1 class="cc-heading on-dark reveal d1">Events &amp; <em>Hackathons</em></h1>
          <p class="reveal d2" style="color:rgba(255,255,255,.4);max-width:480px;margin-top:12px;font-size:.95rem;line-height:1.65;">
            Discover workshops, hackathons, seminars, and campus events. RSVP and never miss out.
          </p>
        </div>
        <?php if (isLoggedIn()): ?>
        <div class="col-lg-5 text-lg-end reveal d2">
          <button data-bs-toggle="modal" data-bs-target="#createEventModal" class="cc-btn-lg-dark">
            <span>Create Event</span><i class="fas fa-plus"></i>
          </button>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="container py-5">

    <!-- Search + category filter -->
    <form method="GET" class="mb-4 reveal">
      <div class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="cc-form-label">Search Events</label>
          <input type="text" name="q" value="<?= e($search) ?>" class="cc-form-input" placeholder="Event name or keyword...">
          <input type="hidden" name="cat" value="<?= e($cat) ?>">
        </div>
        <div class="col-md-2"><button type="submit" class="cc-form-submit w-100">Search</button></div>
      </div>
    </form>

    <!-- Category chips -->
    <div class="d-flex flex-wrap gap-2 mb-5 reveal">
      <?php foreach ($catLabels as $k=>$v): ?>
      <a href="?cat=<?= $k ?>&q=<?= urlencode($search) ?>"
         style="padding:5px 16px;border:1.5px solid <?= ($cat===$k && $k!=='all') ? $catColors[$k] : ($cat===$k?'var(--ink)':'var(--cream)') ?>;
                background:<?= $cat===$k?($k==='all'?'var(--ink)':$catColors[$k]):'transparent' ?>;
                color:<?= $cat===$k?'#fff':'#888' ?>;
                font-family:var(--font-mono);font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;
                display:inline-flex;align-items:center;gap:6px;transition:all .2s;">
        <?php if ($k!=='all'): ?><i class="fas <?= $catIcons[$k] ?>"></i><?php endif; ?><?= $v ?>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Upcoming events -->
    <?php if ($upcoming): ?>
    <div style="font-family:var(--font-mono);font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:#aaa;margin-bottom:20px;">
      Upcoming Events (<?= count($upcoming) ?>)
    </div>
    <div class="row g-4 mb-5">
      <?php foreach (array_values($upcoming) as $i => $ev):
        $clr   = $catColors[$ev['category']] ?? '#888';
        $icon  = $catIcons[$ev['category']]  ?? 'fa-calendar';
        $myStatus = $myRsvps[(int)$ev['id']] ?? null;
        $evDate   = new DateTime($ev['event_date']);
      ?>
      <div class="col-lg-4 col-md-6">
        <div class="reveal d<?= ($i%3)+1 ?>"
             style="border:1.5px solid var(--ink);background:var(--white);overflow:hidden;
                    transition:transform .3s var(--ease-bounce),box-shadow .3s;"
             onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='5px 5px 0 var(--ink)'"
             onmouseout="this.style.transform='';this.style.boxShadow=''">

          <!-- Banner -->
          <div style="height:120px;background:var(--ink);position:relative;overflow:hidden;">
            <img src="https://picsum.photos/seed/<?= e($ev['banner_seed']) ?>/600/240"
                 style="width:100%;height:100%;object-fit:cover;filter:brightness(.3) saturate(.5);" alt="">
            <div style="position:absolute;top:12px;left:12px;padding:3px 10px;background:<?= $clr ?>;
                        font-family:var(--font-mono);font-size:.6rem;color:#fff;text-transform:uppercase;
                        letter-spacing:.08em;display:inline-flex;align-items:center;gap:5px;">
              <i class="fas <?= $icon ?>"></i><?= e($catLabels[$ev['category']]??'') ?>
            </div>
            <?php if ($ev['is_online']): ?>
            <div style="position:absolute;top:12px;right:12px;padding:3px 10px;background:var(--moss);
                        font-family:var(--font-mono);font-size:.6rem;color:#fff;text-transform:uppercase;letter-spacing:.08em;">
              <i class="fas fa-wifi me-1"></i>Online
            </div>
            <?php endif; ?>
            <!-- Big date display -->
            <div style="position:absolute;bottom:10px;right:12px;text-align:right;">
              <div style="font-family:var(--font-display);font-size:2.2rem;color:rgba(255,255,255,.15);line-height:1;">
                <?= $evDate->format('d') ?>
              </div>
            </div>
          </div>

          <div style="padding:22px;">
            <!-- Title & date -->
            <h3 style="font-family:var(--font-body);font-weight:700;font-size:.96rem;color:var(--ink);margin-bottom:6px;line-height:1.3;">
              <?= e($ev['title']) ?>
            </h3>
            <div style="font-family:var(--font-mono);font-size:.68rem;color:var(--rust);margin-bottom:8px;display:flex;align-items:center;gap:6px;">
              <i class="fas fa-calendar"></i><?= $evDate->format('D, M j Y · g:i A') ?>
            </div>
            <?php if ($ev['venue']): ?>
            <div style="font-size:.76rem;color:#888;margin-bottom:10px;display:flex;align-items:center;gap:5px;">
              <i class="fas fa-location-dot" style="color:var(--rust);font-size:10px;"></i><?= e($ev['venue']) ?>
            </div>
            <?php endif; ?>
            <p style="font-size:.82rem;line-height:1.6;color:#555;margin-bottom:16px;">
              <?= e(mb_strimwidth($ev['description']??'',0,95,'…')) ?>
            </p>

            <!-- Attendee count -->
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
              <span style="font-family:var(--font-mono);font-size:.68rem;color:#888;">
                <i class="fas fa-user-check me-1" style="color:var(--moss);"></i><?= (int)$ev['going_count'] ?> going
              </span>
              <span style="font-family:var(--font-mono);font-size:.68rem;color:#888;">
                <i class="fas fa-star me-1" style="color:var(--gold);"></i><?= (int)$ev['interested_count'] ?> interested
              </span>
              <?php if ($ev['max_attendees'] > 0): ?>
              <span style="font-family:var(--font-mono);font-size:.68rem;color:#888;">
                <i class="fas fa-users me-1"></i>Max <?= (int)$ev['max_attendees'] ?>
              </span>
              <?php endif; ?>
            </div>

            <!-- RSVP buttons -->
            <?php if (isLoggedIn()): ?>
            <div class="d-flex gap-2">
              <?php foreach (['going'=>['fa-check','Going','var(--moss)'],'interested'=>['fa-star','Interested','var(--gold)']] as $st=>[$ic,$lbl,$clrBtn]): ?>
              <form method="POST" action="rsvp_event.php" style="flex:1;">
                <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                <input type="hidden" name="status"   value="<?= $st ?>">
                <input type="hidden" name="csrf"     value="<?= e($_SESSION['csrf']) ?>">
                <button type="submit"
                        style="width:100%;padding:8px 4px;border:1.5px solid <?= $myStatus===$st?$clrBtn:'var(--cream)' ?>;
                               background:<?= $myStatus===$st?$clrBtn:'transparent' ?>;
                               color:<?= $myStatus===$st?'#fff':'#888' ?>;
                               font-family:var(--font-body);font-size:.72rem;font-weight:600;
                               text-transform:uppercase;letter-spacing:.04em;cursor:pointer;transition:all .2s;">
                  <i class="fas <?= $ic ?> me-1"></i><?= $lbl ?>
                </button>
              </form>
              <?php endforeach; ?>
            </div>
            <?php else: ?>
            <a href="login.php" class="cc-student-btn">Login to RSVP <i class="fas fa-arrow-right"></i></a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Past events -->
    <?php if ($past): ?>
    <div style="font-family:var(--font-mono);font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:#aaa;margin-bottom:20px;border-top:1px solid var(--cream);padding-top:32px;">
      Past Events (<?= count($past) ?>)
    </div>
    <div class="row g-3">
      <?php foreach (array_values($past) as $ev): ?>
      <div class="col-lg-4 col-md-6">
        <div style="border:1.5px solid var(--cream);background:var(--white);padding:20px;opacity:.65;">
          <div style="font-family:var(--font-mono);font-size:.6rem;color:#aaa;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">
            Past · <?= date('M j, Y', strtotime($ev['event_date'])) ?>
          </div>
          <div style="font-weight:700;font-size:.9rem;color:#888;"><?= e($ev['title']) ?></div>
          <div style="font-family:var(--font-mono);font-size:.65rem;color:#ccc;margin-top:4px;">
            <?= (int)$ev['going_count'] ?> attended
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!$upcoming && !$past): ?>
    <div class="text-center py-5" style="border:1.5px dashed var(--cream);">
      <i class="fas fa-calendar-xmark fa-3x mb-3" style="color:#ccc;"></i>
      <p style="color:#aaa;font-size:.9rem;">No events found.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Create Event Modal -->
<?php if (isLoggedIn()): ?>
<div class="modal fade" id="createEventModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content cc-modal">
      <div class="modal-header cc-modal-header">
        <h5 class="modal-title">Create New Event</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <form method="POST" action="create_event.php">
          <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
          <div class="row g-3">
            <div class="col-12"><label class="cc-form-label">Event Title *</label>
              <input type="text" name="title" required class="cc-form-input" placeholder="e.g. HackFest 2025"></div>
            <div class="col-md-6"><label class="cc-form-label">Category</label>
              <select name="category" class="cc-form-input">
                <?php foreach ($catLabels as $k=>$v): if($k==='all')continue; ?>
                <option value="<?= $k ?>"><?= $v ?></option>
                <?php endforeach; ?>
              </select></div>
            <div class="col-md-3"><label class="cc-form-label">Event Date & Time *</label>
              <input type="datetime-local" name="event_date" required class="cc-form-input"></div>
            <div class="col-md-3"><label class="cc-form-label">Reg. Deadline</label>
              <input type="datetime-local" name="registration_deadline" class="cc-form-input"></div>
            <div class="col-md-6"><label class="cc-form-label">Venue</label>
              <input type="text" name="venue" class="cc-form-input" placeholder="Hall / Building / Online"></div>
            <div class="col-md-3"><label class="cc-form-label">Max Attendees</label>
              <input type="number" name="max_attendees" value="0" min="0" class="cc-form-input"></div>
            <div class="col-md-3"><label class="cc-form-label">Format</label>
              <select name="is_online" class="cc-form-input">
                <option value="0">In-Person</option><option value="1">Online</option>
              </select></div>
            <div class="col-12"><label class="cc-form-label">Description *</label>
              <textarea name="description" rows="3" required class="cc-form-input" style="resize:vertical;"
                        placeholder="Describe the event, prizes, eligibility, etc."></textarea></div>
            <div class="col-12"><label class="cc-form-label">Registration Link</label>
              <input type="url" name="registration_link" class="cc-form-input" placeholder="https://..."></div>
            <div class="col-12"><button type="submit" class="cc-form-submit">Create Event <i class="fas fa-arrow-right ms-2"></i></button></div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
