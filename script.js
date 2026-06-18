/**
 * script.js — Frontend logic for image upload application.
 *
 * Handles drag & drop, click, paste upload with real-time progress,
 * file preview, copy links (URL/BBCode/HTML/Markdown), and deletion.
 */

class FileUploadComponent {
    constructor() {
        this.uploadBox = document.getElementById("uploadBox");
        this.fileInput = document.getElementById("fileInput");
        this.filesPreview = document.getElementById("filesPreview");
        this.filesList = document.getElementById("filesList");
        this.uploadProgress = document.getElementById("uploadProgress");
        this.uploadComplete = document.getElementById("uploadComplete");
        this.addMoreBtn = document.getElementById("addMoreBtn");
        this.newUploadBtn = document.getElementById("newUploadBtn");
        this.viewFilesBtn = document.getElementById("viewFilesBtn");

        this.files = [];
        this.uploadQueue = [];
        this.activeUploads = 0;
        this.maxConcurrentUploads = 5; // Limit to 5 concurrent uploads to protect VPS
        this.maxFileSize = 50 * 1024 * 1024; // 50MB
        this.allowedTypes = [
            "image/jpeg", "image/png", "image/gif", "image/webp", "image/avif"
        ];

        // Read CSRF token from meta tag
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        this.csrfToken = csrfMeta ? csrfMeta.getAttribute("content") : "";

        this.init();
    }

    init() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Upload box events
        this.uploadBox.addEventListener("click", () => {
            this.fileInput.click();
        });

        this.fileInput.addEventListener("change", (e) => {
            this.handleFiles(e.target.files);
        });

        // Drag and drop events
        this.uploadBox.addEventListener("dragover", (e) => {
            e.preventDefault();
            this.uploadBox.classList.add("dragover");
        });

        this.uploadBox.addEventListener("dragleave", (e) => {
            e.preventDefault();
            this.uploadBox.classList.remove("dragover");
        });

        this.uploadBox.addEventListener("drop", (e) => {
            e.preventDefault();
            this.uploadBox.classList.remove("dragover");
            this.handleFiles(e.dataTransfer.files);
        });

        // Action buttons
        this.addMoreBtn.addEventListener("click", () => {
            this.fileInput.click();
        });

        this.newUploadBtn.addEventListener("click", () => {
            this.startNewUpload();
        });

        this.viewFilesBtn.addEventListener("click", () => {
            this.viewUploadedFiles();
        });

        // Copy from clipboard
        document.addEventListener("paste", (e) => {
            if (e.clipboardData && e.clipboardData.files.length > 0) {
                this.handleFiles(e.clipboardData.files);
            }
        });

