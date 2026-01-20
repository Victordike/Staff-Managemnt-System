<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/nigeria_lga.php';

if (!isset($_SESSION['pre_user'])) {
    header('Location: register.php');
    exit;
}

$preUser = $_SESSION['pre_user'];
$currentStep = $_SESSION['registration_step'] ?? 2;
$error = '';
$success = '';

$departments = [
    'Academic' => [
        'School of Business Studies and Petroleum Marketing' => ['Accountancy', 'Business Administration & Management', 'Maritime Transport & Business Studies', 'Petroleum Marketing & Business Studies', 'Public Administration'],
        'School of Applied Science' => ['Computer Science', 'Library & Information Science', 'Science Laboratory Technology', 'Mathematics & Statistics'],
        'School of Engineering and Technology' => ['Chemical Engineering Technology', 'Electrical/Electronic Engineering Technology', 'Industrial Safety & Environmental Engineering Technology', 'Mechanical Engineering Technology', 'Mineral & Petroleum Resources Engineering Technology', 'Welding & Fabrication Engineering Technology'],
        'School of General Studies' => ['Humanities/Social Sciences', 'Languages and Communication'],
        'School of Continuing Education' => ['Continuing Education Programs']
    ],
    'Non-Academic' => [
        'Registrar Office' => ['Assistant Registrar', 'Registry Officer', 'Record Officer', 'Filing Clerk'],
        'Bursar Office' => ['Senior Accountant', 'Accountant', 'Accounts Officer', 'Cashier', 'Accounts Clerk'],
        'Human Resources' => ['HR Officer', 'Personnel Assistant', 'Salary Officer'],
        'Establishment Unit' => ['Director of Establishment', 'Establishment Officer', 'Establishment Assistant'],
        'ICT/Computing Services' => ['IT Director', 'Systems Administrator', 'Network Technician', 'IT Technician', 'Database Administrator', 'Web Developer'],
        'Maintenance & Engineering' => ['Chief Technician', 'Senior Technician', 'Technician Grade I', 'Technician Grade II', 'Maintenance Worker'],
        'Library Services' => ['Head Librarian', 'Senior Librarian', 'Librarian', 'Library Assistant', 'Library Attendant'],
        'Security Services' => ['Head of Security', 'Senior Security Officer', 'Security Officer Grade I', 'Security Officer Grade II', 'Night Guard'],
        'Cleaning & Sanitation' => ['Sanitation Supervisor', 'Senior Cleaner', 'Cleaner Grade I', 'Cleaner Grade II', 'Porter'],
        'Transportation & Logistics' => ['Transport Officer', 'Senior Driver', 'Driver Grade I', 'Driver Grade II', 'Fleet Mechanic'],
        'Health Services' => ['Chief Medical Officer', 'Medical Doctor', 'Nurse Grade I', 'Nurse Grade II', 'Health Assistant'],
        'Quality Assurance Unit' => ['QA Officer'],
        'Research & Innovation' => ['Research Officer', 'Research Coordinator'],
        'Rector Office (Support)' => ['Chief of Staff', 'Senior Secretary', 'Secretary', 'Typist', 'Protocol Officer'],
    ]
];

$academic_positions = [
    'Professor' => 'GL 17-18',
    'Associate Professor' => 'GL 16-17',
    'Senior Lecturer' => 'GL 14-15',
    'Lecturer I' => 'GL 12-13',
    'Lecturer II' => 'GL 10-11',
    'Assistant Lecturer' => 'GL 08-09',
    'Graduate Assistant' => 'GL 06-07'
];

