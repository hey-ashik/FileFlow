<div align="center">
  <img src="readme-thambnail.png" alt="FileFlow Banner" style="width: 100%; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">

  <br />

  <h1>🚀 FileFlow</h1>
  <p><strong>A High-Performance, Secure File Sharing &amp; Social Ecosystem</strong></p>

  <p>
    <a href="#-overview">Overview</a> •
    <a href="#-key-highlights">Highlights</a> •
    <a href="#-system-architecture">Architecture</a> •
    <a href="#-core-features">Features</a> •
    <a href="#-tech-stack">Tech Stack</a> •
    <a href="#-installation--setup">Setup</a> •
    <a href="#-developer--credits">Developer</a>
  </p>

  <p>
    <img src="https://img.shields.io/badge/PHP-8.1+-777bb4?style=for-the-badge&logo=php&logoColor=white" alt="PHP Version" />
    <img src="https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL" />
    <img src="https://img.shields.io/badge/Redis-6.2+-DC382D?style=for-the-badge&logo=redis&logoColor=white" alt="Redis" />
    <img src="https://img.shields.io/badge/JavaScript-ES6+-f7df1e?style=for-the-badge&logo=javascript&logoColor=black" alt="JS Version" />
    <img src="https://img.shields.io/badge/Security-CSRF%20%26%20WAF-success?style=for-the-badge" alt="Security" />
  </p>
</div>

---

## 🌟 Overview

**FileFlow** is a modern, high-speed file-sharing platform designed for both simplicity and power. It allows users to create custom-named "Flow Folders" to share files instantly via professional, unique URLs. 

Unlike traditional file-sharing websites, FileFlow is also a **micro-social ecosystem**. Registered users can build professional profiles (Digital Profile Cards), connect with other members, chat in real-time with typing indicators, and share their updates and files via a global "Thoughts" feed.

Built with a focus on **User Experience (UX)** and **Lightning Speed**, FileFlow bridges the gap between traditional PHP applications and modern SPAs (Single Page Applications) through a custom-built, client-side caching navigation engine.

---

## ⚡ Key Highlights

### 🚄 Next.js Style SPA Engine
FileFlow features a custom-built, client-side Single Page Application (SPA) routing engine that eliminates full browser reloads:
* **Zero-Reload Page Swaps:** Clicked internal links dynamically load target content and update history states.
* **Hover-Prefetching:** Pages are pre-fetched in the background as you hover over links, making transitions feel instantaneous.
* **Global Progress Bar:** A sleek green loader provides immediate visual feedback for all navigation actions.
* **Continuous Uploads:** Since navigation is handled via AJAX/DOM-replacement, files continue uploading seamlessly in the background while users explore the site.

### 🛡️ Enterprise-Grade Security
Security is baked into every layer of the application:
* **Upload Firewall:** Strict validation of MIME-types using PHP `finfo` against a verified whitelist, combined with defensive regex to block double extensions and dangerous script execution (e.g. `.php`, `.exe`, `.bat`).
* **Protected Directories:** Multi-tier access prevention including `.htaccess` directives prohibiting direct indexing or file execution inside upload folders, supplemented by fallback empty `index.html` indexes.
* **State Protection:** Native CSRF tokens validated on all state-changing API endpoints (`POST`, `PUT`, `DELETE`).
* **Credential Hashing:** Industry-standard password hashing using BCrypt with a cost factor of 12.

### ⚙️ High-Performance Caching & raw TCP Fallback
To ensure low Time to First Byte (TTFB), FileFlow leverages Redis:
* **Granular Caching:** Caches folder records, directory trees, statistics, and user profile information.
* **Smart Invalidation:** Cache entries are targeted and flushed only when related mutations occur (e.g., adding files, modifying profiles).
* **RESP Protocol Socket Fallback:** If the `php-redis` extension is missing on the server, FileFlow automatically switches to `SocketRedis`—a custom wrapper that communicates directly with Redis via raw TCP sockets using standard Redis Serialization Protocol (RESP).

---

## 🏗️ System Architecture

