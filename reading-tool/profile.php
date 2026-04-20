<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
requireLogin();

$error   = '';

// ---- Handle photo upload ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'photo') {
    verifyCsrf();

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a valid image file.';
    } else {
        $file    = $_FILES['avatar'];
        $maxSize = 3 * 1024 * 1024; // 3MB

        if ($file['size'] > $maxSize) {
            $error = 'Image must be under 3MB.';
        } else {
            $finfo    = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            $allowed  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

            if (!array_key_exists($mimeType, $allowed)) {
                $error = 'Only JPG, PNG, GIF, or WEBP images are allowed.';
            } else {
                $ext      = $allowed[$mimeType];
                $filename = 'avatar_' . currentUserId() . '_' . time() . '.' . $ext;
                $uploadDir = __DIR__ . '/uploads/avatars/';

                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                // Delete old avatar
                $stmt = $pdo->prepare('SELECT avatar FROM users WHERE id = ?');
                $stmt->execute([currentUserId()]);
                $old = $stmt->fetchColumn();
                if ($old && file_exists(__DIR__ . '/' . $old)) {
                    unlink(__DIR__ . '/' . $old);
                }

                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    $dbPath = 'uploads/avatars/' . $filename;
                    $pdo->prepare('UPDATE users SET avatar = ? WHERE id = ?')
                        ->execute([$dbPath, currentUserId()]);
                    // Update session
                    $_SESSION['avatar'] = $dbPath;
                    setFlash('success', 'Profile photo updated!');
                    header('Location: profile.php');
                    exit;
                } else {
                    $error = 'Failed to save image. Check folder permissions.';
                }
            }
        }
    }
}

// ---- Handle password change ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'password') {
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
        $row = $stmt->fetch();

        if (!$row || !password_verify($currentPw, $row['password'])) {
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
$stmt = $pdo->prepare('SELECT name, email, role, created_at, avatar FROM users WHERE id = ?');
$stmt->execute([currentUserId()]);
$user = $stmt->fetch();

// Recording stats
$stmt = $pdo->prepare('SELECT COUNT(*) FROM recordings WHERE student_id = ?');
$stmt->execute([currentUserId()]);
$totalRec = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM recordings WHERE student_id = ? AND feedback IS NOT NULL');
$stmt->execute([currentUserId()]);
$reviewedRec = $stmt->fetchColumn();

$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:720px;">
  <div class="page-header">
    <div class="page-header-inner">
      <div>
        <h1 class="page-title">&#128100; My Profile</h1>
        <p class="page-subtitle">Manage your account settings</p>
      </div>
    </div>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?><button class="alert-close" onclick="this.parentElement.remove()">&#215;</button></div>
  <?php endif; ?>

  <!-- Profile Photo + Info -->
  <div class="card section">
    <div class="card-body">
      <div style="display:flex;align-items:center;gap:2rem;flex-wrap:wrap;">

        <!-- Avatar -->
        <div style="text-align:center;flex-shrink:0;">
          <?php if (!empty($user['avatar']) && file_exists(__DIR__ . '/' . $user['avatar'])): ?>
            <img src="<?= e($user['avatar']) ?>?v=<?= time() ?>"
                 alt="Profile Photo"
                 style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid #E2E8F0;box-shadow:0 2px 8px rgba(0,0,0,.1);">
          <?php else: ?>
            <div style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#4F46E5);color:#fff;font-size:2.5rem;font-weight:800;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(99,102,241,.3);">
              <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
          <?php endif; ?>

          <!-- Upload form -->
          <form method="POST" action="profile.php" enctype="multipart/form-data" style="margin-top:.75rem;">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="photo">
            <label style="cursor:pointer;display:inline-block;">
              <span class="btn btn-sm btn-outline" style="font-size:.78rem;">&#128247; Change Photo</span>
              <input type="file" name="avatar" accept="image/*" style="display:none;"
                     onchange="this.form.submit()">
            </label>
          </form>
          <div style="font-size:.72rem;color:#94A3B8;margin-top:.3rem;">JPG, PNG, WEBP · Max 3MB</div>
        </div>

        <!-- Info -->
        <div style="flex:1;min-width:200px;">
          <div style="font-size:1.3rem;font-weight:800;color:#1E293B;margin-bottom:.25rem;"><?= e($user['name']) ?></div>
          <div style="color:#64748B;font-size:.9rem;margin-bottom:.5rem;"><?= e($user['email']) ?></div>
          <span class="badge badge-<?= e($user['role']) ?>"><?= e(ucfirst($user['role'])) ?></span>
          <div style="margin-top:.75rem;font-size:.85rem;color:#64748B;">
            Member since <?= e(date('F j, Y', strtotime($user['created_at']))) ?>
          </div>
          <?php if ($user['role'] === 'student'): ?>
          <div style="margin-top:.5rem;font-size:.85rem;color:#64748B;">
            <?= $totalRec ?> recording<?= $totalRec !== 1 ? 's' : '' ?> submitted &nbsp;·&nbsp; <?= $reviewedRec ?> reviewed
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Change Password -->
  <div class="card section">
    <div class="card-header">
      <span class="card-title">&#128274; Change Password</span>
    </div>
    <div class="card-body">
      <form method="POST" action="profile.php">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="password">
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
