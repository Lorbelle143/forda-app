<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$levelMap = [
    'beginner'     => 'Week 1 — Easy',
    'intermediate' => 'Week 2 — Medium',
    'advanced'     => 'Week 3 — Hard',
];

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
           m.session_number, m.reading_number,
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
        <p class="page-subtitle"><?= count($recordings) ?> recording(s) <?= $filter === 'pending' ? 'pending facilitator feedback' : 'total' ?></p>
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
          <span class="badge badge-<?= e($rec['level']) ?>"><?= e($levelMap[$rec['level']] ?? ucfirst($rec['level'])) ?></span>
          &nbsp; Submitted: <?= e(date('M j, Y g:i A', strtotime($rec['submitted_at']))) ?>
          <?php if (!is_null($rec['mispronounced_count'])): ?>
            &nbsp;
            <?php
            $msColors = ['Excellent'=>'#065F46','Great Progress'=>'#1E40AF','Nice Job'=>'#92400E','Brave Start'=>'#7B1450'];
            $msBgs    = ['Excellent'=>'#ECFDF5','Great Progress'=>'#EFF6FF','Nice Job'=>'#FFFBEB','Brave Start'=>'#FDF4FF'];
            $msIcons  = ['Excellent'=>'🌟','Great Progress'=>'👏','Nice Job'=>'💪','Brave Start'=>'🌱'];
            $mc = $rec['milestone'] ?? '';
            $mcColor = $msColors[$mc] ?? '#64748B';
            $mcBg    = $msBgs[$mc]   ?? '#F8FAFC';
            $mcIcon  = $msIcons[$mc] ?? '📊';
            ?>
            <span style="display:inline-flex;align-items:center;gap:.3rem;font-size:.75rem;font-weight:700;color:<?= $mcColor ?>;background:<?= $mcBg ?>;border-radius:999px;padding:.2rem .65rem;">
              <?= $mcIcon ?> <?= e($mc) ?> · <?= $rec['mispronounced_count'] ?> errors
            </span>
          <?php endif; ?>
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

      <!-- AIM Pronunciation Rubric -->
      <details style="margin-bottom:1rem;">
        <summary class="btn btn-sm btn-ghost" style="cursor:pointer;display:inline-flex;margin-bottom:.5rem;">&#128203; View AIM Pronunciation Rubric</summary>
        <div style="overflow-x:auto;margin-top:.75rem;">
          <table class="rubric-table">
            <thead>
              <tr>
                <th>Criteria</th>
                <th>5 — Excellent</th>
                <th>4 — Good</th>
                <th>3 — Satisfactory</th>
                <th>2 — Needs Improvement</th>
                <th>1 — Poor</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Segmental Accuracy<br><small>(Vowels &amp; Consonants)</small></td>
                <td class="rubric-score-5">Almost all sounds correctly produced; no noticeable errors</td>
                <td class="rubric-score-4">Minor errors that do not affect understanding</td>
                <td class="rubric-score-3">Some noticeable errors but meaning is still clear</td>
                <td class="rubric-score-2">Frequent errors that sometimes affect clarity</td>
                <td class="rubric-score-1">Consistent errors that make speech difficult to understand</td>
              </tr>
              <tr>
                <td>Word Stress</td>
                <td class="rubric-score-5">Correct stress on nearly all multisyllabic words</td>
                <td class="rubric-score-4">Most words have correct stress with few errors</td>
                <td class="rubric-score-3">Some correct stress but inconsistent</td>
                <td class="rubric-score-2">Limited control of word stress</td>
                <td class="rubric-score-1">Incorrect stress on most words</td>
              </tr>
              <tr>
                <td>Sentence Stress &amp; Intonation</td>
                <td class="rubric-score-5">Natural and appropriate stress and intonation throughout</td>
                <td class="rubric-score-4">Generally appropriate with minor inconsistencies</td>
                <td class="rubric-score-3">Some attempts at correct intonation but uneven</td>
                <td class="rubric-score-2">Limited control of stress/intonation patterns</td>
                <td class="rubric-score-1">Monotone or inappropriate intonation throughout</td>
              </tr>
              <tr>
                <td>Pronunciation Clarity</td>
                <td class="rubric-score-5">Speech is very clear and easily understood</td>
                <td class="rubric-score-4">Mostly clear with occasional unclear words</td>
                <td class="rubric-score-3">Generally understandable with some unclear parts</td>
                <td class="rubric-score-2">Often unclear requiring listener effort</td>
                <td class="rubric-score-1">Very unclear and difficult to understand</td>
              </tr>
            </tbody>
          </table>
        </div>
      </details>

      <textarea name="feedback" class="form-control" rows="4" required
        placeholder="Write your feedback based on the AIM rubric (segmental accuracy, word stress, intonation, clarity)..."></textarea>
      <button type="submit" class="btn btn-primary btn-sm">Submit Feedback</button>
    </form>
    <?php endif; ?>

  </div><!-- /.recording-item -->
  <?php endforeach; ?>

  <?php endif; ?>
</div><!-- /.container -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
