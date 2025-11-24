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
    <style>
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 0 20px rgba(99, 102, 241, 0.5);
            }
            50% {
                box-shadow: 0 0 30px rgba(99, 102, 241, 0.8);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -1000px 0;
            }
            100% {
                background-position: 1000px 0;
            }
        }

        .animate-slide-in-down {
            animation: slideInDown 0.8s ease-out forwards;
        }

        .animate-slide-in-up {
            animation: slideInUp 0.8s ease-out forwards;
        }

        .animate-fade-in-scale {
            animation: fadeInScale 0.8s ease-out forwards;
        }

        .animate-pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
        .delay-5 { animation-delay: 0.5s; }

        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .feature-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-glow {
            position: relative;
            overflow: hidden;
        }

        .btn-glow::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-glow:hover::before {
            left: 100%;
        }

        .modal-overlay {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }
    </style>
</head>
<body class="bg-gray-900 overflow-x-hidden">
    <!-- Background Slideshow -->
    <div class="slideshow-container fixed inset-0 z-0">
        <img src="assets/images/slideshow/slide1.jpg" class="slideshow-image active w-full h-full object-cover" alt="Slide 1">
        <img src="assets/images/slideshow/slide2.jpg" class="slideshow-image w-full h-full object-cover" alt="Slide 2">
        <img src="assets/images/slideshow/slide3.jpg" class="slideshow-image w-full h-full object-cover" alt="Slide 3">
        <img src="assets/images/slideshow/slide4.jpg" class="slideshow-image w-full h-full object-cover" alt="Slide 4">
        <img src="assets/images/slideshow/slide5.jpg" class="slideshow-image w-full h-full object-cover" alt="Slide 5">
    </div>
    
    <!-- Dark Overlay with Gradient -->
    <div class="overlay fixed inset-0 z-1 bg-gradient-to-b from-black/60 via-black/70 to-black/80"></div>

    <!-- Navigation Bar -->
    <nav class="relative z-50 fixed top-0 w-full glass-effect">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-3 animate-slide-in-down">
                    <img src="assets/images/logo.jpg" alt="Logo" class="w-10 h-10 rounded-full object-cover shadow-lg">
                    <span class="text-white font-bold text-lg hidden sm:inline">FPOG SMS</span>
                </div>
                <div class="flex items-center gap-4 animate-slide-in-down delay-1">
                    <button id="loginBtnNav" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold transition duration-300 transform hover:scale-105">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="relative z-10">
        <!-- Hero Section -->
        <section class="min-h-screen flex flex-col items-center justify-center px-4 pt-20">
            <!-- Floating Logo -->
            <div class="mb-8 animate-float">
                <div class="relative">
                    <div class="absolute inset-0 bg-gradient-to-r from-indigo-500 to-purple-500 blur-lg opacity-20 rounded-full"></div>
                    <img src="assets/images/logo.jpg" alt="Federal Polytechnic of Oil and Gas Logo" class="w-40 h-40 md:w-48 md:h-48 object-contain mx-auto relative z-10 drop-shadow-2xl">
                </div>
            </div>
            
            <!-- Main Heading -->
            <h1 class="text-white text-4xl md:text-5xl lg:text-6xl font-bold text-center mb-4 animate-slide-in-down delay-2 leading-tight">
                <span class="bg-gradient-to-r from-blue-400 via-cyan-400 to-indigo-400 bg-clip-text text-transparent">
                    Federal Polytechnic of Oil and Gas
                </span>
            </h1>
            
            <!-- Subheading -->
            <h2 class="text-gray-300 text-xl md:text-2xl lg:text-3xl font-semibold text-center mb-3 animate-slide-in-down delay-3">
                Advanced Staff Management System
            </h2>

            <!-- Description -->
            <p class="text-gray-400 text-center max-w-2xl mb-12 text-sm md:text-base lg:text-lg leading-relaxed animate-slide-in-down delay-4">
                Streamline your institutional operations with our comprehensive staff management solution. 
                Featuring advanced authentication, real-time communication, and seamless data management.
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row gap-6 mb-16 animate-slide-in-down delay-5">
                <button id="loginBtn" class="btn-glow group relative px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-lg transition duration-300 transform hover:scale-110 shadow-lg hover:shadow-2xl flex items-center justify-center gap-3">
                    <i class="fas fa-sign-in-alt text-lg"></i>
                    <span>Access Portal</span>
                </button>
                <button id="registerBtn" class="group px-8 py-4 border-2 border-white hover:bg-white text-white hover:text-gray-900 font-bold rounded-lg transition duration-300 transform hover:scale-110 flex items-center justify-center gap-3">
                    <i class="fas fa-info-circle text-lg"></i>
                    <span>Learn More</span>
                </button>
            </div>

            <!-- Scroll Indicator -->
            <div class="animate-bounce mt-8">
                <i class="fas fa-chevron-down text-white text-2xl opacity-75"></i>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-20 px-4 bg-gray-800/50 backdrop-blur-sm">
            <div class="max-w-6xl mx-auto">
                <h2 class="text-4xl font-bold text-center text-white mb-4 animate-slide-in-up">
                    Powerful Features
                </h2>
                <p class="text-center text-gray-400 mb-16 animate-slide-in-up delay-1">
                    Everything you need for efficient staff management
                </p>

                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="feature-card glass-effect p-8 rounded-xl border border-indigo-500/20 hover:border-indigo-500/50 animate-slide-in-up delay-2">
                        <div class="bg-indigo-600/20 w-16 h-16 rounded-lg flex items-center justify-center mb-6">
                            <i class="fas fa-shield-alt text-3xl text-indigo-400"></i>
                        </div>
                        <h3 class="text-white text-xl font-bold mb-3">Secure Authentication</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">
                            Role-based access control with multi-level security protocols ensuring authorized access at all times.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="feature-card glass-effect p-8 rounded-xl border border-purple-500/20 hover:border-purple-500/50 animate-slide-in-up delay-3">
                        <div class="bg-purple-600/20 w-16 h-16 rounded-lg flex items-center justify-center mb-6">
                            <i class="fas fa-users text-3xl text-purple-400"></i>
                        </div>
                        <h3 class="text-white text-xl font-bold mb-3">User Management</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">
                            Complete CRUD operations with advanced search, filtering, and CSV export capabilities for staff data.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="feature-card glass-effect p-8 rounded-xl border border-cyan-500/20 hover:border-cyan-500/50 animate-slide-in-up delay-4">
                        <div class="bg-cyan-600/20 w-16 h-16 rounded-lg flex items-center justify-center mb-6">
                            <i class="fas fa-envelope text-3xl text-cyan-400"></i>
                        </div>
                        <h3 class="text-white text-xl font-bold mb-3">Communication Hub</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">
                            Send memos with image/PDF support, automatic quality detection, and comprehensive history tracking.
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="feature-card glass-effect p-8 rounded-xl border border-blue-500/20 hover:border-blue-500/50 animate-slide-in-up delay-5">
                        <div class="bg-blue-600/20 w-16 h-16 rounded-lg flex items-center justify-center mb-6">
                            <i class="fas fa-database text-3xl text-blue-400"></i>
                        </div>
                        <h3 class="text-white text-xl font-bold mb-3">Data Management</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">
                            CSV-based staff pre-verification with real-time synchronization and automated data validation.
                        </p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="feature-card glass-effect p-8 rounded-xl border border-green-500/20 hover:border-green-500/50 animate-slide-in-up delay-4">
                        <div class="bg-green-600/20 w-16 h-16 rounded-lg flex items-center justify-center mb-6">
                            <i class="fas fa-mobile-alt text-3xl text-green-400"></i>
                        </div>
                        <h3 class="text-white text-xl font-bold mb-3">Responsive Design</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">
                            Fully responsive interface optimized for mobile, tablet, and desktop devices with smooth transitions.
                        </p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="feature-card glass-effect p-8 rounded-xl border border-pink-500/20 hover:border-pink-500/50 animate-slide-in-up delay-5">
                        <div class="bg-pink-600/20 w-16 h-16 rounded-lg flex items-center justify-center mb-6">
                            <i class="fas fa-chart-bar text-3xl text-pink-400"></i>
                        </div>
                        <h3 class="text-white text-xl font-bold mb-3">Analytics & Reports</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">
                            Real-time statistics, user activity tracking, and comprehensive reporting for institutional insights.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="py-20 px-4 relative">
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-600/10 to-purple-600/10 opacity-50"></div>
            <div class="max-w-6xl mx-auto relative z-10">
                <h2 class="text-4xl font-bold text-center text-white mb-16 animate-slide-in-up">
                    By The Numbers
                </h2>

                <div class="grid md:grid-cols-4 gap-8">
                    <div class="text-center glass-effect p-8 rounded-xl animate-fade-in-scale delay-1">
                        <div class="stat-number mb-2">100%</div>
                        <p class="text-gray-400 font-semibold">Secure</p>
                        <p class="text-gray-500 text-sm mt-2">Enterprise-grade encryption</p>
                    </div>
                    <div class="text-center glass-effect p-8 rounded-xl animate-fade-in-scale delay-2">
                        <div class="stat-number mb-2">24/7</div>
                        <p class="text-gray-400 font-semibold">Available</p>
                        <p class="text-gray-500 text-sm mt-2">Always online & accessible</p>
                    </div>
                    <div class="text-center glass-effect p-8 rounded-xl animate-fade-in-scale delay-3">
                        <div class="stat-number mb-2">50+</div>
                        <p class="text-gray-400 font-semibold">Features</p>
                        <p class="text-gray-500 text-sm mt-2">Comprehensive tools</p>
                    </div>
                    <div class="text-center glass-effect p-8 rounded-xl animate-fade-in-scale delay-4">
                        <div class="stat-number mb-2">∞</div>
                        <p class="text-gray-400 font-semibold">Scalable</p>
                        <p class="text-gray-500 text-sm mt-2">Grows with your needs</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call to Action Section -->
        <section class="py-20 px-4 bg-gradient-to-r from-indigo-600/20 to-purple-600/20 backdrop-blur-sm">
            <div class="max-w-4xl mx-auto text-center">
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-6 animate-slide-in-up">
                    Ready to Get Started?
                </h2>
                <p class="text-gray-300 text-lg mb-10 animate-slide-in-up delay-1">
                    Join thousands of institutions managing their staff efficiently with our platform.
                    Secure, reliable, and built for success.
                </p>
                <div class="flex flex-col sm:flex-row gap-6 justify-center animate-slide-in-up delay-2">
                    <button id="loginBtnCTA" class="btn-glow px-8 py-4 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold rounded-lg transition duration-300 transform hover:scale-110 shadow-lg hover:shadow-2xl">
                        <i class="fas fa-arrow-right mr-2"></i>Access Now
                    </button>
                    <button class="px-8 py-4 border-2 border-white hover:bg-white text-white hover:text-gray-900 font-bold rounded-lg transition duration-300 transform hover:scale-110">
                        <i class="fas fa-question-circle mr-2"></i>Get Support
                    </button>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-12 px-4 bg-black/40 backdrop-blur-md border-t border-white/10">
            <div class="max-w-6xl mx-auto">
                <div class="grid md:grid-cols-4 gap-8 mb-8">
                    <div>
                        <h4 class="text-white font-bold mb-4 flex items-center gap-2">
                            <img src="assets/images/logo.jpg" alt="Logo" class="w-6 h-6 rounded">
                            FPOG SMS
                        </h4>
                        <p class="text-gray-400 text-sm">Advanced staff management for modern institutions.</p>
                    </div>
                    <div>
                        <h4 class="text-white font-bold mb-4">Features</h4>
                        <ul class="text-gray-400 text-sm space-y-2">
                            <li><a href="#" class="hover:text-white transition">User Management</a></li>
                            <li><a href="#" class="hover:text-white transition">Communication</a></li>
                            <li><a href="#" class="hover:text-white transition">Analytics</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-bold mb-4">Support</h4>
                        <ul class="text-gray-400 text-sm space-y-2">
                            <li><a href="#" class="hover:text-white transition">Documentation</a></li>
                            <li><a href="#" class="hover:text-white transition">Help Center</a></li>
                            <li><a href="#" class="hover:text-white transition">Contact Us</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-white font-bold mb-4">Legal</h4>
                        <ul class="text-gray-400 text-sm space-y-2">
                            <li><a href="#" class="hover:text-white transition">Privacy Policy</a></li>
                            <li><a href="#" class="hover:text-white transition">Terms of Service</a></li>
                            <li><a href="#" class="hover:text-white transition">Security</a></li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-white/10 pt-8 text-center">
                    <p class="text-gray-500 text-sm">
                        &copy; 2025 Federal Polytechnic of Oil and Gas. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- Login Dialog -->
    <div id="loginDialog" class="fixed inset-0 z-50 hidden">
        <div class="modal-overlay absolute inset-0"></div>
        <div class="relative z-10 flex items-center justify-center min-h-screen px-4">
            <div class="glass-effect rounded-2xl p-8 max-w-md w-full animate-fade-in-scale shadow-2xl border border-indigo-500/50">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-white">Access Portal</h3>
                    <button id="closeDialog" class="text-gray-400 hover:text-white transition text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <p class="text-gray-300 mb-8 text-sm">Select your login type to continue</p>
                
                <div class="space-y-4">
                    <a href="admin_login.php" class="block w-full group bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-bold py-4 px-6 rounded-lg transition duration-300 transform hover:scale-105 hover:shadow-lg flex items-center justify-center gap-3">
                        <i class="fas fa-user-tie text-2xl"></i>
                        <div class="text-left">
                            <span class="block">Login as Admin</span>
                            <span class="text-xs opacity-80">Staff Member Access</span>
                        </div>
                    </a>
                    <a href="superadmin_login.php" class="block w-full group bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-bold py-4 px-6 rounded-lg transition duration-300 transform hover:scale-105 hover:shadow-lg flex items-center justify-center gap-3">
                        <i class="fas fa-user-shield text-2xl"></i>
                        <div class="text-left">
                            <span class="block">Login as Super Admin</span>
                            <span class="text-xs opacity-80">Administrative Access</span>
                        </div>
                    </a>
                </div>

                <div class="mt-6 pt-6 border-t border-white/10">
                    <p class="text-gray-400 text-sm text-center">
                        Need a new account? Contact your administrator.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="assets/js/slideshow.js"></script>
    <script>
        // Login Dialog Handlers
        const loginDialog = $('#loginDialog');
        const loginBtns = $('#loginBtn, #loginBtnNav, #loginBtnCTA');
        const closeDialog = $('#closeDialog');
        const registerBtn = $('#registerBtn');

        loginBtns.on('click', function(e) {
            e.preventDefault();
            loginDialog.fadeIn(300).removeClass('hidden');
            $('body').css('overflow', 'hidden');
        });

        closeDialog.on('click', function() {
            loginDialog.fadeOut(300).addClass('hidden');
            $('body').css('overflow', 'auto');
        });

        registerBtn.on('click', function(e) {
            e.preventDefault();
            $('html, body').animate({ scrollTop: $(document).height() }, 1500);
        });

        $(document).on('click', function(e) {
            if ($(e.target).attr('id') === 'loginDialog') {
                loginDialog.fadeOut(300).addClass('hidden');
                $('body').css('overflow', 'auto');
            }
        });

        // Smooth scroll behavior
        $('html').css('scroll-behavior', 'smooth');
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>
