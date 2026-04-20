<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (!checkLoginRateLimit()) {
        $error = 'Too many login attempts. Please wait 15 minutes and try again.';
    } else {
        $stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            resetLoginRateLimit();
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
            setFlash('success', 'Welcome back, ' . $user['name'] . '!');
            header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php'));
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — ReadEase</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body { background: linear-gradient(135deg, #1E1B4B 0%, #4F46E5 60%, #7C3AED 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }

    .auth-wrapper { width: 100%; max-width: 420px; }

    .auth-logo { text-align: center; margin-bottom: 2rem; }
    .auth-logo .logo-icon { font-size: 3rem; display: block; margin-bottom: .5rem; }
    .auth-logo h1 { color: #fff; font-size: 2rem; font-weight: 900; letter-spacing: -1px; }
    .auth-logo p { color: #C4B5FD; font-size: .9rem; margin-top: .25rem; }

    .auth-box {
      background: #fff;
      border-radius: 20px;
      padding: 2.5rem;
      box-shadow: 0 25px 60px rgba(0,0,0,.3);
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
    .form-control:focus { border-color: #4F46E5; background: #fff; box-shadow: 0 0 0 4px rgba(79,70,229,.1); }
    .form-control::placeholder { color: #9CA3AF; }

    .btn-login {
      width: 100%; padding: .85rem;
      background: linear-gradient(135deg, #4F46E5, #7C3AED);
      color: #fff; border: none; border-radius: 10px;
      font-size: 1rem; font-weight: 700; cursor: pointer;
      margin-top: .5rem;
      transition: opacity .15s, transform .15s, box-shadow .15s;
      box-shadow: 0 4px 15px rgba(79,70,229,.4);
    }
    .btn-login:hover { opacity: .92; transform: translateY(-1px); box-shadow: 0 8px 20px rgba(79,70,229,.4); }
    .btn-login:active { transform: translateY(0); }

    .alert-error {
      background: #FEF2F2; color: #991B1B;
      border: 1px solid #FECACA; border-radius: 10px;
      padding: .75rem 1rem; font-size: .875rem;
      margin-bottom: 1.25rem; display: flex; align-items: center; gap: .5rem;
    }

    .auth-footer { text-align: center; margin-top: 1.5rem; font-size: .875rem; color: #64748B; }
    .auth-footer a { color: #4F46E5; font-weight: 700; text-decoration: none; }
    .auth-footer a:hover { text-decoration: underline; }

    .divider { display: flex; align-items: center; gap: .75rem; margin: 1.25rem 0; color: #CBD5E1; font-size: .8rem; }
    .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #E2E8F0; }

    .demo-hint { background: #EEF2FF; border-radius: 10px; padding: .75rem 1rem; font-size: .8rem; color: #4338CA; text-align: center; }
    .demo-hint strong { display: block; margin-bottom: .2rem; font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: #6366F1; }
  </style>
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-logo">
    <span class="logo-icon">📖</span>
    <h1>ReadEase</h1>
    <p>Your Reading Companion</p>
  </div>

  <div class="auth-box">
    <h2>Welcome back</h2>
    <p class="auth-sub">Sign in to your account to continue</p>

    <?php if ($error): ?>
      <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php
      $flash = getFlash();
      if ($flash && $flash['type'] === 'success'):
    ?>
      <div style="background:#ECFDF5;color:#065F46;border:1px solid #A7F3D0;border-radius:10px;padding:.75rem 1rem;font-size:.875rem;margin-bottom:1.25rem;">
        ✅ <?= htmlspecialchars($flash['message']) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <?= csrfField() ?>
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
                 placeholder="Enter your password" required>
        </div>
      </div>

      <button type="submit" class="btn-login">Sign In →</button>
    </form>

    <div class="auth-footer">
      Don't have an account? <a href="register.php">Create one</a>
    </div>

    <div style="text-align:center; margin-top:1rem;">
      <a href="index.php" style="font-size:.82rem; color:#94A3B8; text-decoration:none;">&#8592; Back to Home</a>
      &nbsp;&nbsp;
      <button id="pwaInstallBtn" style="display:none;background:none;border:none;font-size:.82rem;color:#94A3B8;cursor:pointer;">&#11015; Install App</button>
    </div>
  </div>
</div>

<script>
var pwaInstallEvent = null;
var installBtn = document.getElementById('pwaInstallBtn');

window.addEventListener('beforeinstallprompt', function(e) {
  e.preventDefault();
  pwaInstallEvent = e;
  if (installBtn) installBtn.style.display = 'inline';
});

if (installBtn) {
  installBtn.addEventListener('click', function() {
    if (!pwaInstallEvent) return;
    pwaInstallEvent.prompt();
    pwaInstallEvent.userChoice.then(function() {
      pwaInstallEvent = null;
      installBtn.style.display = 'none';
    });
  });
}

window.addEventListener('appinstalled', function() {
  if (installBtn) installBtn.style.display = 'none';
});
</script>

<!-- PWA -->
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#4F46E5">
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('sw.js');
}
</script>
</body>
</html>
