<?php
require_once 'includes/session.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'superadmin') {
        header('Location: superadmin_dashboard.php');
        exit;
    } elseif ($_SESSION['role'] === 'admin') {
        header('Location: admin_dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management System - Federal Polytechnic of Oil and Gas</title>
    <link rel="stylesheet" href="assets/css/output.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="overflow-hidden">
    <!-- Background Slideshow -->
    <div class="slideshow-container">
        <img src="assets/images/slideshow/slide1.svg" class="slideshow-image active" alt="Slide 1">
        <img src="assets/images/slideshow/slide2.svg" class="slideshow-image" alt="Slide 2">
        <img src="assets/images/slideshow/slide3.svg" class="slideshow-image" alt="Slide 3">
        <img src="assets/images/slideshow/slide4.svg" class="slideshow-image" alt="Slide 4">
        <img src="assets/images/slideshow/slide5.svg" class="slideshow-image" alt="Slide 5">
    </div>
    
    <!-- Dark Blue Overlay -->
    <div class="overlay"></div>
    
    <!-- Main Content -->
    <div class="relative z-10 flex flex-col items-center justify-center min-h-screen px-4">
        <!-- School Logo -->
        <div class="mb-6 animate-fade-in">
            <img src="assets/images/logo.png" alt="Federal Polytechnic of Oil and Gas Logo" class="w-32 h-32 md:w-40 md:h-40 object-contain mx-auto">
        </div>
        
        <!-- School Name -->
        <h1 class="text-white text-3xl md:text-4xl lg:text-5xl font-bold text-center mb-4 animate-fade-in">
            Federal Polytechnic of Oil and Gas
        </h1>
        
        <!-- System Title -->
        <h2 class="text-white text-xl md:text-2xl lg:text-3xl font-semibold text-center mb-12 animate-fade-in">
            Staff Management System
        </h2>
        
        <!-- Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 animate-fade-in">
            <button id="loginBtn" class="btn-primary flex items-center gap-2">
                <i class="fas fa-sign-in-alt"></i>
                Login
            </button>
            <button id="registerBtn" class="btn-secondary flex items-center gap-2">
                <i class="fas fa-user-plus"></i>
                Register
            </button>
        </div>
    </div>
    
    <!-- Login Dialog -->
    <div id="loginDialog" style="display: none;">
        <div class="text-center py-6">
            <h3 class="text-xl font-semibold mb-6 text-gray-800">Select Login Type</h3>
            <div class="flex flex-col gap-4">
                <a href="admin_login.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-6 rounded-lg transition duration-300 flex items-center justify-center gap-3">
                    <i class="fas fa-user-tie text-2xl"></i>
                    <span>Login as Admin User</span>
                </a>
                <a href="superadmin_login.php" class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-4 px-6 rounded-lg transition duration-300 flex items-center justify-center gap-3">
                    <i class="fas fa-user-shield text-2xl"></i>
                    <span>Login as Super Admin</span>
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="assets/js/slideshow.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
