<div align="center">
  <img src="readme-thambnail.png" alt="FileFlow Thumbnail" style="width: 100%; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">

  <h1>🚀 FileFlow - Secure File Sharing Platform</h1>
  
  <p>A modern, fast, and secure PHP-based web application for effortless file sharing and personalized folder management.</p>

  <p>
    <a href="#features">Features</a> •
    <a href="#tech-stack">Tech Stack</a> •
    <a href="#installation">Installation</a> •
    <a href="#developer">Developer</a>
  </p>
</div>

---

## 🌟 Overview
**FileFlow** is a comprehensive file-sharing platform that allows users to create custom, unique folder names (e.g., `fileflow.ashikone.com/my-folder/`) to instantly share files across the internet. It supports both anonymous usage via browser history and a complete multi-user authentication system with personalized dashboards and analytics.

---

## ✨ Features

### 🛡️ Authentication & User Management
* **Secure Login & Registration:** Fast and secure authentication system.
* **Password Recovery:** Token-based forgot password and reset functionality.
* **Personalized Dashboard:** A dynamic dashboard displaying total files, total storage used, and recent download counts.
* **Data Visualization:** Real-time interactive bar charts showing upload activity over the last 7 days.

### 📁 Core File Sharing
* **Custom Shareable Links:** Create folders with unique names that instantly generate shareable public links.
* **Smart History Tracking:** Automatically tracks and saves your created and visited folders so you never lose them.
* **QR Code Generation:** Generate instant QR codes for quick mobile access and sharing.
* **Drag-and-Drop Uploads:** Seamless file uploading experience with real-time progress bars.

### 📄 Supported File Types
FileFlow securely accepts the following file formats:
* **Documents:** `PDF`, `DOCX`, `DOC`, `PPTX`, `PPT`, `XLSX`, `XLS`
* **Media:** `MP3`, `JPG`, `JPEG`, `PNG`, `WEBP`
* **Archives:** `ZIP`

---

## 🛠️ Tech Stack
* **Frontend:** HTML5, CSS3 (Custom Vanilla CSS, Responsive Design), Vanilla JavaScript (ES6+).
* **Backend:** PHP 8+ (Core PHP, completely custom routing architecture).
* **Database:** MySQL (PDO for secure, prepared statements).
* **Security:** Built-in CSRF protection, secure file validation, XSS prevention, and strict `.htaccess` routing rules.

---

## 🚀 Installation & Setup

1. **Clone the Repository**
   ```bash
   git clone https://github.com/yourusername/fileflow.git
   cd fileflow
   ```

2. **Database Configuration**
   * Create a new MySQL database.
   * Import the provided `database.sql` file into your MySQL database to set up the schemas (Users, Folders, Files, Password Resets).
   * Update your database credentials in `config/database.php`.

3. **Environment Setup**
   * Update the `APP_URL` in `config/config.php` to match your local or live domain.
   * Ensure that your web server (Apache/Nginx) has `mod_rewrite` enabled for the `.htaccess` rules to route cleanly to `index.php`.
   * Ensure the `user_documents` folder has the proper read/write permissions for file uploads.

4. **Launch**
   * Start your local server (e.g., XAMPP, Laragon, or MAMP) and visit the configured domain.

---

## 👨‍💻 Developer

Developed with ❤️ by **[Ashikul Islam](https://wa.me/8801792250709)**.

Have questions, feedback, or need a custom web solution? 
👉 **[Click here to chat with me on WhatsApp!](https://wa.me/8801792250709)**

<br>
<div align="center">
  <sub>© 2026 FileFlow. All rights reserved.</sub>
</div>
