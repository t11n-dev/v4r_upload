<?php
/**
 * upload.php — Handles image file upload with security validation.
 * 
 * Validates MIME type, file extension, and size before saving.
 * Returns JSON response with upload status and file URL.
 */

header('Content-Type: application/json');

// CSRF token validation
session_start();
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && (!isset($_SERVER['HTTP_X_CSRF_TOKEN'])
        || !isset($_SESSION['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'], $_SERVER['HTTP_X_CSRF_TOKEN']))
) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

require_once __DIR__ . '/config.php';

$targetDir = UPLOAD_DIR;
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

if (!isset($_FILES["file"])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES["file"];

// Check for upload errors
if ($file["error"] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload error: ' . $file["error"]]);
    exit;
}

// Validate file size
if ($file["size"] > MAX_FILE_SIZE) {
    echo json_encode(['success' => false, 'message' => 'File size exceeds 50MB limit']);
    exit;
}

// Validate MIME type using finfo (reads actual file content, not just extension)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$detectedMime = $finfo->file($file["tmp_name"]);

if (!isset(ALLOWED_MIME_TYPES[$detectedMime])) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, WEBP, AVIF allowed.']);
    exit;
}

// Double-check with getimagesize to prevent extension spoofing
$imageInfo = getimagesize($file["tmp_name"]);
if ($imageInfo === false) {
    echo json_encode(['success' => false, 'message' => 'File is not a valid image']);
    exit;
}

// Use detected MIME to determine safe extension
$allowedExtensions = ALLOWED_MIME_TYPES[$detectedMime];
$originalExt = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
$finalExt = in_array($originalExt, $allowedExtensions) ? $originalExt : $allowedExtensions[0];

$savedName = generateUniqueFileName($file["name"], $finalExt);
$targetFile = $targetDir . $savedName;

// Calculate base URL
$baseUrl = getBaseUrl();

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        clearStatsCache();
        echo json_encode([
            'success' => true,
            'url'     => $baseUrl . '/uploads/' . $savedName,
        ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save uploaded file',
    ]);
}
