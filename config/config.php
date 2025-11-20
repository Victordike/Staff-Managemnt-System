<?php
// Database configuration
define('DB_HOST', getenv('PGHOST'));
define('DB_USER', getenv('PGUSER'));
define('DB_PASS', getenv('PGPASSWORD'));
define('DB_NAME', getenv('PGDATABASE'));
define('DB_PORT', getenv('PGPORT'));

// Application configuration
define('SITE_NAME', 'Staff Management System');
define('SCHOOL_NAME', 'Federal Polytechnic of Oil and Gas');
define('BASE_URL', '/');

// Session configuration
define('SESSION_LIFETIME', 3600); // 1 hour
ini_set('session.cookie_lifetime', SESSION_LIFETIME);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

// Upload configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/csv/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['csv']);

// Timezone
date_default_timezone_set('Africa/Lagos');
?>