### 1. Request Routing Flow
FileFlow uses Apache's `mod_rewrite` to channel clean URLs through a single-entry-point router script. Static assets are bypassed automatically to ensure high-performance browser retrieval.

```mermaid
graph TD
    A[Client Request] --> B{Apache Rewrite Engine}
    B -->|Static Asset /assets/* [L]| C[Serve Static Asset]
    B -->|All Other Requests [Rewrite]| D[index.php Router]
    D --> E{Router Switch}
    E -->|Auth/Dashboard/Static Pages| F[Load PHP Templates in /pages]
    E -->|API Endpoints /api/*| G[Process API in /api]
    E -->|User Profile Card /u/*| H[Serve pages/profile-card.php]
    E -->|Single Thought /p/*| I[Serve pages/single-thought.php]
    E -->|Default Catch-All Slug| J{Validate Slug in DB}
    J -->|Found| K[Serve pages/folder.php]
    J -->|Not Found| L[Serve pages/error.php]
```

### 2. Client-Side SPA & Prefetch Lifecycle
The Javascript engine (`app.js`) intercepts default link clicking events, loading pages asynchronously and pre-fetching links on hover to provide a premium SPA-like experience.

```mermaid
sequenceDiagram
    autonumber
    actor User
    participant Browser
    participant SPAEngine as SPA Engine (app.js)
    participant Cache as SPA Cache (Map)
    participant Server as FileFlow Server

    User->>Browser: Hovers over link
    Browser->>SPAEngine: Mouseover Event
    SPAEngine->>Cache: Check if cached
    alt Not Cached
        SPAEngine->>Server: HTTP GET Page Content
        Server-->>SPAEngine: HTML Response
        SPAEngine->>Cache: Save page in Map
    end
    User->>Browser: Clicks link
    Browser->>SPAEngine: Click Event (Intercepted)
    SPAEngine->>Cache: Fetch HTML from Map
    SPAEngine->>Browser: Update DOM (.main-content & document.title)
    SPAEngine->>Browser: Re-initialize Page Scripts
    SPAEngine->>Browser: Update URL history (pushState)
```

### 3. Repository Directory Structure
```
FileFlow/
├── api/                       # API endpoints (Auth, folders, uploads, messages, thoughts)
│   ├── admin-*.php            # Administrative API controllers
│   ├── auth-*.php             # Authentication controllers
│   └── *.php                  # Feature controllers (upload, download, thoughts, connection)
├── assets/                    # Static UI resources
│   ├── css/
│   │   ├── base.css           # Core browser resets and CSS variables
│   │   └── style.css          # Main UI components, grids, and themes
│   ├── js/
│   │   └── app.js             # SPA engine, upload manager, UI utilities
│   └── img/                   # Graphics assets
├── config/                    # Configuration settings
│   ├── config.php             # General settings (sizes, extensions, constraints)
│   └── database.php           # DB credentials (MySQL and Redis)
├── includes/                  # Core helpers, helpers, components
│   ├── auth.php               # Login/Registration validation libraries
│   ├── footer.php             # Shared layout footer
│   ├── functions.php          # Database helper utilities and security validations
│   ├── header.php             # HTML head, layouts, and SPA progress bar
│   ├── redis.php              # Redis instance wrapper & raw TCP socket implementation
│   └── thought_component.php  # UI component helper for thoughts feed rendering
├── pages/                     # Frontend views
│   ├── admin.php              # Command Center dashboard for admin
│   ├── dashboard.php          # Logged-in user stats and history dashboard
│   ├── error.php              # Error layout handler
│   ├── folder.php             # Flow Folder view (file download / upload area)
│   ├── home.php               # Land page layout (Folder creation input)
│   ├── messages.php           # User connection chats and inbox page
│   ├── profile-card.php       # Public User Profile page (/u/[slug])
│   ├── profile.php            # Profile Card editing page
│   ├── thoughts.php           # Public social Feed and Microblog page
│   └── *.php                  # Auth forms templates (login, register, reset-password)
├── user_documents/            # Physical folder uploads (auto-generated, protected)
├── .htaccess                  # Apache server rewrites and upload optimizations
├── database.sql               # Default raw database database structure
├── index.php                  # Application entry point and router switch
└── setup_db.php               # Dynamic DB upgrade script and Admin setup
```

