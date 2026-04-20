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
