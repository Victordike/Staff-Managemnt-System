<?php
// Ensure session is started before any session operations
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Extract text from Word document (.docx)
function extractWordDocumentText($file_path) {
    if (!file_exists($file_path) || !extension_loaded('zip')) {
        return null;
    }
    
    try {
        $zip = new ZipArchive();
        if ($zip->open($file_path) === true) {
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            
            if ($xml === false) return null;
            
            // Remove XML tags and decode HTML entities
            $text = preg_replace('/<[^>]*>/', ' ', $xml);
            $text = html_entity_decode($text, ENT_XML1, 'UTF-8');
            $text = preg_replace('/\s+/', ' ', $text); // Normalize whitespace
            return trim($text);
        }
    } catch (Exception $e) {
        return null;
    }
    
    return null;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function isSuperAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'superadmin';
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /index.php');
        exit;
    }
}

function requireSuperAdmin() {
    requireLogin();
    if (!isSuperAdmin()) {
        header('Location: /index.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /index.php');
        exit;
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function setFlashMessage($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'type' => $_SESSION['flash_type'],
            'message' => $_SESSION['flash_message']
        ];
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function uploadCSV($file) {
    $uploadDir = __DIR__ . '/../uploads/csv/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error');
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($extension !== 'csv') {
        throw new Exception('Only CSV files are allowed');
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('File size exceeds limit');
    }
    
    $fileName = 'pre_users_' . date('Y-m-d_His') . '.csv';
    $destination = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $fileName;
    }
    
    throw new Exception('Failed to upload file');
}

function parseCSV($filePath) {
    $data = [];
    if (($handle = fopen($filePath, 'r')) !== false) {
        $headers = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== false) {
            $data[] = array_combine($headers, $row);
        }
        fclose($handle);
    }
    return $data;
}

function getInitials($firstname, $lastname) {
    $first = !empty($firstname) ? strtoupper($firstname[0]) : '';
    $last = !empty($lastname) ? strtoupper($lastname[0]) : '';
    return $first . $last;
}

function formatDate($date) {
    return date('F j, Y', strtotime($date));
}
?>
