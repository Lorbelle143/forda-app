<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'dashboard.php'));
    exit;
}
$pageTitle = 'Welcome';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ===== HERO ===== -->
<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-grid"></div>
  <div class="container hero-content">

    <div class="hero-eyebrow">&#128218; Reading Tool for Students</div>

    <h1>Read Better.<br><span class="accent">Learn Faster.</span></h1>

    <p class="hero-desc">
      ReadEase combines text-to-speech, voice recording, and personalized teacher
      feedback into one simple platform — built to help every student improve.
    </p>

    <div class="hero-actions">
      <a href="login.php" class="btn btn-hero-primary btn-lg">Get Started Free</a>
      <button id="pwaInstallBtn" class="btn btn-hero-outline btn-lg" style="display:none;">
        &#11015; Install App
      </button>
      <a href="#features" class="btn btn-hero-outline btn-lg" id="learnMoreBtn">See How It Works</a>
    </div>

    <div class="hero-stats">
      <div>
        <div class="hero-stat-value">3</div>
        <div class="hero-stat-label">Difficulty Levels</div>
      </div>
      <div>
        <div class="hero-stat-value">TTS</div>
        <div class="hero-stat-label">Text-to-Speech</div>
      </div>
      <div>
        <div class="hero-stat-value">100%</div>
        <div class="hero-stat-label">Free to Use</div>
      </div>
    </div>

  </div>
</section>

<!-- ===== FEATURES ===== -->
<section class="features" id="features">
  <div class="container">
    <div class="features-header">
      <div class="section-label">Features</div>
      <h2 class="section-title">Everything you need to read better</h2>
      <p class="section-desc">Powerful tools designed for students and teachers — simple enough for anyone to use.</p>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon-wrap purple">&#128266;</div>
        <h3>Text-to-Speech</h3>
        <p>Listen to any reading material read aloud with real-time word-by-word highlighting. Control speed from slow to fast.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon-wrap red">&#127908;</div>
        <h3>Voice Recording</h3>
        <p>Record yourself reading directly in the browser and submit your recording to your teacher with one click.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon-wrap green">&#128172;</div>
        <h3>Teacher Feedback</h3>
        <p>Receive personalized written feedback from your teacher on every recording you submit — tracked over time.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon-wrap blue">&#128218;</div>
        <h3>Leveled Materials</h3>
        <p>Reading materials organized by difficulty: Beginner, Intermediate, and Advanced — so every student finds the right challenge.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon-wrap amber">&#128202;</div>
        <h3>Progress Tracking</h3>
        <p>Students and teachers can view all submitted recordings and feedback history in one organized dashboard.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon-wrap teal">&#128737;</div>
        <h3>Secure Access</h3>
        <p>Role-based login system keeps student and admin areas separate. Accounts are protected with encrypted passwords.</p>
      </div>
    </div>
  </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section class="how-it-works" id="how">
  <div class="container">
    <div class="section-header">
      <div class="section-label" style="text-align:center;">How It Works</div>
      <h2 class="section-title" style="text-align:center;">Three simple steps</h2>
    </div>
    <div class="steps">
      <div class="step">
        <div class="step-number">1</div>
        <h3>Choose a Material</h3>
        <p>Browse reading materials by difficulty level and pick one that matches your skill.</p>
      </div>
      <div class="step">
        <div class="step-number">2</div>
        <h3>Read &amp; Record</h3>
        <p>Use text-to-speech to hear the text, then record yourself reading it aloud.</p>
      </div>
      <div class="step">
        <div class="step-number">3</div>
        <h3>Get Feedback</h3>
        <p>Submit your recording and receive personalized feedback from your teacher.</p>
      </div>
    </div>
  </div>
</section>

<!-- ===== PRIVACY NOTICE ===== -->
<section style="background:#F8FAFC;padding:4rem 0;border-top:1px solid #E2E8F0;">
  <div class="container" style="max-width:760px;text-align:center;">
    <div class="section-label">Privacy &amp; Data Protection</div>
    <h2 class="section-title" style="margin-bottom:1rem;">Your data is safe with us</h2>
    <p style="color:#64748B;font-size:.95rem;line-height:1.8;margin-bottom:2rem;">
      ReadEase collects only what is necessary to run the platform — your name, email, and voice recordings.
      We never sell your data. Recordings are accessible only to your teacher for feedback purposes.
      Passwords are encrypted and sessions are secured. No third-party trackers or ads.
    </p>
    <div style="display:flex;justify-content:center;gap:2rem;flex-wrap:wrap;margin-bottom:2rem;">
      <div style="display:flex;align-items:center;gap:.5rem;font-size:.875rem;color:#475569;">
        <span style="color:#10B981;font-size:1.1rem;">&#10003;</span> Encrypted passwords
      </div>
      <div style="display:flex;align-items:center;gap:.5rem;font-size:.875rem;color:#475569;">
        <span style="color:#10B981;font-size:1.1rem;">&#10003;</span> No third-party tracking
      </div>
      <div style="display:flex;align-items:center;gap:.5rem;font-size:.875rem;color:#475569;">
        <span style="color:#10B981;font-size:1.1rem;">&#10003;</span> CSRF-protected forms
      </div>
      <div style="display:flex;align-items:center;gap:.5rem;font-size:.875rem;color:#475569;">
        <span style="color:#10B981;font-size:1.1rem;">&#10003;</span> Data never sold
      </div>
      <div style="display:flex;align-items:center;gap:.5rem;font-size:.875rem;color:#475569;">
        <span style="color:#10B981;font-size:1.1rem;">&#10003;</span> Recordings for teachers only
      </div>
      <div style="display:flex;align-items:center;gap:.5rem;font-size:.875rem;color:#475569;">
        <span style="color:#10B981;font-size:1.1rem;">&#10003;</span> You can delete your recordings
      </div>
    </div>
    <a href="privacy.php" class="btn btn-outline">Read Full Privacy Policy &#8594;</a>
  </div>
</section>

<!-- ===== CTA ===== -->
<section class="cta-section">
  <div class="container inner">
    <h2>Ready to start reading?</h2>
    <p>Join ReadEase today and take your reading skills to the next level.</p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
      <a href="register.php" class="btn btn-hero-primary btn-lg">Create Free Account</a>
      <a href="login.php" class="btn btn-hero-outline btn-lg">Sign In</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
// PWA Install prompt
var pwaInstallEvent = null;
var installBtn = document.getElementById('pwaInstallBtn');

window.addEventListener('beforeinstallprompt', function(e) {
  e.preventDefault();
  pwaInstallEvent = e;
  if (installBtn) {
    installBtn.style.display = 'inline-flex';
  }
});

if (installBtn) {
  installBtn.addEventListener('click', function() {
    if (!pwaInstallEvent) return;
    pwaInstallEvent.prompt();
    pwaInstallEvent.userChoice.then(function(result) {
      if (result.outcome === 'accepted') {
        installBtn.style.display = 'none';
      }
      pwaInstallEvent = null;
    });
  });
}

// If already installed, hide the button
window.addEventListener('appinstalled', function() {
  if (installBtn) installBtn.style.display = 'none';
});
</script>
