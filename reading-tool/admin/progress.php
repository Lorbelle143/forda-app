<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

// ── Session metadata ───────────────────────────────────────
$sessionMeta = [
    1 => ['name' => 'The Baseline',         'week' => 1, 'label' => 'Easy'],
    2 => ['name' => 'The Bridge',           'week' => 1, 'label' => 'Easy'],
    3 => ['name' => 'The Shift',            'week' => 2, 'label' => 'Medium'],
    4 => ['name' => 'The Flow',             'week' => 2, 'label' => 'Medium'],
    5 => ['name' => 'The Challenge',        'week' => 3, 'label' => 'Hard'],
    6 => ['name' => 'The Final Evaluation', 'week' => 3, 'label' => 'Advanced'],
];

// ── Get all reading materials grouped by session ───────────
$allMaterials = $pdo->query('SELECT id, session_number, reading_number FROM reading_materials ORDER BY session_number, reading_number')->fetchAll();
$matsBySession = [];
$totalPerSession = [];
foreach ($allMaterials as $m) {
    $matsBySession[$m['session_number']][] = $m['id'];
    $totalPerSession[$m['session_number']] = ($totalPerSession[$m['session_number']] ?? 0) + 1;
}
$totalReadings = count($allMaterials);

// ── Get all students ───────────────────────────────────────
$students = $pdo->query("SELECT id, name, email, created_at FROM users WHERE role='student' ORDER BY name")->fetchAll();