---

## ✨ Core Features & Technical Deep-Dive

### 📂 Flow Folders & File Sharing System
FileFlow implements an isolated, customizable directory sharing system known as **Flow Folders**. 
* **Dynamic Slug Routing:** Clean URLs are mapped directly to user-created folders (e.g., `fileflow.ashikone.com/my-folder`). The Apache engine translates all catch-all URL parameters through `index.php`, querying the folders table for matching slugs.
* **Salted Password Protection:** Folders can be locked using custom passwords. Passwords are saved using salted `bcrypt` hashes (`PASSWORD_BCRYPT`). Upon accessing a locked folder slug, a temporary session-based clearance token is assigned to prevent repeated password queries.
* **Automatic Expiration Cleanup:** Folders are associated with a Time-To-Live (TTL) attribute (`expired_at` timestamp). An asynchronous background cleanup scheduler validates expired directories, deletes physical files from the server, and purges metadata records from the database.
* **Resilient Chunked Upload Pipeline:** 
  - To bypass small PHP configuration limits (e.g., `upload_max_filesize`), files are sliced into **2MB chunks** on the client side using the HTML5 File API.
  - Chunks are uploaded sequentially via AJAX. If a chunk fails, the queue manager retries the specific chunk instead of re-uploading the entire file.
  - The server dynamically appends incoming chunks to a temporary file (`.part` suffix) and renames the complete file once the final chunk checksum matches.
  - Managed by a floating, persistent control panel displaying upload speed, progress bar, active queue, and time-remaining (ETA) calculations. Users can browse the SPA without breaking the uploads.

### ⚡ Redis Caching & Socket RESP Fallback
To ensure low database overhead and lightning-fast page response times, the application implements a dedicated caching layer.

* **Cache Keys Layout:**
  - `folder:slug:[slug_name]` -> Caches general folder properties and configuration flags.
  - `folder:files:[folder_id]` -> Caches directory file tree contents.
  - `profile:slug:[user_slug]` -> Stores professional digital profile card contents.
  - `stats:global` -> Caches site metrics for the administrator dashboard.
* **RESP TCP Socket Fallback (`SocketRedis`):** 
  - When the native PHP Redis extension is unavailable, `includes/redis.php` opens a raw TCP connection (`fsockopen`) to the Redis daemon.
  - It implements a lightweight parser to handle the **Redis Serialization Protocol (RESP)**. 
  - It parses responses using RESP prefixes:
    - `+` (Simple Strings)
    - `-` (Errors)
    - `:` (Integers)
    - `$` (Bulk Strings)
    - `*` (Arrays)
  This ensures caching remains active across cheap or restricted hosting nodes.

### 🔒 Thoughts Microblog & Post Privacy Matrix
The platform features a social microblogging wall ("Thoughts") where users post updates, attachments, or links with specific privacy guards:

* **Post Privacy Levels:**
  - **`public`**: Visible to everyone, indexed on global feeds, and queryable by anyone.
  - **`connections`**: Restricts post rendering strictly to users who have an accepted connection status inside the `connections` table.
  - **`private`**: Only readable by the author. Hidden from search queries and global/user profile feeds.
* **Hierarchical Nesting Comments:** Comments are modeled with a self-referencing relationship (`parent_id`). The thoughts engine renders infinite nesting lists with mobile-friendly indentation offsets and clean, async CRUD controls.
* **Share Event Trackers:** Shares are verified on click and incremented in the DB, fetching share-specific links to prevent data duplication.

### 👤 Custom Digital Profile Cards
Accessible via `/u/[username]`, these responsive card systems showcase professional branding.
* **Theme Styling & Colors:** Custom branding tokens including profile banner backgrounds, avatar border colors, and resume download button colors are customizable by users and saved directly within their user profile metadata.
* **Integrated QRCode Engine:** Uses client-side `qrcode.js` to dynamically encode the digital profile link into a high-density QR code, facilitating physical scans for virtual networking.
* **Dynamic Connection Triggers:** Direct integration with the friendship and chat systems. Visitors can request connections, accept incoming invites, or initiate direct message chats directly from the profile card interface.

