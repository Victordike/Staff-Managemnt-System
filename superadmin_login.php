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
                <input type="text" id="username" name="username" class="input-field" placeholder="Enter your username or email" required>
            </div>
            
            <div class="mb-6">
                <label for="password" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-lock mr-2"></i>Password
                </label>
                <input type="password" id="password" name="password" class="input-field" placeholder="Enter your password" required>
            </div>
            
            <button type="submit" class="w-full btn-primary mb-4">
                <i class="fas fa-sign-in-alt mr-2"></i>Login
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
