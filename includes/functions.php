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

// Extract text from PDF
function extractPDFText($file_path) {
    if (!file_exists($file_path)) {
        return null;
    }
    
    try {
        // Try using pdftotext command (Unix/Linux)
        if (function_exists('exec')) {
            $output = null;
            $return_var = null;
            @exec('which pdftotext 2>/dev/null', $output, $return_var);
            
            if ($return_var === 0) {
                $temp_file = tempnam(sys_get_temp_dir(), 'pdf_text_');
                @exec('pdftotext "' . escapeshellarg($file_path) . '" "' . escapeshellarg($temp_file) . '" 2>/dev/null', $output, $return_var);
                
                if ($return_var === 0 && file_exists($temp_file)) {
                    $text = file_get_contents($temp_file);
                    @unlink($temp_file);
                    return !empty($text) ? trim($text) : null;
                }
                @unlink($temp_file);
            }
        }
        
        // Fallback: Try reading raw PDF content for text
        $content = file_get_contents($file_path);
        if ($content) {
            // Simple extraction: find text between BT (Begin Text) and ET (End Text)
            $matches = [];
            preg_match_all('/BT(.*?)ET/s', $content, $matches);
            
            if (!empty($matches[1])) {
                $text = implode(' ', $matches[1]);
                // Remove PDF encoding noise
                $text = preg_replace('/[^\x20-\x7E\n\r\t]/u', ' ', $text);
                $text = preg_replace('/\s+/', ' ', $text);
                return !empty($text) ? trim($text) : null;
            }
        }
    } catch (Exception $e) {
        return null;
    }
    
    return null;
}

// Validate memo content for required criteria
function validateMemoContent($file_path, $file_type, $required_text) {
    if (empty($required_text) || !file_exists($file_path)) {
        return ['valid' => true, 'message' => ''];
    }
    
    $extracted_text = null;
    
    // Extract text based on file type
    if (in_array($file_type, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
        $extracted_text = extractWordDocumentText($file_path);
    } elseif ($file_type === 'application/pdf') {
        $extracted_text = extractPDFText($file_path);
    } elseif (in_array($file_type, ['image/jpeg', 'image/png', 'image/gif'])) {
        // For images, we cannot easily extract text without OCR
        return [
            'valid' => false, 
            'message' => 'Content validation not supported for images. Please use text-based documents or PDFs.'
        ];
    }
    
    // Check if required text is found
    if ($extracted_text === null) {
        return [
            'valid' => false,
            'message' => 'Could not extract text from file. Please check the file format.'
        ];
    }
    
    // Case-insensitive search for required text
    $required_text_lower = strtolower(trim($required_text));
    $extracted_text_lower = strtolower($extracted_text);
    
    if (strpos($extracted_text_lower, $required_text_lower) !== false) {
        return [
            'valid' => true,
            'message' => 'Required text found in document'
        ];
    } else {
        return [
            'valid' => false,
            'message' => "Required text '{$required_text}' not found in document"
        ];
    }
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
