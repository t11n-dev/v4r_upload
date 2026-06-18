<?php
/**
 * stats.php — Upload statistics dashboard with server-side authentication.
 * 
 * Displays uploaded images list with pagination and provides
 * authenticated bulk delete functionality.
 */

session_start();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/config.php';

// Set default timezone to Asia/Ho_Chi_Minh (UTC+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');
// Dynamic bulk delete password based on current date in dmY format (e.g., 18062026)
$bulkDeletePassword = date('dmY');

$dir = UPLOAD_DIR;
$allowedExtensions = array_merge(...array_values(ALLOWED_MIME_TYPES));
$perPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $perPage;

// Handle bulk delete (server-side password check)
$deleteMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $deleteMessage = '<div class="delete-msg error">Invalid CSRF token.</div>';
    } elseif (!isset($_POST['password']) || $_POST['password'] !== $bulkDeletePassword) {
        $deleteMessage = '<div class="delete-msg error">Incorrect password.</div>';
    } else {
        $deleted = 0;
        if (is_dir($dir)) {
            $dh = opendir($dir);
            if ($dh) {
                while (($file = readdir($dh)) !== false) {
                    $filePath = $dir . '/' . $file;
                    if ($file[0] !== '.' && is_file($filePath)) {
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        if (in_array($ext, $allowedExtensions)) {
                            if (unlink($filePath)) {
                                $deleted++;
                            }
                        }
                    }
                }
                closedir($dh);
            }
        }
        // Clear stats cache after bulk deletion
        clearStatsCache();

        $deleteMessage = '<div class="delete-msg success">Deleted ' . $deleted . ' images. Reloading page in 3s...</div>';
        // Regenerate CSRF token after successful action
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Reload page after 3 seconds
        echo '<script>setTimeout(function(){ window.location.href = "stats.php"; }, 3000);</script>';
    }
}

// Get cached total file count and size
$stats = getStats();
$totalFiles = $stats['totalFiles'];
$totalSize = $stats['totalSize'];

$imagesPage = [];
$allFiles = [];

// Collect all files and parse timestamps from filenames (saving massive disk stat calls)
if ($totalFiles > 0 && is_dir($dir)) {
    $dh = opendir($dir);
    if ($dh) {
        while (($file = readdir($dh)) !== false) {
            $filePath = $dir . '/' . $file;
            if ($file[0] !== '.' && is_file($filePath)) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, $allowedExtensions)) {
                    // Fast timestamp extraction: random_timestamp_name.ext
                    $parts = explode('_', $file, 3);
                    if (count($parts) >= 2 && is_numeric($parts[1])) {
                        $time = (int)$parts[1];
                    } else {
                        $time = filemtime($filePath);
                    }
                    $allFiles[] = [
                        'name' => $file,
                        'time' => $time
                    ];
                }
            }
        }
        closedir($dh);
    }
}

// Sort files by time descending (newest first)
usort($allFiles, function ($a, $b) {
    return $b['time'] <=> $a['time'];
});

// Slice the files for the current page
$pageFiles = array_slice($allFiles, $start, $perPage);

foreach ($pageFiles as $f) {
    $filePath = $dir . '/' . $f['name'];
    $imagesPage[] = [
        'name' => $f['name'],
        'size' => filesize($filePath),
        'time' => $f['time'],
        'url'  => 'uploads/' . $f['name'],
    ];
}

function formatSize($bytes)
{
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return round($bytes / 1024, 2) . ' KB';
    if ($bytes < 1024 * 1024 * 1024) return round($bytes / 1024 / 1024, 2) . ' MB';
    return round($bytes / 1024 / 1024 / 1024, 2) . ' GB';
}

