<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$materialId = (int)($_POST['material_id'] ?? 0);
if (!$materialId) {
    echo json_encode(['success' => false, 'error' => 'Missing material ID.']);
    exit;
}

// Validate material exists
$stmt = $pdo->prepare('SELECT id FROM reading_materials WHERE id = ?');
$stmt->execute([$materialId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Material not found.']);
    exit;
}

// Validate uploaded file
if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['audio']['error'] ?? 'none';
    echo json_encode(['success' => false, 'error' => 'File upload error: ' . $errCode]);
    exit;
}

$file = $_FILES['audio'];
$maxSize = 20 * 1024 * 1024; // 20 MB
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'File too large (max 20MB).']);
    exit;
}

// Validate MIME type
$allowedMimes = ['audio/webm', 'audio/ogg', 'audio/mpeg', 'audio/wav', 'audio/mp4', 'video/webm'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
if (!in_array($mimeType, $allowedMimes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type: ' . $mimeType]);
    exit;
}

// Determine extension
$ext = 'webm';
if (strpos($mimeType, 'ogg') !== false) $ext = 'ogg';
elseif (strpos($mimeType, 'mpeg') !== false) $ext = 'mp3';
elseif (strpos($mimeType, 'wav') !== false) $ext = 'wav';

// Create upload directory
$uploadDir = __DIR__ . '/../uploads/recordings/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate filename
$studentId = currentUserId();
$timestamp = time();
$filename  = $studentId . '_' . $materialId . '_' . $timestamp . '.' . $ext;
$destPath  = $uploadDir . $filename;
$dbPath    = 'uploads/recordings/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save file.']);
    exit;
}

// Insert into DB
$misCount  = isset($_POST['mispronounced_count']) ? (int)$_POST['mispronounced_count'] : null;
$milestone = trim($_POST['milestone'] ?? '') ?: null;
// Validate milestone value
$validMilestones = ['Excellent', 'Great Progress', 'Nice Job', 'Brave Start'];
if ($milestone && !in_array($milestone, $validMilestones)) $milestone = null;

$stmt = $pdo->prepare('INSERT INTO recordings (student_id, material_id, audio_path, mispronounced_count, milestone) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$studentId, $materialId, $dbPath, $misCount, $milestone]);

echo json_encode(['success' => true, 'recording_id' => $pdo->lastInsertId()]);
