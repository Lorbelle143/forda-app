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
  <title>Login — A.I.M.</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#7B1450">
  <style>
    body {
      background: #1A0A12;
      min-height: 100vh;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      padding: 2rem 1.5rem;
      position: relative;
      overflow-x: hidden;
    }

    .bg-waves {
      position: fixed;
      inset: 0;
      pointer-events: none;
      z-index: 0;
      overflow: hidden;
    }
    .bg-wave {
      position: absolute;
      width: 200%;
      height: 200%;
      border-radius: 45%;
      opacity: .06;
      animation: waveRotate 18s linear infinite;
    }
    .bg-wave:nth-child(1) { background: #A855A0; top: -80%; left: -50%; animation-duration: 18s; }
    .bg-wave:nth-child(2) { background: #7B1450; top: -60%; left: -30%; animation-duration: 24s; animation-direction: reverse; }
    .bg-wave:nth-child(3) { background: #C026A0; bottom: -80%; right: -50%; animation-duration: 20s; }
    @keyframes waveRotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    .bg-glow {
      position: fixed;
      width: 600px; height: 600px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(168,85,160,.18) 0%, transparent 70%);
      top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      pointer-events: none;
      z-index: 0;
    }

    .auth-wrapper {
      width: 100%;
      max-width: 440px;
      position: relative;
      z-index: 1;
      margin: auto;
    }

    /* AIM Logo */
    .aim-auth-logo {
      text-align: center;
      margin-bottom: 2rem;
    }
    .aim-logo-icon-wrap {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 80px; height: 80px;
      border-radius: 50%;
      background: linear-gradient(135deg, #7B1450, #A855A0);
      margin-bottom: 1rem;
      box-shadow: 0 0 40px rgba(168,85,160,.4);
    }
    .aim-logo-icon-wrap span { font-size: 2.2rem; }
    .aim-auth-title {
      font-size: 2.5rem;
      font-weight: 900;
      color: #fff;
      letter-spacing: 4px;
      text-shadow: 0 0 30px rgba(168,85,160,.5);
    }
    .aim-auth-subtitle {
      color: #C9A0B8;
      font-size: .8rem;
      letter-spacing: .08em;
      margin-top: .3rem;
    }

    /* Auth card */
    .auth-box {
      background: rgba(255,255,255,.04);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(168,85,160,.25);
      border-radius: 20px;
      padding: 2.5rem;
      box-shadow: 0 25px 60px rgba(0,0,0,.5), inset 0 1px 0 rgba(255,255,255,.06);
    }
    .auth-box h2 {
      font-size: 1.4rem;
      font-weight: 800;
      color: #fff;
      margin-bottom: .25rem;
    }
    .auth-box .auth-sub {
      color: #C9A0B8;
      font-size: .875rem;
      margin-bottom: 1.75rem;
    }

    .form-label {
      display: block;
      font-size: .78rem;
      font-weight: 700;
      color: #C9A0B8;
      margin-bottom: .4rem;
      text-transform: uppercase;
      letter-spacing: .06em;
    }
    .input-wrap { position: relative; }
    .input-icon {
      position: absolute;
      left: .9rem;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1rem;
      pointer-events: none;
    }
    .form-control {
      width: 100%;
      padding: .75rem .9rem .75rem 2.6rem;
      border: 1.5px solid rgba(168,85,160,.3);
      border-radius: 10px;
      font-size: .95rem;
      color: #fff;
      background: rgba(255,255,255,.06);
      transition: border-color .15s, box-shadow .15s, background .15s;
      outline: none;
    }
    .form-control:focus {
      border-color: #A855A0;
      background: rgba(255,255,255,.1);
      box-shadow: 0 0 0 3px rgba(168,85,160,.2);
    }
    .form-control::placeholder { color: rgba(201,160,184,.5); }
    .form-group { margin-bottom: 1.1rem; }

    .btn-login {
      width: 100%;
      padding: .9rem;
      background: linear-gradient(135deg, #7B1450, #A855A0);
      color: #fff;
      border: none;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      margin-top: .5rem;
      transition: opacity .15s, transform .15s, box-shadow .15s;
      box-shadow: 0 4px 20px rgba(123,20,80,.5);
      letter-spacing: .03em;
    }
    .btn-login:hover {
      opacity: .92;
      transform: translateY(-1px);
      box-shadow: 0 8px 28px rgba(123,20,80,.6);
    }

    .alert-error {
      background: rgba(239,68,68,.15);
      color: #FCA5A5;
      border: 1px solid rgba(239,68,68,.3);
      border-radius: 10px;
      padding: .75rem 1rem;
      font-size: .875rem;
      margin-bottom: 1.25rem;
      display: flex;
      align-items: center;
      gap: .5rem;
    }
    .alert-success-auth {
      background: rgba(16,185,129,.12);
      color: #6EE7B7;
      border: 1px solid rgba(16,185,129,.25);
      border-radius: 10px;
      padding: .75rem 1rem;
      font-size: .875rem;
      margin-bottom: 1.25rem;
    }

    .auth-footer {
      text-align: center;
      margin-top: 1.5rem;
      font-size: .875rem;
      color: #8B5070;
    }
    .auth-footer a {
      color: #F0A0D8;
      font-weight: 700;
      text-decoration: none;
    }
    .auth-footer a:hover { text-decoration: underline; }

    .auth-back {
      text-align: center;
      margin-top: 1rem;
    }
    .auth-back a {
      font-size: .82rem;
      color: #8B5070;
      text-decoration: none;
    }
    .auth-back a:hover { color: #C9A0B8; }
  </style>
</head>
<body>
<div class="bg-waves">
  <div class="bg-wave"></div>
  <div class="bg-wave"></div>
  <div class="bg-wave"></div>
</div>
<div class="bg-glow"></div>

<div class="auth-wrapper">
  <div class="aim-auth-logo">
    <div class="aim-logo-icon-wrap"><span>🎙️</span></div>
    <div class="aim-auth-title">A.I.M.</div>
    <div class="aim-auth-subtitle">Audio-Visual Intervention Mirroring</div>
  </div>

  <div class="auth-box">
    <h2>Welcome back</h2>
    <p class="auth-sub">Sign in to continue your pronunciation journey</p>

    <?php if ($error): ?>
      <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php
      $flash = getFlash();
      if ($flash && $flash['type'] === 'success'):
    ?>
      <div class="alert-success-auth">✅ <?= htmlspecialchars($flash['message']) ?></div>
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
    <div class="auth-back">
      <a href="index.php">← Back to Home</a>
    </div>
  </div>
</div>

<script>
if ('serviceWorker' in navigator) { navigator.serviceWorker.register('sw.js'); }
</script>
</body>
</html>
