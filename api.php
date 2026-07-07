<?php
/**
 * api.php — REST API for upload and delete (supports single/bulk).
 *
 * Endpoints:
 *   POST /api.php?action=upload
 *      Content-Type: multipart/form-data
 *      Field: `files[]` (multiple files — REQUIRED to have `[]`), `file` (1 file), or any field name
 *
 *   POST /api.php?action=delete
 *      Content-Type: application/json
 *      Body: { "names": ["img1.jpg", "img2.jpg"] }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/config.php';

function jsonResponse(int $statusCode, array $data): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Normalize $_FILES into a flat array of easy-to-process file objects.
 *
 * Supports all transmission methods:
 *   - files[]  → PHP parses as array (multi)
 *   - files    → PHP only keeps the last file (scalar) — but we scan all fields
 *   - file     → single scalar
 *   - Any other field name
 */
function collectAllUploadedFiles(): array
{
    $normalized = [];

    foreach ($_FILES as $fieldName => $entry) {
        // Case 1: field dạng array (files[] / images[] / ...)
        if (is_array($entry['name'])) {
            foreach ($entry['name'] as $idx => $name) {
                if ($entry['error'][$idx] === UPLOAD_ERR_NO_FILE) {
                    continue; // slot rỗng, bỏ qua
                }
                $normalized[] = [
                    'name'     => $name,
                    'type'     => $entry['type'][$idx],
                    'tmp_name' => $entry['tmp_name'][$idx],
                    'error'    => $entry['error'][$idx],
                    'size'     => $entry['size'][$idx],
                ];
            }
        }
        // Case 2: field scalar (file / files without brackets)
        else {
            if ($entry['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $normalized[] = $entry;
        }
    }

    return $normalized;
}

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['success' => false, 'error' => 'Method must be POST']);
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'upload':
        handleUpload();
        break;
    case 'delete':
        handleDelete();
        break; 
    default:
        jsonResponse(400, ['success' => false, 'error' => 'Unknown action']);
}

// ──────────────────────────────────────────────

function handleUpload(): void
{
    // Scan all $_FILES fields — independent of field name
    $fileList = collectAllUploadedFiles();

    if (empty($fileList)) {
        jsonResponse(400, ['success' => false, 'error' => 'No files provided. Use field "files[]", "file", or any field name']);
    }

    // Limit the number of files per request to prevent server resource exhaustion
    if (count($fileList) > MAX_API_FILE_COUNT) {
        jsonResponse(400, ['success' => false, 'error' => 'Too many files. Maximum allowed per request is ' . MAX_API_FILE_COUNT]);
    }

    $results = [];
    $anySuccess = false;

    // Get base URL
    $baseUrl = getBaseUrl();

    foreach ($fileList as $file) {
        // Init result object
        $fileResult = [
            'original_name' => $file['name'],
            'status'        => 'error', 
            'error'         => ''
        ];

        // 1. Check lỗi upload hệ thống
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $fileResult['error'] = 'Upload error code: ' . $file['error'];
            $results[] = $fileResult;
            continue;
        }

        // 2. Check kích thước
        if ($file['size'] > MAX_FILE_SIZE) {
            $fileResult['error'] = 'File too large (Max: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB)';
            $results[] = $fileResult;
            continue;
        }

        // 3. Check MIME type (dùng finfo)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($file['tmp_name']);

        if (!isset(ALLOWED_MIME_TYPES[$detectedMime])) {
            $fileResult['error'] = 'Invalid file type. Only JPG, PNG, GIF, WEBP, AVIF allowed.';
            $results[] = $fileResult;
            continue;
        }

        // 4. Check ảnh thật sự (getimagesize) to prevent extension spoofing
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $fileResult['error'] = 'Not a valid image file';
            $results[] = $fileResult;
            continue;
        }

        // 5. Xử lý tên file và lưu
        $allowedExtensions = ALLOWED_MIME_TYPES[$detectedMime];
        $originalExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        // If original ext is in allowed list, keep it; otherwise, take the first one
        $finalExt = in_array($originalExt, $allowedExtensions) ? $originalExt : $allowedExtensions[0];

        $savedName = generateUniqueFileName($file['name'], $finalExt);
        $targetFile = UPLOAD_DIR . $savedName;

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $anySuccess = true;
            $results[] = [
                'name'          => $savedName,          // Tên file mới trên disk
                'original_name' => $file['name'],       // Tên gốc user up lên
                'status'        => 'success',
                'url'           => $baseUrl . '/uploads/' . $savedName,
                'size'          => $file['size'],
                'mime'          => $detectedMime,
                'width'         => $imageInfo[0],
                'height'        => $imageInfo[1],
            ];
        } else {
            $fileResult['error'] = 'Failed to save file to disk';
            $results[] = $fileResult;
        }
    }

    if ($anySuccess) {
        clearStatsCache();
    }

    jsonResponse(200, [
        'success' => true,
        'data'    => $results
    ]);
}

function handleDelete(): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        // Fallback: Check $_POST if calling form-data delete (rarely used but kept as backup)
        $input = $_POST;
    }

    $names = $input['names'] ?? (isset($input['name']) ? [$input['name']] : []);

    if (empty($names)) {
        jsonResponse(400, ['success' => false, 'error' => 'No image names provided.']);
    }

    $results = [];
    $anySuccess = false;

    foreach ($names as $name) {
        if (empty($name)) continue;
        
        $imageName = basename($name); // Prevent directory traversal
        $filePath = UPLOAD_DIR . $imageName;

        // Check path traversal & extension (security)
        $ext = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
        $allExtensions = array_merge(...array_values(ALLOWED_MIME_TYPES));
        
        // Only allow deleting files with allowed extensions (prevents malicious system file deletion)
        if (!in_array($ext, $allExtensions)) {
            $results[] = ['name' => $imageName, 'status' => 'error', 'error' => 'Invalid extension'];
            continue;
        }

        if (!is_file($filePath)) {
            $results[] = ['name' => $imageName, 'status' => 'error', 'error' => 'File not found'];
            continue;
        }

        if (unlink($filePath)) {
            $anySuccess = true;
            $results[] = ['name' => $imageName, 'status' => 'success'];
        } else {
            $results[] = ['name' => $imageName, 'status' => 'error', 'error' => 'Delete failed'];
        }
    }

    if ($anySuccess) {
        clearStatsCache();
    }

    jsonResponse(200, [
        'success' => true,
        'data'    => $results
    ]);
}
