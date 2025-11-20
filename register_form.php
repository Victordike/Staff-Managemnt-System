<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['pre_user'])) {
    header('Location: register.php');
    exit;
}

$preUser = $_SESSION['pre_user'];
$currentStep = $_SESSION['registration_step'] ?? 2;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['complete_registration'])) {
        // Validate all required fields
        $required_fields = ['dob', 'sex', 'marital_status', 'address', 'lga', 'department', 'position', 
                           'employment_type', 'assumption_date', 'cadre', 'phone', 'email', 
                           'bank_name', 'account_name', 'account_number', 'pfa_name', 'pfa_pin',
                           'nok_name', 'nok_phone', 'nok_relationship', 'nok_address', 'password', 'confirm_password'];
        
        $missing = [];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $error = 'Please fill in all required fields';
        } elseif ($_POST['password'] !== $_POST['confirm_password']) {
            $error = 'Passwords do not match';
        } elseif (strlen($_POST['password']) < 8) {
            $error = 'Password must be at least 8 characters';
        } else {
            try {
                $db = Database::getInstance();
                
                // Insert admin user
                $db->query(
                    "INSERT INTO admin_users (
                        staff_id, surname, firstname, othername,
                        date_of_birth, sex, marital_status, permanent_home_address, lga_origin,
                        department, position, type_of_employment, date_of_assumption, cadre,
                        salary_structure, gl, step, rank,
                        phone_number, official_email,
                        bank_name, account_name, account_number, pfa_name, pfa_pin,
                        nok_fullname, nok_phone_number, nok_relationship, nok_address,
                        password, is_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $preUser['staff_id'],
                        $preUser['surname'],
                        $preUser['firstname'],
                        $preUser['othername'],
                        sanitize($_POST['dob']),
                        sanitize($_POST['sex']),
                        sanitize($_POST['marital_status']),
                        sanitize($_POST['address']),
                        sanitize($_POST['lga']),
                        sanitize($_POST['department']),
                        sanitize($_POST['position']),
                        sanitize($_POST['employment_type']),
                        sanitize($_POST['assumption_date']),
                        sanitize($_POST['cadre']),
                        $preUser['salary_structure'],
                        $preUser['gl'],
                        $preUser['step'],
                        $preUser['rank'],
                        sanitize($_POST['phone']),
                        sanitize($_POST['email']),
                        sanitize($_POST['bank_name']),
                        sanitize($_POST['account_name']),
                        sanitize($_POST['account_number']),
                        sanitize($_POST['pfa_name']),
                        sanitize($_POST['pfa_pin']),
                        sanitize($_POST['nok_name']),
                        sanitize($_POST['nok_phone']),
                        sanitize($_POST['nok_relationship']),
                        sanitize($_POST['nok_address']),
                        hashPassword($_POST['password']),
                        true
                    ]
                );
                
                // Mark as registered in pre_users
                $db->query(
                    "UPDATE pre_users SET is_registered = TRUE WHERE staff_id = ?",
                    [$preUser['staff_id']]
                );
                
                unset($_SESSION['pre_user']);
                unset($_SESSION['registration_step']);
                
                setFlashMessage('success', 'Registration completed successfully! You can now login.');
                header('Location: admin_login.php');
                exit;
                
            } catch (Exception $e) {
                $error = 'Registration failed. Please try again.';
                error_log($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Registration</title>
    <link rel="stylesheet" href="assets/css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen py-8 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="card mb-6">
            <div class="text-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Complete Your Registration</h1>
                <p class="text-gray-600">Federal Polytechnic of Oil and Gas</p>
                <p class="text-sm text-gray-500 mt-2">Welcome, <?php echo htmlspecialchars($preUser['firstname'] . ' ' . $preUser['surname']); ?>!</p>
            </div>
            
            <!-- Progress Steps -->
            <div class="flex justify-center items-center mb-8">
                <div class="flex items-center gap-2">
                    <div class="step-indicator completed">1</div>
                    <div class="w-16 h-1 bg-green-600"></div>
                    <div class="step-indicator active">2</div>
                    <span class="ml-2 text-sm font-semibold text-gray-700">Complete Registration</span>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="registrationForm" class="space-y-8">
                <!-- Section A: Personal Details -->
                <div class="border-l-4 border-blue-600 pl-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-user text-blue-600 mr-2"></i>A. Personal Details
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Date of Birth *</label>
                            <input type="date" name="dob" class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Sex *</label>
                            <select name="sex" class="input-field" required>
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Marital Status *</label>
                            <select name="marital_status" class="input-field" required>
                                <option value="">Select</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Divorced">Divorced</option>
                                <option value="Widowed">Widowed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">LGA/Origin *</label>
                            <input type="text" name="lga" class="input-field" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-gray-700 font-semibold mb-2">Permanent Home Address *</label>
                            <textarea name="address" class="input-field" rows="2" required></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Section B: Employment Details -->
                <div class="border-l-4 border-green-600 pl-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-briefcase text-green-600 mr-2"></i>B. Employment Details
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Department *</label>
                            <input type="text" name="department" class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Position *</label>
                            <input type="text" name="position" class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Type of Employment *</label>
                            <select name="employment_type" class="input-field" required>
                                <option value="">Select</option>
                                <option value="Permanent">Permanent</option>
                                <option value="Contract">Contract</option>
                                <option value="Temporary">Temporary</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Date of Assumption of Duty *</label>
                            <input type="date" name="assumption_date" class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">CADRE *</label>
                            <input type="text" name="cadre" class="input-field" required>
                        </div>
                    </div>
                </div>
                
                <!-- Section C: Contact Details -->
                <div class="border-l-4 border-yellow-600 pl-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-phone text-yellow-600 mr-2"></i>C. Contact Details
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Phone Number *</label>
                            <input type="tel" name="phone" class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Official Email *</label>
                            <input type="email" name="email" class="input-field" required>
                        </div>
                    </div>
                </div>
                
                <!-- Section D: Bank/Finance Details -->
                <div class="border-l-4 border-purple-600 pl-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-university text-purple-600 mr-2"></i>D. Bank/Finance Details
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Bank Name *</label>
                            <input type="text" name="bank_name" class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Account Name *</label>
                            <input type="text" name="account_name" class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Account Number *</label>
                            <input type="text" name="account_number" class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">PFA Name *</label>
                            <input type="text" name="pfa_name" class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">PFA PIN *</label>
                            <input type="text" name="pfa_pin" class="input-field" required>
                        </div>
                    </div>
                </div>
                
                <!-- Section E: Next of Kin -->
                <div class="border-l-4 border-red-600 pl-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-user-friends text-red-600 mr-2"></i>E. Next of Kin (NOK)
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">NOK Full Name *</label>
                            <input type="text" name="nok_name" class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">NOK Phone Number *</label>
                            <input type="tel" name="nok_phone" class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">NOK Relationship *</label>
                            <input type="text" name="nok_relationship" class="input-field" placeholder="e.g., Spouse, Parent, Sibling" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-gray-700 font-semibold mb-2">NOK Address *</label>
                            <textarea name="nok_address" class="input-field" rows="2" required></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Password Section -->
                <div class="border-l-4 border-indigo-600 pl-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-lock text-indigo-600 mr-2"></i>Set Your Password
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Password *</label>
                            <input type="password" name="password" class="input-field" minlength="8" required>
                            <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Confirm Password *</label>
                            <input type="password" name="confirm_password" class="input-field" minlength="8" required>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="flex gap-4">
                    <button type="submit" name="complete_registration" class="flex-1 btn-primary">
                        <i class="fas fa-check-circle mr-2"></i>Complete Registration
                    </button>
                    <a href="index.php" class="flex-1 btn-secondary text-center">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
