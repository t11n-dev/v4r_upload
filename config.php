<?php
/**
 * config.php — Cấu hình chung cho ứng dụng upload.
 *
 * Chứa API keys và các hằng số dùng chung giữa web UI và API.
 */

// API keys cho developer (thêm key mới vào mảng)
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

const MAX_FILE_SIZE = 100 * 1024 * 1024; // 100MB
const UPLOAD_DIR = __DIR__ . '/uploads/';

/**
 * Sanitize tên file thành dạng an toàn (chỉ chứa a-z, 0-9, -, _, .)
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
 * Tạo tên file ngẫu nhiên: 32kitungaunhien_timestamp_originalName
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

    // Timestamp hiện tại
    $timestamp = time();

    // Kết hợp: random_timestamp_name.ext
    return "{$randomStr}_{$timestamp}_{$safeName}.{$ext}";
}
