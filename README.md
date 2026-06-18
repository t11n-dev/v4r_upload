# Ứng Dụng Upload Hình Ảnh

Ứng dụng web upload hình ảnh (PNG, JPG, GIF, WEBP, AVIF) hiện đại, hỗ trợ kéo thả, dán từ clipboard và upload hàng loạt. File được đổi tên an toàn, có thể xoá từ giao diện và server.

## Tính năng
- Kéo thả, click hoặc dán để upload
- Upload hàng loạt
- Kiểm tra định dạng file và kích thước (tối đa 100MB)
- Đổi tên file ngẫu nhiên: `32kytu_timestamp_tenanh.ext` (chống đoán tên file)
- Sao chép link: URL, BBCode, HTML, Markdown
- Xoá file từ giao diện và server
- Giao diện responsive, hiện đại
- **REST API** cho developer (upload/xoá đơn lẻ hoặc hàng loạt)
- Link không tồn tại tự chuyển về trang chủ (`.htaccess`)

## Bảo mật
- CSRF token cho request upload/xoá (web UI)
- Validate MIME type thực sự bằng `finfo` + `getimagesize`
- Chỉ cho phép file ảnh (JPG, PNG, GIF, WEBP, AVIF)
- Tên file ngẫu nhiên (32 ký tự alphanumeric + timestamp)
- Escape HTML chống XSS khi hiển thị tên file
- Xoá hàng loạt yêu cầu mật khẩu (xác thực server-side)

## Hướng dẫn sử dụng
1. Clone/download dự án vào web server (Laragon/XAMPP).
2. Đảm bảo thư mục `uploads/` có quyền ghi.
3. Mở `index.php` trên trình duyệt.
4. Upload ảnh bằng cách kéo thả, click hoặc dán.
5. Sao chép link hoặc xoá file tuỳ ý.
6. **Lưu ý:** Tên ảnh sau upload sẽ có dạng `a1b2c3d4..._1739245678_image.jpg`.

## Cấu trúc thư mục
- `index.php` — Giao diện chính
- `script.js` — Logic frontend
- `style.css` — Giao diện CSS
- `upload.php` — Xử lý upload file từ web UI
- `delete.php` — Xử lý xoá file từ web UI
- `api.php` — REST API cho developer (hỗ trợ bulk)
- `config.php` — Cấu hình chung + hàm helper filename
- `stats.php` — Thống kê và quản lý ảnh
- `.htaccess` — Redirect
- `uploads/` — Thư mục lưu ảnh

---

## REST API

Không cần API key. Hỗ trợ CORS. Trả về JSON chuẩn với danh sách kết quả cho từng item.

### 1. Upload ảnh (1 hoặc nhiều file)

```
POST /api.php?action=upload
```

**Field:** `files[]` (nhiều file) hoặc `file` (1 file)

> ⚠️ **Lưu ý:** Khi upload nhiều file, field **BẮT BUỘC** phải là `files[]` (có `[]`). Nếu dùng `files` (không có `[]`), PHP chỉ nhận file cuối cùng.

**cURL (upload nhiều file):**
```bash
curl -X POST "https://up.t11n.dev/api.php?action=upload" \
  -F "files[]=@image1.jpg" \
  -F "files[]=@image2.png"
```

**cURL (upload 1 file):**
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

### 2. Xoá ảnh (1 hoặc nhiều file)

```
POST /api.php?action=delete
Content-Type: application/json
```

**Body:** truyền mảng tên file `names` (hoặc `name` nếu xoá 1 file). Tên file phải **chính xác** tên đã upload (gồm 32 ký tự random).

**cURL (xoá hàng loạt):**
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

## Giấy phép
MIT
