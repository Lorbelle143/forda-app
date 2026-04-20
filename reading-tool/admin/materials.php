<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$error = '';
$editMaterial = null;

// ── Model audio upload helper ──────────────────────────────────────────────
function saveModelAudio(array $file, string &$error): ?string {
    $maxSize = 30 * 1024 * 1024; // 30 MB
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload error code: ' . $file['error'];
        return null;
    }
    if ($file['size'] > $maxSize) {
        $error = 'File too large (max 30 MB).';
        return null;
    }
    $allowed = ['audio/mpeg','audio/mp3','audio/wav','audio/ogg','audio/webm','audio/mp4','video/webm'];
    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed)) {
        $error = 'Invalid file type. Upload MP3, WAV, OGG, or WebM audio.';
        return null;
    }
    $extMap = ['audio/mpeg'=>'mp3','audio/mp3'=>'mp3','audio/wav'=>'wav','audio/ogg'=>'ogg','audio/webm'=>'webm','audio/mp4'=>'m4a','video/webm'=>'webm'];
    $ext    = $extMap[$mime] ?? 'mp3';
    $dir    = __DIR__ . '/../uploads/model_audio/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $fname  = 'model_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $fname)) {
        $error = 'Failed to save file.';
        return null;
    }
    return 'uploads/model_audio/' . $fname;
}

$sessionMeta = [
    1 => 'Session 1 — The Baseline (Week 1 · Easy)',
    2 => 'Session 2 — The Bridge (Week 1 · Easy)',
    3 => 'Session 3 — The Shift (Week 2 · Medium)',
    4 => 'Session 4 — The Flow (Week 2 · Medium)',
    5 => 'Session 5 — The Challenge (Week 3 · Hard)',
    6 => 'Session 6 — The Final Evaluation (Week 3 · Advanced)',
];
$sessionLevel = [
    1 => 'beginner', 2 => 'beginner',
    3 => 'intermediate', 4 => 'intermediate',
    5 => 'advanced', 6 => 'advanced',
];

// ── Handle Add ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    verifyCsrf();
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $sn      = (int)($_POST['session_number'] ?? 1);
    $rn      = (int)($_POST['reading_number'] ?? 1);
    if (!isset($sessionLevel[$sn])) $sn = 1;
    if ($rn < 1 || $rn > 20) $rn = 1;
    $level = $sessionLevel[$sn];

    if (empty($title) || empty($content)) {
        $error = 'Title and content are required.';
    } else {
        $modelAudioPath = null;
        if (!empty($_FILES['model_audio']['name'])) {
            $modelAudioPath = saveModelAudio($_FILES['model_audio'], $error);
        }
        if (!$error) {
            $pdo->prepare('INSERT INTO reading_materials (title, content, level, session_number, reading_number, model_audio_path, created_by) VALUES (?,?,?,?,?,?,?)')
                ->execute([$title, $content, $level, $sn, $rn, $modelAudioPath, currentUserId()]);
            setFlash('success', 'Material added to Session ' . $sn . ', Reading ' . $rn . '.');
            header('Location: materials.php'); exit;
        }
    }
}

// ── Handle Edit ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    verifyCsrf();
    $id      = (int)($_POST['id'] ?? 0);
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $sn      = (int)($_POST['session_number'] ?? 1);
    $rn      = (int)($_POST['reading_number'] ?? 1);
    if (!isset($sessionLevel[$sn])) $sn = 1;
    if ($rn < 1 || $rn > 20) $rn = 1;
    $level = $sessionLevel[$sn];

    if (empty($title) || empty($content) || !$id) {
        $error = 'All fields are required.';
    } else {
        // Fetch existing to maybe delete old audio
        $existing = $pdo->prepare('SELECT model_audio_path FROM reading_materials WHERE id=?');
        $existing->execute([$id]);
        $existingRow = $existing->fetch();

        $modelAudioPath = $existingRow['model_audio_path'] ?? null;

        // New file uploaded?
        if (!empty($_FILES['model_audio']['name'])) {
            $newPath = saveModelAudio($_FILES['model_audio'], $error);
            if (!$error) {
                // Delete old file
                if ($modelAudioPath && file_exists(__DIR__ . '/../' . $modelAudioPath)) {
                    unlink(__DIR__ . '/../' . $modelAudioPath);
                }
                $modelAudioPath = $newPath;
            }
        }

        // Remove audio?
        if (isset($_POST['remove_model_audio']) && $_POST['remove_model_audio'] === '1') {
            if ($modelAudioPath && file_exists(__DIR__ . '/../' . $modelAudioPath)) {
                unlink(__DIR__ . '/../' . $modelAudioPath);
            }
            $modelAudioPath = null;
        }

        if (!$error) {
            $pdo->prepare('UPDATE reading_materials SET title=?, content=?, level=?, session_number=?, reading_number=?, model_audio_path=? WHERE id=?')
                ->execute([$title, $content, $level, $sn, $rn, $modelAudioPath, $id]);
            setFlash('success', 'Material updated.');
            header('Location: materials.php'); exit;
        }
    }
}

