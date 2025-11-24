<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['pre_user'])) {
    header('Location: register.php');
    exit;
}

$preUser = $_SESSION['pre_user'];
$db = Database::getInstance();
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
    'dob' => $draft['date_of_birth'],
    'sex' => $draft['sex'],
    'marital_status' => $draft['marital_status'],
    'address' => $draft['permanent_home_address'],
    'lga' => $draft['lga_origin'],
] : []);

$employment = $_SESSION['employment_data'] ?? ($draft ? [
    'department' => $draft['department'],
    'position' => $draft['position'],
    'employment_type' => $draft['type_of_employment'],
    'assumption_date' => $draft['date_of_assumption'],
    'cadre' => $draft['cadre'],
    'phone' => $draft['phone_number'],
    'email' => $draft['official_email'],
] : []);

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
                        'dob' => sanitize($_POST['dob'] ?? ''),
                        'sex' => sanitize($_POST['sex'] ?? ''),
                        'marital_status' => sanitize($_POST['marital_status'] ?? ''),
                        'address' => sanitize($_POST['address'] ?? ''),
                        'lga' => sanitize($_POST['lga'] ?? ''),
                    ];
                    $_SESSION['personal_data'] = $personal;
                    
                    // Save to database
                    $existingDraft = $db->fetchOne("SELECT id FROM registration_draft WHERE staff_id = ?", [$preUser['staff_id']]);
                    if ($existingDraft) {
                        $db->query(
                            "UPDATE registration_draft SET date_of_birth = ?, sex = ?, marital_status = ?, permanent_home_address = ?, lga_origin = ?, current_step = ?, updated_at = CURRENT_TIMESTAMP WHERE staff_id = ?",
                            [$personal['dob'] ?: null, $personal['sex'] ?: null, $personal['marital_status'] ?: null, $personal['address'] ?: null, $personal['lga'] ?: null, $exitReg ? $currentStep : ($moveNext ? 2 : $currentStep), $preUser['staff_id']]
                        );
                    } else {
                        $db->query(
                            "INSERT INTO registration_draft (staff_id, surname, firstname, date_of_birth, sex, marital_status, permanent_home_address, lga_origin, current_step) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [$preUser['staff_id'], $preUser['surname'], $preUser['firstname'], $personal['dob'] ?: null, $personal['sex'] ?: null, $personal['marital_status'] ?: null, $personal['address'] ?: null, $personal['lga'] ?: null, $exitReg ? 1 : ($moveNext ? 2 : 1)]
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
                        'department' => sanitize($_POST['department'] ?? ''),
                        'position' => sanitize($_POST['position'] ?? ''),
                        'employment_type' => sanitize($_POST['employment_type'] ?? ''),
                        'assumption_date' => sanitize($_POST['assumption_date'] ?? ''),
                        'cadre' => sanitize($_POST['cadre'] ?? ''),
                        'phone' => sanitize($_POST['phone'] ?? ''),
                        'email' => sanitize($_POST['email'] ?? ''),
                    ];
                    $_SESSION['employment_data'] = $employment;
                    
                    $existingDraft = $db->fetchOne("SELECT id FROM registration_draft WHERE staff_id = ?", [$preUser['staff_id']]);
                    if ($existingDraft) {
                        $db->query(
                            "UPDATE registration_draft SET department = ?, position = ?, type_of_employment = ?, date_of_assumption = ?, cadre = ?, phone_number = ?, official_email = ?, current_step = ?, updated_at = CURRENT_TIMESTAMP WHERE staff_id = ?",
                            [$employment['department'] ?: null, $employment['position'] ?: null, $employment['employment_type'] ?: null, $employment['assumption_date'] ?: null, $employment['cadre'] ?: null, $employment['phone'] ?: null, $employment['email'] ?: null, $exitReg ? $currentStep : ($moveNext ? 3 : $currentStep), $preUser['staff_id']]
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
                    $allPersonalFilled = !empty($personal['dob']) && !empty($personal['sex']) && !empty($personal['marital_status']) && !empty($personal['address']) && !empty($personal['lga']);
                    $allEmploymentFilled = !empty($employment['department']) && !empty($employment['position']) && !empty($employment['employment_type']) && !empty($employment['assumption_date']) && !empty($employment['cadre']) && !empty($employment['phone']) && !empty($employment['email']);
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
                            staff_id, surname, firstname,
                            date_of_birth, sex, marital_status, permanent_home_address, lga_origin,
                            department, position, type_of_employment, date_of_assumption, cadre,
                            phone_number, official_email,
                            bank_name, account_name, account_number, pfa_name, pfa_pin,
                            nok_fullname, nok_phone_number, nok_relationship, nok_address,
                            password, profile_picture, is_active
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $preUser['staff_id'],
                            $preUser['surname'],
                            $preUser['firstname'],
                            $personal['dob'] ?? null,
                            $personal['sex'] ?? null,
                            $personal['marital_status'] ?? null,
                            $personal['address'] ?? null,
                            $personal['lga'] ?? null,
                            $employment['department'] ?? null,
                            $employment['position'] ?? null,
                            $employment['employment_type'] ?? null,
                            $employment['assumption_date'] ?? null,
                            $employment['cadre'] ?? null,
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
                            $profilePicture,
                            1
                        ]
                    );
                    
                    // Delete draft
                    $db->query("DELETE FROM registration_draft WHERE staff_id = ?", [$preUser['staff_id']]);
                    
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen py-8 px-4">
    <div class="max-w-2xl mx-auto">
        <div class="card">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Staff Registration</h1>
                <p class="text-gray-600">Federal Polytechnic of Oil and Gas</p>
            </div>
            
            <!-- Progress Steps -->
            <div class="mb-8">
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
            <div class="w-full bg-gray-300 rounded-full h-2 mb-8">
                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo ($currentStep / count($steps)) * 100; ?>%"></div>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <!-- Step 1: Personal Data -->
                <?php if ($currentStep == 1): ?>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-4"><i class="fas fa-user text-blue-600 mr-2"></i>Personal Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                <label class="block text-gray-700 font-semibold mb-2">LGA of Origin</label>
                                <input type="text" name="lga" class="input-field" value="<?php echo htmlspecialchars($personal['lga'] ?? ''); ?>">
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
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Department</label>
                                <input type="text" name="department" class="input-field" value="<?php echo htmlspecialchars($employment['department'] ?? ''); ?>">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Position</label>
                                <input type="text" name="position" class="input-field" value="<?php echo htmlspecialchars($employment['position'] ?? ''); ?>">
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
                                <input type="text" name="cadre" class="input-field" value="<?php echo htmlspecialchars($employment['cadre'] ?? ''); ?>">
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
                
                <!-- Navigation Buttons -->
                <div class="flex gap-2 pt-6 border-t flex-wrap">
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
    </script>
</body>
</html>
