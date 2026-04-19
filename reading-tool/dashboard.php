<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();
if (isAdmin()) { header('Location: admin/dashboard.php'); exit; }

// Level filter
$filterLevel = $_GET['level'] ?? 'all';
$allowed     = ['all', 'beginner', 'intermediate', 'advanced'];
if (!in_array($filterLevel, $allowed)) $filterLevel = 'all';

// Get reading materials
if ($filterLevel === 'all') {
    $materials = $pdo->query('SELECT * FROM reading_materials ORDER BY FIELD(level,"beginner","intermediate","advanced"), title')->fetchAll();
} else {
    $stmt = $pdo->prepare('SELECT * FROM reading_materials WHERE level = ? ORDER BY title');
    $stmt->execute([$filterLevel]);
    $materials = $stmt->fetchAll();
}

// Get student recording count
$stmt = $pdo->prepare('SELECT COUNT(*) FROM recordings WHERE student_id = ?');
$stmt->execute([currentUserId()]);
$recCount = $stmt->fetchColumn();

// Get material IDs already recorded by this student
$stmt = $pdo->prepare('SELECT DISTINCT material_id FROM recordings WHERE student_id = ?');
$stmt->execute([currentUserId()]);
$recordedIds = array_column($stmt->fetchAll(), 'material_id');

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
  <div class="page-header">
    <div class="page-header-inner">
      <div>
        <h1 class="page-title">👋 Welcome, <?= e(currentUserName()) ?>!</h1>
        <p class="page-subtitle">Choose a reading material to get started.</p>
      </div>
      <a href="student/my-recordings.php" class="btn btn-outline">🎤 My Recordings (<?= $recCount ?>)</a>
    </div>
  </div>

  <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); margin-bottom:2rem;">
    <div class="stat-card">
      <div class="stat-icon indigo">📚</div>
      <div><div class="stat-value"><?= count($materials) ?></div><div class="stat-label">Materials Available</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green">🎤</div>
      <div><div class="stat-value"><?= $recCount ?></div><div class="stat-label">Recordings Submitted</div></div>
    </div>
  </div>

  <!-- Level Filter -->
  <div class="filter-bar">
    <label>Filter by level:</label>
    <?php foreach (['all' => 'All', 'beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'] as $val => $label): ?>
    <a href="dashboard.php?level=<?= $val ?>" class="btn btn-sm <?= $filterLevel === $val ? 'btn-primary' : 'btn-ghost' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>

  <?php
  $levels = ['beginner' => '🟢 Beginner', 'intermediate' => '🔵 Intermediate', 'advanced' => '🔴 Advanced'];
  foreach ($levels as $level => $label):
    if ($filterLevel !== 'all' && $filterLevel !== $level) continue;
    $levelMaterials = array_filter($materials, function($m) use ($level) { return $m['level'] === $level; });
    if (empty($levelMaterials)) continue;
  ?>
  <div class="section">
    <h2 style="font-size:1.1rem;font-weight:700;color:#374151;margin-bottom:1rem;"><?= $label ?></h2>
    <div class="grid-auto">
      <?php foreach ($levelMaterials as $m): ?>
      <div class="material-card">
        <div style="display:flex;align-items:center;justify-content:space-between;">
          <span class="badge badge-<?= e($m['level']) ?>"><?= e(ucfirst($m['level'])) ?></span>
          <?php if (in_array($m['id'], $recordedIds)): ?>
            <span style="font-size:.75rem;color:#10B981;font-weight:600;">&#10003; Recorded</span>
          <?php endif; ?>
        </div>
        <div class="material-card-title"><?= e($m['title']) ?></div>
        <div class="material-card-preview"><?= e($m['content']) ?></div>
        <div class="material-card-footer">
          <span class="text-muted text-sm"><?= e(date('M j, Y', strtotime($m['created_at']))) ?></span>
          <a href="student/read.php?id=<?= $m['id'] ?>" class="btn btn-primary btn-sm">&#9654; Start Reading</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if (empty($materials)): ?>
  <div class="empty-state">
    <div class="empty-state-icon">📭</div>
    <h3>No materials yet</h3>
    <p>Ask your teacher to add reading materials.</p>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
