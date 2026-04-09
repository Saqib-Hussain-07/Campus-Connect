<?php
// pages/leaderboard.php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Leaderboard';
$db = getDB();

// Top by connections
$topConnected = $db->query(
    'SELECT u.id, u.name, u.department, u.university, u.is_online, u.skills,
            COUNT(c.id) AS conn_count
     FROM users u LEFT JOIN connections c
       ON (c.from_user=u.id OR c.to_user=u.id) AND c.status="accepted"
     WHERE u.is_verified=1 GROUP BY u.id ORDER BY conn_count DESC LIMIT 10'
)->fetchAll();

// Top by project likes
$topBuilders = $db->query(
    'SELECT u.id, u.name, u.department, u.university,
            COUNT(DISTINCT p.id) AS project_count,
            COALESCE(SUM(pl.cnt),0) AS total_likes
     FROM users u
     LEFT JOIN projects p ON p.user_id=u.id
     LEFT JOIN (SELECT project_id, COUNT(*) AS cnt FROM project_likes GROUP BY project_id) pl ON pl.project_id=p.id
     WHERE u.is_verified=1 GROUP BY u.id HAVING project_count > 0 ORDER BY total_likes DESC, project_count DESC LIMIT 10'
)->fetchAll();

// Top by endorsements received
$topEndorsed = $db->query(
    'SELECT u.id, u.name, u.department, u.university, u.skills,
            COUNT(e.id) AS endorse_count,
            GROUP_CONCAT(DISTINCT e.skill ORDER BY e.skill SEPARATOR ",") AS endorsed_skills
     FROM users u LEFT JOIN endorsements e ON e.endorsed_id=u.id
     WHERE u.is_verified=1 GROUP BY u.id ORDER BY endorse_count DESC LIMIT 10'
)->fetchAll();

// Top by group participation
$topGroupers = $db->query(
    'SELECT u.id, u.name, u.department, u.university,
            COUNT(gm.id) AS group_count
     FROM users u LEFT JOIN group_members gm ON gm.user_id=u.id
     WHERE u.is_verified=1 GROUP BY u.id ORDER BY group_count DESC LIMIT 10'
)->fetchAll();

$tab = $_GET['tab'] ?? 'connections';

include __DIR__ . '/../includes/header.php';
?>

