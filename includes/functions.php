<?php
// Ensure session is started before any session operations
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Common session variables
$userRole = $_SESSION['role'] ?? '';
$adminId = $_SESSION['admin_id'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

// Add a notification for leave application
function addLeaveNotification($admin_id, $leave_id, $type, $message) {
    try {
        $db = Database::getInstance();
        $db->query(
            "INSERT INTO leave_notifications (admin_id, leave_id, notification_type, message) VALUES (?, ?, ?, ?)",
            [$admin_id, $leave_id, $type, $message]
        );
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Checks for and completes ended leave applications for a specific admin.
 * This can be called upon login or dashboard access to ensure the status is up to date.
 */
function checkAndCompleteLeave($adminId) {
    $db = Database::getInstance();
    $today = date('Y-m-d');
    
    $endedLeaves = $db->fetchAll("
        SELECT la.*, lt.name as leave_type 
        FROM leave_applications la
        JOIN leave_types lt ON la.leave_type_id = lt.id
        WHERE la.admin_id = ? AND la.status = 'approved' AND la.end_date < ?
    ", [$adminId, $today]);

    foreach ($endedLeaves as $leave) {
        $db->query("UPDATE leave_applications SET status = 'completed' WHERE id = ?", [$leave['id']]);
        
        $message = "Your {$leave['leave_type']} that started on {$leave['start_date']} and ended on {$leave['end_date']} is now marked as completed.";
        addLeaveNotification($leave['admin_id'], $leave['id'], 'completed', $message);
    }
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
    
    // For images, skip validation (cannot extract text without OCR)
    // Images will be allowed to pass through without validation
    if (in_array($file_type, ['image/jpeg', 'image/png', 'image/gif'])) {
        return [
            'valid' => true, 
            'message' => 'Image uploaded (content validation skipped - not applicable for images)'
        ];
    }
    
    // Extract text based on file type
    if (in_array($file_type, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) {
        $extracted_text = extractWordDocumentText($file_path);
    } elseif ($file_type === 'application/pdf') {
        $extracted_text = extractPDFText($file_path);
    }
    
    // Check if required text is found
    if ($extracted_text === null) {
        return [
            'valid' => false,
            'message' => 'Could not extract text from file. Please check the file format and that it contains text.'
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
    if (!isLoggedIn()) return false;
    $role = $_SESSION['role'] ?? '';
    return $role === 'admin' || $role === 'superadmin';
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

function setFlashMessage($message, $type = 'success') {
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

/**
 * Get all available faculties and their departments
 */
function getFacultiesAndDepartments() {
    return [
        'School of Applied Sciences' => ['Computer Science', 'Library and Information Science', 'Science Laboratory Technology', 'Statistics'],
        'School of Business Studies' => ['Accountancy', 'Business Administration and Management', 'Maritime Transport and Business Studies', 'Petroleum Marketing and Business Studies', 'Public Administration'],
        'School of Engineering Technology' => ['Chemical Engineering Technology', 'Electrical Electronics Engineering Technology', 'Industrial Safety and Environmental Engineering Technology', 'Mechanical Engineering Technology', 'Welding and Fabrication Technology', 'Mineral and Petroleum Resource Engineering Technology'],
        'Administrative and Support Services' => ['Rectorate', 'Registry', 'Bursary', 'Internal Audit', 'Physical Planning', 'Works and Services', 'Security Unit', 'Medical Center', 'Library (Admin)', 'Student Affairs', 'Academic Planning', 'Information and Communication Technology (ICT)']
    ];
}

/**
 * Get the Faculty/School for a given department
 */
function getFacultyByDepartment($department) {
    $faculties = getFacultiesAndDepartments();

    foreach ($faculties as $faculty => $departments) {
        if (in_array($department, $departments)) {
            return $faculty;
        }
    }
    return null;
}

/**
 * Get all administrators (all staff)
 */
function getAllStaff($db) {
    return $db->fetchAll("SELECT id FROM admin_users WHERE is_active = TRUE");
}

/**
 * Get all Deans
 */
function getAllDeans($db) {
    $sql = "SELECT au.id FROM admin_users au 
            JOIN admin_roles ar ON au.id = ar.admin_id 
            WHERE (ar.role_name = 'Dean' OR ar.role_name = 'Academic Dean') AND ar.removed_at IS NULL AND au.is_active = TRUE";
    return $db->fetchAll($sql);
}

/**
 * Get all HODs
 */
function getAllHODs($db) {
    $sql = "SELECT au.id FROM admin_users au 
            JOIN admin_roles ar ON au.id = ar.admin_id 
            WHERE ar.role_name = 'HOD' AND ar.removed_at IS NULL AND au.is_active = TRUE";
    return $db->fetchAll($sql);
}

/**
 * Get all staff in a specific faculty
 */
function getStaffByFaculty($faculty, $db) {
    $faculties = getFacultiesAndDepartments();

    if (!isset($faculties[$faculty])) return [];
    
    $departments = $faculties[$faculty];
    $placeholders = implode(',', array_fill(0, count($departments), '?'));
    $sql = "SELECT id FROM admin_users WHERE department IN ($placeholders) AND is_active = TRUE";
    return $db->fetchAll($sql, $departments);
}

/**
 * Get all staff in a specific department
 */
function getStaffByDepartment($department, $db) {
    $sql = "SELECT id FROM admin_users WHERE department = ? AND is_active = TRUE";
    return $db->fetchAll($sql, [$department]);
}

/**
 * Find HOD of a department
 */
function getHOD($department, $db) {
    $sql = "SELECT au.id FROM admin_users au 
            JOIN admin_roles ar ON au.id = ar.admin_id 
            WHERE ar.role_name = 'HOD' AND ar.department = ? AND ar.removed_at IS NULL AND au.is_active = TRUE 
            LIMIT 1";
    $result = $db->fetchOne($sql, [$department]);
    return $result ? $result['id'] : null;
}

/**
 * Find Dean of a faculty
 */
function getDean($faculty, $db) {
    $sql = "SELECT au.id FROM admin_users au 
            JOIN admin_roles ar ON au.id = ar.admin_id 
            WHERE (ar.role_name = 'Dean' OR ar.role_name = 'Academic Dean') AND ar.faculty = ? AND ar.removed_at IS NULL AND au.is_active = TRUE 
            LIMIT 1";
    $result = $db->fetchOne($sql, [$faculty]);
    return $result ? $result['id'] : null;
}

/**
 * Find Registrar
 */
function getRegistrar($db) {
    $sql = "SELECT au.id FROM admin_users au 
            JOIN admin_roles ar ON au.id = ar.admin_id 
            WHERE ar.role_name = 'Registrar' AND ar.removed_at IS NULL AND au.is_active = TRUE 
            LIMIT 1";
    $result = $db->fetchOne($sql);
    return $result ? $result['id'] : null;
}

/**
 * Find Establishment Officer (Director level)
 */
function getEstablishment($db) {
    $sql = "SELECT au.id FROM admin_users au 
            JOIN admin_roles ar ON au.id = ar.admin_id 
            WHERE ar.role_name = 'Establishment' AND ar.removed_at IS NULL AND au.is_active = TRUE 
            LIMIT 1";
    $result = $db->fetchOne($sql);
    return $result ? $result['id'] : null;
}

/**
 * Find Deputy Rector
 */
function getDeputyRector($db) {
    $sql = "SELECT au.id FROM admin_users au 
            JOIN admin_roles ar ON au.id = ar.admin_id 
            WHERE ar.role_name = 'Deputy Rector' AND ar.removed_at IS NULL AND au.is_active = TRUE 
            LIMIT 1";
    $result = $db->fetchOne($sql);
    return $result ? $result['id'] : null;
}

/**
 * Find Rector
 */
function getRector($db) {
    $sql = "SELECT au.id FROM admin_users au 
            JOIN admin_roles ar ON au.id = ar.admin_id 
            WHERE ar.role_name = 'Rector' AND ar.removed_at IS NULL AND au.is_active = TRUE 
            LIMIT 1";
    $result = $db->fetchOne($sql);
    return $result ? $result['id'] : null;
}

/**
 * Find any specific role
 */
function getAdminByRole($role_name, $db) {
    $sql = "SELECT au.id FROM admin_users au 
            JOIN admin_roles ar ON au.id = ar.admin_id 
            WHERE ar.role_name = ? AND ar.removed_at IS NULL AND au.is_active = TRUE 
            LIMIT 1";
    $result = $db->fetchOne($sql, [$role_name]);
    return $result ? $result['id'] : null;
}

/**
 * Get all roles for a user
 */
function getUserRoles($admin_id, $db) {
    $sql = "SELECT role_name, department, faculty FROM admin_roles WHERE admin_id = ? AND removed_at IS NULL";
    return $db->fetchAll($sql, [$admin_id]);
}

/**
 * Check if user has a specific role
 */
function hasRole($admin_id, $role_name, $db) {
    $roles = getUserRoles($admin_id, $db);
    foreach ($roles as $role) {
        if ($role['role_name'] === $role_name) return true;
    }
    return false;
}

/**
 * Calculate memo routing path and first recipient
 * @return array [first_recipient_id, current_stage, is_approved]
 */
function calculateMemoRouting($sender_id, $target_id, $db) {
    // Get sender details
    $sender = $db->fetchOne("SELECT department, position FROM admin_users WHERE id = ?", [$sender_id]);
    $target = $db->fetchOne("SELECT department, position FROM admin_users WHERE id = ?", [$target_id]);
    
    if (!$sender || !$target) {
        return [$target_id, 'completed', 1];
    }

    $sender_dept = $sender['department'];
    $target_dept = $target['department'];
    $sender_faculty = getFacultyByDepartment($sender_dept);
    $target_faculty = getFacultyByDepartment($target_dept);

    // If target is HOD of same department, send direct
    $hod_id = getHOD($sender_dept, $db);
    if ($target_id == $hod_id) {
        return [$target_id, 'completed', 1];
    }

    // Otherwise, start routing
    // Stage 1: HOD
    if ($hod_id && $sender_id != $hod_id) {
        return [$hod_id, 'hod_approval', 0];
    }

    // Stage 2: Dean (if sender is HOD or no HOD found)
    $dean_id = getDean($sender_faculty, $db);
    if ($dean_id && $sender_id != $dean_id) {
        // If target is Dean, complete it
        if ($target_id == $dean_id) return [$target_id, 'completed', 1];
        return [$dean_id, 'dean_approval', 0];
    }

    // Stage 3: Establishment / Directors
    $establishment_id = getEstablishment($db);
    if ($establishment_id && $sender_id != $establishment_id) {
        if ($target_id == $establishment_id) return [$target_id, 'completed', 1];
        return [$establishment_id, 'establishment_approval', 0];
    }

    // Stage 4: Deputy Rector
    $deputy_rector_id = getDeputyRector($db);
    if ($deputy_rector_id && $sender_id != $deputy_rector_id) {
        if ($target_id == $deputy_rector_id) return [$target_id, 'completed', 1];
        return [$deputy_rector_id, 'deputy_rector_approval', 0];
    }

    // Stage 5: Registrar (only if target is outside faculty or management level)
    if ($sender_faculty !== $target_faculty || hasRole($target_id, 'Rector', $db) || hasRole($target_id, 'Registrar', $db) || hasRole($target_id, 'Deputy Rector', $db)) {
        $registrar_id = getRegistrar($db);
        if ($registrar_id && $sender_id != $registrar_id) {
            if ($target_id == $registrar_id) return [$target_id, 'completed', 1];
            return [$registrar_id, 'registrar_approval', 0];
        }
    }

    // Stage 6: Target
    return [$target_id, 'completed', 1];
}

/**
 * Forward memo to next stage
 * @return array [success, message, next_recipient_id, next_stage]
 */
function forwardMemo($memo_id, $db) {
    $memo = $db->fetchOne("SELECT * FROM memos WHERE id = ?", [$memo_id]);
    if (!$memo) return ['success' => false, 'message' => 'Memo not found'];
    
    $sender_id = $memo['sender_id'];
    $final_target_id = $memo['final_recipient_id'];
    $current_stage = $memo['current_stage'];
    
    $sender = $db->fetchOne("SELECT department FROM admin_users WHERE id = ?", [$sender_id]);
    $target = $db->fetchOne("SELECT department FROM admin_users WHERE id = ?", [$final_target_id]);
    $sender_dept = $sender['department'];
    $target_dept = $target['department'];
    $sender_faculty = getFacultyByDepartment($sender_dept);
    $target_faculty = getFacultyByDepartment($target_dept);
    
    $next_recipient_id = null;
    $next_stage = 'completed';
    
    if ($current_stage === 'hod_approval') {
        // From HOD to Dean
        $next_recipient_id = getDean($sender_faculty, $db);
        $next_stage = 'dean_approval';
        
        // If no Dean, check if we need Establishment
        if (!$next_recipient_id) {
            $next_recipient_id = getEstablishment($db);
            $next_stage = 'establishment_approval';
        }
    } elseif ($current_stage === 'dean_approval') {
        // From Dean to Establishment
        $next_recipient_id = getEstablishment($db);
        $next_stage = 'establishment_approval';
        
        // If no Establishment, check Registrar
        if (!$next_recipient_id) {
            if ($sender_faculty !== $target_faculty || hasRole($final_target_id, 'Rector', $db) || hasRole($final_target_id, 'Registrar', $db)) {
                $next_recipient_id = getRegistrar($db);
                $next_stage = 'registrar_approval';
            } else {
                $next_recipient_id = $final_target_id;
                $next_stage = 'completed';
            }
        }
    } elseif ($current_stage === 'establishment_approval') {
        // From Establishment to Deputy Rector
        $next_recipient_id = getDeputyRector($db);
        $next_stage = 'deputy_rector_approval';
        
        // If no Deputy Rector, check Registrar
        if (!$next_recipient_id) {
            if ($sender_faculty !== $target_faculty || hasRole($final_target_id, 'Rector', $db) || hasRole($final_target_id, 'Registrar', $db) || hasRole($final_target_id, 'Deputy Rector', $db)) {
                $next_recipient_id = getRegistrar($db);
                $next_stage = 'registrar_approval';
            } else {
                $next_recipient_id = $final_target_id;
                $next_stage = 'completed';
            }
        }
    } elseif ($current_stage === 'deputy_rector_approval') {
        // From Deputy Rector to Registrar
        if ($sender_faculty !== $target_faculty || hasRole($final_target_id, 'Rector', $db) || hasRole($final_target_id, 'Registrar', $db) || hasRole($final_target_id, 'Deputy Rector', $db)) {
            $next_recipient_id = getRegistrar($db);
            $next_stage = 'registrar_approval';
        } else {
            $next_recipient_id = $final_target_id;
            $next_stage = 'completed';
        }
    } elseif ($current_stage === 'registrar_approval') {
        // From Registrar to Final Target
        $next_recipient_id = $final_target_id;
        $next_stage = 'completed';
    }
    
    // Fallback if next recipient is null or same as final target
    if (!$next_recipient_id || $next_recipient_id == $final_target_id) {
        $next_recipient_id = $final_target_id;
        $next_stage = 'completed';
    }
    
    // Update memo
    $db->query(
        "UPDATE memos SET recipient_id = ?, current_stage = ?, is_approved = ? WHERE id = ?",
        [$next_recipient_id, $next_stage, ($next_stage === 'completed' ? 1 : 0), $memo_id]
    );
    
    // Add to memo_recipients
    $db->query(
        "INSERT IGNORE INTO memo_recipients (memo_id, recipient_id) VALUES (?, ?)",
        [$memo_id, $next_recipient_id]
    );
    
    return [
        'success' => true, 
        'message' => 'Memo forwarded successfully',
        'next_recipient_id' => $next_recipient_id,
        'next_stage' => $next_stage
    ];
}

/**
 * Calculates leave duration excluding weekends and public holidays
 * @param string $startDate Y-m-d
 * @param string $endDate Y-m-d
 * @param Database $db Database instance
 * @return int Duration in days
 */
function calculateLeaveDuration($startDate, $endDate, $db) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    
    if ($start > $end) return 0;
    
    // Fetch holidays for the relevant years
    $years = [ (int)$start->format('Y') ];
    if ($start->format('Y') !== $end->format('Y')) {
        $years[] = (int)$end->format('Y');
    }
    
    $placeholders = implode(',', array_fill(0, count($years), '?'));
    $holidaysRaw = $db->fetchAll("SELECT holiday_date FROM holidays WHERE year IN ($placeholders)", $years);
    $holidays = array_column($holidaysRaw, 'holiday_date');
    
    $duration = 0;
    $period = new DatePeriod($start, new DateInterval('P1D'), (clone $end)->modify('+1 day'));
    
    foreach ($period as $date) {
        $dateStr = $date->format('Y-m-d');
        $dayOfWeek = $date->format('N'); // 1 (Mon) to 7 (Sun)
        
        if ($dayOfWeek < 6 && !in_array($dateStr, $holidays)) {
            $duration++;
        }
    }
    
    return $duration;
}
?>
