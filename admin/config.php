<?php
/**
 * African Journal of Finance, Taxation, and Fiscal Policy (AJFTFP)
 * Admin Configuration & Database Layer (SQLite with fallback to JSON)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -----------------------------------------------------------------------------
// 1. Core Settings & Default Admin Credentials
// -----------------------------------------------------------------------------
define('ADMIN_USERNAME', 'admin');
// Default password: admin@ajftfp2026 (Editor can change credentials anytime)
define('ADMIN_PASSWORD_HASH', '$2y$12$e5rGJV8pf5D7yoOp09pC7eiYfgHmNCBX.rpaUsskeDXJ98nFETM8.'); 

define('AJFTFP_DATA_DIR', __DIR__ . '/data');
define('AJFTFP_UPLOADS_DIR', __DIR__ . '/uploads');
define('AJFTFP_DB_PATH', AJFTFP_DATA_DIR . '/ajftfp.sqlite');

// Ensure storage folders exist
if (!is_dir(AJFTFP_DATA_DIR)) {
    mkdir(AJFTFP_DATA_DIR, 0755, true);
}
if (!is_dir(AJFTFP_UPLOADS_DIR . '/manuscripts')) {
    mkdir(AJFTFP_UPLOADS_DIR . '/manuscripts', 0755, true);
}
if (!is_dir(AJFTFP_UPLOADS_DIR . '/published')) {
    mkdir(AJFTFP_UPLOADS_DIR . '/published', 0755, true);
}

// -----------------------------------------------------------------------------
// 2. Database Connection (SQLite via PDO)
// -----------------------------------------------------------------------------
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . AJFTFP_DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Submissions table (For author submitted manuscripts)
        $pdo->exec("CREATE TABLE IF NOT EXISTS submissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tracking_ref TEXT UNIQUE,
            author_title TEXT,
            author_name TEXT,
            author_surname TEXT,
            author_email TEXT,
            author_phone TEXT,
            affiliation TEXT,
            country TEXT,
            manuscript_title TEXT,
            running_title TEXT,
            category TEXT,
            abstract TEXT,
            keywords TEXT,
            jel_codes TEXT,
            file_manuscript TEXT,
            file_cover_sheet TEXT,
            file_declaration TEXT,
            status TEXT DEFAULT 'Under Review',
            reviewer_notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Published Articles table (For volumes & issues)
        $pdo->exec("CREATE TABLE IF NOT EXISTS published_articles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            doi TEXT UNIQUE,
            volume INTEGER DEFAULT 1,
            issue INTEGER DEFAULT 1,
            year INTEGER DEFAULT 2026,
            title TEXT,
            short_title TEXT,
            authors TEXT,
            affiliations TEXT,
            corresponding_email TEXT,
            abstract TEXT,
            keywords TEXT,
            category TEXT,
            file_docx TEXT,
            file_pdf TEXT,
            status TEXT DEFAULT 'Published',
            published_date TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }
    return $pdo;
}

// -----------------------------------------------------------------------------
// 3. Auth Helpers
// -----------------------------------------------------------------------------
function isAdminLoggedIn() {
    return !empty($_SESSION['ajftfp_admin_logged_in']);
}

function requireAdminAuth() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
