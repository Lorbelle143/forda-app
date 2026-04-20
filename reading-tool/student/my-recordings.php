<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();
if (isAdmin()) { header('Location: ../admin/dashboard.php'); exit; }

// Handle delete recording
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verifyCsrf();
    $recId = (int)($_POST['recording_id'] ?? 0);
    if ($recId) {
        $stmt = $pdo->prepare('SELECT audio_path FROM recordings WHERE id = ? AND student_id = ?');
        $stmt->execute([$recId, currentUserId()]);
        $rec = $stmt->fetch();
        if ($rec) {
            $filePath = __DIR__ . '/../' . $rec['audio_path'];
            if (file_exists($filePath)) unlink($filePath);
            $pdo->prepare('DELETE FROM recordings WHERE id = ?')->execute([$recId]);
            setFlash('success', 'Recording deleted.');
        }
    }
    header('Location: my-recordings.php'); exit;
}

$stmt = $pdo->prepare('
    SELECT r.*, m.title AS material_title, m.level, m.id AS material_id,
           m.session_number, m.reading_number,
           u.name AS feedback_by_name
    FROM recordings r
    JOIN reading_materials m ON r.material_id = m.id
    LEFT JOIN users u ON r.feedback_by = u.id
    WHERE r.student_id = ?
    ORDER BY r.submitted_at DESC
');
$stmt->execute([currentUserId()]);
$recordings = $stmt->fetchAll();

$pageTitle = 'My Recordings';
require_once __DIR__ . '/../includes/header.php';

$levelMap = [
    'beginner'     => 'Week 1 — Easy',
    'intermediate' => 'Week 2 — Medium',
    'advanced'     => 'Week 3 — Hard',
];
?>

<div class="container">
  <div class="page-header">
    <div class="breadcrumb">
      <a href="../dashboard.php">Home</a>
      <span class="breadcrumb-sep">&#8250;</span>
      <span>My Recordings</span>
    </div>
    <div class="page-header-inner">
      <div>
        <h1 class="page-title">&#127908; My Session Recordings</h1>
        <p class="page-subtitle"><?= count($recordings) ?> recording(s) submitted across all sessions</p>
      </div>
      <a href="../dashboard.php" class="btn btn-primary btn-sm">+ New Recording</a>
    </div>
  </div>

  <?php if (empty($recordings)): ?>
  <div class="empty-state">
    <div class="empty-state-icon">&#127908;</div>
    <h3>No recordings yet</h3>
    <p>Go to a reading material and record yourself reading!</p>
    <a href="../dashboard.php" class="btn btn-primary mt-4">Browse Materials</a>
  </div>
  <?php else: ?>

  <?php foreach ($recordings as $rec): ?>
  <div class="recording-item">
    <div class="recording-item-header">
      <div>
        <div class="recording-item-title"><?= e($rec['material_title']) ?></div>
        <div class="recording-item-meta">
          <span class="badge badge-<?= e($rec['level']) ?>"><?= e($levelMap[$rec['level']] ?? ucfirst($rec['level'])) ?></span>
          &nbsp; Submitted: <?= e(date('M j, Y g:i A', strtotime($rec['submitted_at']))) ?>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
        <?php if ($rec['feedback']): ?>
          <span class="badge badge-done">&#10003; Feedback Received</span>
        <?php else: ?>
          <span class="pending-badge">&#9203; Awaiting Feedback</span>
        <?php endif; ?>
        <?php if (!$rec['feedback']): ?>
        <form method="POST" style="display:inline;">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="recording_id" value="<?= $rec['id'] ?>">
          <button type="submit" class="btn btn-sm btn-outline-danger"
            data-confirm="Delete this recording? This cannot be undone.">Delete</button>
        </form>
        <?php endif; ?>
        <a href="../student/read.php?id=<?= $rec['material_id'] ?>" class="btn btn-sm btn-ghost">Re-record</a>
      </div>
    </div>

    <?php
    $audioFile = __DIR__ . '/../' . $rec['audio_path'];
    if (file_exists($audioFile)):
    ?>
    <audio controls style="width:100%;border-radius:8px;margin:.5rem 0;">
      <source src="<?= e('../' . $rec['audio_path']) ?>">
      Your browser does not support audio playback.
    </audio>
    <?php else: ?>
    <p class="text-muted text-sm" style="margin:.5rem 0;">Audio file not found.</p>
    <?php endif; ?>

    <?php
    // ── Accuracy Score ──────────────────────────────────
    $milestoneStyles = [
        'Excellent'      => ['bg'=>'#ECFDF5','border'=>'#A7F3D0','color'=>'#065F46','icon'=>'🌟'],
        'Great Progress' => ['bg'=>'#EFF6FF','border'=>'#BFDBFE','color'=>'#1E40AF','icon'=>'👏'],
        'Nice Job'       => ['bg'=>'#FFFBEB','border'=>'#FDE68A','color'=>'#92400E','icon'=>'💪'],
        'Brave Start'    => ['bg'=>'#FDF4FF','border'=>'#E9B8D4','color'=>'#7B1450','icon'=>'🌱'],
    ];
    if (!is_null($rec['mispronounced_count'])): 
        $ms = $milestoneStyles[$rec['milestone']] ?? ['bg'=>'#F8FAFC','border'=>'#E2E8F0','color'=>'#64748B','icon'=>'📊'];
    ?>
    <div style="background:<?= $ms['bg'] ?>;border:1.5px solid <?= $ms['border'] ?>;border-radius:10px;padding:.85rem 1rem;margin:.5rem 0;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
      <div style="font-size:1.5rem;"><?= $ms['icon'] ?></div>
      <div style="flex:1;">
        <div style="font-size:.78rem;font-weight:700;color:<?= $ms['color'] ?>;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.15rem;">🌸 Accuracy Tracker Result</div>
        <div style="font-size:.95rem;font-weight:800;color:<?= $ms['color'] ?>;"><?= e($rec['milestone']) ?></div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:1.6rem;font-weight:900;color:<?= $ms['color'] ?>;"><?= $rec['mispronounced_count'] ?></div>
        <div style="font-size:.72rem;color:<?= $ms['color'] ?>;opacity:.8;">mispronounced</div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($rec['feedback']): ?>
    <div class="feedback-box">
      <div class="feedback-box-label">&#128172; Teacher Feedback</div>
      <div class="feedback-box-text"><?= e($rec['feedback']) ?></div>
      <?php if ($rec['feedback_at']): ?>
      <div class="text-muted text-sm mt-2">
        — <?= e($rec['feedback_by_name'] ?? 'Teacher') ?>, <?= e(date('M j, Y', strtotime($rec['feedback_at']))) ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>

  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
