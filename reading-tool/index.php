<?php
require_once __DIR__ . '/includes/auth.php';
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'dashboard.php'));
    exit;
}
$pageTitle = 'Welcome';
require_once __DIR__ . '/includes/header.php';
?>

<style>
/* ---- AIM Landing Page Styles ---- */
.aim-hero {
  position: relative;
  background: #1A0A12;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  padding: 5rem 0 4rem;
}

/* Animated wave lines like the prototype */
.aim-hero-waves {
  position: absolute;
  inset: 0;
  pointer-events: none;
  overflow: hidden;
}
.aim-wave-svg {
  position: absolute;
  width: 100%;
  opacity: .18;
}
.aim-wave-svg.top { top: 0; }
.aim-wave-svg.bottom { bottom: 0; transform: scaleY(-1); }

/* Radial glow */
.aim-hero-glow {
  position: absolute;
  width: 700px; height: 700px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(168,85,160,.22) 0%, transparent 65%);
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  pointer-events: none;
}

.aim-hero-content {
  position: relative;
  z-index: 1;
  text-align: center;
  max-width: 700px;
  margin: 0 auto;
  padding: 0 1.5rem;
}

/* AIM Logo */
.aim-hero-logo {
  margin-bottom: 2rem;
}
.aim-hero-logo-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 100px; height: 100px;
  border-radius: 50%;
  background: linear-gradient(135deg, #7B1450, #A855A0);
  margin-bottom: 1.25rem;
  box-shadow: 0 0 60px rgba(168,85,160,.45), 0 0 120px rgba(123,20,80,.2);
  position: relative;
}
.aim-hero-logo-icon::before {
  content: '';
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  border: 2px solid rgba(168,85,160,.3);
}
.aim-hero-logo-icon span { font-size: 2.8rem; }

.aim-hero-title {
  font-size: clamp(3.5rem, 10vw, 6rem);
  font-weight: 900;
  color: #fff;
  letter-spacing: 8px;
  line-height: 1;
  text-shadow: 0 0 40px rgba(168,85,160,.5);
  margin-bottom: .5rem;
}
.aim-hero-fullname {
  font-size: clamp(1rem, 2.5vw, 1.4rem);
  font-weight: 700;
  color: #E0A0C8;
  letter-spacing: .05em;
  margin-bottom: .75rem;
}
.aim-hero-tagline {
  font-size: .9rem;
  color: #8B5070;
  letter-spacing: .06em;
  margin-bottom: 3rem;
}

.aim-hero-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 4rem;
}
.btn-aim-primary {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  padding: .9rem 2.2rem;
  background: linear-gradient(135deg, #7B1450, #A855A0);
  color: #fff;
  border: none;
  border-radius: 50px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  text-decoration: none;
  transition: transform .15s, box-shadow .15s, opacity .15s;
  box-shadow: 0 4px 24px rgba(123,20,80,.5);
  letter-spacing: .03em;
}
.btn-aim-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 32px rgba(123,20,80,.65);
  opacity: .95;
  text-decoration: none;
  color: #fff;
}
.btn-aim-outline {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  padding: .9rem 2.2rem;
  background: transparent;
  color: #E0A0C8;
  border: 1.5px solid rgba(168,85,160,.4);
  border-radius: 50px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: all .15s;
}
.btn-aim-outline:hover {
  background: rgba(168,85,160,.1);
  border-color: #A855A0;
  color: #fff;
  text-decoration: none;
}

/* Stats row */
.aim-hero-stats {
  display: flex;
  justify-content: center;
  gap: 3rem;
  flex-wrap: wrap;
  padding-top: 2.5rem;
  border-top: 1px solid rgba(168,85,160,.15);
}
.aim-stat-value { font-size: 2rem; font-weight: 900; color: #fff; line-height: 1; }
.aim-stat-label { font-size: .75rem; color: #8B5070; margin-top: .3rem; text-transform: uppercase; letter-spacing: .08em; }

/* ---- How It Works ---- */
.aim-section {
  padding: 6rem 0;
  background: #FDF8FB;
}
.aim-section-dark {
  padding: 6rem 0;
  background: #1A0A12;
  position: relative;
  overflow: hidden;
}
.aim-section-dark::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse 80% 50% at 50% 50%, rgba(168,85,160,.08) 0%, transparent 70%);
  pointer-events: none;
}

