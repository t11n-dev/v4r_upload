<?php
/**
 * apidoc.php — API Documentation page.
 */
session_start();

require_once __DIR__ . '/config.php';
$currentDomain = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Reference | T11N Fast Image Upload</title>
    <meta name="description" content="REST API documentation for T11N Upload. Easy integration guide for uploading and deleting images (single or bulk).">
    <link rel="icon" href="./assets/favicon.png" type="image/png">
    
    <!-- Link to the main style.css for consistent header/footer/font styling -->
    <link rel="stylesheet" href="./style.css">
    
    <style>
        .api-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 1rem;
        }

        .api-intro {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
        }

        .api-intro h1 {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: #1a202c;
            letter-spacing: -0.5px;
        }

        .api-intro p {
            color: #4a5568;
            font-size: 1.05rem;
            line-height: 1.7;
        }

        .badge-info {
            display: inline-block;
            background: #ebf8ff;
            color: #2b6cb0;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 8px;
            margin-top: 1rem;
            border: 1px solid #bee3f8;
        }

        /* Endpoint Card */
        .endpoint-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 2.5rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .endpoint-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.04);
        }

        .endpoint-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .method-badge {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #ffffff;
            font-weight: 700;
            font-size: 0.9rem;
            padding: 6px 16px;
            border-radius: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
        }

        .endpoint-url {
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 1.15rem;
            font-weight: 600;
            color: #1a202c;
            background: #f7fafc;
            padding: 6px 16px;
            border-radius: 8px;
            border: 1px solid #edf2f7;
            flex: 1;
            min-width: 250px;
            word-break: break-all;
        }

        .endpoint-desc {
            color: #4a5568;
            font-size: 1.05rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        /* Tables styling */
        .params-table-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #edf2f7;
            margin: 1.5rem 0;
        }

        .params-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .params-table th {
            background: #f8fafc;
            color: #4a5568;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 18px;
            border-bottom: 1px solid #e2e8f0;
        }

        .params-table td {
            padding: 14px 18px;
            border-bottom: 1px solid #edf2f7;
            font-size: 0.95rem;
            color: #2d3748;
            vertical-align: top;
        }

        .params-table tr:last-child td {
            border-bottom: none;
        }

        .param-name {
            font-family: 'Consolas', 'Monaco', monospace;
            font-weight: 700;
            color: #2b6cb0;
        }

        .param-type {
            font-size: 0.8rem;
            color: #718096;
            background: #edf2f7;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 500;
        }

        .param-req {
            font-size: 0.8rem;
            font-weight: 600;
            color: #e53e3e;
            text-transform: uppercase;
        }

        .param-opt {
            font-size: 0.8rem;
            font-weight: 600;
            color: #718096;
            text-transform: uppercase;
        }

        /* Code Block Container */
        .code-container {
            position: relative;
            margin: 1.5rem 0;
            border-radius: 12px;
            overflow: hidden;
            background: #1a202c;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .code-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #2d3748;
            padding: 10px 18px;
            border-bottom: 1px solid #4a5568;
        }

        .code-label {
            color: #a0aec0;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-copy {
            background: transparent;
            border: 1px solid #4a5568;
            color: #e2e8f0;
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .btn-copy:hover {
            background: #4a5568;
            color: #ffffff;
        }

        .code-block {
            margin: 0;
            padding: 18px;
            overflow-x: auto;
            color: #e2e8f0;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        /* Note Callout */
        .callout-note {
            background: #fffaf0;
            border-left: 4px solid #dd6b20;
            border-radius: 8px;
            padding: 1.25rem;
            margin: 1.5rem 0;
            color: #dd6b20;
        }

        .callout-note h4 {
            font-weight: 700;
            margin-bottom: 4px;
            font-size: 1rem;
        }

        .callout-note p {
            margin: 0;
            font-size: 0.95rem;
            color: #7b341e;
            line-height: 1.5;
        }

        /* Status codes section */
        .status-section {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 2.5rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 1.5rem;
        }

        /* Status Colors with high contrast on white background */
        .status-200 {
            color: #2f855a !important;
            font-weight: 700;
        }
        .status-400 {
            color: #dd6b20 !important;
            font-weight: 700;
        }
        .status-500 {
            color: #c53030 !important;
            font-weight: 700;
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
            
            <!-- Intro Section -->
            <div class="api-intro">
                <h1>REST API Reference</h1>
                <p>Welcome to the T11N Upload REST API documentation. This API allows developers to easily integrate image upload and delete capabilities (supporting single or bulk actions) directly into their own applications. All endpoints return standard JSON responses and support CORS.</p>
                <div class="badge-info">ℹ️ Authentication: No API key required for public use.</div>
            </div>

            <!-- Endpoint 1: Upload -->
            <div class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge">POST</span>
                    <div class="endpoint-url">/api.php?action=upload</div>
                </div>
                <div class="endpoint-desc">
                    Upload one or multiple images at once. Supports formats: <code>JPG</code>, <code>PNG</code>, <code>GIF</code>, <code>WEBP</code>, and <code>AVIF</code>. Maximum size limit is 50MB per file.
                </div>

                <div class="section-title">Request Headers</div>
                <div class="params-table-wrapper">
                    <table class="params-table">
                        <thead>
                            <tr>
                                <th>Header</th>
                                <th>Value</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="param-name">Content-Type</td>
                                <td><code>multipart/form-data</code></td>
                                <td>Required for file transmission.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="section-title">Parameters (Form Data)</div>
                <div class="params-table-wrapper">
                    <table class="params-table">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Required</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="param-name">files[]</td>
                                <td><span class="param-type">file[]</span></td>
                                <td><span class="param-req">Yes*</span></td>
                                <td>An array of image files to upload. <strong>Brackets <code>[]</code> are mandatory</strong> for uploading multiple files.</td>
                            </tr>
                            <tr>
                                <td class="param-name">file</td>
                                <td><span class="param-type">file</span></td>
                                <td><span class="param-req">Yes*</span></td>
                                <td>A single image file (fallback option if not using array field).</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="callout-note">
                    <h4>💡 Field Name Flexibility</h4>
                    <p>While <code>files[]</code> and <code>file</code> are standard, the API automatically scans <em>all</em> fields inside the upload payload. However, using <code>files[]</code> is highly recommended for bulk uploads.</p>
                </div>

                <div class="section-title">cURL Examples</div>
                
                <!-- Bulk upload example -->
                <div class="code-container">
                    <div class="code-header">
                        <span class="code-label">cURL — Bulk Upload</span>
                        <button class="btn-copy">Copy</button>
                    </div>
                    <pre class="code-block">curl -X POST "<?php echo htmlspecialchars($currentDomain); ?>/api.php?action=upload" \
  -F "files[]=@image1.jpg" \
  -F "files[]=@image2.png"</pre>
                </div>

                <!-- Single upload example -->
                <div class="code-container">
                    <div class="code-header">
                        <span class="code-label">cURL — Single Upload</span>
                        <button class="btn-copy">Copy</button>
                    </div>
                    <pre class="code-block">curl -X POST "<?php echo htmlspecialchars($currentDomain); ?>/api.php?action=upload" \
  -F "file=@image1.jpg"</pre>
                </div>

                <div class="section-title">Success Response (200 OK)</div>
                <div class="code-container">
                    <div class="code-header">
                        <span class="code-label">JSON Response</span>
                        <button class="btn-copy">Copy</button>
                    </div>
                    <pre class="code-block">{
  "success": true,
  "data": [
    {
      "name": "a1b2c3d4..._1739245678_image1.jpg",
      "original_name": "image1.jpg",
      "status": "success",
      "url": "<?php echo htmlspecialchars($currentDomain); ?>/uploads/a1b2c3d4..._1739245678_image1.jpg",
      "size": 123456,
      "mime": "image/jpeg",
      "width": 1920,
      "height": 1080
    }
  ]
}</pre>
                </div>
            </div>

            <!-- Endpoint 2: Delete -->
            <div class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge">POST</span>
                    <div class="endpoint-url">/api.php?action=delete</div>
                </div>
                <div class="endpoint-desc">
                    Delete one or multiple image files previously uploaded to the server.
                </div>

                <div class="section-title">Request Headers</div>
                <div class="params-table-wrapper">
                    <table class="params-table">
                        <thead>
                            <tr>
                                <th>Header</th>
                                <th>Value</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="param-name">Content-Type</td>
                                <td><code>application/json</code></td>
                                <td>Required for transmitting request body.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="section-title">JSON Body Parameters</div>
                <div class="params-table-wrapper">
                    <table class="params-table">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Required</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="param-name">names</td>
                                <td><span class="param-type">string[]</span></td>
                                <td><span class="param-req">Yes*</span></td>
                                <td>Array of filenames to delete (e.g. <code>["a1b2..._image1.jpg", "x1y2..._image2.png"]</code>).</td>
                            </tr>
                            <tr>
                                <td class="param-name">name</td>
                                <td><span class="param-type">string</span></td>
                                <td><span class="param-req">Yes*</span></td>
                                <td>Single filename to delete (fallback if <code>names</code> array is not provided).</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="section-title">cURL Example</div>
                <div class="code-container">
                    <div class="code-header">
                        <span class="code-label">cURL — Delete Files</span>
                        <button class="btn-copy">Copy</button>
                    </div>
                    <pre class="code-block">curl -X POST "<?php echo htmlspecialchars($currentDomain); ?>/api.php?action=delete" \
  -H "Content-Type: application/json" \
  -d '{"names": ["a1b2c3d4e5f678901234567890abcdef_1739245678_image1.jpg"]}'</pre>
                </div>

                <div class="section-title">Response (200 OK)</div>
                <div class="code-container">
                    <div class="code-header">
                        <span class="code-label">JSON Response</span>
                        <button class="btn-copy">Copy</button>
                    </div>
                    <pre class="code-block">{
  "success": true,
  "data": [
    {
      "name": "a1b2c3d4e5f678901234567890abcdef_1739245678_image1.jpg",
      "status": "success"
    }
  ]
}</pre>
                </div>
            </div>

            <!-- Error Codes Section -->
            <div class="status-section">
                <div class="section-title">API Response Codes</div>
                <div class="params-table-wrapper">
                    <table class="params-table">
                        <thead>
                            <tr>
                                <th>HTTP Code</th>
                                <th>Meaning</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="status-200">200</td>
                                <td>OK</td>
                                <td>Request succeeded. The detailed status of each file is in the response body.</td>
                            </tr>
                            <tr>
                                <td class="status-400">400</td>
                                <td>Bad Request</td>
                                <td>Missing parameters, invalid file format, or improper request payload.</td>
                            </tr>
                            <tr>
                                <td class="status-400">405</td>
                                <td>Method Not Allowed</td>
                                <td>The request method is invalid (only <code>POST</code> is supported).</td>
                            </tr>
                            <tr>
                                <td class="status-500">500</td>
                                <td>Internal Server Error</td>
                                <td>The server encountered an error saving or deleting files on disk.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <footer class="footer">
        <div class="footer-container">
            <p class="footer-text">© 2026 <a href="https://t11n.dev/" target="_blank">T11N Team.</a> All rights reserved.</p>
        </div>
    </footer>

    <!-- Interactive copy code snippet helper -->
    <script>
        document.querySelectorAll('.btn-copy').forEach((button) => {
            button.addEventListener('click', function() {
                const codeBlock = this.closest('.code-container').querySelector('.code-block');
                const textToCopy = codeBlock.textContent;

                navigator.clipboard.writeText(textToCopy).then(() => {
                    const originalText = this.textContent;
                    this.textContent = 'Copied!';
                    this.style.background = '#22c55e';
                    this.style.color = '#ffffff';
                    this.style.borderColor = '#22c55e';

                    setTimeout(() => {
                        this.textContent = originalText;
                        this.style.background = 'transparent';
                        this.style.color = '#e2e8f0';
                        this.style.borderColor = '#4a5568';
                    }, 1500);
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            });
        });
    </script>
</body>
</html>
