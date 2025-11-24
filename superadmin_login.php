<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            $db = Database::getInstance();
            $user = $db->fetchOne(
                "SELECT * FROM users WHERE (username = ? OR email = ?) AND role = 'superadmin'",
                [$username, $username]
            );
            
            if ($user && verifyPassword($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = 'superadmin';
                $_SESSION['firstname'] = $user['firstname'];
                $_SESSION['lastname'] = $user['lastname'];
                $_SESSION['fullname'] = $user['firstname'] . ' ' . $user['lastname'];
                $_SESSION['profile_picture'] = $user['profile_picture'];
                
                redirect('superadmin_dashboard.php');
            } else {
                $error = 'Invalid username or password';
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
    <title>Super Admin Login - Staff Management System</title>
    <link rel="stylesheet" href="assets/css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-indigo-600 to-purple-800 min-h-screen flex items-center justify-center px-4">
    <div class="card max-w-md w-full">
        <div class="text-center mb-8">
            <div class="mb-4">
                <i class="fas fa-user-shield text-6xl text-indigo-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Super Admin Login</h1>
            <p class="text-gray-600">Federal Polytechnic of Oil and Gas</p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-6">
                <label for="username" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-user mr-2"></i>Username or Email
                </label>
                <input type="text" id="username" name="username" class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-white text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm hover:shadow-md transition-all duration-200" placeholder="Enter your username or email" required>
            </div>
            
            <div class="mb-6">
                <label for="password" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-lock mr-2"></i>Password
                </label>
                <div class="flex gap-0 border border-gray-300 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow focus-within:ring-2 focus-within:ring-indigo-500">
                    <input type="password" id="password" name="password" class="flex-1 px-4 py-3 bg-white text-gray-800 placeholder-gray-400 focus:outline-none" placeholder="Enter your password" required>
                    <button type="button" tabindex="-1" class="px-4 bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-600 hover:text-indigo-700 hover:from-indigo-100 hover:to-purple-100 transition-all duration-200 border-l border-gray-200 flex items-center justify-center" onclick="togglePasswordVisibility('password')">
                        <i class="fas fa-eye text-lg" id="passwordToggle"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 transform hover:-translate-y-0.5 mb-4">
                <i class="fas fa-sign-in-alt mr-2"></i>Login
            </button>
            
            <div class="text-center">
                <a href="index.php" class="text-indigo-600 hover:text-indigo-800 transition duration-200 font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Home
                </a>
            </div>
        </form>
    </div>
    <script>
    function togglePasswordVisibility(fieldId) {
        const passwordField = document.getElementById(fieldId);
        const toggleIcon = document.getElementById(fieldId + 'Toggle');
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }
    </script>
</body>
</html>