.aim-section-label {
  display: inline-block;
  font-size: .72rem;
  font-weight: 700;
  color: #A855A0;
  text-transform: uppercase;
  letter-spacing: .12em;
  margin-bottom: .75rem;
}
.aim-section-title {
  font-size: clamp(1.75rem, 4vw, 2.5rem);
  font-weight: 900;
  color: #1A0A12;
  line-height: 1.2;
  margin-bottom: 1rem;
}
.aim-section-title.light { color: #fff; }
.aim-section-desc {
  font-size: .95rem;
  color: #64748B;
  max-width: 520px;
  line-height: 1.7;
}
.aim-section-desc.light { color: #C9A0B8; }

/* Session steps */
.aim-steps-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
  margin-top: 3rem;
}
.aim-step-card {
  background: #fff;
  border: 1.5px solid #F0D0E8;
  border-radius: 16px;
  padding: 2rem 1.5rem;
  text-align: center;
  transition: box-shadow .2s, transform .2s, border-color .2s;
}
.aim-step-card:hover {
  box-shadow: 0 12px 32px rgba(123,20,80,.1);
  transform: translateY(-4px);
  border-color: #A855A0;
}
.aim-step-num-circle {
  width: 52px; height: 52px;
  border-radius: 50%;
  background: linear-gradient(135deg, #7B1450, #A855A0);
  color: #fff;
  font-size: 1.2rem;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.25rem;
  box-shadow: 0 4px 16px rgba(123,20,80,.3);
}
.aim-step-card h3 { font-size: 1rem; font-weight: 700; color: #7B1450; margin-bottom: .5rem; }
.aim-step-card p { font-size: .875rem; color: #64748B; line-height: 1.6; }

/* Week levels */
.aim-weeks-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 1.5rem;
  margin-top: 3rem;
}
.aim-week-card {
  border-radius: 16px;
  padding: 2rem;
  position: relative;
  overflow: hidden;
  transition: transform .2s, box-shadow .2s;
}
.aim-week-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,.15); }
.aim-week-card.week1 { background: linear-gradient(135deg, #FDF4FF, #F5E8F9); border: 1.5px solid #E9B8D4; }
.aim-week-card.week2 { background: linear-gradient(135deg, #F5E8F9, #EDD5F5); border: 1.5px solid #D4A0E0; }
.aim-week-card.week3 { background: linear-gradient(135deg, #EDD5F5, #E0C0F0); border: 1.5px solid #C080D8; }
.aim-week-badge {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .3rem .85rem;
  border-radius: 999px;
  font-size: .72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .06em;
  margin-bottom: 1rem;
}
.week1 .aim-week-badge { background: rgba(123,20,80,.12); color: #7B1450; }
.week2 .aim-week-badge { background: rgba(107,33,119,.12); color: #6B2177; }
.week3 .aim-week-badge { background: rgba(74,16,96,.12); color: #4A1060; }
.aim-week-card h3 { font-size: 1.1rem; font-weight: 800; color: #1A0A12; margin-bottom: .4rem; }
.aim-week-card p { font-size: .875rem; color: #64748B; line-height: 1.6; }
.aim-week-sessions { font-size: .75rem; font-weight: 700; color: #A855A0; margin-top: .75rem; }

/* Features */
.aim-features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 1.5rem;
  margin-top: 3rem;
}
.aim-feature-card {
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(168,85,160,.15);
  border-radius: 16px;
  padding: 2rem;
  transition: border-color .2s, background .2s;
}
.aim-feature-card:hover {
  border-color: rgba(168,85,160,.4);
  background: rgba(168,85,160,.06);
}
.aim-feature-icon {
  width: 48px; height: 48px;
  border-radius: 12px;
  background: rgba(168,85,160,.15);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  margin-bottom: 1.25rem;
}
.aim-feature-card h3 { font-size: 1rem; font-weight: 700; color: #F0D0E8; margin-bottom: .5rem; }
.aim-feature-card p { font-size: .875rem; color: #8B5070; line-height: 1.65; }

/* CTA */
.aim-cta {
  padding: 6rem 0;
  background: linear-gradient(135deg, #1A0A12 0%, #3D0A2A 50%, #1A0A12 100%);
  text-align: center;
  position: relative;
  overflow: hidden;
}
.aim-cta::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse 70% 60% at 50% 50%, rgba(168,85,160,.2) 0%, transparent 70%);
  pointer-events: none;
}
.aim-cta .inner { position: relative; z-index: 1; }
.aim-cta h2 { font-size: clamp(1.75rem, 4vw, 2.75rem); font-weight: 900; color: #fff; margin-bottom: 1rem; }
.aim-cta p { color: #C9A0B8; font-size: 1rem; margin-bottom: 2.5rem; max-width: 480px; margin-left: auto; margin-right: auto; }

@media(max-width: 768px) {
  .aim-hero-title { letter-spacing: 4px; }
  .aim-hero-stats { gap: 2rem; }
}
</style>

<!-- ===== HERO ===== -->
<section class="aim-hero">
  <div class="aim-hero-waves">
    <!-- Top wave lines (mimicking the prototype's audio waveform decoration) -->
    <svg class="aim-wave-svg top" viewBox="0 0 1440 200" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M0,100 C60,60 120,140 180,100 C240,60 300,140 360,100 C420,60 480,140 540,100 C600,60 660,140 720,100 C780,60 840,140 900,100 C960,60 1020,140 1080,100 C1140,60 1200,140 1260,100 C1320,60 1380,140 1440,100" stroke="#A855A0" stroke-width="3" fill="none"/>
      <path d="M0,100 C80,40 160,160 240,100 C320,40 400,160 480,100 C560,40 640,160 720,100 C800,40 880,160 960,100 C1040,40 1120,160 1200,100 C1280,40 1360,160 1440,100" stroke="#7B1450" stroke-width="2" fill="none" opacity=".6"/>
      <path d="M0,100 C40,70 80,130 120,100 C160,70 200,130 240,100 C280,70 320,130 360,100 C400,70 440,130 480,100 C520,70 560,130 600,100 C640,70 680,130 720,100 C760,70 800,130 840,100 C880,70 920,130 960,100 C1000,70 1040,130 1080,100 C1120,70 1160,130 1200,100 C1240,70 1280,130 1320,100 C1360,70 1400,130 1440,100" stroke="#C026A0" stroke-width="1.5" fill="none" opacity=".4"/>
    </svg>
    <!-- Bottom wave lines -->
    <svg class="aim-wave-svg bottom" viewBox="0 0 1440 200" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M0,100 C60,60 120,140 180,100 C240,60 300,140 360,100 C420,60 480,140 540,100 C600,60 660,140 720,100 C780,60 840,140 900,100 C960,60 1020,140 1080,100 C1140,60 1200,140 1260,100 C1320,60 1380,140 1440,100" stroke="#A855A0" stroke-width="3" fill="none"/>
      <path d="M0,100 C80,40 160,160 240,100 C320,40 400,160 480,100 C560,40 640,160 720,100 C800,40 880,160 960,100 C1040,40 1120,160 1200,100 C1280,40 1360,160 1440,100" stroke="#7B1450" stroke-width="2" fill="none" opacity=".6"/>
    </svg>
  </div>
  <div class="aim-hero-glow"></div>

  <div class="aim-hero-content">
    <div class="aim-hero-logo">
      <div class="aim-hero-logo-icon"><span>🎙️</span></div>
    </div>

    <div class="aim-hero-title">A.I.M.</div>
    <div class="aim-hero-fullname">Audio-Visual Intervention Mirroring</div>
    <div class="aim-hero-tagline">A Level-Based Pronunciation Accuracy Program for Pre-Service Teachers</div>

    <div class="aim-hero-actions">
      <a href="login.php" class="btn-aim-primary">🎙️ Get Started</a>
      <a href="register.php" class="btn-aim-outline">Create Account</a>
      <button id="pwaInstallBtn" class="btn-aim-outline" style="display:none;">
        ⬇️ Install App
      </button>
    </div>

    <div class="aim-hero-stats">
      <div>
        <div class="aim-stat-value">6</div>
        <div class="aim-stat-label">Sessions</div>
      </div>
      <div>
        <div class="aim-stat-value">3</div>
        <div class="aim-stat-label">Weeks</div>
      </div>
      <div>
        <div class="aim-stat-value">4</div>
        <div class="aim-stat-label">Step Cycle</div>
      </div>
      <div>
        <div class="aim-stat-value">100%</div>
        <div class="aim-stat-label">Free</div>
      </div>
    </div>
  </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section class="aim-section" id="how">
  <div class="container">
    <div style="text-align:center;margin-bottom:1rem;">
      <div class="aim-section-label">General Flow of Each Session</div>
      <h2 class="aim-section-title">The 4-Step Cycle</h2>
      <p class="aim-section-desc" style="margin:0 auto;">Every session follows a consistent four-step cycle to ensure productive, organized, and easy-to-follow practice.</p>
    </div>

    <div class="aim-steps-grid">
      <div class="aim-step-card">
        <div class="aim-step-num-circle">1</div>
        <h3>👁️ Listen &amp; Observe</h3>
        <p>Listen to the correct pronunciation while carefully observing how sounds are produced using the audio-visual model.</p>
      </div>
      <div class="aim-step-card">
        <div class="aim-step-num-circle">2</div>
        <h3>🎙️ Record Your Speech</h3>
        <p>Record your own voice using the provided reading materials to capture your current performance for that session.</p>
      </div>
      <div class="aim-step-card">
        <div class="aim-step-num-circle">3</div>
        <h3>🔄 Compare &amp; Reflect</h3>
        <p>Play back your recording and compare it with the model to identify gaps or errors in your articulation. Re-record with corrections.</p>
      </div>
      <div class="aim-step-card">
        <div class="aim-step-num-circle">4</div>
        <h3>📤 Submit &amp; Get Feedback</h3>
        <p>Submit your best recording to your facilitator, who will provide personalized feedback to help you improve.</p>
      </div>
    </div>
  </div>
</section>

<!-- ===== PROGRESSION ===== -->
<section class="aim-section" style="background:#fff;padding-top:0;">
  <div class="container">
    <div style="text-align:center;margin-bottom:1rem;">
      <div class="aim-section-label">Progression of the Intervention</div>
      <h2 class="aim-section-title">Three-Week Journey</h2>
      <p class="aim-section-desc" style="margin:0 auto;">A level-based system that builds your skills step-by-step over three weeks.</p>
    </div>

    <div class="aim-weeks-grid">
      <div class="aim-week-card week1">
        <div class="aim-week-badge">📗 Week 1</div>
        <h3>Starting Your Journey</h3>
        <p>Basic phoneme patterns and short, simple narratives to get you comfortable with the Listen-Record-Compare process.</p>
        <div class="aim-week-sessions">Sessions 1–2 · Easy Level</div>
      </div>
      <div class="aim-week-card week2">
        <div class="aim-week-badge">📘 Week 2</div>
        <h3>Building Your Skills</h3>
        <p>Longer sentences with more complex words, focusing on maintaining clarity as the reading material becomes more descriptive.</p>
        <div class="aim-week-sessions">Sessions 3–4 · Medium Level</div>
      </div>
      <div class="aim-week-card week3">
        <div class="aim-week-badge">📙 Week 3</div>
        <h3>Mastering the Craft</h3>
        <p>Dense vocabulary and highly complex sentence structures to test your overall pronunciation accuracy and professional speech flow.</p>
        <div class="aim-week-sessions">Sessions 5–6 · Hard &amp; Advanced</div>
      </div>
    </div>
  </div>
</section>

<!-- ===== FEATURES (dark) ===== -->
<section class="aim-section-dark" id="features">
  <div class="container" style="position:relative;z-index:1;">
    <div style="text-align:center;margin-bottom:1rem;">
      <div class="aim-section-label">Platform Features</div>
      <h2 class="aim-section-title light">Everything you need</h2>
      <p class="aim-section-desc light" style="margin:0 auto;">Built specifically for the AIM pronunciation intervention program.</p>
    </div>

    <div class="aim-features-grid">
      <div class="aim-feature-card">
        <div class="aim-feature-icon">🔊</div>
        <h3>Text-to-Speech Model</h3>
        <p>Listen to correct pronunciation with real-time word-by-word highlighting. Control speed from slow to fast.</p>
      </div>
      <div class="aim-feature-card">
        <div class="aim-feature-icon">🎙️</div>
        <h3>Voice Recording</h3>
        <p>Record yourself directly in the browser. Make a first attempt, compare, then re-record with improvements.</p>
      </div>
      <div class="aim-feature-card">
        <div class="aim-feature-icon">🔄</div>
        <h3>Side-by-Side Comparison</h3>
        <p>Compare your recording against the TTS model to identify articulation gaps and errors in real-time.</p>
      </div>
      <div class="aim-feature-card">
        <div class="aim-feature-icon">💬</div>
        <h3>Facilitator Feedback</h3>
        <p>Receive personalized written feedback from your facilitator based on the AIM pronunciation rubric.</p>
      </div>
      <div class="aim-feature-card">
        <div class="aim-feature-icon">📊</div>
        <h3>Progress Monitoring</h3>
        <p>Facilitators track improvement through monitoring logs and standardized pronunciation rubrics across all sessions.</p>
      </div>
      <div class="aim-feature-card">
        <div class="aim-feature-icon">🔒</div>
        <h3>Secure &amp; Private</h3>
        <p>Role-based access keeps student and facilitator areas separate. Recordings are accessible only to your assigned facilitator.</p>
      </div>
    </div>
  </div>
</section>

<!-- ===== CTA ===== -->
<section class="aim-cta">
  <div class="container inner">
    <h2>Ready to improve your pronunciation?</h2>
    <p>Join the A.I.M. program and bridge the gap in pronunciation accuracy over three weeks.</p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
      <a href="register.php" class="btn-aim-primary btn-lg">🎙️ Join the Program</a>
      <a href="login.php" class="btn-aim-outline btn-lg">Sign In</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
var pwaInstallEvent = null;
var installBtn = document.getElementById('pwaInstallBtn');

window.addEventListener('beforeinstallprompt', function(e) {
  e.preventDefault();
  pwaInstallEvent = e;
  if (installBtn) installBtn.style.display = 'inline-flex';
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
