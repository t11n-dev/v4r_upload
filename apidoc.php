<?php
/**
 * apidoc.php — API Documentation page.
 */
session_start();

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'up.t11n.dev';
$dir = dirname($_SERVER['SCRIPT_NAME']);
$dir = ($dir === '/' || $dir === '\\') ? '' : $dir;
$currentDomain = $protocol . '://' . $host . $dir;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation | T11N Fast Image Upload</title>
    <meta name="description" content="API documentation for T11N Upload. Integration guide for uploading and deleting images.">
    <link rel="icon" href="./assets/favicon.png" type="image/png">
    <link rel="stylesheet" href="./style.css">
    <style>
        .api-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            /* background: rgba(255, 255, 255, 0.9); */
            border-radius: 12px;
            /* box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); */
        }
        .api-section {
            margin-bottom: 40px;
        }
        .api-title {
            font-size: 2rem;
            color: #1a202c;
            margin-bottom: 1rem;
            border-bottom: 2px solid #cbd5e0;
            padding-bottom: 0.5rem;
        }
        .api-subtitle {
            font-size: 1.5rem;
            color: #2d3748;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        .endpoint {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #4299e1;
            border-radius: 6px;
            padding: 10px 15px;
            margin-bottom: 15px;
            font-family: monospace;
            font-size: 1.1em;
            color: #2b6cb0;
        }
        .method {
            font-weight: bold;
            color: #fff;
            background: #4299e1;
            padding: 2px 8px;
            border-radius: 4px;
            margin-right: 10px;
        }
        .code-block {
            background: #1a202c;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 0.9em;
            margin: 10px 0;
            line-height: 1.5;
        }
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background-color: #edf2f7;
            color: #4a5568;
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
                <a href="apidoc.php" class="active">API Doc</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="api-container">
            <h1 class="api-title">REST API Documentation</h1>
            <p>Integration guide for uploading and deleting images (single & bulk). Supports JPG, PNG, GIF, WEBP, AVIF. CORS enabled. No API key required.</p>

            <div class="api-section">
                <h2 class="api-subtitle">1. Upload Images (Bulk Support)</h2>
                <div class="endpoint">
                    <span class="method">POST</span> /api.php?action=upload
                </div>
                <p>Upload one or multiple images at once.</p>

                <p style="margin: 10px 0;background:#fffbeb; border-left:4px solid #f59e0b; padding:10px 15px; border-radius:4px; color:#92400e;">⚠️ <strong>Note:</strong> When uploading multiple files, the field name <strong>MUST</strong> be <code>files[]</code> (with <code>[]</code>). Using <code>files</code> (without <code>[]</code>) will only upload the last file.</p>
                
                <h3>Parameters</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>files[]</code></td>
                            <td>Array of image files to upload (supports multiple files). <strong>Brackets <code>[]</code> required</strong>.</td>
                        </tr>
                        <tr>
                            <td><code>file</code></td>
                            <td>Single image file (for uploading one file only).</td>
                        </tr>
                    </tbody>
                </table>

                <h3>cURL — Multiple Files</h3>
                <pre class="code-block">
curl -X POST "<?php echo htmlspecialchars($currentDomain); ?>/api.php?action=upload" \
  -F "files[]=@image1.jpg" \
  -F "files[]=@image2.png"
                </pre>

                <h3>cURL — Single File</h3>
                <pre class="code-block">
curl -X POST "<?php echo htmlspecialchars($currentDomain); ?>/api.php?action=upload" \
  -F "file=@image1.jpg"
                </pre>

                <h3>Success Response</h3>
                <pre class="code-block">
{
  "success": true,
  "data": [
    {
      "name": "a1b2c3..._1739245678_image1.jpg",
      "original_name": "image1.jpg",
      "status": "success",
      "url": "<?php echo htmlspecialchars($currentDomain); ?>/uploads/a1b2c3..._1739245678_image1.jpg",
      "size": 123456,
      "mime": "image/jpeg",
      "width": 1920,
      "height": 1080
    }
  ]
}
                </pre>
            </div>

            <div class="api-section">
                <h2 class="api-subtitle">2. Delete Images (Bulk Support)</h2>
                <div class="endpoint">
                    <span class="method">POST</span> /api.php?action=delete
                </div>
                <p>Delete one or multiple images.</p>

                <h3 style="margin: 10px 0;">Body JSON</h3>
                <pre class="code-block">
{
  "names": [
    "a1b2c3..._1739245678_image1.jpg",
    "xyz789..._1739245679_image2.png"
  ]
}
                </pre>

                <h3>cURL Example</h3>
                <pre class="code-block">
curl -X POST "<?php echo htmlspecialchars($currentDomain); ?>/api.php?action=delete" \
  -H "Content-Type: application/json" \
  -d '{"names": ["filename_to_delete.jpg"]}'
                </pre>

                <h3>Response</h3>
                <pre class="code-block">
{
  "success": true,
  "data": [
    {
      "name": "filename_to_delete.jpg",
      "status": "success"
    }
  ]
}
                </pre>
            </div>

            <div class="api-section">
                <h2 class="api-subtitle">HTTP Error Codes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>200</td>
                            <td>Success (details in <code>data</code>)</td>
                        </tr>
                        <tr>
                            <td>400</td>
                            <td>Missing parameters or invalid format</td>
                        </tr>
                        <tr>
                            <td>405</td>
                            <td>Invalid method (POST only)</td>
                        </tr>
                        <tr>
                            <td>413</td>
                            <td>File too large (>50MB)</td>
                        </tr>
                        <tr>
                            <td>500</td>
                            <td>Internal server error</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-container">
            <p class="footer-text">© 2026 <a href="https://t11n.dev/" target="_blank">T11N Team.</a> All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