        // Prevent default drag behaviors
        ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
            document.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
        });
    }

    /**
     * Escape HTML special characters to prevent XSS.
     */
    escapeHtml(text) {
        const div = document.createElement("div");
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    handleFiles(fileList) {
        const newFiles = Array.from(fileList);
        const filesToUpload = [];

        newFiles.forEach((file) => {
            if (this.validateFile(file)) {
                this.addFile(file);
                filesToUpload.push(this.files[this.files.length - 1]);
            }
        });

        if (filesToUpload.length > 0) {
            this.showPreview();
            this.uploadBox.classList.add("uploading");

            // Add new files to upload queue
            this.uploadQueue.push(...filesToUpload);
            
            // Start processing queue
            this.processQueue();
        }
    }

    processQueue() {
        // If we reached max concurrent uploads or queue is empty, do nothing
        if (this.activeUploads >= this.maxConcurrentUploads || this.uploadQueue.length === 0) {
            return;
        }

        const fileObj = this.uploadQueue.shift();
        this.activeUploads++;

        this.uploadFileToServer(fileObj).then(() => {
            this.activeUploads--;
            
            // Check if all files in the list have finished uploading
            const allFinished = this.files.every(f => f.status === "success" || f.status === "error");
            if (allFinished) {
                this.completeUpload();
            } else {
                this.processQueue();
            }
        });

        // Trigger next queue item if available and below limit
        this.processQueue();
    }

    validateFile(file) {
        // Check file type
        const ext = file.name.split('.').pop().toLowerCase();
        const allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

        const isAllowedMime = this.allowedTypes.includes(file.type);
        const isAllowedExt = allowedExts.includes(ext);

        if (!isAllowedExt || (!isAllowedMime && file.type !== "")) {
            this.showError(`${file.name}: Unsupported file type. Only JPG, PNG, GIF, WEBP, AVIF allowed.`);
            return false;
        }

        // Check file size
        if (file.size > this.maxFileSize) {
            this.showError(`${file.name}: File size must be less than 50MB.`);
            return false;
        }

        // Check if file already exists
        if (this.files.some((f) => f.name === file.name && f.size === file.size)) {
            this.showError(`${file.name}: File already selected.`);
            return false;
        }

        return true;
    }

    addFile(file) {
        const fileObj = {
            file: file,
            id: Date.now() + Math.random(),
            name: file.name,
            size: this.formatFileSize(file.size),
            status: "pending",
            progress: 0,
            startTime: 0,
            loaded: 0
        };

        this.files.push(fileObj);
        this.renderFile(fileObj);
    }

    renderFile(fileObj) {
        const fileElement = document.createElement("div");
        fileElement.className = "file-item";
        fileElement.setAttribute("data-file-id", fileObj.id);

        const escapedName = this.escapeHtml(fileObj.name);

        const updateContent = (src, isIcon = false) => {
            const style = isIcon ? 'padding: 10px; background: rgba(255,255,255,0.2);' : '';
            fileElement.innerHTML = `
                <img src="${src}" alt="${escapedName}" class="file-preview" style="${style}">
                <div class="file-info">
                    <div class="file-name">${escapedName}</div>
                    <div class="file-size">${fileObj.size}</div>
                </div>
                <div class="file-status">
                     <div class="upload-speed" style="font-size: 0.8rem; color: #666; margin-right: 10px;"></div>
                    <div class="status-icon status-uploading">⏳</div>
                </div>
                <div class="file-actions">
                    <button class="file-action delete" onclick="fileUpload.removeFile('${fileObj.id}')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 6h18M19 6v14a2 2 0 0 0-2 2H7a2 2 0 0 0-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                    </button>
                </div>
            `;
        };

        if (fileObj.file.type.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'].includes(fileObj.name.split('.').pop().toLowerCase())) {
            const reader = new FileReader();
            reader.onload = (e) => {
                updateContent(e.target.result);
            };
            reader.readAsDataURL(fileObj.file);
        } else {
            // Default image placeholder icon (fallback)
            const icon = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='3' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='21' y1='15' x2='16' y2='10'%3E%3C/line%3E%3Cline x1='5' y1='21' x2='16' y2='10'%3E%3C/line%3E%3Ccircle cx='8.5' cy='8.5' r='1.5'%3E%3C/circle%3E%3C/svg%3E";
            updateContent(icon, true);
        }

        this.filesList.appendChild(fileElement);
    }

    showPreview() {
        this.filesPreview.classList.add("show");
        this.addMoreBtn.style.display = "inline-block";
    }

    getOverallProgress() {
        if (!this.files.length) return 0;
        const total = this.files.reduce((sum, f) => sum + (f.progress || 0), 0);
        return total / this.files.length;
    }

    async uploadFileToServer(fileObj) {
        const formData = new FormData();
        formData.append("file", fileObj.file);
        const fileElement = document.querySelector(`[data-file-id="${fileObj.id}"]`);

        return new Promise((resolve) => {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "upload.php", true);

            // Send CSRF token in header
            if (this.csrfToken) {
                xhr.setRequestHeader("X-CSRF-Token", this.csrfToken);
            }

            fileObj.startTime = Date.now();
            fileObj.loaded = 0;

            xhr.upload.onprogress = (e) => {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    fileObj.progress = percent;

                    // Calculate speed
                    const now = Date.now();
                    const diffTime = (now - fileObj.startTime) / 1000; // time in seconds
                    if (diffTime > 0) {
                        const speedBytes = e.loaded / diffTime;
                        const speedText = this.formatFileSize(speedBytes) + '/s';
                        if (fileElement) {
                            const speedDiv = fileElement.querySelector(".upload-speed");
                            if (speedDiv) speedDiv.textContent = speedText;
                        }
                    }

                    if (fileElement) {
                        const statusIcon = fileElement.querySelector(".status-icon");
                        statusIcon.textContent = percent + "%";
                        statusIcon.className = "status-icon status-uploading";
                    }
                    this.updateProgress(this.getOverallProgress());
                }
            };

            xhr.onload = () => {
                let result = {};
                try {
                    result = JSON.parse(xhr.responseText);
                } catch (e) {
                    result = { success: false, message: "Upload failed" };
                }
                if (result.success) {
                    fileObj.status = "success";
                    fileObj.url = result.url;
                    if (fileElement) {
                        const statusIcon = fileElement.querySelector(".status-icon");
                        statusIcon.className = "status-icon status-success";
                        statusIcon.textContent = "✓";
                        const speedDiv = fileElement.querySelector(".upload-speed");
                        if (speedDiv) speedDiv.style.display = 'none'; // Hide speed on completion
                    }
                    this.showFileUrl(fileObj);
                } else {
                    fileObj.status = "error";
                    if (fileElement) {
                        const statusIcon = fileElement.querySelector(".status-icon");
                        statusIcon.className = "status-icon status-error";
                        statusIcon.textContent = "✗";
                    }
                    this.showError(result.message || "Upload failed");
                }
                resolve();
            };

            xhr.onerror = () => {
                fileObj.status = "error";
                if (fileElement) {
                    const statusIcon = fileElement.querySelector(".status-icon");
                    statusIcon.className = "status-icon status-error";
                    statusIcon.textContent = "✗";
                }
                this.showError("Upload failed");
                resolve();
            };

            xhr.send(formData);
        });
    }

    showFileUrl(fileObj) {
        const fileElement = document.querySelector(`[data-file-id="${fileObj.id}"]`);
        if (fileObj.url && fileElement) {
            const infoDiv = fileElement.querySelector(".file-info");
            const urlDiv = document.createElement("div");
            urlDiv.className = "file-url";
            const escapedUrl = this.escapeHtml(fileObj.url);
            urlDiv.innerHTML = `<a href="${escapedUrl}" target="_blank">View file</a>`;
            infoDiv.appendChild(urlDiv);
        }
    }

    updateProgress(progress) {
        const progressBar = document.querySelector(".progress-bar");
        const progressText = document.querySelector(".progress-text");

        const circumference = 2 * Math.PI * 25;
        const offset = circumference - (progress / 100) * circumference;

        progressBar.style.strokeDashoffset = offset;
        progressText.textContent = Math.round(progress) + "%";
    }

    completeUpload() {
        setTimeout(() => {
            this.uploadBox.style.display = "none";
            this.uploadComplete.style.display = "block";

            const completeTitle = this.uploadComplete.querySelector(".complete-title");
            const completeSubtitle = this.uploadComplete.querySelector(".complete-subtitle");

            completeTitle.textContent = "Upload Successful!";
            completeSubtitle.textContent = `${this.files.length} file(s) uploaded successfully`;

            this.updateUploadedUrlList();
        }, 500);
    }

    startNewUpload() {
        this.files = [];
        this.filesList.innerHTML = "";

        this.uploadComplete.style.display = "none";
        this.uploadBox.style.display = "block";
        this.uploadBox.classList.remove("uploading", "success");
        this.filesPreview.classList.remove("show");
        this.addMoreBtn.style.display = "none";

        const progressBar = document.querySelector(".progress-bar");
        const progressText = document.querySelector(".progress-text");
        progressBar.style.strokeDashoffset = "157";
        progressText.textContent = "0%";

        this.fileInput.value = "";
    }

    showError(message) {
        const errorDiv = document.createElement("div");
        errorDiv.className = "error-notification";
        errorDiv.style.animation = "slideInRight 0.3s ease";
        errorDiv.textContent = message;

        document.body.appendChild(errorDiv);

        setTimeout(() => {
            errorDiv.style.animation = "slideOutRight 0.3s ease";
            setTimeout(() => errorDiv.remove(), 300);
        }, 4000);
    }

    viewUploadedFiles() {
        this.uploadComplete.style.display = "none";
        this.filesPreview.classList.add("show");
        this.addMoreBtn.style.display = "inline-block";

        const previewTitle = this.filesPreview.querySelector(".preview-title");
        previewTitle.textContent = "Uploaded Files";
    }

    formatFileSize(bytes) {
        if (bytes === 0) return "0 Bytes";

        const k = 1024;
        const sizes = ["Bytes", "KB", "MB", "GB"];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
    }

    updateUploadedUrlList() {
        const urlListDiv = this.uploadComplete.querySelector(".uploaded-url-list");
        if (urlListDiv) urlListDiv.remove();

        const div = document.createElement("div");
        div.className = "uploaded-url-list";
        div.style.marginTop = "24px";
        div.style.padding = "8px 0";
        div.style.borderTop = "1px solid #e2e8f0";

        const origin = window.location.origin + "/";

        this.files.forEach((fileObj, idx) => {
            if (fileObj.url) {
                const fullUrl = fileObj.url.startsWith("http")
                    ? fileObj.url
                    : origin + fileObj.url.replace(/^\/+/, "");
                const escapedUrl = this.escapeHtml(fullUrl);

                div.innerHTML += `
                    <div style="margin-bottom:24px; background:#f7fafc; border:1px solid #e2e8f0; border-radius:12px; padding:20px; text-align:left;">
                        <div style="font-weight:700; margin-bottom:16px; color:#1a202c; font-size:1.05rem;">Image ${idx + 1}</div>
                        
                        <!-- URL Row -->
                        <div style="margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                            <span style="font-weight:600; font-size:0.85rem; color:#4a5568; min-width:85px; text-transform:uppercase; letter-spacing:0.5px;">URL</span>
                            <div style="flex:1; display:flex; background:#ffffff; border:1px solid #cbd5e0; border-radius:6px; overflow:hidden; box-shadow:inset 0 1px 2px rgba(0,0,0,0.02);">
                                <input type="text" readonly value="${escapedUrl}" style="flex:1; border:none; padding:8px 12px; font-size:0.85rem; color:#2d3748; background:transparent; outline:none; font-family:Consolas, Monaco, monospace;">
                                <button class="copy-text-btn" data-copy="${escapedUrl}">Copy</button>
                            </div>
                        </div>

                        <!-- BBCode Row -->
                        <div style="margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                            <span style="font-weight:600; font-size:0.85rem; color:#4a5568; min-width:85px; text-transform:uppercase; letter-spacing:0.5px;">BBCode</span>
                            <div style="flex:1; display:flex; background:#ffffff; border:1px solid #cbd5e0; border-radius:6px; overflow:hidden; box-shadow:inset 0 1px 2px rgba(0,0,0,0.02);">
                                <input type="text" readonly value="[img]${escapedUrl}[/img]" style="flex:1; border:none; padding:8px 12px; font-size:0.85rem; color:#2d3748; background:transparent; outline:none; font-family:Consolas, Monaco, monospace;">
                                <button class="copy-text-btn" data-copy="[img]${escapedUrl}[/img]">Copy</button>
                            </div>
                        </div>

                        <!-- HTML Row -->
                        <div style="margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                            <span style="font-weight:600; font-size:0.85rem; color:#4a5568; min-width:85px; text-transform:uppercase; letter-spacing:0.5px;">HTML</span>
                            <div style="flex:1; display:flex; background:#ffffff; border:1px solid #cbd5e0; border-radius:6px; overflow:hidden; box-shadow:inset 0 1px 2px rgba(0,0,0,0.02);">
                                <input type="text" readonly value="&lt;img src='${escapedUrl}' alt='image'&gt;" style="flex:1; border:none; padding:8px 12px; font-size:0.85rem; color:#2d3748; background:transparent; outline:none; font-family:Consolas, Monaco, monospace;">
                                <button class="copy-text-btn" data-copy="&lt;img src='${escapedUrl}' alt='image'&gt;">Copy</button>
                            </div>
                        </div>

                        <!-- Markdown Row -->
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-weight:600; font-size:0.85rem; color:#4a5568; min-width:85px; text-transform:uppercase; letter-spacing:0.5px;">Markdown</span>
                            <div style="flex:1; display:flex; background:#ffffff; border:1px solid #cbd5e0; border-radius:6px; overflow:hidden; box-shadow:inset 0 1px 2px rgba(0,0,0,0.02);">
                                <input type="text" readonly value="![](${escapedUrl})" style="flex:1; border:none; padding:8px 12px; font-size:0.85rem; color:#2d3748; background:transparent; outline:none; font-family:Consolas, Monaco, monospace;">
                                <button class="copy-text-btn" data-copy="![](${escapedUrl})">Copy</button>
                            </div>
                        </div>
                    </div>
                `;
            }
        });

        this.uploadComplete.appendChild(div);

        // Attach copy event listeners
        setTimeout(() => {
            document.querySelectorAll(".copy-text-btn").forEach((btn) => {
                btn.addEventListener("click", function () {
                    const val = this.getAttribute("data-copy");
                    navigator.clipboard.writeText(val).then(() => {
                        const originalBg = btn.style.background;
                        const originalColor = btn.style.color;
                        const originalBorder = btn.style.borderColor;
                        
                        btn.style.background = "#d4edda";
                        btn.style.color = "#155724";
                        btn.style.borderColor = "#c3e6cb";
                        btn.textContent = "Copied!";
                        
                        setTimeout(() => {
                            btn.textContent = "Copy";
                            btn.style.background = originalBg;
                            btn.style.color = originalColor;
                            btn.style.borderColor = originalBorder;
                        }, 1200);
                    });
                });
            });
        }, 100);
    }

    removeFile(fileId) {
        const fileObj = this.files.find((f) => f.id == fileId);

        // Send delete request with CSRF token
        if (fileObj && fileObj.url) {
            fetch("delete.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-Token": this.csrfToken,
                },
                body: JSON.stringify({ url: fileObj.url }),
            });
        }

        this.files = this.files.filter((f) => f.id != fileId);
        const fileElement = document.querySelector(`[data-file-id="${fileId}"]`);

        if (fileElement) {
            fileElement.style.animation = "slideOut 0.3s ease forwards";
            setTimeout(() => {
                fileElement.remove();

                if (this.files.length === 0) {
                    this.filesPreview.classList.remove("show");
                    this.addMoreBtn.style.display = "none";
                    this.uploadBox.classList.remove("uploading");

                    if (this.uploadComplete.style.display === "block") {
                        const urlListDiv = this.uploadComplete.querySelector(".uploaded-url-list");
                        if (urlListDiv) urlListDiv.remove();
                        const completeSubtitle = this.uploadComplete.querySelector(".complete-subtitle");
                        if (completeSubtitle) completeSubtitle.textContent = "No files left.";

                        this.uploadComplete.style.display = "none";
                        this.uploadBox.style.display = "block";
                        this.uploadBox.classList.remove("uploading", "success");

                        const progressBar = document.querySelector(".progress-bar");
                        const progressText = document.querySelector(".progress-text");
                        if (progressBar) progressBar.style.strokeDashoffset = "157";
                        if (progressText) progressText.textContent = "0%";
                        this.fileInput.value = "";
                    }
                } else {
                    if (this.uploadComplete.style.display === "block") {
                        this.updateUploadedUrlList();
                        const completeSubtitle = this.uploadComplete.querySelector(".complete-subtitle");
                        if (completeSubtitle) {
                            completeSubtitle.textContent = `${this.files.length} file(s) uploaded successfully`;
                        }
                    }
                }
            }, 300);
        }
    }
}

// Initialize the component
let fileUpload;
document.addEventListener("DOMContentLoaded", () => {
    fileUpload = new FileUploadComponent();
});

// Export for potential module use
if (typeof module !== "undefined" && module.exports) {
    module.exports = FileUploadComponent;
}
