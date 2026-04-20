<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_role') {
    verifyCsrf();
    $userId  = (int)($_POST['user_id'] ?? 0);
    $newRole = $_POST['role'] ?? '';
    if ($userId && in_array($newRole, ['admin','student']) && $userId !== currentUserId()) {
        $pdo->prepare('UPDATE users SET role=? WHERE id=?')->execute([$newRole, $userId]);
        setFlash('success', 'User role updated.');
    } else {
        setFlash('error', 'Cannot change your own role.');
    }
    header('Location: users.php'); exit;
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verifyCsrf();
    $userId = (int)($_POST['user_id'] ?? 0);
    if ($userId && $userId !== currentUserId()) {
        // 1. Null out feedback_by references (this user may have given feedback)
        $pdo->prepare('UPDATE recordings SET feedback_by = NULL WHERE feedback_by = ?')->execute([$userId]);

        // 2. Delete their uploaded recording audio files
        $recs = $pdo->prepare('SELECT audio_path FROM recordings WHERE student_id = ?');
        $recs->execute([$userId]);
        foreach ($recs->fetchAll() as $rec) {
            $path = __DIR__ . '/../' . $rec['audio_path'];
            if (file_exists($path)) unlink($path);
        }

        // 3. Delete their recordings rows (FK cascade should handle it, but be explicit)
        $pdo->prepare('DELETE FROM recordings WHERE student_id = ?')->execute([$userId]);

        // 4. Delete the user
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);

        setFlash('success', 'User deleted successfully.');
    } else {
        setFlash('error', 'Cannot delete your own account.');
    }
    header('Location: users.php'); exit;
}

// Get all users with recording counts
$users = $pdo->query("
    SELECT u.*, COUNT(r.id) AS recording_count
    FROM users u
    LEFT JOIN recordings r ON r.student_id = u.id
    GROUP BY u.id
    ORDER BY u.role, u.name
")->fetchAll();

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="page-header">
    <div class="breadcrumb">
      <a href="dashboard.php">Dashboard</a>
      <span class="breadcrumb-sep">&#8250;</span>
      <span>Users</span>
    </div>
    <div class="page-header-inner">
      <div>
        <h1 class="page-title">&#128101; Manage Users</h1>
        <p class="page-subtitle"><?= count($users) ?> user(s) registered</p>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Recordings</th>
            <th>Joined</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td class="text-muted text-sm"><?= $u['id'] ?></td>
            <td>
              <strong><?= e($u['name']) ?></strong>
              <?php if ($u['id'] === currentUserId()): ?>
                <span class="badge badge-admin" style="margin-left:.4rem;">You</span>
              <?php endif; ?>
            </td>
            <td class="text-sm"><?= e($u['email']) ?></td>
            <td><span class="badge badge-<?= e($u['role']) ?>"><?= e(ucfirst($u['role'])) ?></span></td>
            <td class="text-sm"><?= $u['recording_count'] ?></td>
            <td class="text-sm text-muted"><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
            <td>
              <?php if ($u['id'] !== currentUserId()): ?>
              <div class="table-actions">
                <form method="POST" style="display:inline-flex;align-items:center;gap:.3rem;">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="change_role">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <select name="role" class="form-control" style="padding:.25rem .5rem;font-size:.8rem;width:auto;">
                    <option value="student" <?= $u['role']==='student'?'selected':'' ?>>Student</option>
                    <option value="admin"   <?= $u['role']==='admin'  ?'selected':'' ?>>Admin</option>
                  </select>
                  <button type="submit" class="btn btn-sm btn-outline">Save</button>
                </form>
                <form method="POST" style="display:inline;">
                  <?= csrfField() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger"
                    data-confirm="Delete user <?= e($u['name']) ?>? This will also delete all their recordings.">Delete</button>
                </form>
              </div>
              <?php else: ?>
              <span class="text-muted text-sm">Current user</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