<div style="margin-top:92px;background:var(--paper);min-height:100vh;">

  <div style="background:var(--ink);padding:64px 0 50px;">
    <div class="container">
      <div class="cc-section-label white-lbl reveal">Campus Rankings</div>
      <h1 class="cc-heading on-dark reveal d1">Leader<em>board</em></h1>
      <p class="reveal d2" style="color:rgba(255,255,255,.4);max-width:480px;margin-top:12px;font-size:.95rem;line-height:1.65;">
        The most connected, most active, and most endorsed students on campus.
      </p>
    </div>
  </div>

  <div class="container py-5">

    <!-- Tabs -->
    <div class="cc-tab-strip mb-5 reveal">
      <?php
      $tabs = ['connections'=>['fa-user-check','Most Connected'],
               'builders'   =>['fa-code','Top Builders'],
               'endorsed'   =>['fa-award','Most Endorsed'],
               'groupers'   =>['fa-layer-group','Most Active']];
      foreach ($tabs as $k=>[$ic,$lbl]):
      ?>
      <a href="?tab=<?= $k ?>" class="cc-tab-btn <?= $tab===$k?'active':'' ?>">
        <i class="fas <?= $ic ?> me-1"></i><?= $lbl ?>
      </a>
      <?php endforeach; ?>
    </div>

    <?php
    // Select data for active tab
    $data = match($tab) {
        'builders' => $topBuilders,
        'endorsed' => $topEndorsed,
        'groupers' => $topGroupers,
        default    => $topConnected
    };
    $medalColors = ['var(--gold)','#94a3b8','#c9843c'];
    $medalLabels = ['🥇','🥈','🥉'];
    ?>

    <!-- Top 3 podium -->
    <?php if (count($data) >= 3): ?>
    <div class="row justify-content-center g-3 mb-5">
      <?php
      $podium = [
        ['col' => 'col-lg-3 col-md-4', 'order' => 1, 'rank' => 0, 'height' => '180px'],  // 1st
        ['col' => 'col-lg-3 col-md-4', 'order' => 0, 'rank' => 1, 'height' => '140px'],  // 2nd (left)
        ['col' => 'col-lg-3 col-md-4', 'order' => 2, 'rank' => 2, 'height' => '120px'],  // 3rd (right)
      ];
      usort($podium, fn($a,$b)=>$a['order']<=>$b['order']);
      foreach ($podium as $p):
        $s = $data[$p['rank']];
        $stat = match($tab) {
            'builders' => $s['total_likes'].' likes on '.$s['project_count'].' project'.($s['project_count']!=1?'s':''),
            'endorsed' => $s['endorse_count'].' endorsement'.($s['endorse_count']!=1?'s':''),
            'groupers' => $s['group_count'].' group'.($s['group_count']!=1?'s':''),
            default    => $s['conn_count'].' connection'.($s['conn_count']!=1?'s':''),
        };
      ?>
      <div class="<?= $p['col'] ?>">
        <div style="border:1.5px solid var(--ink);background:var(--white);padding:28px;text-align:center;
                    <?= $p['rank']===0?'border-color:var(--gold);background:rgba(201,168,76,.05);':'' ?>
                    transition:transform .3s var(--ease-bounce);"
             onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform=''">
          <div style="font-size:1.8rem;margin-bottom:8px;"><?= $medalLabels[$p['rank']] ?></div>
          <div style="position:relative;display:inline-block;margin-bottom:12px;">
            <img src="https://picsum.photos/seed/<?= e($s['name']) ?>/120/120"
                 style="width:72px;height:72px;object-fit:cover;
                        border:3px solid <?= $medalColors[$p['rank']] ?>;" alt="">
          </div>
          <div style="font-weight:700;font-size:.96rem;color:var(--ink);"><?= e($s['name']) ?></div>
          <div style="font-size:.72rem;color:#888;font-family:var(--font-mono);margin-bottom:8px;"><?= e($s['department']??'') ?></div>
          <div style="font-family:var(--font-display);font-size:1.4rem;color:<?= $medalColors[$p['rank']] ?>;"><?= e($stat) ?></div>
          <a href="view_student.php?id=<?= $s['id'] ?>" style="display:block;margin-top:12px;font-size:.72rem;color:var(--rust);font-family:var(--font-mono);">View Profile →</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Full rankings table -->
    <div style="border:1.5px solid var(--ink);background:var(--white);overflow:hidden;" class="reveal">
      <div style="padding:20px 24px;border-bottom:1px solid var(--cream);background:var(--paper);">
        <div style="font-family:var(--font-mono);font-size:.65rem;letter-spacing:.12em;text-transform:uppercase;color:#aaa;">
          Full Rankings · <?= ucfirst($tab) ?>
        </div>
      </div>
      <div class="table-responsive">
        <table style="width:100%;border-collapse:collapse;">
          <thead>
            <tr style="border-bottom:1.5px solid var(--ink);">
              <th style="padding:12px 24px;font-family:var(--font-mono);font-size:.62rem;letter-spacing:.1em;text-transform:uppercase;color:#aaa;text-align:left;width:50px;">#</th>
              <th style="padding:12px 16px;font-family:var(--font-mono);font-size:.62rem;letter-spacing:.1em;text-transform:uppercase;color:#aaa;text-align:left;">Student</th>
              <th style="padding:12px 16px;font-family:var(--font-mono);font-size:.62rem;letter-spacing:.1em;text-transform:uppercase;color:#aaa;text-align:left;">Department</th>
              <th style="padding:12px 16px;font-family:var(--font-mono);font-size:.62rem;letter-spacing:.1em;text-transform:uppercase;color:#aaa;text-align:right;">Score</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($data as $rank=>$s):
              $score = match($tab) {
                  'builders' => $s['total_likes'].' likes',
                  'endorsed' => $s['endorse_count'].' endorsements',
                  'groupers' => $s['group_count'].' groups',
                  default    => $s['conn_count'].' connections',
              };
            ?>
            <tr style="border-bottom:1px solid var(--cream);transition:background .15s;"
                onmouseover="this.style.background='var(--paper)'" onmouseout="this.style.background=''">
              <td style="padding:14px 24px;font-family:var(--font-display);font-size:1.2rem;
                         color:<?= $rank<3?$medalColors[$rank]:'#ccc' ?>;">
                <?= $rank+1 ?>
              </td>
              <td style="padding:14px 16px;">
                <div class="d-flex align-items-center gap-2">
                  <img src="https://picsum.photos/seed/<?= e($s['name']) ?>/60/60"
                       style="width:36px;height:36px;object-fit:cover;border:1.5px solid var(--ink);" alt="">
                  <div>
                    <a href="view_student.php?id=<?= $s['id'] ?>"
                       style="font-weight:700;font-size:.88rem;color:var(--ink);"><?= e($s['name']) ?></a>
                    <?php if ($tab==='endorsed' && !empty($s['endorsed_skills'])): ?>
                    <div style="font-size:.65rem;color:#aaa;font-family:var(--font-mono);">
                      <?= e(implode(', ', array_slice(explode(',',$s['endorsed_skills']),0,3))) ?>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td style="padding:14px 16px;font-size:.78rem;color:#888;font-family:var(--font-mono);">
                <?= e($s['department']??'') ?>
              </td>
              <td style="padding:14px 24px;text-align:right;">
                <span style="font-family:var(--font-display);font-size:1.3rem;color:var(--rust);"><?= e($score) ?></span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
