# Staff Management System - Federal Polytechnic of Oil and Gas

## Overview
A comprehensive staff management system for Federal Polytechnic of Oil and Gas featuring CSV-based pre-verification, multi-role authentication (Admin User and Super Admin), and a complete employee registration workflow.

## Project Architecture

### Technology Stack
- **Frontend**: HTML5, Tailwind CSS (built-in compilation), jQuery 3.x, jQuery UI, FontAwesome
- **Backend**: PHP 8.2, MySQL/MariaDB
- **Deployment**: WordPress-ready (custom plugin structure)

### Directory Structure
```
/
├── assets/
│   ├── css/          # Compiled Tailwind CSS and custom styles
│   ├── js/           # JavaScript files (jQuery, custom scripts)
│   └── images/       # Logo, slideshow images, profile pictures
│       └── slideshow/ # 5 background slideshow images
├── includes/
│   ├── head.php      # Dashboard header with collapsible sidebar
│   ├── foot.php      # Dashboard footer
│   ├── db.php        # Database connection
│   └── functions.php # Helper functions
├── config/
│   └── config.php    # Configuration settings
├── uploads/
│   └── csv/          # CSV file uploads for pre-verification
├── public/           # Public-facing pages
└── [PHP pages]       # Main application files

### Database Schema

**Tables:**
1. `pre_users` - CSV uploaded data for pre-verification
   - Surname, Firstname, Othername, Staff_ID, Salary_Structure, GL, STEP, Rank

2. `users` - Super Admin users
   - id, username, email, password, role, created_at

3. `admin_users` - Registered Admin users (complete registration data)
   - Personal, Employment, Contact, Bank/Finance, Next of Kin details

4. `sessions` - User session management

### Key Features
1. **Welcome Page**: 5-image background slideshow with dark blue overlay, school branding, Login/Register buttons
2. **Authentication**: Popup dialog for role selection (Admin/Super Admin), separate login pages
3. **Super Admin Dashboard**: Collapsible sidebar, CSV upload module for pre-verification
4. **Admin Registration**: Multi-step form (5 sections) with CSV pre-verification
5. **Admin Dashboard**: Same collapsible sidebar design for logged-in admin users

## Recent Changes
- **2025-11-20**: Project initialized with PHP 8.2, Node.js 20, Tailwind CSS build system

## User Preferences
- Use built-in Tailwind CSS compilation (no CDN)
- Modular PHP structure (head.php/foot.php)
- WordPress-compatible architecture
- Easily replaceable slideshow images

## Development Notes
- Tailwind CSS is compiled locally using Node.js
- CSV files are stored in uploads/csv/ directory
- Session secret available in environment variables
- PHP development server runs on port 5000
