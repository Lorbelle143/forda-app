<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$error = '';
$success = '';
$editMaterial = null;

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    verifyCsrf();
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $level   = $_POST['level'] ?? 'beginner';
    $allowed = ['beginner','intermediate','advanced'];
    if (empty($title) || empty($content)) {
        $error = 'Title and content are required.';
    } elseif (!in_array($level, $allowed)) {
        $error = 'Invalid level.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO reading_materials (title, content, level, created_by) VALUES (?,?,?,?)');
        $stmt->execute([$title, $content, $level, currentUserId()]);
        setFlash('success', 'Material added successfully.');
        header('Location: materials.php'); exit;
    }
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    verifyCsrf();
    $id      = (int)($_POST['id'] ?? 0);
    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $level   = $_POST['level'] ?? 'beginner';
    $allowed = ['beginner','intermediate','advanced'];
    if (empty($title) || empty($content) || !$id) {
        $error = 'All fields are required.';
    } elseif (!in_array($level, $allowed)) {
        $error = 'Invalid level.';
    } else {
        $stmt = $pdo->prepare('UPDATE reading_materials SET title=?, content=?, level=? WHERE id=?');
        $stmt->execute([$title, $content, $level, $id]);
        setFlash('success', 'Material updated.');
        header('Location: materials.php'); exit;
    }
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verifyCsrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $pdo->prepare('DELETE FROM reading_materials WHERE id=?')->execute([$id]);
        setFlash('success', 'Material deleted.');
    }
    header('Location: materials.php'); exit;
}

// Load edit form
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM reading_materials WHERE id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $editMaterial = $stmt->fetch();
}

// Get all materials
$materials = $pdo->query('SELECT m.*, u.name AS creator FROM reading_materials m LEFT JOIN users u ON m.created_by=u.id ORDER BY m.created_at DESC')->fetchAll();

$pageTitle = 'Manage Materials';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="page-header">
    <div class="breadcrumb">
      <a href="dashboard.php">Dashboard</a>
      <span class="breadcrumb-sep">&#8250;</span>
      <span>Materials</span>
    </div>
    <div class="page-header-inner">
      <div>
        <h1 class="page-title">&#128218; Reading Materials</h1>
        <p class="page-subtitle"><?= count($materials) ?> material(s) total</p>
      </div>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?><button class="alert-close" onclick="this.parentElement.remove()">&#215;</button></div>
  <?php endif; ?>

  <div class="grid-2" style="align-items:start;">

    <!-- Form -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><?= $editMaterial ? '&#9998; Edit Material' : '&#43; Add New Material' ?></span>
        <?php if ($editMaterial): ?>
          <a href="materials.php" class="btn btn-ghost btn-sm">Cancel</a>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <form method="POST" action="materials.php">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="<?= $editMaterial ? 'edit' : 'add' ?>">
          <?php if ($editMaterial): ?>
            <input type="hidden" name="id" value="<?= (int)$editMaterial['id'] ?>">
          <?php endif; ?>

          <div class="form-group">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required
              value="<?= e($editMaterial['title'] ?? '') ?>" placeholder="e.g. The Cat">
          </div>
          <div class="form-group">
            <label class="form-label">Content</label>
            <textarea name="content" class="form-control" rows="6" required
              placeholder="Enter the reading text here..."><?= e($editMaterial['content'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Level</label>
            <select name="level" class="form-control">
              <?php foreach (['beginner','intermediate','advanced'] as $lvl): ?>
              <option value="<?= $lvl ?>" <?= (($editMaterial['level'] ?? 'beginner') === $lvl) ? 'selected' : '' ?>><?= ucfirst($lvl) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary btn-block">
            <?= $editMaterial ? 'Update Material' : 'Add Material' ?>
          </button>
        </form>
      </div>
    </div>

    <!-- Table -->
    <div>
      <?php if (empty($materials)): ?>
      <div class="empty-state">
        <div class="empty-state-icon">&#128218;</div>
        <h3>No materials yet</h3>
        <p>Add your first reading material using the form.</p>
      </div>
      <?php else: ?>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr><th>Title</th><th>Level</th><th>Created</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($materials as $m): ?>
            <tr>
              <td>
                <div style="font-weight:600;"><?= e($m['title']) ?></div>
                <div class="text-muted text-sm" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($m['content']) ?></div>
              </td>
              <td><span class="badge badge-<?= e($m['level']) ?>"><?= e(ucfirst($m['level'])) ?></span></td>
              <td class="text-sm text-muted"><?= e(date('M j, Y', strtotime($m['created_at']))) ?></td>
              <td>
                <div class="table-actions">
                  <a href="materials.php?edit=<?= $m['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                  <form method="POST" style="display:inline;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"
                      data-confirm="Delete this material? All associated recordings will also be deleted.">Delete</button>
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

  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