$totalPages = $totalFiles ? ceil($totalFiles / $perPage) : 1;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Upload Statistics | T11N Upload</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="./style.css">
    <!-- Favicon -->
    <link rel="icon" href="./assets/favicon.png" type="image/png">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
            font-family: 'Outfit', sans-serif;
            color: #2d3748;
        }

        .stats-box {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 3rem 2.5rem;
            max-width: 850px;
            margin: 3rem auto;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .stats-title {
            font-size: 2.4rem;
            font-weight: 800;
            margin-bottom: 2rem;
            color: #1a202c;
            letter-spacing: -0.5px;
            text-align: center;
        }

        .stats-list {
            margin-bottom: 2.5rem;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            list-style: none;
            padding: 0;
        }

        .stats-list li {
            background: #ffffff;
            border-radius: 14px;
            padding: 1.25rem 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
            border: 1px solid #edf2f7;
            color: #4a5568;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .stats-list li:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.04);
            border-color: #cbd5e0;
        }

        .stats-list li .stats-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stats-list li .stats-val {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1a202c;
        }

        .images-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
            border: 1px solid #edf2f7;
        }

        .images-table th,
        .images-table td {
            padding: 14px 18px;
            text-align: left;
            vertical-align: middle;
        }

        .images-table th {
            background: #f8fafc;
            color: #4a5568;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }

        .images-table tr {
            transition: background 0.2s ease;
        }

        .images-table tr:hover {
            background-color: #f8fafc;
        }

        .images-table td {
            border-bottom: 1px solid #edf2f7;
            font-size: 0.95rem;
            color: #2d3748;
        }

        .images-table tr:last-child td {
            border-bottom: none;
        }

        /* Thumb column */
        .preview-col {
            width: 80px;
        }

        .table-thumb {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            display: block;
            transition: transform 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        }

        .table-thumb:hover {
            transform: scale(1.08);
        }

        .filename-col {
            max-width: 280px;
            overflow-wrap: anywhere;
            word-break: break-all;
            font-weight: 500;
        }

        .btn-view {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            border-radius: 8px;
            background: #edf2f7;
            color: #4a5568;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .btn-view:hover {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(245, 87, 108, 0.2);
            transform: translateY(-1px);
        }

        .no-images {
            text-align: center;
            color: #4a5568;
            font-size: 1.1rem;
            margin-top: 2rem;
            background: #f8fafc;
            border-radius: 12px;
            padding: 2.5rem;
            border: 1px dashed #cbd5e0;
        }

        /* Bulk delete */
        .bulk-delete-section {
            text-align: right;
            margin-bottom: 1.5rem;
        }

        .btn-delete-all {
            background: #fff5f5;
            color: #e53e3e;
            font-weight: 600;
            border: 1px solid #fed7d7;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-delete-all:hover {
            background: #e53e3e;
            color: #fff;
            border-color: #e53e3e;
            box-shadow: 0 4px 12px rgba(229, 62, 62, 0.2);
            transform: translateY(-1px);
        }

        /* Modal styling */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease-out;
        }

        .modal-box {
            background: #fff;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            max-width: 400px;
            width: 90%;
            text-align: center;
            border: 1px solid rgba(226, 232, 240, 0.8);
            animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 1.25rem;
        }

        .modal-input {
            width: 100%;
            padding: 0.85rem 1rem;
            border-radius: 10px;
            border: 1px solid #cbd5e0;
            font-size: 1rem;
            margin-bottom: 1.25rem;
            outline: none;
            transition: all 0.2s ease;
        }

        .modal-input:focus {
            border-color: #f5576c;
            box-shadow: 0 0 0 3px rgba(245, 87, 108, 0.15);
        }

        .modal-error {
            color: #e53e3e;
            font-size: 0.9rem;
            margin-bottom: 1.25rem;
            display: none;
            font-weight: 500;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn-confirm {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(245, 87, 108, 0.2);
        }

        .btn-confirm:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(245, 87, 108, 0.3);
        }

        .btn-cancel {
            background: #edf2f7;
            color: #4a5568;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
        }

        /* Delete messages */
        .delete-msg {
            font-weight: 600;
            text-align: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-radius: 10px;
            font-size: 0.95rem;
        }

        .delete-msg.error {
            color: #c53030;
            background: #fff5f5;
            border: 1px solid #feb2b2;
        }

        .delete-msg.success {
            color: #2f855a;
            background: #f0fff4;
            border: 1px solid #9ae6b4;
        }

        /* Pagination */
        .pagination {
            margin: 2.5rem 0 0 0;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 4px;
        }

        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 0.95rem;
        }

        .page-link--default {
            background: #ffffff;
            color: #4a5568;
            border: 1px solid #e2e8f0;
        }

        .page-link--default:hover {
            border-color: #cbd5e0;
            background: #f8fafc;
            color: #1a202c;
        }

        .page-link--active {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: #fff;
            border: none;
            box-shadow: 0 4px 10px rgba(245, 87, 108, 0.25);
        }

        .page-ellipsis {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            color: #a0aec0;
        }

        @media (max-width: 768px) {
            .stats-box {
                padding: 1.5rem;
                margin: 1.5rem 10px;
            }

            .stats-title {
                font-size: 1.8rem;
            }

            .stats-list {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .stats-list li {
                padding: 1rem;
            }

            .images-table th,
            .images-table td {
                padding: 10px 12px;
            }

            .filename-col {
                max-width: 150px;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-container">
            <a href="/" id="home-link" class="logo-text">
                <img src="./assets/logo-icon.png" alt="T11N Icon" class="logo-icon-img">
                t11n<span class="logo-highlight">upload</span>
            </a>
            <div class="nav-links">
                <a href="/">Home</a>
                <a href="apidoc.php">API Doc</a>
            </div>
        </div>
    </header>

    <div class="stats-box">
        <div class="stats-title">Upload Statistics</div>
        <ul class="stats-list">
            <li>
                <span class="stats-label">Total Files</span>
                <span class="stats-val"><?php echo $totalFiles; ?></span>
            </li>
            <li>
                <span class="stats-label">Total Size</span>
                <span class="stats-val"><?php echo formatSize($totalSize); ?></span>
            </li>
        </ul>

        <?php echo $deleteMessage; ?>

        <?php if ($totalFiles > 0) { ?>
            <div class="bulk-delete-section">
                <button type="button" id="bulkDeleteBtn" class="btn-delete-all">Delete All Images</button>
            </div>

            <!-- Password modal will be rendered at the bottom of the page to fix backdrop-filter containment issues -->


            <table class="images-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th class="preview-col">Preview</th>
                        <th>File Name</th>
                        <th style="width: 180px;">Upload Date</th>
                        <th style="width: 120px;">Size</th>
                        <th style="width: 100px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($imagesPage as $i => $img) { ?>
                        <tr>
                            <td><?php echo $start + $i + 1; ?></td>
                            <td class="preview-col">
                                <img src="<?php echo htmlspecialchars($img['url']); ?>" alt="Preview" class="table-thumb" loading="lazy">
                            </td>
                            <td class="filename-col"><?php echo htmlspecialchars($img['name']); ?></td>
                            <td><?php echo date('Y-m-d H:i:s', $img['time']); ?></td>
                            <td><?php echo formatSize($img['size']); ?></td>
                            <td><a href="<?php echo htmlspecialchars($img['url']); ?>" target="_blank" class="btn-view">View</a></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1) { ?>
                <div class="pagination">
                    <?php
                    $maxLinks = 1;
                    $startPage = max(1, $page - $maxLinks);
                    $endPage = min($totalPages, $page + $maxLinks);

                    if ($startPage > 1) {
                        echo '<a href="?page=1" class="page-link page-link--default">1</a>';
                        if ($startPage > 2) echo '<span class="page-ellipsis">...</span>';
                    }
                    for ($p = $startPage; $p <= $endPage; $p++) {
                        $activeClass = ($p == $page) ? 'page-link--active' : 'page-link--default';
                        echo '<a href="?page=' . $p . '" class="page-link ' . $activeClass . '">' . $p . '</a>';
                    }
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) echo '<span class="page-ellipsis">...</span>';
                        echo '<a href="?page=' . $totalPages . '" class="page-link page-link--default">' . $totalPages . '</a>';
                    }
                    ?>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="no-images">No images uploaded yet.</div>
        <?php } ?>
    </div>

    <?php if ($totalFiles > 0) { ?>
        <!-- Password modal -->
        <div class="modal-overlay" id="passwordModal">
            <div class="modal-box">
                <div class="modal-title">Enter password to delete all images</div>
                <form id="bulkDeleteForm" method="post" action="stats.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="delete_all" value="1">
                    <input type="password" name="password" id="deletePassword" class="modal-input" placeholder="Password" autocomplete="off">
                    <div class="modal-error" id="passwordError"></div>
                    <div class="modal-actions">
                        <button type="submit" class="btn-confirm">Confirm</button>
                        <button type="button" id="cancelDeleteBtn" class="btn-cancel">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            document.getElementById('bulkDeleteBtn').onclick = function () {
                document.getElementById('passwordModal').style.display = 'flex';
                document.getElementById('deletePassword').value = '';
                document.getElementById('passwordError').style.display = 'none';
            };
            document.getElementById('cancelDeleteBtn').onclick = function () {
                document.getElementById('passwordModal').style.display = 'none';
            };
        </script>
    <?php } ?>
</body>

</html>