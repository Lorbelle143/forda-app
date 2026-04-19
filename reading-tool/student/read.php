<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();
if (isAdmin()) { header('Location: ../admin/dashboard.php'); exit; }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ../dashboard.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM reading_materials WHERE id = ?');
$stmt->execute([$id]);
$material = $stmt->fetch();
if (!$material) { header('Location: ../dashboard.php'); exit; }

// Check if student already submitted a recording for this material
$stmt = $pdo->prepare('SELECT id, feedback FROM recordings WHERE student_id = ? AND material_id = ? ORDER BY submitted_at DESC LIMIT 1');
$stmt->execute([currentUserId(), $id]);
$existingRec = $stmt->fetch();

$pageTitle = $material['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="page-header">
    <div class="breadcrumb">
      <a href="../dashboard.php">Home</a>
      <span class="breadcrumb-sep">&#8250;</span>
      <span><?= e($material['title']) ?></span>
    </div>
    <div class="page-header-inner">
      <div>
        <h1 class="page-title"><?= e($material['title']) ?></h1>
        <p class="page-subtitle">
          <span class="badge badge-<?= e($material['level']) ?>"><?= e(ucfirst($material['level'])) ?></span>
        </p>
      </div>
      <a href="../dashboard.php" class="btn btn-ghost btn-sm">&#8592; Back</a>
    </div>
  </div>

  <div class="reading-container">

    <div id="ttsNotice" class="alert alert-warning" style="display:none;">
      Text-to-Speech is not supported in your browser. Try Chrome or Edge.
    </div>

    <?php if ($existingRec): ?>
    <div class="alert alert-<?= $existingRec['feedback'] ? 'success' : 'warning' ?>">
      <?php if ($existingRec['feedback']): ?>
        &#10003; You already submitted a recording for this material and received feedback.
        <a href="../student/my-recordings.php" style="font-weight:700;">View feedback</a>
      <?php else: ?>
        &#9203; You already submitted a recording for this material. It is awaiting feedback.
        <a href="../student/my-recordings.php" style="font-weight:700;">View my recordings</a>
      <?php endif; ?>
      You can still record again below.
      <button class="alert-close" onclick="this.parentElement.remove()">&#215;</button>
    </div>
    <?php endif; ?>

    <!-- Reading Text -->
    <div class="card section">
      <div class="card-header">
        <span class="card-title">&#128214; Reading Text</span>
        <span class="text-muted text-sm"><?= str_word_count($material['content']) ?> words</span>
      </div>
      <div class="card-body">
        <div
          id="readingText"
          class="reading-text-box"
          data-raw-text="<?= htmlspecialchars($material['content'], ENT_QUOTES, 'UTF-8') ?>"
        ><?= e($material['content']) ?></div>
      </div>
    </div>

    <!-- TTS Controls -->
    <div class="card section">
      <div class="card-header">
        <span class="card-title">&#128266; Text-to-Speech</span>
      </div>
      <div class="card-body">
        <div class="tts-controls">
          <div class="control-group">
            <button id="ttsPlay" class="btn btn-primary">&#9654; Play</button>
            <button id="ttsPause" class="btn btn-accent" disabled>&#9646;&#9646; Pause</button>
            <button id="ttsStop" class="btn btn-ghost" disabled>&#9632; Stop</button>
          </div>
          <div class="control-group">
            <span class="speed-label">Speed:</span>
            <select id="ttsSpeed" class="speed-select">
              <option value="0.6">Slow</option>
              <option value="1" selected>Normal</option>
              <option value="1.5">Fast</option>
            </select>
          </div>
          <p class="text-muted text-sm" style="width:100%;margin:0;">Words are highlighted as they are spoken.</p>
        </div>
      </div>
    </div>

    <!-- Recording Section -->
    <div class="card section">
      <div class="card-header">
        <span class="card-title">&#127908; Record Your Reading</span>
      </div>
      <div class="card-body">
        <p class="text-muted text-sm mb-4">Read the text above aloud and record yourself. Submit your recording for teacher feedback.</p>

        <div class="recording-controls">
          <div class="recording-indicator" id="recIndicator"></div>
          <span class="recording-timer" id="recTimer">00:00</span>
          <button id="recStart" class="btn btn-danger">&#127908; Start Recording</button>
          <button id="recStop" class="btn btn-ghost" disabled>&#9632; Stop</button>
        </div>

        <p id="recStatus" class="text-muted text-sm mt-3"></p>

        <div id="audioPreview" class="audio-preview" style="display:none;">
          <p class="audio-preview-label">Preview your recording:</p>
          <audio controls></audio>
        </div>

        <div style="margin-top:1rem;">
          <button
            id="recSubmit"
            class="btn btn-secondary btn-lg"
            disabled
            data-material-id="<?= (int)$material['id'] ?>"
            data-base="../"
          >&#128228; Submit Recording</button>
        </div>

        <div id="submitSuccess" class="alert alert-success mt-4" style="display:none;">
          Your recording has been submitted! Your teacher will review it and provide feedback.
          <br><br>
          <a href="../student/my-recordings.php" class="btn btn-secondary btn-sm">View My Recordings</a>
          <a href="../dashboard.php" class="btn btn-ghost btn-sm">Back to Dashboard</a>
        </div>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