// ── Get all submitted material_ids per student ─────────────
$allRecs = $pdo->query("
    SELECT student_id, material_id, submitted_at, feedback,
           mispronounced_count, milestone
    FROM recordings
    ORDER BY submitted_at ASC
")->fetchAll();

// Index: $submitted[$student_id][$material_id] = [...]
$submitted = [];
foreach ($allRecs as $r) {
    $submitted[$r['student_id']][$r['material_id']] = [
        'submitted_at'        => $r['submitted_at'],
        'has_feedback'        => !empty($r['feedback']),
        'mispronounced_count' => $r['mispronounced_count'],
        'milestone'           => $r['milestone'],
    ];
}

// ── Compute per-student session progress ──────────────────
function getStudentProgress(int $studentId, array $matsBySession, array $totalPerSession, array $submitted): array {
    $progress = [];
    foreach ($matsBySession as $sn => $matIds) {
        $done = 0;
        foreach ($matIds as $mid) {
            if (isset($submitted[$studentId][$mid])) $done++;
        }
        $total = $totalPerSession[$sn];
        $progress[$sn] = [
            'done'      => $done,
            'total'     => $total,
            'complete'  => ($total > 0 && $done === $total),
            'pct'       => $total > 0 ? round($done / $total * 100) : 0,
        ];
    }
    return $progress;
}

// ── Filter ─────────────────────────────────────────────────
$filterStudent = (int)($_GET['student'] ?? 0);

$pageTitle = 'Student Progress';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ── Progress page styles ─────────────────────────────── */
.prog-summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px,1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

/* Student progress table */
.prog-table th { white-space: nowrap; }
.session-dots {
  display: flex;
  gap: .3rem;
  flex-wrap: wrap;
}
.session-dot {
  width: 28px; height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .65rem;
  font-weight: 800;
  border: 2px solid;
  flex-shrink: 0;
  cursor: default;
  position: relative;
}
.session-dot.complete  { background: linear-gradient(135deg,#7B1450,#A855A0); border-color: #A855A0; color: #fff; }
.session-dot.partial   { background: #FDF4FF; border-color: #E9B8D4; color: #7B1450; }
.session-dot.empty     { background: #F8FAFC; border-color: #E2E8F0; color: #CBD5E1; }

.prog-bar-wrap { width: 100%; background: #F0D0E8; border-radius: 999px; height: 6px; }
.prog-bar-fill { height: 6px; border-radius: 999px; background: linear-gradient(90deg,#7B1450,#A855A0); transition: width .4s; }

/* Detail view */
.detail-session-card {
  background: #fff;
  border: 1.5px solid #F0D0E8;
  border-radius: 12px;
  padding: 1.25rem;
  margin-bottom: 1rem;
}
.detail-session-card.complete { border-color: rgba(16,185,129,.3); background: #F0FDF9; }
.detail-session-header {
  display: flex;
  align-items: center;
  gap: .75rem;
  margin-bottom: .75rem;
}
.detail-sn-badge {
  width: 36px; height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg,#7B1450,#A855A0);
  color: #fff;
  font-size: .85rem;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.detail-sn-badge.complete { background: linear-gradient(135deg,#059669,#10B981); }
.reading-status-row {
  display: flex;
  align-items: center;
  gap: .6rem;
  padding: .4rem 0;
  border-bottom: 1px solid #F5E8F9;
  font-size: .82rem;
}
.reading-status-row:last-child { border-bottom: none; }
.reading-dot-sm {
  width: 20px; height: 20px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .6rem;
  font-weight: 700;
  flex-shrink: 0;
}
.reading-dot-sm.done     { background: linear-gradient(135deg,#7B1450,#A855A0); color: #fff; }
.reading-dot-sm.pending  { background: #F0D0E8; color: #7B1450; border: 1.5px solid #E9B8D4; }
.reading-dot-sm.empty    { background: #F1F5F9; color: #CBD5E1; border: 1.5px solid #E2E8F0; }
</style>

<div class="container">
  <div class="page-header">
    <div class="breadcrumb">
      <a href="dashboard.php">Dashboard</a>
      <span class="breadcrumb-sep">›</span>
      <span>Student Progress</span>
    </div>
    <div class="page-header-inner">
      <div>
        <h1 class="page-title">📊 Student Progress Tracker</h1>
        <p class="page-subtitle">Session completion status for all <?= count($students) ?> student(s)</p>
      </div>
      <?php if ($filterStudent): ?>
        <a href="progress.php" class="btn btn-ghost btn-sm">← All Students</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!$filterStudent): ?>
  <!-- ══════════════════════════════════════════════════════
       OVERVIEW — all students
  ══════════════════════════════════════════════════════ -->

  <!-- Summary stats -->
  <?php
  $completedAll = 0; $inProgress = 0; $notStarted = 0;
  foreach ($students as $s) {
      $recs = $submitted[$s['id']] ?? [];
      if (empty($recs)) { $notStarted++; }
      else {
          $prog = getStudentProgress($s['id'], $matsBySession, $totalPerSession, $submitted);
          $allDone = array_sum(array_column($prog, 'complete')) === count($sessionMeta);
          if ($allDone) $completedAll++; else $inProgress++;
      }
  }
  ?>
  <div class="prog-summary-grid">
    <div class="stat-card">
      <div class="stat-icon" style="background:#FDF4FF;font-size:1.4rem;width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;">👥</div>
      <div><div class="stat-value"><?= count($students) ?></div><div class="stat-label">Total Students</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#ECFDF5;font-size:1.4rem;width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;">✅</div>
      <div><div class="stat-value"><?= $completedAll ?></div><div class="stat-label">Completed All</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#FDF4FF;font-size:1.4rem;width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;">⏳</div>
      <div><div class="stat-value"><?= $inProgress ?></div><div class="stat-label">In Progress</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#F8FAFC;font-size:1.4rem;width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;">🔘</div>
      <div><div class="stat-value"><?= $notStarted ?></div><div class="stat-label">Not Started</div></div>
    </div>
  </div>

  <?php if (empty($students)): ?>
    <div class="empty-state">
      <div class="empty-state-icon">👥</div>
      <h3>No students yet</h3>
      <p>Students will appear here once they register.</p>
    </div>
  <?php else: ?>
  <div class="card">
    <div class="card-header">
      <span class="card-title">📋 All Students — Session Overview</span>
    </div>
    <div class="table-wrapper">
      <table class="prog-table">
        <thead>
          <tr>
            <th>Student</th>
            <th>Overall Progress</th>
            <th>Current Status</th>
            <th style="text-align:center;" colspan="6">Sessions (1–6)</th>
            <th>Actions</th>
          </tr>
          <tr style="background:#FDF4FF;">
            <th colspan="3"></th>
            <?php for ($s = 1; $s <= 6; $s++): ?>
            <th style="text-align:center;font-size:.7rem;color:#7B1450;padding:.4rem .5rem;">
              S<?= $s ?><br>
              <span style="font-weight:400;color:#A855A0;">Wk<?= $sessionMeta[$s]['week'] ?></span>
            </th>
            <?php endfor; ?>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $u):
            $prog       = getStudentProgress($u['id'], $matsBySession, $totalPerSession, $submitted);
            $sesssDone  = array_sum(array_column($prog, 'complete'));
            $totalRecs  = count($submitted[$u['id']] ?? []);
            $overallPct = $totalReadings > 0 ? round($totalRecs / $totalReadings * 100) : 0;

            // Current session = first incomplete session with at least one submission
            $currentSess = null;
            foreach ($prog as $sn => $p) {
                if (!$p['complete']) { $currentSess = $sn; break; }
            }
            $currentWeek = $currentSess ? $sessionMeta[$currentSess]['week'] : 3;
          ?>
          <tr>
            <td>
              <div style="font-weight:700;color:#1A0A12;"><?= e($u['name']) ?></div>
              <div class="text-muted text-sm"><?= e($u['email']) ?></div>
            </td>
            <td style="min-width:140px;">
              <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.3rem;">
                <span style="font-size:.85rem;font-weight:700;color:#7B1450;"><?= $sesssDone ?>/6</span>
                <span class="text-muted text-sm">sessions</span>
              </div>
              <div class="prog-bar-wrap">
                <div class="prog-bar-fill" style="width:<?= round($sesssDone/6*100) ?>%"></div>
              </div>
              <div class="text-muted text-sm" style="margin-top:.2rem;"><?= $totalRecs ?>/<?= $totalReadings ?> readings</div>
            </td>
            <td>
              <?php if ($totalRecs === 0): ?>
                <span style="font-size:.78rem;font-weight:700;color:#94A3B8;background:#F1F5F9;border-radius:999px;padding:.2rem .65rem;">Not Started</span>
              <?php elseif ($sesssDone === 6): ?>
                <span style="font-size:.78rem;font-weight:700;color:#065F46;background:#ECFDF5;border:1px solid #A7F3D0;border-radius:999px;padding:.2rem .65rem;">✓ Completed</span>
              <?php else: ?>
                <span style="font-size:.78rem;font-weight:700;color:#7B1450;background:#FDF4FF;border:1px solid #E9B8D4;border-radius:999px;padding:.2rem .65rem;">
                  Session <?= $currentSess ?>/6 · Week <?= $currentWeek ?>
                </span>
              <?php endif; ?>
            </td>
            <?php for ($sn = 1; $sn <= 6; $sn++):
              $p = $prog[$sn] ?? ['done'=>0,'total'=>0,'complete'=>false,'pct'=>0];
              if ($p['complete']) {
                  $dotClass = 'complete'; $dotLabel = '✓';
              } elseif ($p['done'] > 0) {
                  $dotClass = 'partial'; $dotLabel = $p['done'].'/'.$p['total'];
              } else {
                  $dotClass = 'empty'; $dotLabel = $sn;
              }
            ?>
            <td style="text-align:center;">
              <div class="session-dot <?= $dotClass ?>"
                   title="Session <?= $sn ?> — <?= e($sessionMeta[$sn]['name']) ?>: <?= $p['done'] ?>/<?= $p['total'] ?> readings">
                <?= $dotLabel ?>
              </div>
            </td>
            <?php endfor; ?>
            <td>
              <a href="progress.php?student=<?= $u['id'] ?>" class="btn btn-sm btn-outline">View Detail</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <?php else:
  // ══════════════════════════════════════════════════════
  // DETAIL VIEW — single student
  // ══════════════════════════════════════════════════════
  $student = null;
  foreach ($students as $s) { if ($s['id'] === $filterStudent) { $student = $s; break; } }
  if (!$student): ?>
    <div class="alert alert-error">Student not found. <a href="progress.php">Go back</a></div>
  <?php else:
    $prog      = getStudentProgress($student['id'], $matsBySession, $totalPerSession, $submitted);
    $sesssDone = array_sum(array_column($prog, 'complete'));
    $stuRecs   = $submitted[$student['id']] ?? [];
    $totalRecs = count($stuRecs);
    $overallPct = $totalReadings > 0 ? round($totalRecs / $totalReadings * 100) : 0;
  ?>

  <!-- Student header card -->
  <div class="card section" style="background:linear-gradient(135deg,#1A0A12,#3D0A2A);border:1px solid rgba(168,85,160,.2);">
    <div class="card-body" style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
      <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#7B1450,#A855A0);display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:900;color:#fff;flex-shrink:0;">
        <?= strtoupper(substr($student['name'],0,1)) ?>
      </div>
      <div style="flex:1;">
        <div style="font-size:1.2rem;font-weight:800;color:#fff;"><?= e($student['name']) ?></div>
        <div style="font-size:.85rem;color:#C9A0B8;"><?= e($student['email']) ?></div>
        <div style="font-size:.78rem;color:#8B5070;margin-top:.2rem;">Joined <?= date('M j, Y', strtotime($student['created_at'])) ?></div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:2rem;font-weight:900;color:#F0A0D8;"><?= $sesssDone ?>/6</div>
        <div style="font-size:.78rem;color:#C9A0B8;">Sessions Complete</div>
        <div style="font-size:.85rem;color:#A855A0;margin-top:.2rem;"><?= $totalRecs ?>/<?= $totalReadings ?> readings · <?= $overallPct ?>%</div>
      </div>
    </div>
    <!-- Overall progress bar -->
    <div style="padding:0 1.5rem 1.25rem;">
      <div style="height:8px;background:rgba(255,255,255,.1);border-radius:999px;overflow:hidden;">
        <div style="height:8px;width:<?= $overallPct ?>%;background:linear-gradient(90deg,#7B1450,#A855A0);border-radius:999px;transition:width .4s;"></div>
      </div>
    </div>
  </div>

  <!-- Week groups -->
  <?php
  $weekGroups = [1=>[1,2], 2=>[3,4], 3=>[5,6]];
  $weekNames  = [1=>'Week 1 — Starting Your Journey', 2=>'Week 2 — Building Your Skills', 3=>'Week 3 — Mastering the Craft'];
  foreach ($weekGroups as $wk => $sessions):
    $wkDone = 0;
    foreach ($sessions as $sn) { if ($prog[$sn]['complete'] ?? false) $wkDone++; }
  ?>
  <div class="section">
    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
      <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#7B1450,#A855A0);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.85rem;">W<?= $wk ?></div>
      <div>
        <div style="font-size:1rem;font-weight:800;color:#7B1450;"><?= $weekNames[$wk] ?></div>
        <div class="text-muted text-sm"><?= $wkDone ?>/2 sessions complete</div>
      </div>
    </div>

    <?php foreach ($sessions as $sn):
      $p    = $prog[$sn] ?? ['done'=>0,'total'=>0,'complete'=>false,'pct'=>0];
      $meta = $sessionMeta[$sn];
      $mats = $matsBySession[$sn] ?? [];
    ?>
    <div class="detail-session-card <?= $p['complete'] ? 'complete' : '' ?>">
      <div class="detail-session-header">
        <div class="detail-sn-badge <?= $p['complete'] ? 'complete' : '' ?>">
          <?= $p['complete'] ? '✓' : $sn ?>
        </div>
        <div style="flex:1;">
          <div style="font-weight:700;color:<?= $p['complete'] ? '#065F46' : '#1A0A12' ?>;">
            Session <?= $sn ?> — <?= e($meta['name']) ?>
          </div>
          <div style="font-size:.78rem;color:#64748B;">Week <?= $meta['week'] ?> · <?= $meta['label'] ?> Level</div>
        </div>
        <div style="text-align:right;">
          <div style="font-size:.9rem;font-weight:700;color:<?= $p['complete'] ? '#059669' : '#7B1450' ?>;">
            <?= $p['done'] ?>/<?= $p['total'] ?>
          </div>
          <div style="font-size:.72rem;color:#64748B;">readings</div>
        </div>
      </div>

      <!-- Session progress bar -->
      <div style="height:4px;background:#F0D0E8;border-radius:999px;margin-bottom:.75rem;overflow:hidden;">
        <div style="height:4px;width:<?= $p['pct'] ?>%;background:<?= $p['complete'] ? 'linear-gradient(90deg,#059669,#10B981)' : 'linear-gradient(90deg,#7B1450,#A855A0)' ?>;border-radius:999px;"></div>
      </div>

      <!-- Per-reading status -->
      <?php
      // Get all materials for this session
      $sessionMats = $pdo->prepare('SELECT id, reading_number, title FROM reading_materials WHERE session_number = ? ORDER BY reading_number');
      $sessionMats->execute([$sn]);
      $sessionMatsData = $sessionMats->fetchAll();
      ?>
      <?php foreach ($sessionMatsData as $mat):
        $recData = $stuRecs[$mat['id']] ?? null;
        if ($recData) {
            $dotClass = 'done';
            $statusText = 'Submitted ' . date('M j, Y', strtotime($recData['submitted_at']));
            $feedbackBadge = $recData['has_feedback']
                ? '<span style="font-size:.7rem;font-weight:700;color:#065F46;background:#ECFDF5;border-radius:999px;padding:.1rem .45rem;">✓ Feedback</span>'
                : '<span style="font-size:.7rem;font-weight:700;color:#92400E;background:#FFFBEB;border-radius:999px;padding:.1rem .45rem;">⏳ Pending</span>';
            // Score badge
            $msIcons = ['Excellent'=>'🌟','Great Progress'=>'👏','Nice Job'=>'💪','Brave Start'=>'🌱'];
            $msBgs   = ['Excellent'=>'#ECFDF5','Great Progress'=>'#EFF6FF','Nice Job'=>'#FFFBEB','Brave Start'=>'#FDF4FF'];
            $msClrs  = ['Excellent'=>'#065F46','Great Progress'=>'#1E40AF','Nice Job'=>'#92400E','Brave Start'=>'#7B1450'];
            $mc = $recData['milestone'] ?? '';
            $scoreBadge = '';
            if (!is_null($recData['mispronounced_count'])) {
                $icon = $msIcons[$mc] ?? '📊';
                $bg   = $msBgs[$mc]   ?? '#F8FAFC';
                $clr  = $msClrs[$mc]  ?? '#64748B';
                $scoreBadge = '<span style="font-size:.7rem;font-weight:700;color:'.$clr.';background:'.$bg.';border-radius:999px;padding:.1rem .5rem;">'
                    . $icon . ' ' . htmlspecialchars($mc) . ' · ' . $recData['mispronounced_count'] . ' errors</span>';
            }
        } else {
            $dotClass = 'empty';
            $statusText = 'Not submitted';
            $feedbackBadge = '';
            $scoreBadge = '';
        }
      ?>
      <div class="reading-status-row">
        <div class="reading-dot-sm <?= $dotClass ?>"><?= $dotClass === 'done' ? '✓' : $mat['reading_number'] ?></div>
        <span style="font-weight:600;color:#374151;">Reading <?= $mat['reading_number'] ?></span>
        <span class="text-muted" style="flex:1;font-size:.78rem;"><?= e($mat['title']) ?></span>
        <span style="font-size:.78rem;color:<?= $recData ? '#059669' : '#94A3B8' ?>;"><?= $statusText ?></span>
        <?= $feedbackBadge ?>
        <?= $scoreBadge ?? '' ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>

  <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
