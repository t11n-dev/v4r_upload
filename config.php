<?php
/**
 * config.php — General configuration for the upload application.
 *
 * Contains API keys and shared constants between web UI and API.
 */

// API keys for developers (add new keys to this array)
const API_KEYS = [
    't11n-dev-key-2026-change-me',
];

// Allowed MIME types và extension tương ứng
const ALLOWED_MIME_TYPES = [
    'image/jpeg' => ['jpg', 'jpeg'],
    'image/png'  => ['png'],
    'image/gif'  => ['gif'],
    'image/webp' => ['webp'],
    'image/avif' => ['avif'],
];

const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB
const MAX_API_FILE_COUNT = 20; // Maximum number of files allowed in a single API request
const UPLOAD_DIR = __DIR__ . '/uploads/';

/**
 * Get dynamic application base URL.
 */
function getBaseUrl()
{
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'up.t11n.dev';
    $dir = dirname($_SERVER['SCRIPT_NAME']);
    $dir = ($dir === '/' || $dir === '\\') ? '' : $dir;
    return rtrim($protocol . '://' . $host . $dir, '/');
}

/**
 * Sanitize file name to a safe format (contains only a-z, 0-9, -, _, .)
 */
function sanitizeFileName($fileName)
{
    if (function_exists('transliterator_transliterate')) {
        $asciiName = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9._-] remove', $fileName);
    } else {
        $asciiName = iconv('UTF-8', 'ASCII//TRANSLIT', $fileName);
        if ($asciiName === false) {
            $asciiName = $fileName;
        }
    }
    $asciiName = str_replace(' ', '-', $asciiName);
    return preg_replace('/[^a-zA-Z0-9\-_.]/', '', $asciiName);
}

/**
 * Generate a random file name: 32randomChars_timestamp_originalName
 */
function generateUniqueFileName($originalName, $ext = null)
{
    // Lấy phần mở rộng an toàn nếu chưa có
    if ($ext === null) {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    }

    // Lấy tên gốc đã sanitize (bỏ extension)
    $safeName = pathinfo(sanitizeFileName($originalName), PATHINFO_FILENAME);
    if (empty($safeName)) {
        $safeName = 'file';
    }

    // Tạo chuỗi ngẫu nhiên 32 ký tự
    try {
        $randomStr = bin2hex(random_bytes(16)); // 16 bytes -> 32 hex chars
    } catch (Exception $e) {
        $randomStr = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 32);
    }

    // Current timestamp
    $timestamp = time();

    // Combine: random_timestamp_name.ext
    return "{$randomStr}_{$timestamp}_{$safeName}.{$ext}";
}

/**
 * Get uploaded files stats (total files count and total size) using a cache file
 * to optimize memory and disk I/O on low-resource servers.
 *
 * @param bool $forceRefresh Force cache rebuild
 * @return array Array containing totalFiles (int) and totalSize (float)
 */
function getStats($forceRefresh = false)
{
    $cacheFile = UPLOAD_DIR . '.stats_cache.json';
    $cacheLifetime = 60; // Cache valid for 60 seconds

    if (!$forceRefresh && is_file($cacheFile) && (time() - filemtime($cacheFile)) < $cacheLifetime) {
        $content = file_get_contents($cacheFile);
        if ($content !== false) {
            $data = json_decode($content, true);
            if (is_array($data) && isset($data['total_files']) && isset($data['total_size'])) {
                return [
                    'totalFiles' => (int)$data['total_files'],
                    'totalSize'  => (float)$data['total_size']
                ];
            }
        }
    }

    // Rebuild cache
    $totalFiles = 0;
    $totalSize = 0;
    $dir = UPLOAD_DIR;
    $allowedExtensions = array_merge(...array_values(ALLOWED_MIME_TYPES));

    if (is_dir($dir)) {
        $dh = opendir($dir);
        if ($dh) {
            while (($file = readdir($dh)) !== false) {
                // Ignore dotfiles and cache file
                if ($file[0] !== '.' && is_file($dir . $file)) {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($ext, $allowedExtensions)) {
                        $totalFiles++;
                        $totalSize += filesize($dir . $file);
                    }
                }
            }
            closedir($dh);
        }
    }

    $stats = [
        'total_files' => $totalFiles,
        'total_size'  => $totalSize
    ];

    // Ensure upload directory exists before writing cache
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($cacheFile, json_encode($stats));

    return [
        'totalFiles' => $totalFiles,
        'totalSize'  => $totalSize
    ];
}

/**
 * Clear the stats cache file. Called when files are uploaded or deleted.
 */
function clearStatsCache()
{
    $cacheFile = UPLOAD_DIR . '.stats_cache.json';
    if (is_file($cacheFile)) {
        @unlink($cacheFile);
    }
}
