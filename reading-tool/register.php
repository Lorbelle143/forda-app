<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'dashboard.php'));
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, "student")');
            $stmt->execute([$name, $email, $hash]);
            setFlash('success', 'Account created! You can now log in.');
            header('Location: login.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — ReadEase</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body { background: linear-gradient(135deg, #064E3B 0%, #10B981 60%, #34D399 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }

    .auth-wrapper { width: 100%; max-width: 440px; }

    .auth-logo { text-align: center; margin-bottom: 2rem; }
    .auth-logo .logo-icon { font-size: 3rem; display: block; margin-bottom: .5rem; }
    .auth-logo h1 { color: #fff; font-size: 2rem; font-weight: 900; letter-spacing: -1px; }
    .auth-logo p { color: #A7F3D0; font-size: .9rem; margin-top: .25rem; }

    .auth-box {
      background: #fff;
      border-radius: 20px;
      padding: 2.5rem;
      box-shadow: 0 25px 60px rgba(0,0,0,.25);
    }

    .auth-box h2 { font-size: 1.4rem; font-weight: 800; color: #1E293B; margin-bottom: .25rem; }
    .auth-box .auth-sub { color: #64748B; font-size: .875rem; margin-bottom: 1.75rem; }

    .form-group { margin-bottom: 1.1rem; }
    .form-label { display: block; font-size: .82rem; font-weight: 700; color: #374151; margin-bottom: .4rem; text-transform: uppercase; letter-spacing: .04em; }
    .input-wrap { position: relative; }
    .input-icon { position: absolute; left: .9rem; top: 50%; transform: translateY(-50%); font-size: 1rem; pointer-events: none; }
    .form-control {
      width: 100%; padding: .7rem .9rem .7rem 2.5rem;
      border: 2px solid #E2E8F0; border-radius: 10px;
      font-size: .95rem; color: #1E293B; background: #F8FAFC;
      transition: border-color .15s, box-shadow .15s, background .15s;
      outline: none;
    }
    .form-control:focus { border-color: #10B981; background: #fff; box-shadow: 0 0 0 4px rgba(16,185,129,.1); }
    .form-control::placeholder { color: #9CA3AF; }

    .btn-register {
      width: 100%; padding: .85rem;
      background: linear-gradient(135deg, #10B981, #059669);
      color: #fff; border: none; border-radius: 10px;
      font-size: 1rem; font-weight: 700; cursor: pointer;
      margin-top: .5rem;
      transition: opacity .15s, transform .15s, box-shadow .15s;
      box-shadow: 0 4px 15px rgba(16,185,129,.4);
    }
    .btn-register:hover { opacity: .92; transform: translateY(-1px); box-shadow: 0 8px 20px rgba(16,185,129,.4); }
    .btn-register:active { transform: translateY(0); }

    .alert-error {
      background: #FEF2F2; color: #991B1B;
      border: 1px solid #FECACA; border-radius: 10px;
      padding: .75rem 1rem; font-size: .875rem;
      margin-bottom: 1.25rem; display: flex; align-items: center; gap: .5rem;
    }

    .auth-footer { text-align: center; margin-top: 1.5rem; font-size: .875rem; color: #64748B; }
    .auth-footer a { color: #10B981; font-weight: 700; text-decoration: none; }
    .auth-footer a:hover { text-decoration: underline; }

    .info-note { background: #ECFDF5; border-radius: 10px; padding: .75rem 1rem; font-size: .8rem; color: #065F46; margin-top: 1rem; text-align: center; }
  </style>
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-logo">
    <span class="logo-icon">📖</span>
    <h1>ReadEase</h1>
    <p>Join and start reading today</p>
  </div>

  <div class="auth-box">
    <h2>Create an account</h2>
    <p class="auth-sub">Sign up as a student to get started</p>

    <?php if ($error): ?>
      <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php">
      <?= csrfField() ?>
      <div class="form-group">
        <label class="form-label" for="name">Full Name</label>
        <div class="input-wrap">
          <span class="input-icon">👤</span>
          <input type="text" id="name" name="name" class="form-control"
                 placeholder="Your full name" required
                 value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <div class="input-wrap">
          <span class="input-icon">✉️</span>
          <input type="email" id="email" name="email" class="form-control"
                 placeholder="you@example.com" required
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-wrap">
          <span class="input-icon">🔒</span>
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="Min. 6 characters" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="confirm">Confirm Password</label>
        <div class="input-wrap">
          <span class="input-icon">🔑</span>
          <input type="password" id="confirm" name="confirm" class="form-control"
                 placeholder="Repeat your password" required>
        </div>
      </div>

      <button type="submit" class="btn-register">Create Account →</button>
    </form>

    <div class="auth-footer">
      Already have an account? <a href="login.php">Sign in</a>
    </div>

    <div style="text-align:center; margin-top:1rem;">
      <a href="index.php" style="font-size:.82rem; color:#94A3B8; text-decoration:none;">&#8592; Back to Home</a>
    </div>
  </div>
</div>
</body>
</html>
