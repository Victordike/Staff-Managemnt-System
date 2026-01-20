<?php
require_once 'includes/session.php';

$error = '';
if (isset($_GET['error']) && $_GET['error'] === 'session_expired') {
    $error = 'Your session has expired or the database was reset. Please verify your details again.';
}
$step = 1;

// Handle pre-verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    require_once 'includes/db.php';
    require_once 'includes/functions.php';
    
    $staffId = sanitize($_POST['staff_id'] ?? '');
    $firstname = sanitize($_POST['firstname'] ?? '');
    $lastname = sanitize($_POST['lastname'] ?? '');
    
    if (empty($staffId) || empty($firstname) || empty($lastname)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            $db = Database::getInstance();
            
            // Check if already registered
            $existing = $db->fetchOne(
                "SELECT id FROM admin_users WHERE staff_id = ?",
                [$staffId]
            );
            
            if ($existing) {
                $error = 'This Staff ID has already been registered. Please login instead.';
            } else {
                // Verify against pre_users
                $preUser = $db->fetchOne(
                    "SELECT * FROM pre_users WHERE TRIM(LOWER(staff_id)) = TRIM(LOWER(?)) AND TRIM(LOWER(firstname)) = TRIM(LOWER(?)) AND TRIM(LOWER(surname)) = TRIM(LOWER(?))",
                    [$staffId, $firstname, $lastname]
                );
                
                if ($preUser) {
                    // Store pre-user data in session
                    $_SESSION['pre_user'] = $preUser;
                    $_SESSION['registration_step'] = 1;
                    header('Location: register_step.php');
                    exit;
                } else {
                    $error = 'Verification failed. Please ensure your Staff ID, First Name, and Last Name match the records uploaded by the administrator.';
                }
            }
        } catch (Exception $e) {
            $error = 'An error occurred during verification. Please try again.';
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Registration - Pre-Verification</title>
    <link rel="stylesheet" href="assets/css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-500 to-indigo-700 min-h-screen flex items-center justify-center px-4">
    <div class="card max-w-lg w-full">
        <div class="text-center mb-8">
            <div class="mb-4">
                <i class="fas fa-user-check text-6xl text-blue-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Staff Registration</h1>
            <p class="text-gray-600">Federal Polytechnic of Oil and Gas</p>
            <p class="text-sm text-gray-500 mt-2">Step 1: Pre-Verification</p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-blue-800 mb-2">
                <i class="fas fa-info-circle mr-2"></i>Pre-Verification Required
            </h3>
            <p class="text-sm text-blue-700">
                To register, please enter your Staff ID, First Name, and Last Name exactly as they appear in the records uploaded by the administrator.
            </p>
        </div>
        
        <form method="POST" action="">
            <div class="mb-6">
                <label for="staff_id" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-id-badge mr-2"></i>Staff ID
                </label>
                <input type="text" id="staff_id" name="staff_id" class="input-field" placeholder="Enter your Staff ID" required value="<?php echo htmlspecialchars($_POST['staff_id'] ?? ''); ?>">
            </div>
            
            <div class="mb-6">
                <label for="firstname" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-user mr-2"></i>First Name
                </label>
                <input type="text" id="firstname" name="firstname" class="input-field" placeholder="Enter your First Name" required value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>">
            </div>
            
            <div class="mb-6">
                <label for="lastname" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-user mr-2"></i>Last Name (Surname)
                </label>
                <input type="text" id="lastname" name="lastname" class="input-field" placeholder="Enter your Last Name" required value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>">
            </div>
            
            <button type="submit" name="verify" class="w-full btn-primary mb-4">
                <i class="fas fa-check-circle mr-2"></i>Verify & Continue
            </button>
            
            <div class="text-center">
                <a href="index.php" class="text-blue-600 hover:text-blue-800 transition duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Home
                </a>
            </div>
        </form>
    </div>
</body>
</html>
