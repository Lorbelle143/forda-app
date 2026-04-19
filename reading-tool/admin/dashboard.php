<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

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

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="page-header">
    <div class="page-header-inner">
      <div>
        <h1 class="page-title">&#128202; Admin Dashboard</h1>
        <p class="page-subtitle">Welcome back, <?= e(currentUserName()) ?>!</p>
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
    <a href="materials.php" class="card" style="padding:1.5rem;text-align:center;text-decoration:none;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 8px 24px rgba(79,70,229,.12)'" onmouseout="this.style.boxShadow=''">
      <div style="font-size:2rem;margin-bottom:.5rem;">&#128218;</div>
      <div style="font-weight:700;color:#1E293B;">Manage Materials</div>
      <div class="text-muted text-sm">Add, edit, delete reading materials</div>
    </a>
    <a href="recordings.php" class="card" style="padding:1.5rem;text-align:center;text-decoration:none;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 8px 24px rgba(79,70,229,.12)'" onmouseout="this.style.boxShadow=''">
      <div style="font-size:2rem;margin-bottom:.5rem;">&#127908;</div>
      <div style="font-weight:700;color:#1E293B;">Review Recordings</div>
      <div class="text-muted text-sm"><?= $pendingFeedback ?> pending feedback</div>
    </a>
    <a href="users.php" class="card" style="padding:1.5rem;text-align:center;text-decoration:none;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 8px 24px rgba(79,70,229,.12)'" onmouseout="this.style.boxShadow=''">
      <div style="font-size:2rem;margin-bottom:.5rem;">&#128101;</div>
      <div style="font-weight:700;color:#1E293B;">Manage Users</div>
      <div class="text-muted text-sm">View and manage student accounts</div>
    </a>
  </div>

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
              <td><span class="badge badge-<?= e($r['level']) ?>"><?= e(ucfirst($r['level'])) ?></span></td>
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
