<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$levelMap = [
    'beginner'     => 'Week 1 — Easy',
    'intermediate' => 'Week 2 — Medium',
    'advanced'     => 'Week 3 — Hard',
];

// Stats
$totalStudents  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$totalMaterials = $pdo->query("SELECT COUNT(*) FROM reading_materials")->fetchColumn();
$totalRec       = $pdo->query("SELECT COUNT(*) FROM recordings")->fetchColumn();
$pendingFeedback= $pdo->query("SELECT COUNT(*) FROM recordings WHERE feedback IS NULL")->fetchColumn();

// Recent recordings needing feedback
$recentPending = $pdo->query("
    SELECT r.id, r.submitted_at, r.audio_path,
           u.name AS student_name,
           m.title AS material_title, m.level
    FROM recordings r
    JOIN users u ON r.student_id = u.id
    JOIN reading_materials m ON r.material_id = m.id
    WHERE r.feedback IS NULL
    ORDER BY r.submitted_at DESC
    LIMIT 5
")->fetchAll();

// Student progress snapshot
$studentProgress = $pdo->query("
    SELECT u.id, u.name,
           COUNT(DISTINCT r.material_id) AS readings_done,
           COUNT(DISTINCT m.id) AS total_readings,
           MAX(r.submitted_at) AS last_activity
    FROM users u
    LEFT JOIN recordings r ON r.student_id = u.id
    LEFT JOIN reading_materials m ON 1=1
    WHERE u.role = 'student'
    GROUP BY u.id, u.name
    ORDER BY last_activity DESC
    LIMIT 6
")->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="page-header">
    <div class="page-header-inner">
      <div>
        <h1 class="page-title">&#127908; A.I.M. Facilitator Dashboard</h1>
        <p class="page-subtitle">Welcome back, <?= e(currentUserName()) ?>! — Audio-Visual Intervention Mirroring</p>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon indigo">&#128101;</div>
      <div><div class="stat-value"><?= $totalStudents ?></div><div class="stat-label">Total Students</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green">&#128218;</div>
      <div><div class="stat-value"><?= $totalMaterials ?></div><div class="stat-label">Reading Materials</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon amber">&#9203;</div>
      <div><div class="stat-value"><?= $pendingFeedback ?></div><div class="stat-label">Pending Feedback</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon red">&#127908;</div>
      <div><div class="stat-value"><?= $totalRec ?></div><div class="stat-label">Total Recordings</div></div>
    </div>
  </div>

  <!-- Quick Links -->
  <div class="grid-3 section">
    <a href="materials.php" class="card" style="padding:1.5rem;text-align:center;text-decoration:none;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 8px 24px rgba(123,20,80,.12)'" onmouseout="this.style.boxShadow=''">
      <div style="font-size:2rem;margin-bottom:.5rem;">📚</div>
      <div style="font-weight:700;color:#1E293B;">Manage Materials</div>
      <div class="text-muted text-sm">Add, edit, delete reading materials</div>
    </a>
    <a href="recordings.php" class="card" style="padding:1.5rem;text-align:center;text-decoration:none;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 8px 24px rgba(123,20,80,.12)'" onmouseout="this.style.boxShadow=''">
      <div style="font-size:2rem;margin-bottom:.5rem;">🎤</div>
      <div style="font-weight:700;color:#1E293B;">Review Recordings</div>
      <div class="text-muted text-sm"><?= $pendingFeedback ?> pending feedback</div>
    </a>
    <a href="progress.php" class="card" style="padding:1.5rem;text-align:center;text-decoration:none;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 8px 24px rgba(123,20,80,.12)'" onmouseout="this.style.boxShadow=''">
      <div style="font-size:2rem;margin-bottom:.5rem;">📊</div>
      <div style="font-weight:700;color:#1E293B;">Student Progress</div>
      <div class="text-muted text-sm">Session completion tracker</div>
    </a>
    <a href="users.php" class="card" style="padding:1.5rem;text-align:center;text-decoration:none;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 8px 24px rgba(123,20,80,.12)'" onmouseout="this.style.boxShadow=''">
      <div style="font-size:2rem;margin-bottom:.5rem;">👥</div>
      <div style="font-weight:700;color:#1E293B;">Manage Users</div>
      <div class="text-muted text-sm">View and manage student accounts</div>
    </a>
  </div>

  <!-- Student Progress Snapshot -->
  <?php if (!empty($studentProgress)): ?>
  <div class="section">
    <div class="card">
      <div class="card-header">
        <span class="card-title">📊 Student Progress Snapshot</span>
        <a href="progress.php" class="btn btn-sm btn-outline">View Full Tracker</a>
      </div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Student</th>
              <th>Progress</th>
              <th>Status</th>
              <th>Last Activity</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($studentProgress as $sp):
              // Get completed sessions for this student
              $sessData = $pdo->prepare("
                  SELECT m.session_number, COUNT(DISTINCT r.material_id) AS done,
                         COUNT(DISTINCT m2.id) AS total
                  FROM reading_materials m2
                  LEFT JOIN recordings r ON r.material_id = m2.id AND r.student_id = ?
                  LEFT JOIN reading_materials m ON m.id = m2.id
                  GROUP BY m2.session_number
              ");
              $sessData->execute([$sp['id']]);

              // Simpler: count distinct sessions where all readings submitted
              $sessCompleted = $pdo->prepare("
                  SELECT COUNT(*) FROM (
                      SELECT m.session_number
                      FROM reading_materials m
                      GROUP BY m.session_number
                      HAVING COUNT(m.id) = (
                          SELECT COUNT(*) FROM recordings r
                          WHERE r.student_id = ? AND r.material_id IN (
                              SELECT id FROM reading_materials WHERE session_number = m.session_number
                          )
                      )
                  ) AS completed_sessions
              ");
              $sessCompleted->execute([$sp['id']]);
              $sessCount = (int)$sessCompleted->fetchColumn();

              $pct = $totalMaterials > 0 ? round($sp['readings_done'] / $totalMaterials * 100) : 0;
            ?>
            <tr>
              <td><strong><?= e($sp['name']) ?></strong></td>
              <td style="min-width:160px;">
                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.25rem;">
                  <span style="font-size:.82rem;font-weight:700;color:#7B1450;"><?= $sessCount ?>/6 sessions</span>
                </div>
                <div style="height:5px;background:#F0D0E8;border-radius:999px;overflow:hidden;">
                  <div style="height:5px;width:<?= round($sessCount/6*100) ?>%;background:linear-gradient(90deg,#7B1450,#A855A0);border-radius:999px;"></div>
                </div>
              </td>
              <td>
                <?php if ($sp['readings_done'] == 0): ?>
                  <span style="font-size:.75rem;font-weight:700;color:#94A3B8;background:#F1F5F9;border-radius:999px;padding:.2rem .6rem;">Not Started</span>
                <?php elseif ($sessCount >= 6): ?>
                  <span style="font-size:.75rem;font-weight:700;color:#065F46;background:#ECFDF5;border:1px solid #A7F3D0;border-radius:999px;padding:.2rem .6rem;">✓ Completed</span>
                <?php else: ?>
                  <span style="font-size:.75rem;font-weight:700;color:#7B1450;background:#FDF4FF;border:1px solid #E9B8D4;border-radius:999px;padding:.2rem .6rem;">
                    Session <?= $sessCount + 1 ?>/6
                  </span>
                <?php endif; ?>
              </td>
              <td class="text-sm text-muted">
                <?= $sp['last_activity'] ? date('M j, Y', strtotime($sp['last_activity'])) : '—' ?>
              </td>
              <td>
                <a href="progress.php?student=<?= $sp['id'] ?>" class="btn btn-sm btn-outline">Detail</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Recent Pending Recordings -->
  <?php if (!empty($recentPending)): ?>
  <div class="section">
    <div class="card">
      <div class="card-header">
        <span class="card-title">&#9203; Recordings Awaiting Feedback</span>
        <a href="recordings.php?filter=pending" class="btn btn-sm btn-outline">View All</a>
      </div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Student</th>
              <th>Material</th>
              <th>Level</th>
              <th>Submitted</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentPending as $r): ?>
            <tr>
              <td><?= e($r['student_name']) ?></td>
              <td><?= e($r['material_title']) ?></td>
              <td><span class="badge badge-<?= e($r['level']) ?>"><?= e($levelMap[$r['level']] ?? ucfirst($r['level'])) ?></span></td>
              <td><?= e(date('M j, Y g:i A', strtotime($r['submitted_at']))) ?></td>
              <td><a href="recordings.php#rec-<?= $r['id'] ?>" class="btn btn-sm btn-primary">Give Feedback</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
