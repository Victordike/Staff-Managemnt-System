<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = sanitize($_POST['firstname'] ?? '');
    $lastname = sanitize($_POST['lastname'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($firstname) || empty($lastname) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        try {
            $db = Database::getInstance();
            
            // Check if username already exists
            $existing_user = $db->fetchOne(
                "SELECT id FROM users WHERE username = ? OR email = ?",
                [$username, $email]
            );
            
            if ($existing_user) {
                $error = 'Username or email already exists';
            } else {
                // Hash password and insert
                $hashed_password = hashPassword($password);
                
                $db->query(
                    "INSERT INTO users (firstname, lastname, username, email, password, role) VALUES (?, ?, ?, ?, ?, 'superadmin')",
                    [$firstname, $lastname, $username, $email, $hashed_password]
                );
                
                $success = 'Registration successful! You can now login.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again.';
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
    <title>Super Admin Registration - Staff Management System</title>
    <link rel="stylesheet" href="assets/css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-indigo-600 to-purple-800 min-h-screen flex items-center justify-center px-4 py-8">
    <div class="card max-w-md w-full">
        <div class="text-center mb-8">
            <div class="mb-4">
                <i class="fas fa-user-shield text-6xl text-indigo-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Super Admin Registration</h1>
            <p class="text-gray-600">Federal Polytechnic of Oil and Gas</p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <div class="text-center mt-6">
                <a href="superadmin_login.php" class="btn-primary inline-block">
                    <i class="fas fa-sign-in-alt mr-2"></i>Go to Login
                </a>
            </div>
        <?php else: ?>
        
        <form method="POST" action="">
            <div class="mb-4">
                <label for="firstname" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-user mr-2"></i>First Name
                </label>
                <input type="text" id="firstname" name="firstname" class="input-field" placeholder="Enter first name" required>
            </div>
            
            <div class="mb-4">
                <label for="lastname" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-user mr-2"></i>Last Name
                </label>
                <input type="text" id="lastname" name="lastname" class="input-field" placeholder="Enter last name" required>
            </div>
            
            <div class="mb-4">
                <label for="username" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-at mr-2"></i>Username
                </label>
                <input type="text" id="username" name="username" class="input-field" placeholder="Enter username" required>
            </div>
            
            <div class="mb-4">
                <label for="email" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-envelope mr-2"></i>Email
                </label>
                <input type="email" id="email" name="email" class="input-field" placeholder="Enter email address" required>
            </div>
            
            <div class="mb-4">
                <label for="password" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-lock mr-2"></i>Password
                </label>
                <input type="password" id="password" name="password" class="input-field" placeholder="Enter password" required minlength="6">
            </div>
            
            <div class="mb-6">
                <label for="confirm_password" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-lock mr-2"></i>Confirm Password
                </label>
                <input type="password" id="confirm_password" name="confirm_password" class="input-field" placeholder="Confirm password" required minlength="6">
            </div>
            
            <button type="submit" class="w-full btn-primary mb-4">
                <i class="fas fa-user-plus mr-2"></i>Create Account
            </button>
            
            <div class="text-center text-sm">
                <p class="text-gray-600 mb-2">Already have an account?</p>
                <a href="superadmin_login.php" class="text-blue-600 hover:text-blue-800 transition duration-200">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login here
                </a>
            </div>
            
            <div class="text-center mt-4">
                <a href="index.php" class="text-gray-600 hover:text-gray-800 transition duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Home
                </a>
            </div>
        </form>
        
        <?php endif; ?>
    </div>
</body>
</html>
