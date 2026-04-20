<?php
// Temporary debug file — DELETE after fixing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<h2>PHP Info</h2>';
echo 'PHP Version: ' . phpversion() . '<br>';
echo 'Server: ' . ($_SERVER['HTTP_HOST'] ?? 'unknown') . '<br><br>';

echo '<h2>Testing DB Connection</h2>';
require_once __DIR__ . '/includes/db.php';
echo '✅ DB connected successfully!<br>';
echo 'Tables check:<br>';

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    echo "- $t: $count rows<br>";
}

echo '<br><h2>Session Test</h2>';
session_start();
$_SESSION['test'] = 'ok';
echo 'Session: ' . ($_SESSION['test'] === 'ok' ? '✅ Working' : '❌ Failed') . '<br>';

echo '<br><h2>File Check</h2>';
$files = [
    'includes/auth.php',
    'includes/header.php',
    'includes/footer.php',
    'admin/dashboard.php',
    'student/read.php',
    'dashboard.php',
    'login.php',
];
foreach ($files as $f) {
    echo ($f . ': ' . (file_exists(__DIR__ . '/' . $f) ? '✅' : '❌ MISSING') . '<br>');
}
?>
