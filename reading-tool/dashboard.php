<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();
if (isAdmin()) { header('Location: admin/dashboard.php'); exit; }

// ── AIM session metadata ───────────────────────────────────────────────────
$sessionMeta = [
    1 => ['name' => 'The Baseline',        'week' => 1, 'level' => 'beginner',     'label' => 'Easy'],
    2 => ['name' => 'The Bridge',          'week' => 1, 'level' => 'beginner',     'label' => 'Easy'],
    3 => ['name' => 'The Shift',           'week' => 2, 'level' => 'intermediate', 'label' => 'Medium'],
    4 => ['name' => 'The Flow',            'week' => 2, 'level' => 'intermediate', 'label' => 'Medium'],
    5 => ['name' => 'The Challenge',       'week' => 3, 'level' => 'advanced',     'label' => 'Hard'],
    6 => ['name' => 'The Final Evaluation','week' => 3, 'level' => 'advanced',     'label' => 'Advanced'],
];

// ── Get all materials grouped by session ──────────────────────────────────
$allMaterials = $pdo->query('
    SELECT * FROM reading_materials
    ORDER BY session_number ASC, reading_number ASC
')->fetchAll();

$bySession = [];
foreach ($allMaterials as $m) {
    $bySession[$m['session_number']][] = $m;
}

// ── Get this student's submitted material IDs ──────────────────────────────
$stmt = $pdo->prepare('SELECT DISTINCT material_id FROM recordings WHERE student_id = ?');
$stmt->execute([currentUserId()]);
$recordedIds = array_column($stmt->fetchAll(), 'material_id');

// ── Stats ──────────────────────────────────────────────────────────────────
$stmt = $pdo->prepare('SELECT COUNT(*) FROM recordings WHERE student_id = ?');
$stmt->execute([currentUserId()]);
$recCount = $stmt->fetchColumn();

// Count completed sessions (all readings in a session submitted)
$completedSessions = 0;
foreach ($bySession as $sn => $mats) {
    $allDone = true;
    foreach ($mats as $m) {
        if (!in_array($m['id'], $recordedIds)) { $allDone = false; break; }
    }
    if ($allDone && count($mats) > 0) $completedSessions++;
}

// Active session = first session with at least one unsubmitted reading
$activeSession = null;
foreach ($sessionMeta as $sn => $meta) {
    $mats = $bySession[$sn] ?? [];
    foreach ($mats as $m) {
        if (!in_array($m['id'], $recordedIds)) { $activeSession = $sn; break 2; }
    }
}

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ── Session Tracker ─────────────────────────────────────── */
.tracker-wrap {
  background: linear-gradient(135deg, #1A0A12, #3D0A2A);
  border-radius: 16px;
  padding: 2rem;
  margin-bottom: 2rem;
  border: 1px solid rgba(168,85,160,.2);
  position: relative;
  overflow: hidden;
}
.tracker-wrap::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(168,85,160,.12) 0%, transparent 70%);
  pointer-events: none;
}
.tracker-title {
  font-size: 1rem;
  font-weight: 800;
  color: #F0D0E8;
  margin-bottom: .25rem;
  position: relative;
}
.tracker-subtitle {
  font-size: .8rem;
  color: #8B5070;
  margin-bottom: 1.5rem;
  position: relative;
}
.tracker-weeks {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  position: relative;
}
.tracker-week {
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(168,85,160,.15);
  border-radius: 12px;
  padding: 1rem;
}
.tracker-week-label {
  font-size: .7rem;
  font-weight: 700;
  color: #A855A0;
  text-transform: uppercase;
  letter-spacing: .08em;
  margin-bottom: .75rem;
}
.tracker-week-name {
  font-size: .8rem;
  color: #C9A0B8;
  margin-bottom: .75rem;
}
.tracker-sessions {
  display: flex;
  flex-direction: column;
  gap: .5rem;
}
.tracker-session {
  border-radius: 8px;
  padding: .6rem .75rem;
  border: 1px solid rgba(168,85,160,.15);
  background: rgba(255,255,255,.03);
  cursor: pointer;
  transition: all .15s;
  text-decoration: none;
  display: block;
}
.tracker-session:hover { border-color: #A855A0; background: rgba(168,85,160,.08); text-decoration: none; }
.tracker-session.active { border-color: #A855A0; background: rgba(168,85,160,.12); }
.tracker-session.completed { border-color: rgba(16,185,129,.3); background: rgba(16,185,129,.06); }
.tracker-session-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: .4rem;
}
.tracker-session-name {
  font-size: .8rem;
  font-weight: 700;
  color: #E0A0C8;
}
.tracker-session.completed .tracker-session-name { color: #6EE7B7; }
.tracker-session.active .tracker-session-name { color: #F0A0D8; }
.tracker-session-badge {
  font-size: .65rem;
  font-weight: 700;
  padding: .15rem .5rem;
  border-radius: 999px;
}
.badge-done-sm { background: rgba(16,185,129,.15); color: #6EE7B7; }
.badge-active-sm { background: rgba(168,85,160,.2); color: #F0A0D8; }
.badge-locked-sm { background: rgba(255,255,255,.06); color: #8B5070; }

/* Reading dots */
.tracker-readings {
  display: flex;
  gap: .35rem;
  flex-wrap: wrap;
}
.reading-dot {
  width: 22px; height: 22px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .6rem;
  font-weight: 700;
  border: 1.5px solid rgba(168,85,160,.3);
  color: #8B5070;
  background: rgba(255,255,255,.03);
  transition: all .15s;
}
.reading-dot.done {
  background: linear-gradient(135deg, #7B1450, #A855A0);
  border-color: #A855A0;
  color: #fff;
}
.reading-dot.done::after { content: '✓'; }
.reading-dot:not(.done) { /* show number */ }

/* ── Session Cards ───────────────────────────────────────── */
.session-section { margin-bottom: 2.5rem; }
.session-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
  padding-bottom: .75rem;
  border-bottom: 2px solid #F0D0E8;
}
.session-num-badge {
  width: 40px; height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #7B1450, #A855A0);
  color: #fff;
  font-size: .9rem;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(123,20,80,.3);
}
.session-num-badge.done {
  background: linear-gradient(135deg, #059669, #10B981);
  box-shadow: 0 4px 12px rgba(16,185,129,.3);
}
.session-header-info h2 {
  font-size: 1.05rem;
  font-weight: 800;
  color: #1A0A12;
  margin-bottom: .1rem;
}
.session-header-meta {
  display: flex;
  align-items: center;
  gap: .5rem;
  flex-wrap: wrap;
}
.session-week-tag {
  font-size: .72rem;
  font-weight: 700;
  color: #7B1450;
  background: #FDF4FF;
  border: 1px solid #E9B8D4;
  border-radius: 999px;
  padding: .15rem .6rem;
}
.session-progress-text {
  font-size: .75rem;
  color: #64748B;
}

/* Reading cards */
.readings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
}
.reading-card {
  background: #fff;
  border: 1.5px solid #F0D0E8;
  border-radius: 12px;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: .6rem;
  transition: box-shadow .2s, transform .2s, border-color .2s;
  position: relative;
}
.reading-card:hover {
  box-shadow: 0 8px 24px rgba(123,20,80,.1);
  transform: translateY(-2px);
  border-color: #A855A0;
}
.reading-card.submitted {
  border-color: rgba(16,185,129,.3);
  background: #F0FDF9;
}
.reading-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.reading-num-tag {
  font-size: .72rem;
  font-weight: 700;
  color: #7B1450;
  background: #FDF4FF;
  border: 1px solid #E9B8D4;
  border-radius: 6px;
  padding: .2rem .55rem;
}
.submitted-check {
  font-size: .75rem;
  font-weight: 700;
  color: #059669;
  display: flex;
  align-items: center;
  gap: .25rem;
}
.reading-card-title {
  font-size: .95rem;
  font-weight: 700;
  color: #1A0A12;
}
.reading-card-preview {
  font-size: .82rem;
  color: #64748B;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.reading-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
  padding-top: .6rem;
  border-top: 1px solid #F5E8F9;
}

/* Progress bar */
.session-progress-bar {
  height: 4px;
  background: #F0D0E8;
  border-radius: 999px;
  margin-bottom: 1rem;
  overflow: hidden;
}
.session-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #7B1450, #A855A0);
  border-radius: 999px;
  transition: width .4s ease;
}

@media(max-width: 768px) {
  .tracker-weeks { grid-template-columns: 1fr; }
}
</style>

<div class="container">
  <div class="page-header">
    <div class="page-header-inner">
      <div>
        <h1 class="page-title">👋 Welcome, <?= e(currentUserName()) ?>!</h1>
        <p class="page-subtitle">Follow the 4-step cycle: Listen → Record → Compare → Submit</p>
      </div>
      <a href="student/my-recordings.php" class="btn btn-outline">🎤 My Recordings (<?= $recCount ?>)</a>
    </div>
  </div>

  <!-- ── Overall Stats ── -->
  <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:1.5rem;">
    <div class="stat-card">
      <div class="stat-icon" style="background:#FDF4FF;font-size:1.6rem;width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;">📅</div>
      <div><div class="stat-value"><?= $completedSessions ?>/6</div><div class="stat-label">Sessions Done</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#FDF4FF;font-size:1.6rem;width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;">🎤</div>
      <div><div class="stat-value"><?= $recCount ?></div><div class="stat-label">Recordings</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#FDF4FF;font-size:1.6rem;width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;">📖</div>
      <div><div class="stat-value"><?= count($recordedIds) ?>/<?= count($allMaterials) ?></div><div class="stat-label">Readings Done</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon" style="background:#FDF4FF;font-size:1.6rem;width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;">🗓️</div>
      <div><div class="stat-value">Wk <?= min(3, (int)ceil(max(1,$completedSessions+1)/2)) ?></div><div class="stat-label">Current Week</div></div>
    </div>
  </div>

  <!-- ── Session Tracker ── -->
  <div class="tracker-wrap">
    <div class="tracker-title">🗺️ Session Progress Tracker</div>
    <div class="tracker-subtitle">Track your journey across all 6 sessions and 3 weeks</div>

    <div class="tracker-weeks">
      <?php
      $weekGroups = [
          1 => [1, 2],
          2 => [3, 4],
          3 => [5, 6],
      ];
      $weekNames = [
          1 => ['Week 1', 'Starting Your Journey', 'Easy Level'],
          2 => ['Week 2', 'Building Your Skills',  'Medium Level'],
          3 => ['Week 3', 'Mastering the Craft',   'Hard & Advanced'],
      ];
      foreach ($weekGroups as $wk => $sessions):
        [$wkLabel, $wkName, $wkDiff] = $weekNames[$wk];
      ?>
      <div class="tracker-week">
        <div class="tracker-week-label"><?= $wkLabel ?> · <?= $wkDiff ?></div>
        <div class="tracker-week-name"><?= $wkName ?></div>
        <div class="tracker-sessions">
          <?php foreach ($sessions as $sn):
            $meta  = $sessionMeta[$sn];
            $mats  = $bySession[$sn] ?? [];
            $total = count($mats);
            $done  = 0;
            foreach ($mats as $m) { if (in_array($m['id'], $recordedIds)) $done++; }
            $isComplete = ($total > 0 && $done === $total);
            $isActive   = ($sn === $activeSession);
            $cls = $isComplete ? 'completed' : ($isActive ? 'active' : '');
          ?>
          <a href="#session-<?= $sn ?>" class="tracker-session <?= $cls ?>">
            <div class="tracker-session-header">
              <span class="tracker-session-name">Session <?= $sn ?> — <?= e($meta['name']) ?></span>
              <?php if ($isComplete): ?>
                <span class="tracker-session-badge badge-done-sm">✓ Done</span>
              <?php elseif ($isActive): ?>
                <span class="tracker-session-badge badge-active-sm">▶ Active</span>
              <?php else: ?>
                <span class="tracker-session-badge badge-locked-sm"><?= $done ?>/<?= $total ?></span>
              <?php endif; ?>
            </div>
            <div class="tracker-readings">
              <?php foreach ($mats as $m): ?>
                <div class="reading-dot <?= in_array($m['id'], $recordedIds) ? 'done' : '' ?>"
                     title="Reading <?= $m['reading_number'] ?>">
                  <?= !in_array($m['id'], $recordedIds) ? $m['reading_number'] : '' ?>
                </div>
              <?php endforeach; ?>
              <?php if (empty($mats)): ?>
                <span style="font-size:.72rem;color:#8B5070;">No readings yet</span>
              <?php endif; ?>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ── Sessions & Readings ── -->
  <?php foreach ($sessionMeta as $sn => $meta):
    $mats  = $bySession[$sn] ?? [];
    $total = count($mats);
    $done  = 0;
    foreach ($mats as $m) { if (in_array($m['id'], $recordedIds)) $done++; }
    $isComplete = ($total > 0 && $done === $total);
    $pct = $total > 0 ? round($done / $total * 100) : 0;
    $wkLabel = 'Week ' . $meta['week'];
    $levelBadge = $meta['level'];
  ?>
  <div class="session-section" id="session-<?= $sn ?>">
    <div class="session-header">
      <div class="session-num-badge <?= $isComplete ? 'done' : '' ?>">
        <?= $isComplete ? '✓' : $sn ?>
      </div>
      <div class="session-header-info">
        <h2>Session <?= $sn ?> — <?= e($meta['name']) ?></h2>
        <div class="session-header-meta">
          <span class="session-week-tag"><?= $wkLabel ?> · <?= $meta['label'] ?></span>
          <span class="session-progress-text"><?= $done ?>/<?= $total ?> readings submitted</span>
          <?php if ($isComplete): ?>
            <span style="font-size:.75rem;font-weight:700;color:#059669;">✓ Session Complete</span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Progress bar -->
    <div class="session-progress-bar">
      <div class="session-progress-fill" style="width:<?= $pct ?>%"></div>
    </div>

    <?php if (empty($mats)): ?>
      <div class="empty-state" style="padding:1.5rem;text-align:center;">
        <p class="text-muted text-sm">No reading materials assigned to this session yet.</p>
      </div>
    <?php else: ?>
    <div class="readings-grid">
      <?php foreach ($mats as $m):
        $submitted = in_array($m['id'], $recordedIds);
      ?>
      <div class="reading-card <?= $submitted ? 'submitted' : '' ?>">
        <div class="reading-card-top">
          <span class="reading-num-tag">Reading <?= $m['reading_number'] ?></span>
          <?php if ($submitted): ?>
            <span class="submitted-check">✓ Submitted</span>
          <?php endif; ?>
        </div>
        <div class="reading-card-title"><?= e($m['title']) ?></div>
        <div class="reading-card-preview"><?= e($m['content']) ?></div>
        <div class="reading-card-footer">
          <span class="text-muted text-sm"><?= str_word_count($m['content']) ?> words</span>
          <a href="student/read.php?id=<?= $m['id'] ?>" class="btn btn-primary btn-sm">
            <?= $submitted ? '🔄 Re-record' : '▶ Start' ?>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