// ── Handle Delete ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verifyCsrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $pdo->prepare('DELETE FROM reading_materials WHERE id=?')->execute([$id]);
        setFlash('success', 'Material deleted.');
    }
    header('Location: materials.php'); exit;
}

// ── Load edit form ─────────────────────────────────────────────────────────
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM reading_materials WHERE id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $editMaterial = $stmt->fetch();
}

// ── Get all materials grouped by session ───────────────────────────────────
$materials = $pdo->query('
    SELECT m.*, u.name AS creator
    FROM reading_materials m
    LEFT JOIN users u ON m.created_by = u.id
    ORDER BY m.session_number ASC, m.reading_number ASC
')->fetchAll();

$bySession = [];
foreach ($materials as $m) {
    $bySession[$m['session_number']][] = $m;
}

$pageTitle = 'Manage Materials';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
.session-group { margin-bottom: 2rem; }
.session-group-header {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: .6rem 1rem;
  background: linear-gradient(135deg, #FDF4FF, #F5E8F9);
  border: 1px solid #E9B8D4;
  border-radius: 10px;
  margin-bottom: .75rem;
}
.session-group-num {
  width: 28px; height: 28px;
  border-radius: 50%;
  background: linear-gradient(135deg, #7B1450, #A855A0);
  color: #fff;
  font-size: .75rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.session-group-title { font-size: .875rem; font-weight: 700; color: #7B1450; }
.session-group-count { font-size: .75rem; color: #A855A0; margin-left: auto; }
</style>

<div class="container">
  <div class="page-header">
    <div class="breadcrumb">
      <a href="dashboard.php">Dashboard</a>
      <span class="breadcrumb-sep">&#8250;</span>
      <span>Materials</span>
    </div>
    <div class="page-header-inner">
      <div>
        <h1 class="page-title">📚 Reading Materials</h1>
        <p class="page-subtitle"><?= count($materials) ?> material(s) across 6 sessions</p>
      </div>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?><button class="alert-close" onclick="this.parentElement.remove()">&#215;</button></div>
  <?php endif; ?>

  <div class="grid-2" style="align-items:start;">

    <!-- ── Add / Edit Form ── -->
    <div class="card" style="position:sticky;top:80px;">
      <div class="card-header">
        <span class="card-title"><?= $editMaterial ? '✏️ Edit Material' : '+ Add New Material' ?></span>
        <?php if ($editMaterial): ?>
          <a href="materials.php" class="btn btn-ghost btn-sm">Cancel</a>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <form method="POST" action="materials.php" enctype="multipart/form-data">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="<?= $editMaterial ? 'edit' : 'add' ?>">
          <?php if ($editMaterial): ?>
            <input type="hidden" name="id" value="<?= (int)$editMaterial['id'] ?>">
          <?php endif; ?>

          <div class="form-group">
            <label class="form-label">Session</label>
            <select name="session_number" class="form-control" id="sessionSelect">
              <?php foreach ($sessionMeta as $sn => $label): ?>
              <option value="<?= $sn ?>"
                <?= (($editMaterial['session_number'] ?? 1) == $sn) ? 'selected' : '' ?>>
                <?= e($label) ?>
              </option>
              <?php endforeach; ?>
            </select>
            <div class="form-hint">Level is automatically set based on the session.</div>
          </div>

          <div class="form-group">
            <label class="form-label">Reading Number</label>
            <select name="reading_number" class="form-control">
              <?php for ($i = 1; $i <= 10; $i++): ?>
              <option value="<?= $i ?>"
                <?= (($editMaterial['reading_number'] ?? 1) == $i) ? 'selected' : '' ?>>
                Reading <?= $i ?>
              </option>
              <?php endfor; ?>
            </select>
            <div class="form-hint">Which reading number within this session (1, 2, 3…)</div>
          </div>

          <div class="form-group">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required
              value="<?= e($editMaterial['title'] ?? '') ?>"
              placeholder="e.g. The Baseline — Reading 1">
          </div>

          <div class="form-group">
            <label class="form-label">Reading Text</label>
            <textarea name="content" class="form-control" rows="7" required
              placeholder="Enter the reading passage here..."><?= e($editMaterial['content'] ?? '') ?></textarea>
          </div>

          <!-- Model Audio Upload -->
          <div class="form-group">
            <label class="form-label">🎙️ Facilitator Model Audio</label>
            <?php if (!empty($editMaterial['model_audio_path'])): ?>
              <div style="background:#FDF4FF;border:1px solid #E9B8D4;border-radius:8px;padding:.75rem;margin-bottom:.6rem;">
                <p style="font-size:.78rem;font-weight:700;color:#7B1450;margin-bottom:.4rem;">Current model audio:</p>
                <audio controls style="width:100%;height:36px;">
                  <source src="<?= e('../' . $editMaterial['model_audio_path']) ?>">
                </audio>
                <label style="display:flex;align-items:center;gap:.4rem;margin-top:.5rem;font-size:.8rem;color:#7B1450;cursor:pointer;">
                  <input type="checkbox" name="remove_model_audio" value="1"> Remove this audio
                </label>
              </div>
            <?php endif; ?>
            <input type="file" name="model_audio" class="form-control"
              accept="audio/mpeg,audio/wav,audio/ogg,audio/webm,audio/mp4"
              style="padding:.5rem;">
            <div class="form-hint">Upload your pronunciation model recording (MP3, WAV, OGG, WebM — max 30 MB). Students will listen to this as their reference.</div>
          </div>

          <button type="submit" class="btn btn-primary btn-block">
            <?= $editMaterial ? 'Update Material' : 'Add Material' ?>
          </button>
        </form>
      </div>
    </div>

    <!-- ── Materials List grouped by session ── -->
    <div>
      <?php if (empty($materials)): ?>
      <div class="empty-state">
        <div class="empty-state-icon">📚</div>
        <h3>No materials yet</h3>
        <p>Add your first reading material using the form.</p>
      </div>
      <?php else: ?>

      <?php for ($sn = 1; $sn <= 6; $sn++):
        $sessionMats = $bySession[$sn] ?? [];
      ?>
      <div class="session-group">
        <div class="session-group-header">
          <div class="session-group-num"><?= $sn ?></div>
          <div class="session-group-title"><?= e($sessionMeta[$sn]) ?></div>
          <div class="session-group-count"><?= count($sessionMats) ?> reading(s)</div>
        </div>

        <?php if (empty($sessionMats)): ?>
          <p class="text-muted text-sm" style="padding:.5rem 1rem;">No readings assigned to this session yet.</p>
        <?php else: ?>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Title</th>
                <th>Model Audio</th>
                <th>Words</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sessionMats as $m): ?>
              <tr>
                <td>
                  <span style="display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,#7B1450,#A855A0);color:#fff;font-size:.7rem;font-weight:700;">
                    <?= $m['reading_number'] ?>
                  </span>
                </td>
                <td>
                  <div style="font-weight:600;font-size:.875rem;"><?= e($m['title']) ?></div>
                  <div class="text-muted text-sm" style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($m['content']) ?></div>
                </td>
                <td class="text-sm text-muted"><?= str_word_count($m['content']) ?></td>
                <td>
                  <?php if (!empty($m['model_audio_path']) && file_exists(__DIR__ . '/../' . $m['model_audio_path'])): ?>
                    <span style="display:inline-flex;align-items:center;gap:.3rem;font-size:.75rem;font-weight:700;color:#7B1450;background:#FDF4FF;border:1px solid #E9B8D4;border-radius:999px;padding:.2rem .6rem;">
                      🎙️ Uploaded
                    </span>
                  <?php else: ?>
                    <span style="font-size:.75rem;color:#94A3B8;">— none</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="table-actions">
                    <a href="materials.php?edit=<?= $m['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                    <form method="POST" style="display:inline;">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $m['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger"
                        data-confirm="Delete this reading? All associated recordings will also be deleted.">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
      <?php endfor; ?>

      <?php endif; ?>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
