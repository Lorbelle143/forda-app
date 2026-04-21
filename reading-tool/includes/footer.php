<?php if (isLoggedIn()): ?>
        </main><!-- /.app-main -->
    </div><!-- /.app-content -->
</div><!-- /.app-layout -->
<?php else: ?>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <p class="footer-brand">&#127908; <strong>A.I.M.</strong> — Audio-Visual Intervention Mirroring</p>
        <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
            <a href="<?= $base ?>privacy.php" style="color:#64748B;font-size:.82rem;text-decoration:none;">Privacy Policy</a>
            <p class="footer-copy">&copy; <?= date('Y') ?> A.I.M. — A Level-Based Pronunciation Accuracy Program.</p>
        </div>
    </div>
</footer>
<?php endif; ?>

<script src="<?= $base ?>assets/js/app.js"></script>

<!-- PWA Install Modal -->
<div id="installModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9999;align-items:center;justify-content:center;padding:1.5rem;">
  <div style="background:#fff;border-radius:20px;padding:2rem;max-width:340px;width:100%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.3);">
    <div style="font-size:3rem;margin-bottom:1rem;">🎙️</div>
    <h2 style="font-size:1.2rem;font-weight:800;color:#7B1450;margin-bottom:.5rem;">Install A.I.M. App</h2>
    <p style="font-size:.875rem;color:#64748B;margin-bottom:1.5rem;">Follow these steps to install on your phone:</p>

    <div style="text-align:left;background:#FDF4FF;border-radius:12px;padding:1rem;margin-bottom:1.5rem;">
      <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;">
        <span style="background:#7B1450;color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0;">1</span>
        <span style="font-size:.875rem;color:#1A0A12;">Tap the <strong>⋮ menu</strong> (top right of Chrome)</span>
      </div>
      <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;">
        <span style="background:#7B1450;color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0;">2</span>
        <span style="font-size:.875rem;color:#1A0A12;">Tap <strong>"Add to Home screen"</strong></span>
      </div>
      <div style="display:flex;align-items:center;gap:.75rem;">
        <span style="background:#7B1450;color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0;">3</span>
        <span style="font-size:.875rem;color:#1A0A12;">Tap <strong>"Add"</strong> — done! 🎉</span>
      </div>
    </div>

    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='280' height='80' viewBox='0 0 280 80'%3E%3Crect width='280' height='80' rx='12' fill='%23F5E8F9'/%3E%3Ctext x='140' y='30' text-anchor='middle' font-size='12' fill='%237B1450' font-family='Arial' font-weight='bold'%3ETap ⋮ in Chrome%3C/text%3E%3Ctext x='140' y='52' text-anchor='middle' font-size='12' fill='%237B1450' font-family='Arial'%3E→ Add to Home screen%3C/text%3E%3Ctext x='140' y='70' text-anchor='middle' font-size='11' fill='%23A855A0' font-family='Arial'%3E→ Add%3C/text%3E%3C/svg%3E"
         style="width:100%;border-radius:8px;margin-bottom:1rem;">

    <button onclick="document.getElementById('installModal').style.display='none'"
            style="width:100%;padding:.75rem;background:linear-gradient(135deg,#7B1450,#A855A0);color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:700;cursor:pointer;">
      Got it! ✓
    </button>
  </div>
</div>
<script>
  // Register Service Worker for PWA
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('<?= $base ?>sw.js')
        .catch(function (err) { console.warn('SW registration failed:', err); });
    });
  }
</script>
</body>
</html>
