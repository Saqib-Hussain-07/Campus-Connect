<?php
// pages/messages.php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Messages';
requireLogin();

$db = getDB();
$me = (int)$_SESSION['user_id'];

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

// Fetch all conversations (people I've messaged or who messaged me)
$stConvs = $db->prepare(
    'SELECT u.id, u.name, u.department, u.is_online, u.skills,
            (SELECT body FROM messages WHERE (from_user=? AND to_user=u.id) OR (from_user=u.id AND to_user=?)
             ORDER BY sent_at DESC LIMIT 1) AS last_msg,
            (SELECT sent_at FROM messages WHERE (from_user=? AND to_user=u.id) OR (from_user=u.id AND to_user=?)
             ORDER BY sent_at DESC LIMIT 1) AS last_time,
            (SELECT COUNT(*) FROM messages WHERE from_user=u.id AND to_user=? AND is_read=0) AS unread
     FROM users u
     WHERE u.id IN (
         SELECT DISTINCT CASE WHEN from_user=? THEN to_user ELSE from_user END
         FROM messages WHERE from_user=? OR to_user=?
     )
     ORDER BY last_time DESC'
);
$stConvs->execute([$me,$me,$me,$me,$me,$me,$me,$me]);
$conversations = $stConvs->fetchAll();

// Active conversation
$withId   = (int)($_GET['with'] ?? ($conversations[0]['id'] ?? 0));
$withUser = null;
$thread   = [];

