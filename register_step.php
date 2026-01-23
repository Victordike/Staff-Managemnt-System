<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/nigeria_lga.php';

if (!isset($_SESSION['pre_user'])) {
    header('Location: register.php');
    exit;
}

$db = Database::getInstance();
$preUser = $_SESSION['pre_user'];

// Verify pre_user still exists in database (to prevent foreign key errors if DB was reset)
$verifyPreUser = $db->fetchOne("SELECT id FROM pre_users WHERE staff_id = ?", [$preUser['staff_id']]);
if (!$verifyPreUser) {
    unset($_SESSION['pre_user']);
    header('Location: register.php?error=session_expired');
    exit;
}

$error = '';
$success = '';

// Steps: 1=Personal Data, 2=Employment Info, 3=Banking Details, 4=NOK Details, 5=Security/Complete
$steps = [
    1 => 'Personal Data',
    2 => 'Employment Information',
    3 => 'Banking Details',
    4 => 'Next of Kin (NOK)',
    5 => 'Account Security'
];

// Faculty/School options with departments
$faculties = [
    'School of Applied Sciences' => ['Computer Science', 'Library and Information Science', 'Science Laboratory Technology', 'Statistics'],
    'School of Business Studies' => ['Accountancy', 'Business Administration and Management', 'Maritime Transport and Business Studies', 'Petroleum Marketing and Business Studies', 'Public Administration'],
    'School of Engineering Technology' => ['Chemical Engineering Technology', 'Electrical Electronics Engineering Technology', 'Industrial Safety and Environmental Engineering Technology', 'Mechanical Engineering Technology', 'Welding and Fabrication Technology', 'Mineral and Petroleum Resource Engineering Technology'],
    'Administrative and Support Services' => ['Rectorate', 'Registry', 'Bursary', 'Internal Audit', 'Physical Planning', 'Works and Services', 'Security Unit', 'Medical Center', 'Library (Admin)', 'Student Affairs', 'Academic Planning', 'Information and Communication Technology (ICT)']
];

// Load draft data from database on page load
$draft = null;
try {
    $draft = $db->fetchOne("SELECT * FROM registration_draft WHERE staff_id = ?", [$preUser['staff_id']]);
} catch (Exception $e) {
    error_log("Draft load error: " . $e->getMessage());
}

// Determine current step - prefer session, fallback to draft, default to 1
$currentStep = isset($_SESSION['registration_step']) ? intval($_SESSION['registration_step']) : ($draft ? intval($draft['current_step']) : 1);

// Load data from both session and database for form pre-filling
$personal = $_SESSION['personal_data'] ?? ($draft ? [
    'othername' => $draft['othername'] ?? '',
    'dob' => $draft['date_of_birth'],
    'sex' => $draft['sex'],
    'marital_status' => $draft['marital_status'],
    'address' => $draft['permanent_home_address'],
    'state' => $draft['state_origin'],
    'lga' => $draft['lga_origin'],
] : [
    'othername' => $preUser['othername'] ?? '',
]);

$employment = $_SESSION['employment_data'] ?? ($draft ? [
    'faculty' => $draft['faculty'] ?? '',
    'department' => $draft['department'],
    'position' => $draft['position'],
    'employment_type' => $draft['type_of_employment'],
    'assumption_date' => $draft['date_of_assumption'],
    'cadre' => $draft['cadre'],
    'salary_structure' => $draft['salary_structure'],
    'gl' => $draft['gl'],
    'step' => $draft['step'],
    'rank' => $draft['rank'],
    'phone' => $draft['phone_number'],
    'email' => $draft['official_email'],
] : [
    'salary_structure' => $preUser['salary_structure'] ?? '',
    'gl' => $preUser['gl'] ?? '',
    'step' => $preUser['step'] ?? '',
    'rank' => $preUser['rank'] ?? '',
]);

$banking = $_SESSION['banking_data'] ?? ($draft ? [
    'bank_name' => $draft['bank_name'],
    'account_name' => $draft['account_name'],
    'account_number' => $draft['account_number'],
    'pfa_name' => $draft['pfa_name'],
    'pfa_pin' => $draft['pfa_pin'],
] : []);

