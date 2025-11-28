<?php
// Database configuration - Parse from DATABASE_URL or use fallback Replit settings
$database_url = getenv('DATABASE_URL');

if ($database_url && strpos($database_url, 'postgresql://') === 0) {
    // Parse PostgreSQL URL: postgresql://user:password@host:port/database
    $url_parts = parse_url($database_url);
    define('DB_HOST', $url_parts['host'] ?? 'localhost');
    define('DB_USER', $url_parts['user'] ?? 'postgres');
    define('DB_PASS', $url_parts['pass'] ?? '');
    define('DB_NAME', ltrim($url_parts['path'] ?? '/fpog_sms', '/'));
    define('DB_PORT', $url_parts['port'] ?? 5432);
} else {
    // Fallback to individual environment variables
    define('DB_HOST', getenv('PGHOST') ?: 'localhost');
    define('DB_USER', getenv('PGUSER') ?: 'postgres');
    define('DB_PASS', getenv('PGPASSWORD') ?: '');
    define('DB_NAME', getenv('PGDATABASE') ?: 'fpog_sms');
    define('DB_PORT', getenv('PGPORT') ?: 5432);
}

// Application configuration
define('SITE_NAME', 'Staff Management System');
define('SCHOOL_NAME', 'Federal Polytechnic of Oil and Gas');
define('BASE_URL', '/');

// Session configuration
define('SESSION_LIFETIME', 3600); // 1 hour

// Upload configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/csv/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['csv']);

// Timezone
date_default_timezone_set('Africa/Lagos');
?>
