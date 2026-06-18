# T11N UPLOAD - Modern Image Upload Application

A modern web application for image upload (PNG, JPG, GIF, WEBP, AVIF) with drag & drop, clipboard paste, and bulk upload support. Files are securely renamed and can be deleted from both the web UI and the server.

## Features
- Drag & drop, click, or paste from clipboard to upload
- Bulk/multiple file uploads
- Size (Max 50MB) and format validation
- Secure random renaming: `32chars_timestamp_originalName.ext` (prevents filename enumeration/guessing)
- Copy links easily: Direct URL, BBCode, HTML, and Markdown formats
- Delete files from the UI and server
- Fully responsive, premium modern interface
- **REST API** for developers (supports single or bulk upload/delete)
- Non-existent links automatically redirect to home page (`.htaccess`)

## Security
- CSRF token validation for secure upload/delete requests (web UI)
- Strict MIME type verification using `finfo` + `getimagesize` (prevents extension spoofing)
- Restricts uploads strictly to images (JPG, PNG, GIF, WEBP, AVIF)
- Random filename obfuscation (32 hex characters + timestamp)
- HTML escaping to prevent XSS when displaying filenames
- Path traversal protection using `basename()` and `realpath()` comparison

## Getting Started
1. Clone or download this repository to your PHP server (e.g., Laragon, XAMPP, Nginx/Apache).
2. Ensure the `uploads/` directory has write permissions (`chmod 755` or `777`).
3. Open the homepage in your browser.
4. Upload images by dragging, clicking, or pasting.
5. Copy share links or delete files as needed.
6. **Note:** Uploaded files will be renamed like `a1b2c3d4..._1739245678_image.jpg`.

## Directory Structure
- `index.php` — Main Web Interface
- `script.js` — Frontend Logic & AJAX Handler
- `style.css` — Responsive CSS Styling
- `upload.php` — Web UI Upload Handler
- `delete.php` — Web UI Delete Handler
- `api.php` — Developer REST API (supports bulk actions)
- `config.php` — Common Settings & Helper Functions
- `stats.php` — Dashboard & Image Management
- `.htaccess` — URL Rewriting & Protection
- `uploads/` — Directory containing uploaded images

---

## REST API

No API key required. CORS enabled. Returns standard JSON responses with details for each processed file.

### 1. Upload Images (Single or Bulk)

```
POST /api.php?action=upload
```

**Field:** `files[]` (for multiple files) or `file` (for a single file)

> ⚠️ **Important:** For multiple file uploads, the field name **MUST** be `files[]` (with brackets). If you use `files` (without brackets), PHP will only parse the last uploaded file.

**cURL (bulk upload):**
```bash
curl -X POST "https://up.t11n.dev/api.php?action=upload" \
  -F "files[]=@image1.jpg" \
  -F "files[]=@image2.png"
```

**cURL (single upload):**
```bash
curl -X POST "https://up.t11n.dev/api.php?action=upload" \
  -F "file=@image1.jpg"
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "name": "a1b2c3d4e5f678901234567890abcdef_1739245678_image1.jpg",
      "original_name": "image1.jpg",
      "status": "success",
      "url": "https://up.t11n.dev/uploads/a1b2c3d4e5f678901234567890abcdef_1739245678_image1.jpg",
      "size": 123456,
      "mime": "image/jpeg",
      "width": 1920,
      "height": 1080
    },
    {
      "name": "image2.png",
      "status": "error",
      "error": "File too large"
    }
  ]
}
```

### 2. Delete Images (Single or Bulk)

```
POST /api.php?action=delete
Content-Type: application/json
```

**Body:** Send a JSON object containing the `names` array (or `name` for single file). Filenames must match the obfuscated names stored on the server.

**cURL (bulk delete):**
```bash
curl -X POST "https://up.t11n.dev/api.php?action=delete" \
  -H "Content-Type: application/json" \
  -d '{"names": ["a1b2c3d4..._1739245678_image1.jpg"]}'
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "name": "a1b2c3d4..._1739245678_image1.jpg",
      "status": "success"
    }
  ]
}
```

## License
MIT
