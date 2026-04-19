<?php
/**
 * Run this ONCE to generate PWA icons.
 * Visit: http://localhost/forda_app/reading-tool/generate_icons.php
 * Then DELETE this file.
 */

function generateIcon(int $size, string $path): void {
    $img = imagecreatetruecolor($size, $size);

    // Background — indigo gradient simulation
    $bg = imagecolorallocate($img, 79, 70, 229);
    imagefill($img, 0, 0, $bg);

    // Rounded corners via arc masking
    $corner = (int)($size * 0.18);
    $mask   = imagecolorallocate($img, 0, 0, 0);
    imagecolortransparent($img, $mask);

    // Draw a book emoji-style icon
    // White book shape
    $white = imagecolorallocate($img, 255, 255, 255);
    $light = imagecolorallocate($img, 196, 181, 253);

    $pad  = (int)($size * 0.2);
    $mid  = (int)($size / 2);
    $top  = $pad;
    $bot  = $size - $pad;
    $left = $pad;
    $right= $size - $pad;

    // Left page
    imagefilledrectangle($img, $left, $top, $mid - 2, $bot, $white);
    // Right page
    imagefilledrectangle($img, $mid + 2, $top, $right, $bot, $light);
    // Spine
    imagefilledrectangle($img, $mid - 2, $top, $mid + 2, $bot, imagecolorallocate($img, 55, 48, 163));

    // Lines on left page
    $line = imagecolorallocate($img, 200, 200, 220);
    $lineH = (int)(($bot - $top) / 6);
    for ($i = 1; $i <= 4; $i++) {
        $y = $top + $i * $lineH;
        imageline($img, $left + 4, $y, $mid - 6, $y, $line);
    }

    imagepng($img, $path);
    imagedestroy($img);
}

$dir = __DIR__ . '/assets/icons/';
if (!is_dir($dir)) mkdir($dir, 0755, true);

generateIcon(192, $dir . 'icon-192.png');
generateIcon(512, $dir . 'icon-512.png');

echo '<p style="font-family:sans-serif;padding:2rem;">
  ✅ Icons generated successfully!<br><br>
  <strong>Delete this file now:</strong> <code>reading-tool/generate_icons.php</code>
</p>';
