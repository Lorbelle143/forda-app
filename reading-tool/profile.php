<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $currentPw = $_POST['current_password'] ?? '';
    $newPw     = $_POST['new_password'] ?? '';
    $confirmPw = $_POST['confirm_password'] ?? '';

    if (empty($currentPw) || empty($newPw) || empty($confirmPw)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($newPw) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($newPw !== $confirmPw) {
        $error = 'New passwords do not match.';
    } else {
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([currentUserId()]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPw, $user['password'])) {
            $error = 'Current password is incorrect.';
        } else {
            $hash = password_hash($newPw, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([$hash, currentUserId()]);
            setFlash('success', 'Password changed successfully.');
            header('Location: profile.php');
            exit;
        }
    }
}

// Get current user info
$stmt = $pdo->prepare('SELECT name, email, role, created_at FROM users WHERE id = ?');
$stmt->execute([currentUserId()]);
$user = $stmt->fetch();

// Get recording stats
$stmt = $pdo->prepare('SELECT COUNT(*) FROM recordings WHERE student_id = ?');
$stmt->execute([currentUserId()]);
$totalRec = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM recordings WHERE student_id = ? AND feedback IS NOT NULL');
$stmt->execute([currentUserId()]);
$reviewedRec = $stmt->fetchColumn();

$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:700px;">
  <div class="page-header">
    <div class="page-header-inner">
      <div>
        <h1 class="page-title">&#128100; My Profile</h1>
        <p class="page-subtitle">Manage your account settings</p>
      </div>
    </div>
  </div>

  <!-- Profile Info -->
  <div class="card section">
    <div class="card-header">
      <span class="card-title">Account Information</span>
      <span class="badge badge-<?= e($user['role']) ?>"><?= e(ucfirst($user['role'])) ?></span>
    </div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div>
          <div class="form-label">Full Name</div>
          <div style="font-weight:600;color:#1E293B;"><?= e($user['name']) ?></div>
        </div>
        <div>
          <div class="form-label">Email Address</div>
          <div style="font-weight:600;color:#1E293B;"><?= e($user['email']) ?></div>
        </div>
        <div>
          <div class="form-label">Member Since</div>
          <div style="color:#64748B;"><?= e(date('F j, Y', strtotime($user['created_at']))) ?></div>
        </div>
        <?php if ($user['role'] === 'student'): ?>
        <div>
          <div class="form-label">Recordings</div>
          <div style="color:#64748B;"><?= $totalRec ?> submitted &nbsp;&#183;&nbsp; <?= $reviewedRec ?> reviewed</div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Change Password -->
  <div class="card section">
    <div class="card-header">
      <span class="card-title">&#128274; Change Password</span>
    </div>
    <div class="card-body">
      <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?><button class="alert-close" onclick="this.parentElement.remove()">&#215;</button></div>
      <?php endif; ?>

      <form method="POST" action="profile.php">
        <?= csrfField() ?>
        <div class="form-group">
          <label class="form-label" for="current_password">Current Password</label>
          <input type="password" id="current_password" name="current_password"
                 class="form-control" placeholder="Enter current password" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="new_password">New Password</label>
          <input type="password" id="new_password" name="new_password"
                 class="form-control" placeholder="Min. 6 characters" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="confirm_password">Confirm New Password</label>
          <input type="password" id="confirm_password" name="confirm_password"
                 class="form-control" placeholder="Repeat new password" required>
        </div>
        <button type="submit" class="btn btn-primary">Update Password</button>
      </form>
    </div>
  </div>

  <div style="text-align:center;margin-bottom:2rem;">
    <a href="<?= isAdmin() ? 'admin/dashboard.php' : 'dashboard.php' ?>" class="btn btn-ghost">&#8592; Back to Dashboard</a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
