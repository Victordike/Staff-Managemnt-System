<?php
// Database configuration for Local XAMPP MySQL
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'fpog_sms');
define('DB_PORT', '3306');

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