### 👑 Admin Command Center
* Comprehensive overview of system-wide metrics (total folders, user files, storage size, and active networks).
* User management panel to change storage limit allocations, reset credentials, or lock accounts.
* Bulk cleaning operations (bulk purging folders, registered users, and profile cards).
* Global maintenance mode toggle with custom page redirection.

---

## 💻 Tech Stack

* **Backend:** Native PHP 8.1+ (Object-Oriented Database Models, Clean Switch Router)
* **Frontend:** Vanilla JS (ES6+), Vanilla CSS (Custom Variable Tokens Design System), HTML5
* **Caching Engine:** Redis (Standalone daemon or Raw Socket RESP Client)
* **Database:** MySQL 8.0+ / MariaDB (PDO Prepared Statements, optimized foreign-key cascades)
* **Web Server Configuration:** Apache 2.4 (mod_rewrite, mod_deflate, mod_expires, mod_headers)

---

## 🚀 Installation & Setup

Follow these steps to set up FileFlow in your local development environment or hosting server.

### Prerequisites
* PHP 8.1 or higher
* MySQL 5.7+ or MariaDB 10.3+
* Redis Server (Optional)
* Apache Web Server with `mod_rewrite` enabled

### 1. Clone the Repository
Clone the codebase to your web server document root (e.g., `htdocs` or `/var/www/html`):
```bash
git clone https://github.com/hey-ashik/FileFlow.git
cd FileFlow
```

### 2. Configure Virtual Host (Important for clean URLs)
Make sure your Apache Virtual Host points to the root of the project folder and has `AllowOverride All` configured to let the `.htaccess` override directory rules.

Example Apache config:
```apache
<VirtualHost *:80>
    ServerName fileflow.local
    DocumentRoot "C:/path/to/FileFlow"
    
    <Directory "C:/path/to/FileFlow">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 3. Setup the Database
Create a new MySQL database named `ashikone_fileflowdb`.
```sql
CREATE DATABASE `ashikone_fileflowdb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Configuration Setup
Update your configuration settings inside `config/database.php` and `config/config.php`.

* Open `config/database.php` and fill in your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ashikone_fileflowdb');
define('DB_USER', 'your_mysql_username');
define('DB_PASS', 'your_mysql_password');

// If using Redis, configure below. Otherwise, set REDIS_ENABLED to false.
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_PASS', '');
define('REDIS_ENABLED', true);
```

* Open `config/config.php` and set your application URL:
```php
define('APP_URL', 'http://fileflow.local'); // or your server IP/domain
```

### 5. Run Database Setup Tool
Access the database setup tool via browser or command line. This creates the schema structure, configures indices, and sets up your default Admin user.

* **Via Browser:** Navigate to `http://fileflow.local/setup_db.php`
* **Via CLI:** Run `php setup_db.php` in the terminal.

#### Default Admin Credentials (Auto-created during setup)
* **Email:** `ashikulislam2070@gmail.com`
* **Password:** `Ashik@21032001`
* **Role:** Administrator (Permissions to view admin panel and adjust platform settings)

### 6. Verify Permissions
Ensure the upload directory is writable:
* Create the `user_documents` directory in the root if not present.
* On Linux/macOS, run:
  ```bash
  chmod -R 775 user_documents
  chown -R www-data:www-data user_documents
  ```

---

## 👨‍💻 Developer & Credits

Designed and developed with passion and precision by **Ashikul Islam**.

| Channel | Contact / Link |
| :--- | :--- |
| 💬 **WhatsApp** | [+8801792250709](https://wa.me/8801792250709) |
| 🌐 **Portfolio** | [ashikone.com](https://ashikone.com) |
| 🐙 **GitHub Profile** | [@hey-ashik](https://github.com/hey-ashik) |

---

<div align="center">
  <sub>&copy; 2026 FileFlow Platform. Engineered for speed, designed for security.</sub>
</div>
