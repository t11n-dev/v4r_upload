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

$baseUrl = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Title of the webpage -->
    <title>Fast Image Upload | Easy Image Sharing</title>

    <!-- Meta description for SEO -->
    <meta name="description" content="A fast and convenient image upload platform. Share your images easily with high quality and fast loading speed.">

    <!-- Meta keywords for SEO (Optional) -->
    <meta name="keywords" content="image upload, share images, fast upload, upload pictures, high quality images, share images online, free image upload">

    <!-- Robots meta tag to control search engine crawling -->
    <meta name="robots" content="index, follow">

    <!-- Open Graph meta tags for better social sharing -->
    <meta property="og:title" content="Fast Image Upload | Easy Image Sharing">
    <meta property="og:description" content="Share and upload images with fast speed and high quality. Easily upload and share images with friends.">
    <meta property="og:image" content="<?php echo htmlspecialchars($baseUrl); ?>/assets/logo.png">
    <meta property="og:url" content="<?php echo htmlspecialchars($baseUrl); ?>">
    <meta property="og:type" content="website">

    <!-- Twitter card meta tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Fast Image Upload | Easy Image Sharing">
    <meta name="twitter:description" content="Share and upload images with fast speed. Upload images and share easily with outstanding quality.">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($baseUrl); ?>/assets/logo.png">

    <!-- Favicon for website icon -->
    <link rel="icon" href="./assets/favicon.png" type="image/png">

    <!-- Canonical URL to prevent duplicate content issues -->
    <link rel="canonical" href="<?php echo htmlspecialchars($baseUrl); ?>">

    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebPage",
            "url": "<?php echo htmlspecialchars($baseUrl); ?>/",
            "name": "Fast Image Upload | Easy Image Sharing",
            "description": "A fast and convenient image upload platform. Share your images easily with high quality and fast loading speed.",
            "publisher": {
                "@type": "Organization",
                "name": "T11N Upload",
                "logo": {
                    "@type": "ImageObject",
                    "url": "<?php echo htmlspecialchars($baseUrl); ?>/assets/logo.png"
                },
                "sameAs": [
                    "https://github.com/t11n-dev"
                ]
            },
            "mainEntityOfPage": {
                "@type": "WebSite",
                "@id": "<?php echo htmlspecialchars($baseUrl); ?>/"
            },
            "founder": {
                "@type": "Person",
                "name": "T11N Team",
                "url": "https://t11n.dev",
                "sameAs": [
                    "https://github.com/t11n-dev"
                ]
            },
            "foundingDate": "2026",
            "email": "dev@t11n.dev",
            "legalName": "T11N Team"
        }
    </script>

    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "Fast Image Upload | Easy Image Sharing",
            "url": "<?php echo htmlspecialchars($baseUrl); ?>/",
            "logo": "<?php echo htmlspecialchars($baseUrl); ?>/assets/logo.png",
            "sameAs": [
                "https://github.com/t11n-dev"
            ]
        }
    </script>

    <link rel="stylesheet" href="./style.css">
</head>

<body>
    <!-- CSRF token for JS -->
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>">

    <header class="header">
        <div class="header-container">
            <a href="/" id="home-link" class="logo-text">
                <img src="./assets/logo-icon.png" alt="T11N Icon" class="logo-icon-img">
                t11n<span class="logo-highlight">upload</span>
            </a>
            <div class="nav-links">
                <a href="/" class="active">Home</a>
                <a href="apidoc.php">API Doc</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="upload-container">
            <div class="upload-box" id="uploadBox">
                <div class="upload-content">
                    <div class="upload-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <h3 class="upload-title">Drop files here, click to upload or paste image</h3>
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
                    <h4 class="preview-title">Selected Files</h4>
                    <button class="add-more-btn" id="addMoreBtn" style="display: none;">Add More Files</button>
                </div>
                <div class="files-list" id="filesList">
                    <!-- Files will be dynamically added here -->
                </div>
            </div>

            <div class="upload-complete" id="uploadComplete" style="display: none;">
                <div class="complete-header">
                    <div class="success-icon">✓</div>
                    <h3 class="complete-title">Upload Successful!</h3>
                    <p class="complete-subtitle">Your files have been uploaded successfully</p>
                </div>
                <div class="complete-actions">
                    <button class="new-upload-btn" id="newUploadBtn">Start New Upload</button>
                    <button class="view-files-btn" id="viewFilesBtn">View Uploaded Files</button>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-container">
            <p class="footer-text">© 2026 <a href="https://t11n.dev/" target="_blank">T11N Team.</a> All rights reserved.</p>
        </div>
    </footer>

    <script src="./script.js"></script>
</body>

</html>