if ($withId > 0) {
    $stWith = $db->prepare('SELECT * FROM users WHERE id=?');
    $stWith->execute([$withId]);
    $withUser = $stWith->fetch();

    if ($withUser) {
        // Mark as read
        $db->prepare('UPDATE messages SET is_read=1 WHERE from_user=? AND to_user=? AND is_read=0')
           ->execute([$withId, $me]);

        // Fetch thread
        $stThread = $db->prepare(
            'SELECT * FROM messages WHERE (from_user=? AND to_user=?) OR (from_user=? AND to_user=?)
             ORDER BY sent_at ASC LIMIT 100'
        );
        $stThread->execute([$me,$withId,$withId,$me]);
        $thread = $stThread->fetchAll();
    }
}

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['body'])) {
    if (hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $body   = trim($_POST['body'] ?? '');
        $toUser = (int)($_POST['to_user'] ?? 0);
        if ($body !== '' && $toUser > 0 && $toUser !== $me) {
            $db->prepare('INSERT INTO messages (from_user, to_user, body) VALUES (?,?,?)')
               ->execute([$me, $toUser, $body]);
        }
    }
    header('Location: messages.php?with='.$withId);
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:92px;background:var(--paper);">
  <div class="container-fluid p-0" style="min-height:calc(100vh - 92px);">
    <div class="row g-0" style="min-height:calc(100vh - 92px);">

      <!-- Sidebar: conversation list -->
      <div class="col-lg-3 col-md-4" style="border-right:1.5px solid var(--ink);background:var(--white);display:flex;flex-direction:column;">
        <div style="padding:24px;border-bottom:1.5px solid var(--ink);">
          <div style="font-family:var(--font-display);font-size:1.8rem;color:var(--ink);line-height:1;margin-bottom:4px;">Messages</div>
          <div style="font-family:var(--font-mono);font-size:.65rem;color:#aaa;letter-spacing:.08em;">
            <?= count($conversations) ?> conversation<?= count($conversations) !== 1 ? 's' : '' ?>
          </div>
        </div>

        <!-- New message: find student -->
        <div style="padding:12px 16px;border-bottom:1px solid var(--cream);">
          <a href="students.php" style="display:flex;align-items:center;gap:8px;font-size:.78rem;color:var(--rust);font-family:var(--font-mono);text-transform:uppercase;letter-spacing:.05em;">
            <i class="fas fa-plus-circle"></i> New Conversation
          </a>
        </div>

        <div style="flex:1;overflow-y:auto;">
          <?php if ($conversations): ?>
            <?php foreach ($conversations as $conv): ?>
            <a href="messages.php?with=<?= $conv['id'] ?>"
               style="display:block;padding:16px;border-bottom:1px solid var(--cream);text-decoration:none;transition:background .2s;
                      background:<?= $conv['id'] == $withId ? 'var(--cream)' : 'transparent' ?>;">
              <div class="d-flex gap-3 align-items-start">
                <div class="position-relative flex-shrink-0">
                  <img src="https://picsum.photos/seed/<?= e($conv['name']) ?>/80/80"
                       style="width:42px;height:42px;object-fit:cover;border:2px solid <?= $conv['id'] == $withId ? 'var(--rust)' : 'var(--ink)' ?>;"
                       alt="">
                  <span style="position:absolute;bottom:-1px;right:-1px;width:10px;height:10px;
                               background:<?= $conv['is_online'] ? '#22c55e' : '#94a3b8' ?>;
                               border-radius:50%;border:2px solid var(--white);"></span>
                </div>
                <div class="flex-grow-1 min-width-0">
                  <div class="d-flex justify-content-between align-items-center">
                    <span style="font-weight:700;font-size:.84rem;color:var(--ink);"><?= e($conv['name']) ?></span>
                    <?php if ($conv['unread'] > 0): ?>
                    <span style="background:var(--rust);color:#fff;font-family:var(--font-mono);font-size:.6rem;
                                 padding:1px 6px;border-radius:20px;flex-shrink:0;"><?= (int)$conv['unread'] ?></span>
                    <?php endif; ?>
                  </div>
                  <div style="font-size:.7rem;color:#888;font-family:var(--font-mono);margin-bottom:2px;"><?= e($conv['department'] ?? '') ?></div>
                  <?php if ($conv['last_msg']): ?>
                  <div style="font-size:.76rem;color:#aaa;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px;">
                    <?= e(mb_strimwidth($conv['last_msg'],0,40,'…')) ?>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </a>
            <?php endforeach; ?>
          <?php else: ?>
            <div style="padding:32px;text-align:center;color:#aaa;">
              <i class="fas fa-comment-slash fa-2x mb-3"></i>
              <p style="font-size:.84rem;">No messages yet.<br><a href="students.php" style="color:var(--rust);">Find students to connect with.</a></p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Chat area -->
      <div class="col-lg-9 col-md-8 d-flex flex-column" style="background:var(--paper);height:calc(100vh - 92px);">

        <?php if ($withUser): ?>

        <!-- Chat header -->
        <div style="padding:16px 24px;border-bottom:1.5px solid var(--ink);background:var(--white);display:flex;align-items:center;gap:16px;flex-shrink:0;">
          <div class="position-relative">
            <img src="https://picsum.photos/seed/<?= e($withUser['name']) ?>/80/80"
                 style="width:44px;height:44px;object-fit:cover;border:2px solid var(--ink);" alt="">
            <span style="position:absolute;bottom:-1px;right:-1px;width:11px;height:11px;
                         background:<?= $withUser['is_online'] ? '#22c55e' : '#94a3b8' ?>;
                         border-radius:50%;border:2px solid var(--white);"></span>
          </div>
          <div class="flex-grow-1">
            <div style="font-weight:700;font-size:.96rem;color:var(--ink);"><?= e($withUser['name']) ?></div>
            <div style="font-family:var(--font-mono);font-size:.65rem;color:#aaa;">
              <?= e($withUser['department'] ?? '') ?>
              <?php if ($withUser['is_online']): ?> · <span style="color:#22c55e;">Online</span><?php endif; ?>
            </div>
          </div>
          <a href="view_student.php?id=<?= $withId ?>" style="padding:8px 16px;border:1.5px solid var(--ink);font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--ink);font-family:var(--font-body);">
            View Profile
          </a>
        </div>

        <!-- Messages thread -->
        <div id="chatThread" style="flex:1;overflow-y:auto;padding:24px;display:flex;flex-direction:column;gap:12px;">
          <?php if ($thread): ?>
            <?php foreach ($thread as $msg):
              $isMine = (int)$msg['from_user'] === $me;
            ?>
            <div style="display:flex;justify-content:<?= $isMine ? 'flex-end' : 'flex-start' ?>;">
              <?php if (!$isMine): ?>
              <img src="https://picsum.photos/seed/<?= e($withUser['name']) ?>/60/60"
                   style="width:30px;height:30px;object-fit:cover;border:1.5px solid var(--ink);border-radius:0;margin-right:10px;flex-shrink:0;align-self:flex-end;" alt="">
              <?php endif; ?>
              <div style="max-width:62%;">
                <div style="padding:12px 16px;
                            background:<?= $isMine ? 'var(--ink)' : 'var(--white)' ?>;
                            color:<?= $isMine ? 'var(--paper)' : 'var(--ink)' ?>;
                            border:1.5px solid var(--ink);
                            font-size:.87rem;line-height:1.6;">
                  <?= nl2br(e($msg['body'])) ?>
                </div>
                <div style="font-family:var(--font-mono);font-size:.6rem;color:#bbb;margin-top:4px;text-align:<?= $isMine ? 'right' : 'left' ?>;">
                  <?= date('g:i A · M j', strtotime($msg['sent_at'])) ?>
                  <?php if ($isMine && $msg['is_read']): ?>
                  <i class="fas fa-check-double ms-1" style="color:var(--moss);"></i>
                  <?php endif; ?>
                </div>
              </div>
              <?php if ($isMine): ?>
              <img src="https://picsum.photos/seed/<?= e(currentUser()['name']) ?>/60/60"
                   style="width:30px;height:30px;object-fit:cover;border:1.5px solid var(--ink);margin-left:10px;flex-shrink:0;align-self:flex-end;" alt="">
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#ccc;text-align:center;">
              <i class="fas fa-comment-dots fa-3x mb-3"></i>
              <p style="font-size:.9rem;max-width:240px;line-height:1.6;">
                No messages yet. Send the first message to start collaborating!
              </p>
            </div>
          <?php endif; ?>
        </div>

        <!-- Message input -->
        <div style="border-top:1.5px solid var(--ink);padding:16px 24px;background:var(--white);flex-shrink:0;">
          <form method="POST" action="" class="d-flex gap-0" id="msgForm">
            <input type="hidden" name="csrf"    value="<?= e($_SESSION['csrf']) ?>">
            <input type="hidden" name="to_user" value="<?= $withId ?>">
            <textarea name="body" id="msgInput" rows="1" required
                      placeholder="Write a message…"
                      style="flex:1;padding:12px 16px;border:1.5px solid var(--ink);border-right:none;
                             font-family:var(--font-body);font-size:.88rem;color:var(--ink);
                             resize:none;background:var(--paper);outline:none;line-height:1.5;"
                      onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();document.getElementById('msgForm').submit();}"></textarea>
            <button type="submit"
                    style="padding:12px 20px;background:var(--rust);border:1.5px solid var(--rust);
                           color:#fff;font-size:15px;cursor:pointer;transition:all .2s;flex-shrink:0;"
                    onmouseover="this.style.background='transparent';this.style.color='var(--rust)';"
                    onmouseout="this.style.background='var(--rust)';this.style.color='#fff';">
              <i class="fas fa-paper-plane"></i>
            </button>
          </form>
          <div style="font-family:var(--font-mono);font-size:.6rem;color:#ccc;margin-top:6px;">
            Press Enter to send · Shift+Enter for new line
          </div>
        </div>

        <?php else: ?>
        <!-- Empty state: no conversation selected -->
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#ccc;text-align:center;padding:40px;">
          <i class="fas fa-comments fa-4x mb-4"></i>
          <h3 style="font-family:var(--font-display);font-size:2rem;color:var(--ink);margin-bottom:8px;">Your Messages</h3>
          <p style="font-size:.9rem;max-width:300px;line-height:1.65;color:#aaa;">
            Select a conversation from the left, or find a student to start a new chat.
          </p>
          <a href="students.php" class="cc-btn-lg-dark mt-4"><span>Find Students</span><i class="fas fa-arrow-right"></i></a>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<script>
// Auto-scroll chat to bottom
const thread = document.getElementById('chatThread');
if (thread) thread.scrollTop = thread.scrollHeight;

// Auto-resize textarea
const ta = document.getElementById('msgInput');
if (ta) {
  ta.addEventListener('input', () => {
    ta.style.height = 'auto';
    ta.style.height = Math.min(ta.scrollHeight, 120) + 'px';
  });
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
