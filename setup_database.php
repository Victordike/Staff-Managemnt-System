<?php
require_once 'config/config.php';

/**
 * Staff Manager Pro - Database Setup Script
 * Consolidates all tables and relationships from the entire project.
 */

try {
    // Initial connection to create database if it doesn't exist
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Creating database '" . DB_NAME . "' if it doesn't exist... ";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Done.<br>";

    // Now connect to the database
    $pdo->exec("USE " . DB_NAME);
    $db = $pdo;
    
    // Disable foreign key checks for clean teardown
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // List of tables to drop (in order of child to parent)
    $tables = [
        'leave_notifications',
        'holidays',
        'leave_balances',
        'leave_applications',
        'leave_types',
        'document_notifications',
        'document_submissions',
        'registration_draft',
        'memo_recipients',
        'memos',
        'admin_roles',
        'sessions',
        'admin_users',
        'pre_users',
        'users'
    ];
    
    foreach ($tables as $table) {
        $db->exec("DROP TABLE IF EXISTS $table");
    }
    
    // Enable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<h2>Initializing Database Setup...</h2>";

    // 1. Users table (Super Admin)
    echo "Creating 'users' table... ";
    $db->exec("CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        firstname VARCHAR(100) NOT NULL,
        lastname VARCHAR(100) NOT NULL,
        role VARCHAR(20) DEFAULT 'superadmin',
        profile_picture VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Done.<br>";

    // 2. Pre-users table (CSV uploaded data)
    echo "Creating 'pre_users' table... ";
    $db->exec("CREATE TABLE pre_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id VARCHAR(50) UNIQUE NOT NULL,
        surname VARCHAR(100) NOT NULL,
        firstname VARCHAR(100) NOT NULL,
        othername VARCHAR(100),
        salary_structure VARCHAR(100),
        gl VARCHAR(50),
        step VARCHAR(50),
        rank VARCHAR(100),
        is_registered BOOLEAN DEFAULT FALSE,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Done.<br>";

    // 3. Admin users table (Complete registration data)
    echo "Creating 'admin_users' table... ";
    $db->exec("CREATE TABLE admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id VARCHAR(50) UNIQUE NOT NULL,
        
        -- Personal Details
        surname VARCHAR(100) NOT NULL,
        firstname VARCHAR(100) NOT NULL,
        othername VARCHAR(100),
        staff_category VARCHAR(50) DEFAULT 'Academic',
        date_of_birth DATE NOT NULL,
        sex VARCHAR(10) NOT NULL,
        marital_status VARCHAR(20) NOT NULL,
        permanent_home_address TEXT NOT NULL,
        state_origin VARCHAR(100) NOT NULL,
        lga_origin VARCHAR(100) NOT NULL,
        
        -- Employment Details
        department VARCHAR(100) NOT NULL,
        faculty VARCHAR(100),
        position VARCHAR(100) NOT NULL,
        salary_grade VARCHAR(20),
        type_of_employment VARCHAR(50) NOT NULL,
        date_of_assumption DATE NOT NULL,
        cadre VARCHAR(100) NOT NULL,
        salary_structure VARCHAR(100),
        gl VARCHAR(50),
        step VARCHAR(50),
        rank VARCHAR(100),
        
        -- Contact Details
        phone_number VARCHAR(20) NOT NULL,
        official_email VARCHAR(255) UNIQUE NOT NULL,
        
        -- Bank/Finance Details
        bank_name VARCHAR(100) NOT NULL,
        account_name VARCHAR(100) NOT NULL,
        account_number VARCHAR(20) NOT NULL,
        pfa_name VARCHAR(100) NOT NULL,
        pfa_pin VARCHAR(50) NOT NULL,
        
        -- Next of Kin
        nok_fullname VARCHAR(200) NOT NULL,
        nok_phone_number VARCHAR(20) NOT NULL,
        nok_relationship VARCHAR(50) NOT NULL,
        nok_address TEXT NOT NULL,
        
        -- Authentication
        password VARCHAR(255) NOT NULL,
        profile_picture VARCHAR(255),
        
        -- Status
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        FOREIGN KEY (staff_id) REFERENCES pre_users(staff_id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Done.<br>";

    // 4. Sessions table
    echo "Creating 'sessions' table... ";
    $db->exec("CREATE TABLE sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_type VARCHAR(20) NOT NULL,
        session_token VARCHAR(255) UNIQUE NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Done.<br>";

    // 5. Admin roles table
    echo "Creating 'admin_roles' table... ";
    $db->exec("CREATE TABLE admin_roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        role_name VARCHAR(100) NOT NULL,
        department VARCHAR(100),
        faculty VARCHAR(100),
        assigned_by INT,
        assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        removed_at DATETIME,
        FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
        UNIQUE(admin_id, role_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Done.<br>";

    // 6. Memos table
    echo "Creating 'memos' table... ";
    $db->exec("CREATE TABLE memos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        file_path VARCHAR(500) NOT NULL,
        file_type VARCHAR(255) NOT NULL,
        recipient_type VARCHAR(20) NOT NULL,
        recipient_id INT,
        final_recipient_id INT,
        current_stage VARCHAR(50) DEFAULT 'direct',
        routing_path TEXT,
        is_approved BOOLEAN DEFAULT TRUE,
        rejection_reason TEXT,
        rejected_by INT,
        rejected_at DATETIME,
        blur_detected BOOLEAN DEFAULT FALSE,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (sender_id),
        FOREIGN KEY (recipient_id) REFERENCES admin_users(id) ON DELETE SET NULL,
        FOREIGN KEY (final_recipient_id) REFERENCES admin_users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Done.<br>";

    // 7. Memo recipients table
    echo "Creating 'memo_recipients' table... ";
    $db->exec("CREATE TABLE memo_recipients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        memo_id INT NOT NULL,
        recipient_id INT NOT NULL,
        read_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (memo_id) REFERENCES memos(id) ON DELETE CASCADE,
        FOREIGN KEY (recipient_id) REFERENCES admin_users(id) ON DELETE CASCADE,
        UNIQUE(memo_id, recipient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Done.<br>";

    // 8. Registration draft table
    echo "Creating 'registration_draft' table... ";
    $db->exec("CREATE TABLE registration_draft (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_id VARCHAR(50) UNIQUE NOT NULL,
        surname VARCHAR(100),
        firstname VARCHAR(100),
        othername VARCHAR(100),
        date_of_birth DATE,
        sex VARCHAR(10),
        marital_status VARCHAR(20),
        permanent_home_address TEXT,
        state_origin VARCHAR(100),
        lga_origin VARCHAR(100),
        department VARCHAR(100),
        faculty VARCHAR(100),
        position VARCHAR(100),
        type_of_employment VARCHAR(50),
        date_of_assumption DATE,
        cadre VARCHAR(100),
        salary_structure VARCHAR(50),
        gl VARCHAR(10),
        step VARCHAR(10),
        rank VARCHAR(100),
        phone_number VARCHAR(20),
        official_email VARCHAR(100),
        bank_name VARCHAR(100),
        account_name VARCHAR(100),
        account_number VARCHAR(50),
        pfa_name VARCHAR(100),
        pfa_pin VARCHAR(50),
        nok_fullname VARCHAR(200),
        nok_phone_number VARCHAR(20),
        nok_relationship VARCHAR(50),
        nok_address TEXT,
        current_step INT DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (staff_id) REFERENCES pre_users(staff_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Done.<br>";

    // 9. Document submissions table
    echo "Creating 'document_submissions' table... ";
    $db->exec("CREATE TABLE document_submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        original_filename VARCHAR(255) NOT NULL,
        stored_filename VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_type VARCHAR(50) NOT NULL,
        file_size INT NOT NULL,
        document_category VARCHAR(100) DEFAULT NULL,
        approval_status ENUM('pending', 'establishment_approved', 'registrar_approved', 'rejected') DEFAULT 'pending',
        current_stage ENUM('establishment', 'registrar', 'completed') DEFAULT 'establishment',
        establishment_approved_by INT,
        establishment_approved_at DATETIME,
        registrar_approved_by INT,
        registrar_approved_at DATETIME,
        rejection_reason TEXT,
        rejected_by_admin_id INT,
        rejected_at DATETIME,
        parent_document_id INT,
        version_number INT DEFAULT 1,
        establishment_comments TEXT,
        registrar_comments TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
        FOREIGN KEY (establishment_approved_by) REFERENCES admin_users(id) ON DELETE SET NULL,
        FOREIGN KEY (registrar_approved_by) REFERENCES admin_users(id) ON DELETE SET NULL,
        FOREIGN KEY (rejected_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
        FOREIGN KEY (parent_document_id) REFERENCES document_submissions(id) ON DELETE SET NULL,
        INDEX idx_admin_id (admin_id),
        INDEX idx_approval_status (approval_status),
        INDEX idx_current_stage (current_stage)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Done.<br>";

    // 10. Document notifications table
    echo "Creating 'document_notifications' table... ";
    $db->exec("CREATE TABLE document_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        document_id INT NOT NULL,
        notification_type ENUM('submitted', 'establishment_approved', 'registrar_approved', 'rejected') NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        read_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
        FOREIGN KEY (document_id) REFERENCES document_submissions(id) ON DELETE CASCADE,
        INDEX idx_admin_id (admin_id),
        INDEX idx_is_read (is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Done.<br>";

    // 11. Holidays table
    echo "Creating 'holidays' table... ";
    $db->exec("CREATE TABLE holidays (
        id INT AUTO_INCREMENT PRIMARY KEY,
        holiday_date DATE NOT NULL UNIQUE,
        holiday_name VARCHAR(100) NOT NULL,
        year INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Done.<br>";

    // 12. Leave Types table
    echo "Creating 'leave_types' table... ";
    $db->exec("CREATE TABLE leave_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        default_days INT NOT NULL,
        gender ENUM('Any', 'Male', 'Female') DEFAULT 'Any',
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Done.<br>";

    // 13. Leave Applications table
    echo "Creating 'leave_applications' table... ";
    $db->exec("CREATE TABLE leave_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        leave_type_id INT NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        duration INT NOT NULL,
        reason TEXT NOT NULL,
        status ENUM('pending', 'hod_recommended', 'hod_rejected', 'dean_cleared', 'dean_rejected', 'establishment_verified', 'establishment_rejected', 'approved', 'rejected', 'completed') DEFAULT 'pending',
        
        -- HOD Recommendation
        hod_id INT,
        hod_recommended_at DATETIME,
        hod_remarks TEXT,
        
        -- Dean Clearance
        dean_id INT,
        dean_cleared_at DATETIME,
        dean_remarks TEXT,
        
        -- Establishment Verification
        establishment_id INT,
        establishment_verified_at DATETIME,
        establishment_remarks TEXT,
        
        -- Final Approval (Registrar/Rector)
        approver_id INT,
        approved_at DATETIME,
        approver_remarks TEXT,
        
        -- Resumption
        resumption_date DATE,
        resumption_remarks TEXT,
        resumption_submitted_at DATETIME,
        
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
        FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE CASCADE,
        FOREIGN KEY (hod_id) REFERENCES admin_users(id) ON DELETE SET NULL,
        FOREIGN KEY (dean_id) REFERENCES admin_users(id) ON DELETE SET NULL,
        FOREIGN KEY (establishment_id) REFERENCES admin_users(id) ON DELETE SET NULL,
        FOREIGN KEY (approver_id) REFERENCES admin_users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Done.<br>";

    // 14. Leave Balances table
    echo "Creating 'leave_balances' table... ";
    $db->exec("CREATE TABLE leave_balances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        leave_type_id INT NOT NULL,
        year INT NOT NULL,
        entitled_days INT NOT NULL,
        used_days INT DEFAULT 0,
        remaining_days INT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
        FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE CASCADE,
        UNIQUE(admin_id, leave_type_id, year)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Done.<br>";

    // 15. Leave Notifications table
    echo "Creating 'leave_notifications' table... ";
    $db->exec("CREATE TABLE leave_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        leave_id INT NOT NULL,
        notification_type ENUM('submitted', 'approved', 'rejected', 'completed', 'recommended', 'cleared', 'verified') NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        read_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
        FOREIGN KEY (leave_id) REFERENCES leave_applications(id) ON DELETE CASCADE,
        INDEX idx_admin_id (admin_id),
        INDEX idx_is_read (is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Done.<br>";

    // Insert Default Leave Types
    echo "Inserting default leave types... ";
    $leave_types = [
        ['Annual Leave', 'Standard yearly leave entitlement', 30, 'Any'],
        ['Sick Leave', 'Leave for medical reasons', 15, 'Any'],
        ['Casual Leave', 'Short-term leave for personal reasons', 7, 'Any'],
        ['Maternity Leave', 'Leave for childbirth and childcare', 120, 'Female'],
        ['Paternity Leave', 'Leave for male staff for childbirth and childcare', 15, 'Male'],
        ['Study Leave', 'Leave for educational purposes', 0, 'Any'],
        ['Examination Leave', 'Leave for taking examinations', 0, 'Any'],
        ['Compassionate Leave', 'Leave for family emergencies or bereavement', 5, 'Any']
    ];

    $stmt = $db->prepare("INSERT INTO leave_types (name, description, default_days, gender) VALUES (?, ?, ?, ?)");
    foreach ($leave_types as $type) {
        $stmt->execute($type);
    }
    echo "Done.<br>";

    // Insert default holidays
    echo "Inserting default holidays... ";
    $holidays = [
        ['2026-01-01', 'New Year\'s Day', 2026],
        ['2026-03-30', 'Good Friday', 2026],
        ['2026-04-02', 'Easter Monday', 2026],
        ['2026-05-01', 'Worker\'s Day', 2026],
        ['2026-05-27', 'Children\'s Day', 2026],
        ['2026-06-12', 'Democracy Day', 2026],
        ['2026-10-01', 'Independence Day', 2026],
        ['2026-12-25', 'Christmas Day', 2026],
        ['2026-12-26', 'Boxing Day', 2026],
    ];
    $stmt = $db->prepare("INSERT IGNORE INTO holidays (holiday_date, holiday_name, year) VALUES (?, ?, ?)");
    foreach ($holidays as $h) {
        $stmt->execute($h);
    }
    echo "Done.<br>";

    // Insert Default Super Admin
    echo "Creating default Super Admin... ";
    $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $db->exec("INSERT INTO users (username, email, password, firstname, lastname, role) 
               VALUES ('superadmin', 'superadmin@fpog.edu.ng', '$defaultPassword', 'Super', 'Admin', 'superadmin')");
    echo "Done.<br>";

    echo "<h3 style='color: green;'>✓ Database setup completed successfully!</h3>";
    echo "Default Super Admin credentials:<br>";
    echo "Username: <b>superadmin</b><br>";
    echo "Password: <b>admin123</b><br>";
    echo "Email: <b>superadmin@fpog.edu.ng</b><br>";

} catch(PDOException $e) {
    echo "<h3 style='color: red;'>Error setting up database:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    exit(1);
}
?>
