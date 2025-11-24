<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['pre_user'])) {
    header('Location: register.php');
    exit;
}

$preUser = $_SESSION['pre_user'];
$currentStep = isset($_SESSION['registration_step']) ? intval($_SESSION['registration_step']) : 1;
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

// Load existing registration data from database
$existingData = null;
try {
    $db = Database::getInstance();
    $existingData = $db->fetchOne(
        "SELECT * FROM admin_users WHERE staff_id = ?",
        [$preUser['staff_id']]
    );
} catch (Exception $e) {
    // User record doesn't exist yet - that's fine
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance();
        
        // Determine action
        if (isset($_POST['save_continue'])) {
            // Validate and save current step
            switch ($currentStep) {
                case 1:
                    // Personal Data validation
                    if (empty($_POST['dob']) || empty($_POST['sex']) || empty($_POST['marital_status']) || empty($_POST['address']) || empty($_POST['lga'])) {
                        $error = 'Please fill in all personal data fields';
                        break;
                    }
                    
                    // Save/Update personal data to database
                    $personal_data = [
                        'dob' => sanitize($_POST['dob']),
                        'sex' => sanitize($_POST['sex']),
                        'marital_status' => sanitize($_POST['marital_status']),
                        'address' => sanitize($_POST['address']),
                        'lga' => sanitize($_POST['lga']),
                    ];
                    
                    if ($existingData) {
                        // Update existing record
                        $db->query(
                            "UPDATE admin_users SET date_of_birth = ?, sex = ?, marital_status = ?, permanent_home_address = ?, lga_origin = ? WHERE staff_id = ?",
                            [$personal_data['dob'], $personal_data['sex'], $personal_data['marital_status'], $personal_data['address'], $personal_data['lga'], $preUser['staff_id']]
                        );
                    } else {
                        // Create new record with personal data
                        $db->query(
                            "INSERT INTO admin_users (staff_id, surname, firstname, date_of_birth, sex, marital_status, permanent_home_address, lga_origin, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [$preUser['staff_id'], $preUser['surname'], $preUser['firstname'], $personal_data['dob'], $personal_data['sex'], $personal_data['marital_status'], $personal_data['address'], $personal_data['lga'], false]
                        );
                        $existingData = $db->fetchOne("SELECT * FROM admin_users WHERE staff_id = ?", [$preUser['staff_id']]);
                    }
                    
                    $_SESSION['registration_step'] = 2;
                    $success = 'Personal data saved! Proceed to next section.';
                    header('Refresh: 2; url=register_step.php');
                    break;
                    
                case 2:
                    // Employment Information validation
                    if (empty($_POST['department']) || empty($_POST['position']) || empty($_POST['employment_type']) || empty($_POST['assumption_date']) || empty($_POST['cadre']) || empty($_POST['phone']) || empty($_POST['email'])) {
                        $error = 'Please fill in all employment information fields';
                        break;
                    }
                    
                    // Update employment data to database
                    $db->query(
                        "UPDATE admin_users SET department = ?, position = ?, type_of_employment = ?, date_of_assumption = ?, cadre = ?, phone_number = ?, official_email = ? WHERE staff_id = ?",
                        [sanitize($_POST['department']), sanitize($_POST['position']), sanitize($_POST['employment_type']), sanitize($_POST['assumption_date']), sanitize($_POST['cadre']), sanitize($_POST['phone']), sanitize($_POST['email']), $preUser['staff_id']]
                    );
                    
                    $_SESSION['registration_step'] = 3;
                    $success = 'Employment information saved! Proceed to next section.';
                    header('Refresh: 2; url=register_step.php');
                    break;
                    
                case 3:
                    // Banking Details validation
                    if (empty($_POST['bank_name']) || empty($_POST['account_name']) || empty($_POST['account_number']) || empty($_POST['pfa_name']) || empty($_POST['pfa_pin'])) {
                        $error = 'Please fill in all banking details fields';
                        break;
                    }
                    
                    // Update banking data to database
                    $db->query(
                        "UPDATE admin_users SET bank_name = ?, account_name = ?, account_number = ?, pfa_name = ?, pfa_pin = ? WHERE staff_id = ?",
                        [sanitize($_POST['bank_name']), sanitize($_POST['account_name']), sanitize($_POST['account_number']), sanitize($_POST['pfa_name']), sanitize($_POST['pfa_pin']), $preUser['staff_id']]
                    );
                    
                    $_SESSION['registration_step'] = 4;
                    $success = 'Banking details saved! Proceed to next section.';
                    header('Refresh: 2; url=register_step.php');
                    break;
                    
                case 4:
                    // NOK Details validation
                    if (empty($_POST['nok_name']) || empty($_POST['nok_phone']) || empty($_POST['nok_relationship']) || empty($_POST['nok_address'])) {
                        $error = 'Please fill in all NOK details fields';
                        break;
                    }
                    
                    // Update NOK data to database
                    $db->query(
                        "UPDATE admin_users SET nok_fullname = ?, nok_phone_number = ?, nok_relationship = ?, nok_address = ? WHERE staff_id = ?",
                        [sanitize($_POST['nok_name']), sanitize($_POST['nok_phone']), sanitize($_POST['nok_relationship']), sanitize($_POST['nok_address']), $preUser['staff_id']]
                    );
                    
                    $_SESSION['registration_step'] = 5;
                    $success = 'NOK details saved! Complete your registration.';
                    header('Refresh: 2; url=register_step.php');
                    break;
                    
                case 5:
                    // Final submission - complete registration
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
                    
                    $profilePicture = $existingData['profile_picture'] ?? null;
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
                    
                    // Complete registration - update password and set active
                    $db->query(
                        "UPDATE admin_users SET password = ?, profile_picture = ?, is_active = ? WHERE staff_id = ?",
                        [hashPassword($_POST['password']), $profilePicture, true, $preUser['staff_id']]
                    );
                    
                    // Cleanup session
                    unset($_SESSION['pre_user']);
                    unset($_SESSION['registration_step']);
                    
                    $success = 'Registration completed successfully!';
                    header('Refresh: 2; url=admin_login.php');
                    break;
            }
        } elseif (isset($_POST['prev_step'])) {
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
                                <label class="block text-gray-700 font-semibold mb-2">Date of Birth *</label>
                                <input type="date" name="dob" class="input-field" value="<?php echo htmlspecialchars($existingData['date_of_birth'] ?? ''); ?>" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Sex *</label>
                                <select name="sex" class="input-field" required>
                                    <option value="">Select Sex</option>
                                    <option value="Male" <?php echo ($existingData['sex'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($existingData['sex'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Marital Status *</label>
                                <select name="marital_status" class="input-field" required>
                                    <option value="">Select Status</option>
                                    <option value="Single" <?php echo ($existingData['marital_status'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                                    <option value="Married" <?php echo ($existingData['marital_status'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                                    <option value="Divorced" <?php echo ($existingData['marital_status'] ?? '') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                    <option value="Widowed" <?php echo ($existingData['marital_status'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">LGA of Origin *</label>
                                <input type="text" name="lga" class="input-field" value="<?php echo htmlspecialchars($existingData['lga_origin'] ?? ''); ?>" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-2">Home Address *</label>
                                <textarea name="address" class="input-field" rows="3" required><?php echo htmlspecialchars($existingData['permanent_home_address'] ?? ''); ?></textarea>
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
                                <label class="block text-gray-700 font-semibold mb-2">Department *</label>
                                <input type="text" name="department" class="input-field" value="<?php echo htmlspecialchars($existingData['department'] ?? ''); ?>" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Position *</label>
                                <input type="text" name="position" class="input-field" value="<?php echo htmlspecialchars($existingData['position'] ?? ''); ?>" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Employment Type *</label>
                                <select name="employment_type" class="input-field" required>
                                    <option value="">Select Type</option>
                                    <option value="Permanent" <?php echo ($existingData['type_of_employment'] ?? '') === 'Permanent' ? 'selected' : ''; ?>>Permanent</option>
                                    <option value="Contract" <?php echo ($existingData['type_of_employment'] ?? '') === 'Contract' ? 'selected' : ''; ?>>Contract</option>
                                    <option value="Temporary" <?php echo ($existingData['type_of_employment'] ?? '') === 'Temporary' ? 'selected' : ''; ?>>Temporary</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Date of Assumption *</label>
                                <input type="date" name="assumption_date" class="input-field" value="<?php echo htmlspecialchars($existingData['date_of_assumption'] ?? ''); ?>" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Cadre *</label>
                                <input type="text" name="cadre" class="input-field" value="<?php echo htmlspecialchars($existingData['cadre'] ?? ''); ?>" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Phone Number *</label>
                                <input type="tel" name="phone" class="input-field" value="<?php echo htmlspecialchars($existingData['phone_number'] ?? ''); ?>" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-2">Official Email *</label>
                                <input type="email" name="email" class="input-field" value="<?php echo htmlspecialchars($existingData['official_email'] ?? ''); ?>" required>
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
                                <label class="block text-gray-700 font-semibold mb-2">Bank Name *</label>
                                <input type="text" name="bank_name" class="input-field" value="<?php echo htmlspecialchars($existingData['bank_name'] ?? ''); ?>" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Account Name *</label>
                                <input type="text" name="account_name" class="input-field" value="<?php echo htmlspecialchars($existingData['account_name'] ?? ''); ?>" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-2">Account Number *</label>
                                <input type="text" name="account_number" class="input-field" value="<?php echo htmlspecialchars($existingData['account_number'] ?? ''); ?>" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">PFA Name *</label>
                                <input type="text" name="pfa_name" class="input-field" value="<?php echo htmlspecialchars($existingData['pfa_name'] ?? ''); ?>" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">PFA Pin *</label>
                                <input type="text" name="pfa_pin" class="input-field" value="<?php echo htmlspecialchars($existingData['pfa_pin'] ?? ''); ?>" required>
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
                                <label class="block text-gray-700 font-semibold mb-2">NOK Full Name *</label>
                                <input type="text" name="nok_name" class="input-field" value="<?php echo htmlspecialchars($existingData['nok_fullname'] ?? ''); ?>" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">NOK Phone Number *</label>
                                <input type="tel" name="nok_phone" class="input-field" value="<?php echo htmlspecialchars($existingData['nok_phone_number'] ?? ''); ?>" required>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Relationship *</label>
                                <select name="nok_relationship" class="input-field" required>
                                    <option value="">Select Relationship</option>
                                    <option value="Spouse" <?php echo ($existingData['nok_relationship'] ?? '') === 'Spouse' ? 'selected' : ''; ?>>Spouse</option>
                                    <option value="Parent" <?php echo ($existingData['nok_relationship'] ?? '') === 'Parent' ? 'selected' : ''; ?>>Parent</option>
                                    <option value="Child" <?php echo ($existingData['nok_relationship'] ?? '') === 'Child' ? 'selected' : ''; ?>>Child</option>
                                    <option value="Sibling" <?php echo ($existingData['nok_relationship'] ?? '') === 'Sibling' ? 'selected' : ''; ?>>Sibling</option>
                                    <option value="Other" <?php echo ($existingData['nok_relationship'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-gray-700 font-semibold mb-2">NOK Address *</label>
                                <textarea name="nok_address" class="input-field" rows="3" required><?php echo htmlspecialchars($existingData['nok_address'] ?? ''); ?></textarea>
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
                                <input type="file" name="profile_picture" class="input-field" accept="image/*">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Navigation Buttons -->
                <div class="flex gap-4 pt-6 border-t">
                    <?php if ($currentStep > 1): ?>
                        <button type="submit" name="prev_step" class="btn-secondary flex-1" formnovalidate>
                            <i class="fas fa-arrow-left mr-2"></i>Previous
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($currentStep < count($steps)): ?>
                        <button type="submit" name="save_continue" class="btn-primary flex-1">
                            Save & Continue <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    <?php else: ?>
                        <button type="submit" name="save_continue" class="btn-primary flex-1">
                            <i class="fas fa-check-circle mr-2"></i>Complete Registration
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
