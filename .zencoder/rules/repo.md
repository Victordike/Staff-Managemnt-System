---
description: Repository Information Overview
alwaysApply: true
---

# Staff Management System Information

## Summary
The **Staff Management System** is a web-based platform designed for the **Federal Polytechnic of Oil and Gas** to manage staff records, leave applications, documents, and memos. It is built using **PHP** and **MySQL**, with **Tailwind CSS** for the frontend.

## Structure
- **api/**: Contains backend PHP endpoints for user actions, notifications, and data export.
- **assets/**: Frontend resources including Tailwind CSS inputs/outputs and JavaScript files.
- **config/**: Core configuration files, including database credentials and application constants.
- **includes/**: Shared PHP utilities for database connectivity, session management, and helper functions.
- **uploads/**: Directory for storing user-uploaded documents, passports, memos, and CSV files.
- **root**: Main application pages for dashboards, logins, and management modules.

## Language & Runtime
**Language**: PHP  
**Version**: 7.4+  
**Build System**: npm (for CSS compilation)  
**Package Manager**: npm  

## Dependencies
**Main Dependencies**:
- **Tailwind CSS**: Utility-first CSS framework for styling.
- **PostCSS**: Tool for transforming CSS with JavaScript.
- **Autoprefixer**: PostCSS plugin to parse CSS and add vendor prefixes.
- **PDO**: PHP Data Objects for secure database interactions.

**Development Dependencies**:
- `tailwindcss`: ^3.4.19
- `postcss`: ^8.5.6
- `autoprefixer`: ^10.4.23

## Build & Installation
```bash
# Install frontend dependencies
npm install

# Build Tailwind CSS
npm run build:css

# Database Setup
# 1. Create a MySQL database named 'fpog_sms'
# 2. Import fpog_database_mysql.sql or run setup_database.php
```

## Main Files & Resources
- **Application Entry Points**: 
  - `index.php`: Main landing/welcome page.
  - `admin_login.php`: Login portal for administrative staff.
  - `superadmin_login.php`: Login portal for super administrators.
- **Configuration**:
  - `config/config.php`: Global site and database settings.
  - `includes/db.php`: Database connection class using Singleton pattern.
- **Database Setup**:
  - `setup_database.php`: Script to initialize the database schema.
  - `fpog_database_mysql.sql`: SQL export for manual database import.

## Testing & Validation
**Approach**: No formal testing framework (like Jest or PHPUnit) is present. Validation is performed through:
- **Migration Scripts**: Multiple `migrate_*.php` files for schema updates.
- **Setup Scripts**: `setup_database.php` and `database_leave_setup.php` for environment initialization.
- **Linter/Checkers**: Basic Tailwind/PostCSS validation during build steps.

**Validation Commands**:
```bash
# Watch CSS changes for real-time validation
npm run watch:css
```
