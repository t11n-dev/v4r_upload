<?php
/**
 * index.php — Main UI for image upload application.
 *
 * Generates CSRF token for secure upload/delete operations.
 * Provides drag & drop, click, and paste upload interface.
 */

require_once __DIR__ . '/config.php';

session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$siteUrl = getSiteUrl();

// The .htaccess catch-all routes unknown paths here; answer them with a real 404
// so search engines don't index duplicate/soft-404 copies of the homepage.
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isNotFound = !in_array($requestPath, [$basePath . '/', $basePath . '/index.php'], true);
if ($isNotFound) {
    http_response_code(404);
}

$pageTitle = 'Free Image Upload & Sharing | T11N Upload';
$pageDescription = 'Upload and share images for free. Drag & drop, paste or bulk upload PNG, JPG, WEBP, GIF and AVIF up to 50MB and get direct links instantly.';

$jsonLd = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Organization',
            '@id' => $siteUrl . '/#organization',
            'name' => 'T11N Upload',
            'legalName' => 'T11N Team',
            'url' => $siteUrl . '/',
            'logo' => $siteUrl . '/assets/logo.png',
            'email' => 'dev@t11n.dev',
            'foundingDate' => '2026',
            'sameAs' => ['https://t11n.dev', 'https://github.com/t11n-dev'],
        ],
        [
            '@type' => 'WebSite',
            '@id' => $siteUrl . '/#website',
            'url' => $siteUrl . '/',
            'name' => 'T11N Upload',
            'inLanguage' => 'en',
            'publisher' => ['@id' => $siteUrl . '/#organization'],
        ],
        [
            '@type' => 'WebApplication',
            '@id' => $siteUrl . '/#webapp',
            'name' => 'T11N Upload',
            'url' => $siteUrl . '/',
            'description' => $pageDescription,
            'applicationCategory' => 'MultimediaApplication',
            'operatingSystem' => 'Any',
            'browserRequirements' => 'Requires JavaScript',
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
            'publisher' => ['@id' => $siteUrl . '/#organization'],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="robots" content="<?php echo $isNotFound ? 'noindex, follow' : 'index, follow, max-image-preview:large'; ?>">
    <meta name="theme-color" content="#1a202c">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>">

    <link rel="canonical" href="<?php echo htmlspecialchars($siteUrl); ?>/">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="T11N Upload">
    <meta property="og:locale" content="en_US">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($siteUrl); ?>/">
    <meta property="og:image" content="<?php echo htmlspecialchars($siteUrl); ?>/assets/logo.png">
    <meta property="og:image:width" content="1024">
    <meta property="og:image:height" content="1024">
    <meta property="og:image:alt" content="T11N Upload logo">

    <!-- Twitter card (square logo → "summary") -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($siteUrl); ?>/assets/logo.png">

    <link rel="icon" href="./assets/favicon.png" type="image/png">
    <link rel="apple-touch-icon" href="./assets/logo-icon.png">

    <script type="application/ld+json"><?php echo json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG); ?></script>

    <link rel="stylesheet" href="./style.css">
</head>

<body>
    <header class="header">
        <div class="header-container">
            <a href="/" id="home-link" class="logo-text">
                <img src="./assets/logo-icon.png" alt="" width="42" height="42" class="logo-icon-img">
                t11n<span class="logo-highlight">upload</span>
            </a>
            <nav class="nav-links" aria-label="Main">
                <a href="/" class="active" aria-current="page">Home</a>
                <a href="apidoc.php">API Doc</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="upload-container">
            <div class="upload-box" id="uploadBox">
                <div class="upload-content">
                    <div class="upload-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <h1 class="upload-title">Free Image Upload — drop, click or paste to share</h1>
                    <p class="upload-subtitle">Support for single or bulk uploads. Images (PNG, JPG, WEBP, GIF, AVIF). Max 50MB.</p>
                    <button class="upload-button" type="button">Choose Files</button>
                    <input type="file" id="fileInput" multiple accept="image/jpeg,image/png,image/gif,image/webp,image/avif" hidden>
                </div>

                <div class="upload-progress" id="uploadProgress">
                    <div class="progress-circle">
                        <svg class="progress-ring" width="60" height="60">
                            <circle cx="30" cy="30" r="25" stroke="#4a5568" stroke-width="4" fill="none" />
                            <circle class="progress-bar" cx="30" cy="30" r="25" stroke="#f093fb" stroke-width="4" fill="none" stroke-dasharray="157" stroke-dashoffset="157" />
                        </svg>
                        <span class="progress-text">0%</span>
                    </div>
                    <p class="progress-label">Uploading files...</p>
                </div>
            </div>

            <div class="files-preview" id="filesPreview">
                <div class="preview-header">
                    <h2 class="preview-title">Selected Files</h2>
                    <button class="add-more-btn" id="addMoreBtn" style="display: none;">Add More Files</button>
                </div>
                <div class="files-list" id="filesList">
                    <!-- Files will be dynamically added here -->
                </div>
            </div>

            <div class="upload-complete" id="uploadComplete" style="display: none;">
                <div class="complete-header">
                    <div class="success-icon">✓</div>
                    <h2 class="complete-title">Upload Successful!</h2>
                    <p class="complete-subtitle">Your files have been uploaded successfully</p>
                </div>
                <div class="complete-actions">
                    <button class="new-upload-btn" id="newUploadBtn">Start New Upload</button>
                    <button class="view-files-btn" id="viewFilesBtn">View Uploaded Files</button>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <p class="footer-text">© 2026 <a href="https://t11n.dev/" target="_blank" rel="noopener">T11N Team.</a> All rights reserved.</p>
        </div>
    </footer>

    <script src="./script.js"></script>
</body>

</html>