$nok = $_SESSION['nok_data'] ?? ($draft ? [
    'nok_name' => $draft['nok_fullname'],
    'nok_phone' => $draft['nok_phone_number'],
    'nok_relationship' => $draft['nok_relationship'],
    'nok_address' => $draft['nok_address'],
] : []);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $saveOnly = isset($_POST['save_only']);
        $moveNext = isset($_POST['next_step']);
        $movePrev = isset($_POST['prev_step']);
        $exitReg = isset($_POST['exit_registration']);
        
        if ($saveOnly || $moveNext || $exitReg) {
            // Validate and save current step
            switch ($currentStep) {
                case 1:
                    // No validation - allow partial saves for steps 1-4
                    $personal = [
                        'othername' => sanitize($_POST['othername'] ?? ''),
                        'dob' => sanitize($_POST['dob'] ?? ''),
                        'sex' => sanitize($_POST['sex'] ?? ''),
                        'marital_status' => sanitize($_POST['marital_status'] ?? ''),
                        'address' => sanitize($_POST['address'] ?? ''),
                        'state' => sanitize($_POST['state'] ?? ''),
                        'lga' => sanitize($_POST['lga'] ?? ''),
                    ];
                    $_SESSION['personal_data'] = $personal;
                    
                    // Save to database
                    $existingDraft = $db->fetchOne("SELECT id FROM registration_draft WHERE staff_id = ?", [$preUser['staff_id']]);
                    if ($existingDraft) {
                        $db->query(
                            "UPDATE registration_draft SET othername = ?, date_of_birth = ?, sex = ?, marital_status = ?, permanent_home_address = ?, state_origin = ?, lga_origin = ?, current_step = ?, updated_at = CURRENT_TIMESTAMP WHERE staff_id = ?",
                            [$personal['othername'] ?: null, $personal['dob'] ?: null, $personal['sex'] ?: null, $personal['marital_status'] ?: null, $personal['address'] ?: null, $personal['state'] ?: null, $personal['lga'] ?: null, $exitReg ? $currentStep : ($moveNext ? 2 : $currentStep), $preUser['staff_id']]
                        );
                    } else {
                        $db->query(
                            "INSERT INTO registration_draft (staff_id, surname, firstname, othername, date_of_birth, sex, marital_status, permanent_home_address, state_origin, lga_origin, salary_structure, gl, step, rank, current_step) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [
                                $preUser['staff_id'], $preUser['surname'], $preUser['firstname'], $personal['othername'] ?: null,
                                $personal['dob'] ?: null, $personal['sex'] ?: null, $personal['marital_status'] ?: null, $personal['address'] ?: null, $personal['state'] ?: null, $personal['lga'] ?: null,
                                $preUser['salary_structure'] ?? null, $preUser['gl'] ?? null, $preUser['step'] ?? null, $preUser['rank'] ?? null,
                                $exitReg ? 1 : ($moveNext ? 2 : 1)
                            ]
                        );
                    }
                    
                    $success = 'Personal data saved!';
                    if ($moveNext) {
                        $_SESSION['registration_step'] = 2;
                        header('Refresh: 2; url=register_step.php');
                    } elseif ($exitReg) {
                        $_SESSION['registration_step'] = 1;
                        $success = 'Your registration has been saved. You can continue later.';
                        header('Refresh: 2; url=admin_login.php');
                    }
                    break;
                    
                case 2:
                    $employment = [
                        'faculty' => sanitize($_POST['faculty'] ?? ''),
                        'department' => sanitize($_POST['department'] ?? ''),
                        'position' => sanitize($_POST['position'] ?? ''),
                        'employment_type' => sanitize($_POST['employment_type'] ?? ''),
                        'assumption_date' => sanitize($_POST['assumption_date'] ?? ''),
                        'cadre' => sanitize($_POST['cadre'] ?? ''),
                        'salary_structure' => sanitize($_POST['salary_structure'] ?? ''),
                        'gl' => sanitize($_POST['gl'] ?? ''),
                        'step' => sanitize($_POST['step'] ?? ''),
                        'rank' => sanitize($_POST['rank'] ?? ''),
                        'phone' => sanitize($_POST['phone'] ?? ''),
                        'email' => sanitize($_POST['email'] ?? ''),
                    ];
                    $_SESSION['employment_data'] = $employment;
                    
                    // Backend Validation for Grade Level mapping
                    $isValidGL = true;
                    if ($employment['salary_structure'] === 'CONPCASS') {
                        $gl = (int)$employment['gl'];
                        switch($employment['position']) {
                            case 'Assistant Lecturer': if ($gl !== 1) $isValidGL = false; break;
                            case 'Lecturer III': if ($gl !== 2) $isValidGL = false; break;
                            case 'Lecturer II': if ($gl !== 3) $isValidGL = false; break;
                            case 'Lecturer I': if ($gl !== 4) $isValidGL = false; break;
                            case 'Senior Lecturer': if ($gl < 5 || $gl > 7) $isValidGL = false; break;
                            case 'Principal Lecturer': if ($gl !== 8) $isValidGL = false; break;
                            case 'Chief Lecturer': if ($gl !== 9) $isValidGL = false; break;
                        }
                    } elseif ($employment['salary_structure'] === 'CONTEDISS') {
                        $gl = (int)$employment['gl'];
                        if (in_array($employment['position'], ['Registrar', 'Bursar', 'Librarian'])) {
                            if ($gl !== 15) $isValidGL = false;
                        }
                    }

                    if (!$isValidGL) {
                        $error = "Invalid Grade Level (GL " . $employment['gl'] . ") for the position: " . $employment['position'];
                        break;
                    }
                    
                    $existingDraft = $db->fetchOne("SELECT id FROM registration_draft WHERE staff_id = ?", [$preUser['staff_id']]);
                    if ($existingDraft) {
                        $db->query(
                            "UPDATE registration_draft SET faculty = ?, department = ?, position = ?, type_of_employment = ?, date_of_assumption = ?, cadre = ?, salary_structure = ?, gl = ?, step = ?, rank = ?, phone_number = ?, official_email = ?, current_step = ?, updated_at = CURRENT_TIMESTAMP WHERE staff_id = ?",
                            [$employment['faculty'] ?: null, $employment['department'] ?: null, $employment['position'] ?: null, $employment['employment_type'] ?: null, $employment['assumption_date'] ?: null, $employment['cadre'] ?: null, $employment['salary_structure'] ?: null, $employment['gl'] ?: null, $employment['step'] ?: null, $employment['rank'] ?: null, $employment['phone'] ?: null, $employment['email'] ?: null, $exitReg ? $currentStep : ($moveNext ? 3 : $currentStep), $preUser['staff_id']]
                        );
                    }
                    
                    $success = 'Employment information saved!';
                    if ($moveNext) {
                        $_SESSION['registration_step'] = 3;
                        header('Refresh: 2; url=register_step.php');
                    } elseif ($exitReg) {
                        $_SESSION['registration_step'] = 2;
                        $success = 'Your registration has been saved. You can continue later.';
                        header('Refresh: 2; url=admin_login.php');
                    }
                    break;
                    
                case 3:
                    $banking = [
                        'bank_name' => sanitize($_POST['bank_name'] ?? ''),
                        'account_name' => sanitize($_POST['account_name'] ?? ''),
                        'account_number' => sanitize($_POST['account_number'] ?? ''),
                        'pfa_name' => sanitize($_POST['pfa_name'] ?? ''),
                        'pfa_pin' => sanitize($_POST['pfa_pin'] ?? ''),
                    ];
                    $_SESSION['banking_data'] = $banking;
                    
                    $existingDraft = $db->fetchOne("SELECT id FROM registration_draft WHERE staff_id = ?", [$preUser['staff_id']]);
                    if ($existingDraft) {
                        $db->query(
                            "UPDATE registration_draft SET bank_name = ?, account_name = ?, account_number = ?, pfa_name = ?, pfa_pin = ?, current_step = ?, updated_at = CURRENT_TIMESTAMP WHERE staff_id = ?",
                            [$banking['bank_name'] ?: null, $banking['account_name'] ?: null, $banking['account_number'] ?: null, $banking['pfa_name'] ?: null, $banking['pfa_pin'] ?: null, $exitReg ? $currentStep : ($moveNext ? 4 : $currentStep), $preUser['staff_id']]
                        );
                    }
                    
                    $success = 'Banking details saved!';
                    if ($moveNext) {
                        $_SESSION['registration_step'] = 4;
                        header('Refresh: 2; url=register_step.php');
                    } elseif ($exitReg) {
                        $_SESSION['registration_step'] = 3;
                        $success = 'Your registration has been saved. You can continue later.';
                        header('Refresh: 2; url=admin_login.php');
                    }
                    break;
                    
                case 4:
                    $nok = [
                        'nok_name' => sanitize($_POST['nok_name'] ?? ''),
                        'nok_phone' => sanitize($_POST['nok_phone'] ?? ''),
                        'nok_relationship' => sanitize($_POST['nok_relationship'] ?? ''),
                        'nok_address' => sanitize($_POST['nok_address'] ?? ''),
                    ];
                    $_SESSION['nok_data'] = $nok;
                    
                    $existingDraft = $db->fetchOne("SELECT id FROM registration_draft WHERE staff_id = ?", [$preUser['staff_id']]);
                    if ($existingDraft) {
                        $db->query(
                            "UPDATE registration_draft SET nok_fullname = ?, nok_phone_number = ?, nok_relationship = ?, nok_address = ?, current_step = ?, updated_at = CURRENT_TIMESTAMP WHERE staff_id = ?",
                            [$nok['nok_name'] ?: null, $nok['nok_phone'] ?: null, $nok['nok_relationship'] ?: null, $nok['nok_address'] ?: null, $exitReg ? $currentStep : ($moveNext ? 5 : $currentStep), $preUser['staff_id']]
                        );
                    }
                    
                    $success = 'NOK details saved!';
                    if ($moveNext) {
                        $_SESSION['registration_step'] = 5;
                        header('Refresh: 2; url=register_step.php');
                    } elseif ($exitReg) {
                        $_SESSION['registration_step'] = 4;
                        $success = 'Your registration has been saved. You can continue later.';
                        header('Refresh: 2; url=admin_login.php');
                    }
                    break;
                    
                case 5:
                    if ($exitReg) {
                        // Just save and exit (allow incomplete password on exit)
                        $existingDraft = $db->fetchOne("SELECT id FROM registration_draft WHERE staff_id = ?", [$preUser['staff_id']]);
                        if ($existingDraft) {
                            $db->query(
                                "UPDATE registration_draft SET current_step = 5, updated_at = CURRENT_TIMESTAMP WHERE staff_id = ?",
                                [$preUser['staff_id']]
                            );
                        }
                        $_SESSION['registration_step'] = 5;
                        $success = 'Your registration has been saved. You can continue later.';
                        header('Refresh: 2; url=admin_login.php');
                        break;
                    }
                    
                    // For completion (not exit), validate ALL fields across all steps
                    if (empty($_POST['password']) || empty($_POST['confirm_password'])) {
                        $error = 'Please enter a password';
                        break;
                    }
                    if ($_POST['password'] !== $_POST['confirm_password']) {
                        $error = 'Passwords do not match';
                        break;
                    }
                    if (strlen($_POST['password']) < 8) {
                        $error = 'Password must be at least 8 characters';
                        break;
                    }
                    
                    // Validate all previous steps are complete
                    $allPersonalFilled = !empty($personal['dob']) && !empty($personal['sex']) && !empty($personal['marital_status']) && !empty($personal['address']) && !empty($personal['state']) && !empty($personal['lga']);
                    $allEmploymentFilled = !empty($employment['faculty']) && !empty($employment['department']) && !empty($employment['position']) && !empty($employment['employment_type']) && !empty($employment['assumption_date']) && !empty($employment['salary_structure']) && !empty($employment['gl']) && !empty($employment['step']) && !empty($employment['rank']) && !empty($employment['phone']) && !empty($employment['email']);
                    $allBankingFilled = !empty($banking['bank_name']) && !empty($banking['account_name']) && !empty($banking['account_number']) && !empty($banking['pfa_name']) && !empty($banking['pfa_pin']);
                    $allNokFilled = !empty($nok['nok_name']) && !empty($nok['nok_phone']) && !empty($nok['nok_relationship']) && !empty($nok['nok_address']);
                    
                    if (!$allPersonalFilled) {
                        $error = 'Please complete all fields in Step 1: Personal Data';
                        break;
                    }
                    if (!$allEmploymentFilled) {
                        $error = 'Please complete all fields in Step 2: Employment Information';
                        break;
                    }
                    if (!$allBankingFilled) {
                        $error = 'Please complete all fields in Step 3: Banking Details';
                        break;
                    }
                    if (!$allNokFilled) {
                        $error = 'Please complete all fields in Step 4: Next of Kin';
                        break;
                    }
                    
                    $profilePicture = null;
                    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
                        $max_size = 5 * 1024 * 1024;
                        if (!in_array($_FILES['profile_picture']['type'], $allowed)) {
                            $error = 'Only JPG, PNG, and GIF files are allowed';
                            break;
                        }
                        if ($_FILES['profile_picture']['size'] > $max_size) {
                            $error = 'File size must not exceed 5MB';
                            break;
                        }
                        $filename = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
                        $upload_path = 'uploads/passport/' . $filename;
                        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                            $profilePicture = $upload_path;
                        }
                    }
                    
                    // Complete registration - move draft to admin_users
                    $db->query(
                        "INSERT INTO admin_users (
                            staff_id, surname, firstname, othername,
                            date_of_birth, sex, marital_status, permanent_home_address, state_origin, lga_origin,
                            faculty, department, position, type_of_employment, date_of_assumption, cadre,
                            salary_structure, gl, step, rank,
                            phone_number, official_email,
                            bank_name, account_name, account_number, pfa_name, pfa_pin,
                            nok_fullname, nok_phone_number, nok_relationship, nok_address,
                            password, profile_picture, is_active
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
                        [
                            $preUser['staff_id'],
                            $preUser['surname'],
                            $preUser['firstname'],
                            $personal['othername'] ?? null,
                            $personal['dob'] ?? null,
                            $personal['sex'] ?? null,
                            $personal['marital_status'] ?? null,
                            $personal['address'] ?? null,
                            $personal['state'] ?? null,
                            $personal['lga'] ?? null,
                            $employment['faculty'] ?? null,
                            $employment['department'] ?? null,
                            $employment['position'] ?? null,
                            $employment['employment_type'] ?? null,
                            $employment['assumption_date'] ?? null,
                            $employment['cadre'] ?? null,
                            $employment['salary_structure'] ?? null,
                            $employment['gl'] ?? null,
                            $employment['step'] ?? null,
                            $employment['rank'] ?? null,
                            $employment['phone'] ?? null,
                            $employment['email'] ?? null,
                            $banking['bank_name'] ?? null,
                            $banking['account_name'] ?? null,
                            $banking['account_number'] ?? null,
                            $banking['pfa_name'] ?? null,
                            $banking['pfa_pin'] ?? null,
                            $nok['nok_name'] ?? null,
                            $nok['nok_phone'] ?? null,
                            $nok['nok_relationship'] ?? null,
                            $nok['nok_address'] ?? null,
                            hashPassword($_POST['password']),
                            $profilePicture
                        ]
                    );
                    
                    // Delete draft
                    $db->query("DELETE FROM registration_draft WHERE staff_id = ?", [$preUser['staff_id']]);
                    
                    // Mark as registered in pre_users
                    $db->query("UPDATE pre_users SET is_registered = 1 WHERE staff_id = ?", [$preUser['staff_id']]);
                    
                    // Cleanup session
                    unset($_SESSION['pre_user']);
                    unset($_SESSION['registration_step']);
                    unset($_SESSION['personal_data']);
                    unset($_SESSION['employment_data']);
                    unset($_SESSION['banking_data']);
                    unset($_SESSION['nok_data']);
                    
                    $success = 'Registration completed successfully!';
                    header('Refresh: 2; url=admin_login.php');
                    break;
            }
        } elseif ($movePrev) {
            if ($currentStep > 1) {
                $_SESSION['registration_step'] = $currentStep - 1;
                header('Location: register_step.php');
                exit;
            }
        }
    } catch (Exception $e) {
        $error = 'An error occurred: ' . $e->getMessage();
        error_log($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $steps[$currentStep]; ?> - Registration</title>
    <link rel="stylesheet" href="assets/css/output.css">
    <link rel="stylesheet" href="assets/css/loading.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }
        .registration-container {
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow: hidden;
        }
        .form-scroll-container {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 8px;
        }
        .form-scroll-container::-webkit-scrollbar {
            width: 8px;
        }
        .form-scroll-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .form-scroll-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .form-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body class="bg-gray-100" style="margin: 0; padding: 8px 16px; overflow: hidden;">
    <!-- Advanced Loading Screen -->
    <div id="loadingScreen" class="loading-overlay">
        <div class="loading-content">
            <div class="loading-glow-card">
                <div class="spinner-premium">
                    <span></span>
                </div>
                <h2 class="loading-text">Staff Management System</h2>
                <p class="loading-subtext">Saving Progress<span class="loading-dots"></span></p>
                <div class="loading-progress">
                    <div class="progress-bar"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Hide loading screen and show content when page loads
        window.addEventListener('load', function() {
            const loadingScreen = document.getElementById('loadingScreen');
            const mainContent = document.querySelector('.max-w-2xl.mx-auto');
            if (loadingScreen) {
                loadingScreen.style.opacity = '0';
                loadingScreen.style.transition = 'opacity 0.5s ease-out';
                if (mainContent) mainContent.style.opacity = '1';
                setTimeout(function() {
                    loadingScreen.style.display = 'none';
                }, 500);
            }
        });
    </script>
    
    <div class="max-w-2xl mx-auto registration-container" style="opacity: 0; transition: opacity 0.3s ease-in;">
        <div class="card" style="display: flex; flex-direction: column; height: 100%; overflow: hidden;">
            <!-- Header -->
            <div class="text-center mb-8" style="flex-shrink: 0;">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Staff Registration</h1>
                <p class="text-gray-600">Federal Polytechnic of Oil and Gas</p>
            </div>
            
            <!-- Progress Steps -->
            <div class="mb-8" style="flex-shrink: 0;">
                <div class="flex justify-between mb-2">
                    <?php foreach ($steps as $step => $label): ?>
                        <div class="flex-1">
                            <div class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold mb-2 <?php echo $step < $currentStep ? 'bg-green-600 text-white' : ($step == $currentStep ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600'); ?>">
                                    <?php echo $step < $currentStep ? '<i class="fas fa-check"></i>' : $step; ?>
                                </div>
                                <span class="text-xs text-center text-gray-600 font-semibold"><?php echo $label; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div class="w-full bg-gray-300 rounded-full h-2 mb-8" style="flex-shrink: 0;">
                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo ($currentStep / count($steps)) * 100; ?>%"></div>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4" style="flex-shrink: 0;">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4" style="flex-shrink: 0;">
                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-6" style="display: flex; flex-direction: column; flex: 1; overflow: hidden;">
                
                <div class="form-scroll-container">
                
                <!-- Step 1: Personal Data -->
                <?php if ($currentStep == 1): ?>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-4"><i class="fas fa-user text-blue-600 mr-2"></i>Personal Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-2">Full Name (From Records)</label>
                                <div class="bg-gray-100 p-3 rounded-lg text-gray-700 border border-gray-200">
                                    <?php echo htmlspecialchars($preUser['firstname'] . ' ' . $preUser['surname']); ?>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Staff ID: <?php echo htmlspecialchars($preUser['staff_id']); ?></p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-2">Other Name (Middle Name)</label>
                                <input type="text" name="othername" class="input-field" value="<?php echo htmlspecialchars($personal['othername'] ?? ''); ?>" placeholder="Enter middle name if any">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Date of Birth</label>
                                <input type="date" name="dob" class="input-field" value="<?php echo htmlspecialchars($personal['dob'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Sex</label>
                                <select name="sex" class="input-field">
                                    <option value="">Select Sex</option>
                                    <option value="Male" <?php echo ($personal['sex'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($personal['sex'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Marital Status</label>
                                <select name="marital_status" class="input-field">
                                    <option value="">Select Status</option>
                                    <option value="Single" <?php echo ($personal['marital_status'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                                    <option value="Married" <?php echo ($personal['marital_status'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                                    <option value="Divorced" <?php echo ($personal['marital_status'] ?? '') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                    <option value="Widowed" <?php echo ($personal['marital_status'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">State of Origin</label>
                                <select name="state" id="state_origin" class="input-field" required onchange="updateLGAs()">
                                    <option value="">Select State</option>
                                    <?php 
                                    $statesLgas = getNigerianStatesWithLGAs();
                                    foreach (array_keys($statesLgas) as $state): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars($state); ?>" <?php echo ($personal['state'] ?? '') === $state ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($state); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">LGA of Origin</label>
                                <select name="lga" id="lga_origin" class="input-field" required>
                                    <option value="">Select LGA</option>
                                    <?php 
                                    if (!empty($personal['state']) && isset($statesLgas[$personal['state']])):
                                        foreach ($statesLgas[$personal['state']] as $lga):
                                    ?>
                                        <option value="<?php echo htmlspecialchars($lga); ?>" <?php echo ($personal['lga'] ?? '') === $lga ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($lga); ?>
                                        </option>
                                    <?php 
                                        endforeach;
                                    endif; 
                                    ?>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-2">Home Address</label>
                                <textarea name="address" class="input-field" rows="3"><?php echo htmlspecialchars($personal['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Step 2: Employment Information -->
                <?php if ($currentStep == 2): ?>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-4"><i class="fas fa-briefcase text-blue-600 mr-2"></i>Employment Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-2">Faculty/School</label>
                                <select name="faculty" class="input-field">
                                    <option value="">Select Faculty/School</option>
                                    <?php foreach ($faculties as $faculty => $depts): ?>
                                        <option value="<?php echo htmlspecialchars($faculty); ?>" <?php echo ($employment['faculty'] ?? '') === $faculty ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($faculty); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-2">Department</label>
                                <select name="department" id="department" class="input-field">
                                    <option value="">Select Department</option>
                                    <optgroup label="Academic Departments">
                                        <option value="Accountancy" <?php echo ($employment['department'] ?? '') === 'Accountancy' ? 'selected' : ''; ?>>Accountancy</option>
                                        <option value="Business Administration & Management" <?php echo ($employment['department'] ?? '') === 'Business Administration & Management' ? 'selected' : ''; ?>>Business Administration & Management</option>
                                        <option value="Computer Science" <?php echo ($employment['department'] ?? '') === 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                                        <option value="Electrical/Electronic Engineering Technology" <?php echo ($employment['department'] ?? '') === 'Electrical/Electronic Engineering Technology' ? 'selected' : ''; ?>>Electrical/Electronic Engineering Technology</option>
                                        <option value="Library and Information Technology" <?php echo ($employment['department'] ?? '') === 'Library and Information Technology' ? 'selected' : ''; ?>>Library and Information Technology</option>
                                        <option value="Mechanical Engineering Technology" <?php echo ($employment['department'] ?? '') === 'Mechanical Engineering Technology' ? 'selected' : ''; ?>>Mechanical Engineering Technology</option>
                                        <option value="Petroleum Engineering Technology" <?php echo ($employment['department'] ?? '') === 'Petroleum Engineering Technology' ? 'selected' : ''; ?>>Petroleum Engineering Technology</option>
                                        <option value="Science Laboratory Technology" <?php echo ($employment['department'] ?? '') === 'Science Laboratory Technology' ? 'selected' : ''; ?>>Science Laboratory Technology</option>
                                        <option value="Statistics" <?php echo ($employment['department'] ?? '') === 'Statistics' ? 'selected' : ''; ?>>Statistics</option>
                                    </optgroup>
                                    <optgroup label="Administrative Units">
                                        <option value="Rectorate" <?php echo ($employment['department'] ?? '') === 'Rectorate' ? 'selected' : ''; ?>>Rectorate</option>
                                        <option value="Registry" <?php echo ($employment['department'] ?? '') === 'Registry' ? 'selected' : ''; ?>>Registry</option>
                                        <option value="Bursary" <?php echo ($employment['department'] ?? '') === 'Bursary' ? 'selected' : ''; ?>>Bursary</option>
                                        <option value="Internal Audit" <?php echo ($employment['department'] ?? '') === 'Internal Audit' ? 'selected' : ''; ?>>Internal Audit</option>
                                        <option value="Physical Planning" <?php echo ($employment['department'] ?? '') === 'Physical Planning' ? 'selected' : ''; ?>>Physical Planning</option>
                                        <option value="Works and Services" <?php echo ($employment['department'] ?? '') === 'Works and Services' ? 'selected' : ''; ?>>Works and Services</option>
                                        <option value="Security Unit" <?php echo ($employment['department'] ?? '') === 'Security Unit' ? 'selected' : ''; ?>>Security Unit</option>
                                        <option value="Medical Center" <?php echo ($employment['department'] ?? '') === 'Medical Center' ? 'selected' : ''; ?>>Medical Center</option>
                                        <option value="Library (Admin)" <?php echo ($employment['department'] ?? '') === 'Library (Admin)' ? 'selected' : ''; ?>>Library (Admin)</option>
                                        <option value="Student Affairs" <?php echo ($employment['department'] ?? '') === 'Student Affairs' ? 'selected' : ''; ?>>Student Affairs</option>
                                        <option value="Academic Planning" <?php echo ($employment['department'] ?? '') === 'Academic Planning' ? 'selected' : ''; ?>>Academic Planning</option>
                                        <option value="Information and Communication Technology (ICT)" <?php echo ($employment['department'] ?? '') === 'Information and Communication Technology (ICT)' ? 'selected' : ''; ?>>Information and Communication Technology (ICT)</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Position</label>
                                <select name="position" class="input-field">
                                    <option value="">Select Position</option>
                                    <optgroup label="Academic Ranks">
                                        <option value="Chief Lecturer" <?php echo ($employment['position'] ?? '') === 'Chief Lecturer' ? 'selected' : ''; ?>>Chief Lecturer</option>
                                        <option value="Principal Lecturer" <?php echo ($employment['position'] ?? '') === 'Principal Lecturer' ? 'selected' : ''; ?>>Principal Lecturer</option>
                                        <option value="Senior Lecturer" <?php echo ($employment['position'] ?? '') === 'Senior Lecturer' ? 'selected' : ''; ?>>Senior Lecturer</option>
                                        <option value="Lecturer I" <?php echo ($employment['position'] ?? '') === 'Lecturer I' ? 'selected' : ''; ?>>Lecturer I</option>
                                        <option value="Lecturer II" <?php echo ($employment['position'] ?? '') === 'Lecturer II' ? 'selected' : ''; ?>>Lecturer II</option>
                                        <option value="Lecturer III" <?php echo ($employment['position'] ?? '') === 'Lecturer III' ? 'selected' : ''; ?>>Lecturer III</option>
                                        <option value="Assistant Lecturer" <?php echo ($employment['position'] ?? '') === 'Assistant Lecturer' ? 'selected' : ''; ?>>Assistant Lecturer</option>
                                    </optgroup>
                                    <optgroup label="Technologist Ranks">
                                        <option value="Chief Technologist" <?php echo ($employment['position'] ?? '') === 'Chief Technologist' ? 'selected' : ''; ?>>Chief Technologist</option>
                                        <option value="Principal Technologist" <?php echo ($employment['position'] ?? '') === 'Principal Technologist' ? 'selected' : ''; ?>>Principal Technologist</option>
                                        <option value="Senior Technologist" <?php echo ($employment['position'] ?? '') === 'Senior Technologist' ? 'selected' : ''; ?>>Senior Technologist</option>
                                        <option value="Technologist I" <?php echo ($employment['position'] ?? '') === 'Technologist I' ? 'selected' : ''; ?>>Technologist I</option>
                                        <option value="Technologist II" <?php echo ($employment['position'] ?? '') === 'Technologist II' ? 'selected' : ''; ?>>Technologist II</option>
                                    </optgroup>
                                    <optgroup label="Registry & Administration">
                                        <option value="Registrar" <?php echo ($employment['position'] ?? '') === 'Registrar' ? 'selected' : ''; ?>>Registrar</option>
                                        <option value="Deputy Registrar" <?php echo ($employment['position'] ?? '') === 'Deputy Registrar' ? 'selected' : ''; ?>>Deputy Registrar</option>
                                        <option value="Principal Assistant Registrar" <?php echo ($employment['position'] ?? '') === 'Principal Assistant Registrar' ? 'selected' : ''; ?>>Principal Assistant Registrar</option>
                                        <option value="Senior Assistant Registrar" <?php echo ($employment['position'] ?? '') === 'Senior Assistant Registrar' ? 'selected' : ''; ?>>Senior Assistant Registrar</option>
                                        <option value="Assistant Registrar" <?php echo ($employment['position'] ?? '') === 'Assistant Registrar' ? 'selected' : ''; ?>>Assistant Registrar</option>
                                        <option value="Chief Executive Officer" <?php echo ($employment['position'] ?? '') === 'Chief Executive Officer' ? 'selected' : ''; ?>>Chief Executive Officer</option>
                                        <option value="Principal Executive Officer" <?php echo ($employment['position'] ?? '') === 'Principal Executive Officer' ? 'selected' : ''; ?>>Principal Executive Officer</option>
                                        <option value="Senior Executive Officer" <?php echo ($employment['position'] ?? '') === 'Senior Executive Officer' ? 'selected' : ''; ?>>Senior Executive Officer</option>
                                        <option value="Executive Officer" <?php echo ($employment['position'] ?? '') === 'Executive Officer' ? 'selected' : ''; ?>>Executive Officer</option>
                                    </optgroup>
                                    <optgroup label="Bursary & Finance">
                                        <option value="Bursar" <?php echo ($employment['position'] ?? '') === 'Bursar' ? 'selected' : ''; ?>>Bursar</option>
                                        <option value="Deputy Bursar" <?php echo ($employment['position'] ?? '') === 'Deputy Bursar' ? 'selected' : ''; ?>>Deputy Bursar</option>
                                        <option value="Chief Accountant" <?php echo ($employment['position'] ?? '') === 'Chief Accountant' ? 'selected' : ''; ?>>Chief Accountant</option>
                                        <option value="Principal Accountant" <?php echo ($employment['position'] ?? '') === 'Principal Accountant' ? 'selected' : ''; ?>>Principal Accountant</option>
                                        <option value="Senior Accountant" <?php echo ($employment['position'] ?? '') === 'Senior Accountant' ? 'selected' : ''; ?>>Senior Accountant</option>
                                        <option value="Accountant I" <?php echo ($employment['position'] ?? '') === 'Accountant I' ? 'selected' : ''; ?>>Accountant I</option>
                                        <option value="Accountant II" <?php echo ($employment['position'] ?? '') === 'Accountant II' ? 'selected' : ''; ?>>Accountant II</option>
                                    </optgroup>
                                    <optgroup label="Library">
                                        <option value="Librarian" <?php echo ($employment['position'] ?? '') === 'Librarian' ? 'selected' : ''; ?>>Librarian</option>
                                        <option value="Deputy Librarian" <?php echo ($employment['position'] ?? '') === 'Deputy Librarian' ? 'selected' : ''; ?>>Deputy Librarian</option>
                                        <option value="Principal Librarian" <?php echo ($employment['position'] ?? '') === 'Principal Librarian' ? 'selected' : ''; ?>>Principal Librarian</option>
                                        <option value="Senior Librarian" <?php echo ($employment['position'] ?? '') === 'Senior Librarian' ? 'selected' : ''; ?>>Senior Librarian</option>
                                        <option value="Librarian I" <?php echo ($employment['position'] ?? '') === 'Librarian I' ? 'selected' : ''; ?>>Librarian I</option>
                                        <option value="Librarian II" <?php echo ($employment['position'] ?? '') === 'Librarian II' ? 'selected' : ''; ?>>Librarian II</option>
                                    </optgroup>
                                    <optgroup label="Other Staff">
                                        <option value="Secretarial Assistant" <?php echo ($employment['position'] ?? '') === 'Secretarial Assistant' ? 'selected' : ''; ?>>Secretarial Assistant</option>
                                        <option value="Clerical Officer" <?php echo ($employment['position'] ?? '') === 'Clerical Officer' ? 'selected' : ''; ?>>Clerical Officer</option>
                                        <option value="Driver" <?php echo ($employment['position'] ?? '') === 'Driver' ? 'selected' : ''; ?>>Driver</option>
                                        <option value="Messenger" <?php echo ($employment['position'] ?? '') === 'Messenger' ? 'selected' : ''; ?>>Messenger</option>
                                        <option value="Security Officer" <?php echo ($employment['position'] ?? '') === 'Security Officer' ? 'selected' : ''; ?>>Security Officer</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Employment Type</label>
                                <select name="employment_type" class="input-field">
                                    <option value="">Select Type</option>
                                    <option value="Permanent" <?php echo ($employment['employment_type'] ?? '') === 'Permanent' ? 'selected' : ''; ?>>Permanent</option>
                                    <option value="Contract" <?php echo ($employment['employment_type'] ?? '') === 'Contract' ? 'selected' : ''; ?>>Contract</option>
                                    <option value="Temporary" <?php echo ($employment['employment_type'] ?? '') === 'Temporary' ? 'selected' : ''; ?>>Temporary</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Date of Assumption</label>
                                <input type="date" name="assumption_date" class="input-field" value="<?php echo htmlspecialchars($employment['assumption_date'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Cadre</label>
                                <input type="text" name="cadre" class="input-field" value="<?php echo htmlspecialchars($employment['cadre'] ?? ''); ?>" placeholder="e.g. Academic, Administrative">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Salary Structure</label>
                                <select name="salary_structure" id="salary_structure" class="input-field" onchange="updateGrades()">
                                    <option value="">Select Structure</option>
                                    <option value="CONPCASS" <?php echo ($employment['salary_structure'] ?? '') === 'CONPCASS' ? 'selected' : ''; ?>>CONPCASS (Academic)</option>
                                    <option value="CONTEDISS" <?php echo ($employment['salary_structure'] ?? '') === 'CONTEDISS' ? 'selected' : ''; ?>>CONTEDISS (Non-Academic)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Grade Level (GL)</label>
                                <select name="gl" id="gl" class="input-field">
                                    <option value="">Select GL</option>
                                    <?php for($i=1; $i<=15; $i++): $val = sprintf("%02d", $i); ?>
                                        <option value="<?php echo $val; ?>" <?php echo ($employment['gl'] ?? '') == $val ? 'selected' : ''; ?>><?php echo $val; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Step</label>
                                <select name="step" class="input-field">
                                    <option value="">Select Step</option>
                                    <?php for($i=1; $i<=15; $i++): $val = sprintf("%02d", $i); ?>
                                        <option value="<?php echo $val; ?>" <?php echo ($employment['step'] ?? '') == $val ? 'selected' : ''; ?>><?php echo $val; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Rank</label>
                                <input type="text" name="rank" class="input-field" value="<?php echo htmlspecialchars($employment['rank'] ?? ''); ?>" placeholder="e.g. Lecturer I, Higher Registrar">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Phone Number</label>
                                <input type="tel" name="phone" class="input-field" value="<?php echo htmlspecialchars($employment['phone'] ?? ''); ?>">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-2">Official Email</label>
                                <input type="email" name="email" class="input-field" value="<?php echo htmlspecialchars($employment['email'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Step 3: Banking Details -->
                <?php if ($currentStep == 3): ?>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-4"><i class="fas fa-bank text-blue-600 mr-2"></i>Banking Details</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Bank Name</label>
                                <input type="text" name="bank_name" class="input-field" value="<?php echo htmlspecialchars($banking['bank_name'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Account Name</label>
                                <input type="text" name="account_name" class="input-field" value="<?php echo htmlspecialchars($banking['account_name'] ?? ''); ?>">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-2">Account Number</label>
                                <input type="text" name="account_number" class="input-field" value="<?php echo htmlspecialchars($banking['account_number'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">PFA Name</label>
                                <input type="text" name="pfa_name" class="input-field" value="<?php echo htmlspecialchars($banking['pfa_name'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">PFA Pin</label>
                                <input type="text" name="pfa_pin" class="input-field" value="<?php echo htmlspecialchars($banking['pfa_pin'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Step 4: NOK Details -->
                <?php if ($currentStep == 4): ?>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-4"><i class="fas fa-users text-blue-600 mr-2"></i>Next of Kin (NOK)</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">NOK Full Name</label>
                                <input type="text" name="nok_name" class="input-field" value="<?php echo htmlspecialchars($nok['nok_name'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">NOK Phone Number</label>
                                <input type="tel" name="nok_phone" class="input-field" value="<?php echo htmlspecialchars($nok['nok_phone'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Relationship</label>
                                <select name="nok_relationship" class="input-field">
                                    <option value="">Select Relationship</option>
                                    <option value="Spouse" <?php echo ($nok['nok_relationship'] ?? '') === 'Spouse' ? 'selected' : ''; ?>>Spouse</option>
                                    <option value="Parent" <?php echo ($nok['nok_relationship'] ?? '') === 'Parent' ? 'selected' : ''; ?>>Parent</option>
                                    <option value="Child" <?php echo ($nok['nok_relationship'] ?? '') === 'Child' ? 'selected' : ''; ?>>Child</option>
                                    <option value="Sibling" <?php echo ($nok['nok_relationship'] ?? '') === 'Sibling' ? 'selected' : ''; ?>>Sibling</option>
                                    <option value="Other" <?php echo ($nok['nok_relationship'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-2">NOK Address</label>
                                <textarea name="nok_address" class="input-field" rows="3"><?php echo htmlspecialchars($nok['nok_address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Step 5: Account Security -->
                <?php if ($currentStep == 5): ?>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-4"><i class="fas fa-lock text-blue-600 mr-2"></i>Complete Registration</h2>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <p class="text-blue-800"><i class="fas fa-info-circle mr-2"></i>Set your password and upload profile picture to complete registration.</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-2">Password (min 8 characters) *</label>
                                <input type="password" name="password" class="input-field" minlength="8" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-2">Confirm Password *</label>
                                <input type="password" name="confirm_password" class="input-field" minlength="8" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-2">Profile Picture (JPG, PNG, GIF - Max 5MB)</label>
                                <input type="file" id="profilePictureInput" name="profile_picture" class="input-field" accept="image/*">
                                <div id="previewContainer" class="mt-4 hidden">
                                    <p class="text-gray-600 font-semibold mb-2">Preview:</p>
                                    <img id="profilePreview" src="" alt="Profile Preview" style="width: 120px; height: 120px;" class="object-cover rounded-lg border-2 border-gray-300 shadow-lg">
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                </div>
                
                <!-- Navigation Buttons -->
                <div class="flex gap-2 pt-6 border-t flex-wrap" style="flex-shrink: 0;">
                    <?php if ($currentStep > 1): ?>
                        <button type="submit" name="prev_step" class="btn-secondary flex-1 min-w-[100px]" formnovalidate>
                            <i class="fas fa-arrow-left mr-2"></i>Previous
                        </button>
                    <?php endif; ?>
                    
                    <button type="submit" name="save_only" class="flex-1 min-w-[100px]" style="background-color: #6366f1; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-save mr-2"></i>Save
                    </button>
                    
                    <button type="submit" name="exit_registration" class="flex-1 min-w-[100px]" style="background-color: #f97316; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer;" formnovalidate>
                        <i class="fas fa-sign-out-alt mr-2"></i>Exit & Save
                    </button>
                    
                    <?php if ($currentStep < count($steps)): ?>
                        <button type="submit" name="next_step" class="btn-primary flex-1 min-w-[100px]">
                            Next <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    <?php else: ?>
                        <button type="submit" name="next_step" class="btn-primary flex-1 min-w-[100px]">
                            <i class="fas fa-check-circle mr-2"></i>Complete
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const nigeriaStatesLGAs = <?php echo json_encode(getNigerianStatesWithLGAs()); ?>;

        function updateLGAs() {
            const stateSelect = document.getElementById('state_origin');
            const lgaSelect = document.getElementById('lga_origin');
            const selectedState = stateSelect.value;
            
            // Clear current LGAs
            lgaSelect.innerHTML = '<option value="">Select LGA</option>';
            
            if (selectedState && nigeriaStatesLGAs[selectedState]) {
                nigeriaStatesLGAs[selectedState].forEach(lga => {
                    const option = document.createElement('option');
                    option.value = lga;
                    option.textContent = lga;
                    lgaSelect.appendChild(option);
                });
            }
        }

        // Image preview functionality
        const profilePictureInput = document.getElementById('profilePictureInput');
        const previewContainer = document.getElementById('previewContainer');
        const profilePreview = document.getElementById('profilePreview');
        
        if (profilePictureInput) {
            profilePictureInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                
                if (file) {
                    // Validate file type
                    if (!file.type.startsWith('image/')) {
                        alert('Please select a valid image file');
                        this.value = '';
                        previewContainer.classList.add('hidden');
                        return;
                    }
                    
                    // Validate file size (5MB max)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('File size must not exceed 5MB');
                        this.value = '';
                        previewContainer.classList.add('hidden');
                        return;
                    }
                    
                    // Read and display the file
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        profilePreview.src = event.target.result;
                        previewContainer.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewContainer.classList.add('hidden');
                }
            });
        }

        function updateGrades() {
            const structure = document.getElementById('salary_structure').value;
            const position = document.getElementsByName('position')[0].value;
            const glSelect = document.getElementById('gl');
            const currentVal = glSelect.value;
            
            // Clear existing options
            glSelect.innerHTML = '<option value="">Select GL</option>';
            
            let minGL = 1;
            let maxGL = 15;
            
            if (structure === 'CONPCASS') {
                // Academic Mappings
                switch(position) {
                    case 'Assistant Lecturer': minGL = 1; maxGL = 1; break;
                    case 'Lecturer III': minGL = 2; maxGL = 2; break;
                    case 'Lecturer II': minGL = 3; maxGL = 3; break;
                    case 'Lecturer I': minGL = 4; maxGL = 4; break;
                    case 'Senior Lecturer': minGL = 5; maxGL = 7; break;
                    case 'Principal Lecturer': minGL = 8; maxGL = 8; break;
                    case 'Chief Lecturer': minGL = 9; maxGL = 9; break;
                    default: minGL = 1; maxGL = 9;
                }
            } else if (structure === 'CONTEDISS') {
                // Administrative/Technical Mappings (Common patterns)
                if (['Registrar', 'Bursar', 'Librarian'].includes(position)) {
                    minGL = 15; maxGL = 15;
                } else if (position.includes('Deputy')) {
                    minGL = 14; maxGL = 15;
                } else if (position.includes('Principal')) {
                    minGL = 11; maxGL = 13;
                } else if (position.includes('Senior')) {
                    minGL = 8; maxGL = 10;
                } else if (position.includes(' I')) {
                    minGL = 8; maxGL = 9;
                } else if (position.includes(' II')) {
                    minGL = 6; maxGL = 7;
                } else if (['Messenger', 'Cleaner', 'Driver', 'Security Officer'].includes(position)) {
                    minGL = 1; maxGL = 5;
                } else {
                    minGL = 1; maxGL = 15;
                }
            }
            
            for (let i = minGL; i <= maxGL; i++) {
                const val = i.toString().padStart(2, '0');
                const option = document.createElement('option');
                option.value = val;
                option.text = val;
                if (val === currentVal) option.selected = true;
                glSelect.appendChild(option);
            }
        }
        
        // Add listener to position select
        if (document.getElementsByName('position')[0]) {
            document.getElementsByName('position')[0].addEventListener('change', updateGrades);
        }
        
        // Initialize grades on load
        if (document.getElementById('salary_structure')) {
            updateGrades();
        }

        // Faculty and Department filtering
        const faculties = <?php echo json_encode($faculties); ?>;

        function updateDepartments() {
            const facultySelect = document.querySelector('select[name="faculty"]');
            const departmentSelect = document.getElementById('department');
            const selectedFaculty = facultySelect.value;

            // Clear current options except first
            departmentSelect.innerHTML = '<option value="">Select Department</option>';

            if (selectedFaculty && faculties[selectedFaculty]) {
                faculties[selectedFaculty].forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept;
                    option.textContent = dept;
                    // Preserve selected if matches
                    if (dept === '<?php echo addslashes($employment['department'] ?? ''); ?>') {
                        option.selected = true;
                    }
                    departmentSelect.appendChild(option);
                });
            }
        }

        // Add event listener for faculty change
        document.addEventListener('DOMContentLoaded', function() {
            const facultySelect = document.querySelector('select[name="faculty"]');
            if (facultySelect) {
                facultySelect.addEventListener('change', updateDepartments);
                // Initial update on page load
                updateDepartments();
            }
        });
    </script>
</body>
</html>
