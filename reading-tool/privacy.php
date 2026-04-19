<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Privacy Policy';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:800px;padding-top:2rem;padding-bottom:4rem;">

  <div class="page-header">
    <div>
      <h1 class="page-title">&#128274; Privacy Policy</h1>
      <p class="page-subtitle">Last updated: <?= date('F Y') ?></p>
    </div>
  </div>

  <div class="card">
    <div class="card-body" style="line-height:1.8;color:#374151;">

      <div style="background:#EEF2FF;border-radius:10px;padding:1rem 1.25rem;margin-bottom:2rem;font-size:.9rem;color:#3730A3;">
        <strong>Summary:</strong> ReadEase collects only the minimum data needed to operate the platform.
        We do not sell your data. Voice recordings are used solely for teacher feedback and are stored securely.
      </div>

      <h2 style="font-size:1.1rem;font-weight:700;color:#1E293B;margin:1.5rem 0 .5rem;">1. Who We Are</h2>
      <p>ReadEase is an educational reading tool designed to help students improve their reading skills through text-to-speech, voice recording, and teacher feedback. This platform is operated for educational purposes.</p>

      <h2 style="font-size:1.1rem;font-weight:700;color:#1E293B;margin:1.5rem 0 .5rem;">2. What Data We Collect</h2>
      <p>We collect the following personal information when you register and use ReadEase:</p>
      <ul style="margin:.75rem 0 .75rem 1.5rem;">
        <li><strong>Full name</strong> — used to identify you within the platform</li>
        <li><strong>Email address</strong> — used for account login and identification</li>
        <li><strong>Password</strong> — stored as a secure encrypted hash (never in plain text)</li>
        <li><strong>Voice recordings</strong> — audio files you submit for teacher review</li>
        <li><strong>Usage data</strong> — which materials you read and when recordings were submitted</li>
      </ul>

      <h2 style="font-size:1.1rem;font-weight:700;color:#1E293B;margin:1.5rem 0 .5rem;">3. How We Use Your Data</h2>
      <p>Your data is used exclusively to:</p>
      <ul style="margin:.75rem 0 .75rem 1.5rem;">
        <li>Provide access to reading materials and the recording feature</li>
        <li>Allow teachers (admins) to review your recordings and provide feedback</li>
        <li>Display your progress and recording history on your dashboard</li>
        <li>Maintain the security and integrity of your account</li>
      </ul>
      <p>We do <strong>not</strong> use your data for advertising, profiling, or any commercial purpose.</p>

      <h2 style="font-size:1.1rem;font-weight:700;color:#1E293B;margin:1.5rem 0 .5rem;">4. Voice Recordings</h2>
      <p>Voice recordings you submit are stored on the server and are accessible only to authorized administrators (teachers) for the purpose of providing feedback. Recordings are not shared with any third party. You may delete your own recordings (before feedback is given) from the My Recordings page.</p>

      <h2 style="font-size:1.1rem;font-weight:700;color:#1E293B;margin:1.5rem 0 .5rem;">5. Data Sharing</h2>
      <p>We do <strong>not</strong> sell, trade, or share your personal data with third parties. Your information is only accessible to:</p>
      <ul style="margin:.75rem 0 .75rem 1.5rem;">
        <li>You — through your own account</li>
        <li>Authorized administrators — for educational feedback purposes only</li>
      </ul>

      <h2 style="font-size:1.1rem;font-weight:700;color:#1E293B;margin:1.5rem 0 .5rem;">6. Data Security</h2>
      <p>We take reasonable measures to protect your data:</p>
      <ul style="margin:.75rem 0 .75rem 1.5rem;">
        <li>Passwords are hashed using bcrypt — never stored in plain text</li>
        <li>Sessions are regenerated on login to prevent session fixation</li>
        <li>File uploads are validated and stored outside the web root's executable path</li>
        <li>All forms are protected with CSRF tokens</li>
      </ul>

      <h2 style="font-size:1.1rem;font-weight:700;color:#1E293B;margin:1.5rem 0 .5rem;">7. Your Rights</h2>
      <p>You have the right to:</p>
      <ul style="margin:.75rem 0 .75rem 1.5rem;">
        <li>Access the personal data we hold about you</li>
        <li>Request correction of inaccurate data</li>
        <li>Request deletion of your account and associated data</li>
        <li>Delete your own recordings (before feedback is given)</li>
      </ul>
      <p>To exercise these rights, contact your administrator.</p>

      <h2 style="font-size:1.1rem;font-weight:700;color:#1E293B;margin:1.5rem 0 .5rem;">8. Cookies &amp; Sessions</h2>
      <p>ReadEase uses a single session cookie to keep you logged in. This cookie is essential for the platform to function and does not track you across other websites. No third-party cookies or analytics are used.</p>

      <h2 style="font-size:1.1rem;font-weight:700;color:#1E293B;margin:1.5rem 0 .5rem;">9. Children's Privacy</h2>
      <p>ReadEase is designed for use in educational settings and may be used by minors under teacher supervision. We do not knowingly collect data from children without appropriate institutional authorization. If you are a parent or guardian with concerns, please contact your school administrator.</p>

      <h2 style="font-size:1.1rem;font-weight:700;color:#1E293B;margin:1.5rem 0 .5rem;">10. Changes to This Policy</h2>
      <p>We may update this Privacy Policy from time to time. Changes will be reflected by the "Last updated" date at the top of this page. Continued use of ReadEase after changes constitutes acceptance of the updated policy.</p>

      <h2 style="font-size:1.1rem;font-weight:700;color:#1E293B;margin:1.5rem 0 .5rem;">11. Contact</h2>
      <p>For any privacy-related questions or requests, please contact your school administrator or the ReadEase platform operator.</p>

    </div>
  </div>

  <div style="text-align:center;margin-top:2rem;">
    <a href="index.php" class="btn btn-ghost">&#8592; Back to Home</a>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