$non_academic_grades = [
    'GL 17-18' => 'GL 17-18 (₦400,000 - ₦550,000)',
    'GL 15-16' => 'GL 15-16 (₦280,000 - ₦380,000)',
    'GL 13-14' => 'GL 13-14 (₦200,000 - ₦280,000)',
    'GL 11-12' => 'GL 11-12 (₦140,000 - ₦200,000)',
    'GL 09-10' => 'GL 09-10 (₦100,000 - ₦140,000)',
    'GL 07-08' => 'GL 07-08 (₦70,000 - ₦100,000)',
    'GL 05-06' => 'GL 05-06 (₦50,000 - ₦70,000)',
    'GL 03-04' => 'GL 03-04 (₦35,000 - ₦50,000)'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['complete_registration'])) {
        // Validate all required fields
        $required_fields = ['staff_category', 'school_department', 'position', 'dob', 'sex', 'marital_status', 'address', 'lga',
                           'employment_type', 'assumption_date', 'phone', 'email', 
                           'bank_name', 'account_name', 'account_number', 'pfa_name', 'pfa_pin',
                           'nok_name', 'nok_phone', 'nok_relationship', 'nok_address', 'password', 'confirm_password'];
        
        if ($_POST['staff_category'] === 'Non-Academic') {
            $required_fields[] = 'salary_grade';
        }
        
        $missing = [];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing[] = $field;
            }
        }
        
        // Additional validation checks
        $validation_errors = [];
        
        if (!empty($_POST['dob'])) {
            $dob = strtotime($_POST['dob']);
            $age = (int)((time() - $dob) / (365.25 * 24 * 3600));
            if ($age < 18) {
                $validation_errors[] = 'Staff must be at least 18 years old';
            }
        }
        
        if (!empty($_POST['phone'])) {
            if (!preg_match('/^[\d+\-\s()]+$/', $_POST['phone']) || strlen(preg_replace('/\D/', '', $_POST['phone'])) < 10) {
                $validation_errors[] = 'Invalid phone number format';
            }
        }
        
        if (!empty($_POST['email'])) {
            if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $validation_errors[] = 'Invalid email address';
            }
        }
        
        if (!empty($_POST['account_number'])) {
            if (!preg_match('/^\d{10,}$/', preg_replace('/\D/', '', $_POST['account_number']))) {
                $validation_errors[] = 'Invalid bank account number (must be numeric)';
            }
        }
        
        if (!empty($_POST['assumption_date'])) {
            $assumption = strtotime($_POST['assumption_date']);
            if ($assumption > time()) {
                $validation_errors[] = 'Date of assumption cannot be in the future';
            }
        }
        
        $profilePicture = null;
        
        // Handle file upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['profile_picture']['type'], $allowed)) {
                $error = 'Only JPG, PNG, and GIF files are allowed';
            } elseif ($_FILES['profile_picture']['size'] > $max_size) {
                $error = 'File size must not exceed 5MB';
            } else {
                $filename = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
                $upload_path = 'uploads/passport/' . $filename;
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                    $profilePicture = $upload_path;
                }
            }
        }
        
        if (!empty($missing)) {
            $error = 'Please fill in all required fields: ' . implode(', ', array_slice($missing, 0, 3)) . (count($missing) > 3 ? '...' : '');
        } elseif (!empty($validation_errors)) {
            $error = implode('<br>', $validation_errors);
        } elseif ($_POST['password'] !== $_POST['confirm_password']) {
            $error = 'Passwords do not match';
        } elseif (strlen($_POST['password']) < 8) {
            $error = 'Password must be at least 8 characters';
        } else {
            try {
                $db = Database::getInstance();
                
                // Determine GL/Salary Grade
                $staff_category = sanitize($_POST['staff_category']);
                $position = sanitize($_POST['position']);
                $salary_grade = '';
                
                if ($staff_category === 'Academic') {
                    $salary_grade = $academic_positions[$position] ?? 'GL 06-07';
                } else {
                    $salary_grade = sanitize($_POST['salary_grade'] ?? 'GL 07-08');
                }
                
                // Insert admin user
                $db->query(
                    "INSERT INTO admin_users (
                        staff_id, surname, firstname,
                        date_of_birth, sex, marital_status, permanent_home_address, lga_origin,
                        staff_category, department, position, type_of_employment, date_of_assumption,
                        salary_grade,
                        phone_number, official_email,
                        bank_name, account_name, account_number, pfa_name, pfa_pin,
                        nok_fullname, nok_phone_number, nok_relationship, nok_address,
                        password, is_active, profile_picture
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $preUser['staff_id'],
                        $preUser['surname'],
                        $preUser['firstname'],
                        sanitize($_POST['dob']),
                        sanitize($_POST['sex']),
                        sanitize($_POST['marital_status']),
                        sanitize($_POST['address']),
                        sanitize($_POST['lga']),
                        $staff_category,
                        sanitize($_POST['school_department']),
                        $position,
                        sanitize($_POST['employment_type']),
                        sanitize($_POST['assumption_date']),
                        $salary_grade,
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
                        true,
                        $profilePicture
                    ]
                );
                
                // Mark as registered in pre_users
                $db->query(
                    "UPDATE pre_users SET verified = 1, verified_at = NOW() WHERE staff_id = ?",
                    [$preUser['staff_id']]
                );
                
                unset($_SESSION['pre_user']);
                unset($_SESSION['registration_step']);
                
                setFlashMessage('Registration completed successfully! You can now login.', 'success');
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
    <link rel="stylesheet" href="assets/css/loading.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
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
                <p class="loading-subtext">Processing<span class="loading-dots"></span></p>
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
            const mainContent = document.querySelector('.max-w-4xl.mx-auto');
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
    
    <div class="max-w-4xl mx-auto registration-container" style="opacity: 0; transition: opacity 0.3s ease-in;">
        <div class="card mb-6" style="display: flex; flex-direction: column; height: 100%; overflow: hidden;">
            <div class="text-center mb-6" style="flex-shrink: 0;">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Complete Your Registration</h1>
                <p class="text-gray-600">Federal Polytechnic of Oil and Gas</p>
                <p class="text-sm text-gray-500 mt-2">Welcome, <?php echo htmlspecialchars($preUser['firstname'] . ' ' . $preUser['surname']); ?>!</p>
            </div>
            
            <!-- Progress Steps -->
            <div class="flex justify-center items-center mb-8" style="flex-shrink: 0;">
                <div class="flex items-center gap-2">
                    <div class="step-indicator completed">1</div>
                    <div class="w-16 h-1 bg-green-600"></div>
                    <div class="step-indicator active">2</div>
                    <span class="ml-2 text-sm font-semibold text-gray-700">Complete Registration</span>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4" style="flex-shrink: 0;">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="registrationForm" class="space-y-8" style="display: flex; flex-direction: column; flex: 1; overflow: hidden;">
                
                <div class="form-scroll-container">
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
                            <select name="lga" class="input-field select2" required>
                                <option value="">Select LGA</option>
                                <?php 
                                $statesLgas = getNigerianStatesWithLGAs();
                                foreach ($statesLgas as $state => $lgas): 
                                ?>
                                    <optgroup label="<?php echo htmlspecialchars($state); ?>">
                                        <?php foreach ($lgas as $lga): ?>
                                            <option value="<?php echo htmlspecialchars($lga); ?>">
                                                <?php echo htmlspecialchars($lga); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
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
                            <label class="block text-gray-700 font-semibold mb-2">Staff Category *</label>
                            <select id="staffCategory" name="staff_category" class="input-field" onchange="updateDepartments()" required>
                                <option value="">-- Select Staff Category --</option>
                                <option value="Academic">Academic Staff</option>
                                <option value="Non-Academic">Non-Academic Staff</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">School/Department *</label>
                            <select id="schoolDept" name="school_department" class="input-field" onchange="updatePositions()" required>
                                <option value="">-- Select Department --</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Position *</label>
                            <select id="position" name="position" class="input-field" onchange="updateSalaryGrade()" required>
                                <option value="">-- Select Position --</option>
                            </select>
                        </div>
                        <div id="salaryGradeDiv" style="display:none;">
                            <label class="block text-gray-700 font-semibold mb-2">Salary Grade *</label>
                            <select id="salaryGrade" name="salary_grade" class="input-field">
                                <option value="">-- Select Salary Grade --</option>
                                <?php foreach ($non_academic_grades as $grade => $label): ?>
                                    <option value="<?php echo htmlspecialchars($grade); ?>"><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Type of Employment *</label>
                            <select name="employment_type" class="input-field" required>
                                <option value="">-- Select --</option>
                                <option value="Permanent">Permanent</option>
                                <option value="Contract">Contract</option>
                                <option value="Temporary">Temporary</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Date of Assumption of Duty *</label>
                            <input type="date" name="assumption_date" class="input-field" required>
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
                
                <!-- Section E: Photo/Passport Upload -->
                <div class="border-l-4 border-indigo-600 pl-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-image text-indigo-600 mr-2"></i>E. Passport/Photo
                    </h2>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Upload Passport/Photo (Optional)</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                            <p class="text-gray-600 mb-2">Click to select or drag and drop</p>
                            <input type="file" name="profile_picture" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                            <p class="text-xs text-gray-500 mt-2">JPG, PNG, or GIF (Max 5MB)</p>
                        </div>
                    </div>
                </div>
                
                <!-- Section F: Next of Kin -->
                <div class="border-l-4 border-red-600 pl-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-user-friends text-red-600 mr-2"></i>F. Next of Kin (NOK)
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
                </div>
                
                <!-- Submit Button -->
                <div class="flex gap-4" style="flex-shrink: 0;">
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
    
    <script>
        const departments = <?php echo json_encode($departments); ?>;
        const academicPositions = <?php echo json_encode($academic_positions); ?>;
        
        function updateDepartments() {
            const category = document.getElementById('staffCategory').value;
            const deptSelect = document.getElementById('schoolDept');
            const posSelect = document.getElementById('position');
            const salaryGradeDiv = document.getElementById('salaryGradeDiv');
            
            deptSelect.innerHTML = '<option value="">-- Select Department --</option>';
            posSelect.innerHTML = '<option value="">-- Select Position --</option>';
            
            if (category && departments[category]) {
                Object.keys(departments[category]).forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept;
                    option.textContent = dept;
                    deptSelect.appendChild(option);
                });
                
                if (category === 'Non-Academic') {
                    salaryGradeDiv.style.display = 'block';
                } else {
                    salaryGradeDiv.style.display = 'none';
                }
            }
        }
        
        function updatePositions() {
            const category = document.getElementById('staffCategory').value;
            const dept = document.getElementById('schoolDept').value;
            const posSelect = document.getElementById('position');
            
            posSelect.innerHTML = '<option value="">-- Select Position --</option>';
            
            if (category && dept && departments[category] && departments[category][dept]) {
                departments[category][dept].forEach(pos => {
                    const option = document.createElement('option');
                    option.value = pos;
                    option.textContent = pos;
                    posSelect.appendChild(option);
                });
            }
        }
        
        function updateSalaryGrade() {
            const category = document.getElementById('staffCategory').value;
            const position = document.getElementById('position').value;
            const salaryGradeSelect = document.getElementById('salaryGrade');
            
            if (category === 'Academic' && academicPositions[position]) {
                const glValue = academicPositions[position];
                salaryGradeSelect.value = glValue.split(' ')[1];
                salaryGradeSelect.disabled = true;
            } else if (category === 'Non-Academic') {
                salaryGradeSelect.disabled = false;
            }
        }
    </script>
</body>
</html>
