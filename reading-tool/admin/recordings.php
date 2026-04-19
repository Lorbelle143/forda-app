<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

// Handle delete recording
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verifyCsrf();
    $recId = (int)($_POST['recording_id'] ?? 0);
    if ($recId) {
        $stmt = $pdo->prepare('SELECT audio_path FROM recordings WHERE id = ?');
        $stmt->execute([$recId]);
        $rec = $stmt->fetch();
        if ($rec) {
            $filePath = __DIR__ . '/../' . $rec['audio_path'];
            if (file_exists($filePath)) unlink($filePath);
            $pdo->prepare('DELETE FROM recordings WHERE id = ?')->execute([$recId]);
            setFlash('success', 'Recording deleted.');
        }
    }
    header('Location: recordings.php'); exit;
}

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'feedback') {
    verifyCsrf();
    $recId    = (int)($_POST['recording_id'] ?? 0);
    $feedback = trim($_POST['feedback'] ?? '');
    if ($recId && !empty($feedback)) {
        $stmt = $pdo->prepare('UPDATE recordings SET feedback=?, feedback_at=NOW(), feedback_by=? WHERE id=?');
        $stmt->execute([$feedback, currentUserId(), $recId]);
        setFlash('success', 'Feedback submitted successfully.');
    } else {
        setFlash('error', 'Feedback text cannot be empty.');
    }
    header('Location: recordings.php'); exit;
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$where  = ($filter === 'pending') ? 'WHERE r.feedback IS NULL' : '';

$recordings = $pdo->query("
    SELECT r.*, u.name AS student_name, m.title AS material_title, m.level,
           fb.name AS feedback_by_name
    FROM recordings r
    JOIN users u ON r.student_id = u.id
    JOIN reading_materials m ON r.material_id = m.id
    LEFT JOIN users fb ON r.feedback_by = fb.id
    $where
    ORDER BY r.submitted_at DESC
")->fetchAll();

$pageTitle = 'Student Recordings';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="page-header">
    <div class="breadcrumb">
      <a href="dashboard.php">Dashboard</a>
      <span class="breadcrumb-sep">&#8250;</span>
      <span>Recordings</span>
    </div>
    <div class="page-header-inner">
      <div>
        <h1 class="page-title">&#127908; Student Recordings</h1>
        <p class="page-subtitle"><?= count($recordings) ?> recording(s) <?= $filter === 'pending' ? 'pending feedback' : 'total' ?></p>
      </div>
    </div>
  </div>

  <!-- Filter Bar -->
  <div class="filter-bar">
    <label>Filter:</label>
    <a href="recordings.php" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-ghost' ?>">All Recordings</a>
    <a href="recordings.php?filter=pending" class="btn btn-sm <?= $filter === 'pending' ? 'btn-primary' : 'btn-ghost' ?>">Pending Feedback</a>
  </div>

  <?php if (empty($recordings)): ?>
  <div class="empty-state">
    <div class="empty-state-icon">&#127908;</div>
    <h3>No recordings found</h3>
    <p><?= $filter === 'pending' ? 'All recordings have been reviewed!' : 'No students have submitted recordings yet.' ?></p>
  </div>
  <?php else: ?>

  <?php foreach ($recordings as $rec): ?>
  <div class="recording-item" id="rec-<?= $rec['id'] ?>">
    <div class="recording-item-header">
      <div>
        <div class="recording-item-title">
          <?= e($rec['student_name']) ?>
          <span class="text-muted" style="font-weight:400;"> &mdash; </span>
          <?= e($rec['material_title']) ?>
        </div>
        <div class="recording-item-meta">
          <span class="badge badge-<?= e($rec['level']) ?>"><?= e(ucfirst($rec['level'])) ?></span>
          &nbsp; Submitted: <?= e(date('M j, Y g:i A', strtotime($rec['submitted_at']))) ?>
        </div>
      </div>
      <div>
        <?php if ($rec['feedback']): ?>
          <span class="badge badge-done">&#10003; Reviewed</span>
        <?php else: ?>
          <span class="pending-badge">&#9203; Pending</span>
        <?php endif; ?>
        &nbsp;
        <form method="POST" style="display:inline;">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="recording_id" value="<?= $rec['id'] ?>">
          <button type="submit" class="btn btn-sm btn-outline-danger"
            data-confirm="Delete this recording permanently?">Delete</button>
        </form>
      </div>
    </div>

    <?php
    $audioFile = __DIR__ . '/../' . $rec['audio_path'];
    if (file_exists($audioFile)):
    ?>
    <audio controls style="width:100%;border-radius:8px;margin-bottom:.75rem;">
      <source src="<?= e('../' . $rec['audio_path']) ?>">
      Your browser does not support audio playback.
    </audio>
    <?php else: ?>
    <p class="text-muted text-sm" style="margin-bottom:.75rem;">Audio file not found on server.</p>
    <?php endif; ?>

    <?php if ($rec['feedback']): ?>
    <div class="feedback-box">
      <div class="feedback-box-label">Your Feedback</div>
      <div class="feedback-box-text"><?= e($rec['feedback']) ?></div>
      <div class="text-muted text-sm mt-2">
        Reviewed by <?= e($rec['feedback_by_name'] ?? 'Admin') ?> on <?= e(date('M j, Y', strtotime($rec['feedback_at']))) ?>
      </div>
    </div>
    <!-- Allow updating feedback -->
    <details style="margin-top:.75rem;">
      <summary class="btn btn-sm btn-ghost" style="cursor:pointer;display:inline-flex;">Update Feedback</summary>
      <form method="POST" action="recordings.php" class="feedback-form" style="margin-top:.75rem;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="feedback">
        <input type="hidden" name="recording_id" value="<?= $rec['id'] ?>">
        <textarea name="feedback" class="form-control" rows="3" required placeholder="Update feedback..."><?= e($rec['feedback']) ?></textarea>
        <button type="submit" class="btn btn-primary btn-sm">Update</button>
      </form>
    </details>
    <?php else: ?>
    <form method="POST" action="recordings.php" class="feedback-form">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="feedback">
      <input type="hidden" name="recording_id" value="<?= $rec['id'] ?>">
      <label class="form-label">&#128172; Give Feedback</label>
      <textarea name="feedback" class="form-control" rows="3" required
        placeholder="Write your feedback for this student..."></textarea>
      <button type="submit" class="btn btn-primary btn-sm">Submit Feedback</button>
    </form>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>

  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
