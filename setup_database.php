<?php
require_once 'includes/db.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Create users table (for Super Admin)
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        firstname VARCHAR(100) NOT NULL,
        lastname VARCHAR(100) NOT NULL,
        role VARCHAR(20) DEFAULT 'superadmin',
        profile_picture VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create pre_users table (CSV uploaded data)
    $db->exec("CREATE TABLE IF NOT EXISTS pre_users (
        id SERIAL PRIMARY KEY,
        surname VARCHAR(100) NOT NULL,
        firstname VARCHAR(100) NOT NULL,
        othername VARCHAR(100),
        staff_id VARCHAR(50) UNIQUE NOT NULL,
        salary_structure VARCHAR(100),
        gl VARCHAR(50),
        step VARCHAR(50),
        rank VARCHAR(100),
        is_registered BOOLEAN DEFAULT FALSE,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create admin_users table (Complete registration data)
    $db->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id SERIAL PRIMARY KEY,
        staff_id VARCHAR(50) UNIQUE NOT NULL,
        
        -- Personal Details
        surname VARCHAR(100) NOT NULL,
        firstname VARCHAR(100) NOT NULL,
        othername VARCHAR(100),
        date_of_birth DATE NOT NULL,
        sex VARCHAR(10) NOT NULL,
        marital_status VARCHAR(20) NOT NULL,
        permanent_home_address TEXT NOT NULL,
        lga_origin VARCHAR(100) NOT NULL,
        
        -- Employment Details
        department VARCHAR(100) NOT NULL,
        position VARCHAR(100) NOT NULL,
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
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY (staff_id) REFERENCES pre_users(staff_id) ON DELETE RESTRICT
    )");
    
    // Create sessions table
    $db->exec("CREATE TABLE IF NOT EXISTS sessions (
        id SERIAL PRIMARY KEY,
        user_id INTEGER NOT NULL,
        user_type VARCHAR(20) NOT NULL,
        session_token VARCHAR(255) UNIQUE NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL
    )");
    
    // Create admin_roles table (for role assignments to admin users)
    $db->exec("CREATE TABLE IF NOT EXISTS admin_roles (
        id SERIAL PRIMARY KEY,
        admin_id INTEGER NOT NULL,
        role_name VARCHAR(100) NOT NULL,
        assigned_by INTEGER NOT NULL,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        removed_at TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
        FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
        UNIQUE(admin_id, role_name)
    )");
    
    // Create memos table
    $db->exec("CREATE TABLE IF NOT EXISTS memos (
        id SERIAL PRIMARY KEY,
        sender_id INTEGER NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        file_path VARCHAR(500) NOT NULL,
        file_type VARCHAR(255) NOT NULL,
        recipient_type VARCHAR(20) NOT NULL,
        recipient_id INTEGER,
        blur_detected BOOLEAN DEFAULT FALSE,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (recipient_id) REFERENCES admin_users(id) ON DELETE SET NULL
    )");
    
    // Create memo_recipients table (for tracking who received what memo)
    $db->exec("CREATE TABLE IF NOT EXISTS memo_recipients (
        id SERIAL PRIMARY KEY,
        memo_id INTEGER NOT NULL,
        admin_id INTEGER NOT NULL,
        read_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (memo_id) REFERENCES memos(id) ON DELETE CASCADE,
        FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
        UNIQUE(memo_id, admin_id)
    )");
    
    // Insert default super admin (password: admin123)
    $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $db->exec("INSERT INTO users (username, email, password, firstname, lastname, role) 
               VALUES ('superadmin', 'superadmin@fpog.edu.ng', '$defaultPassword', 'Super', 'Admin', 'superadmin')
               ON CONFLICT (username) DO NOTHING");
    
    echo "Database setup completed successfully!\n";
    echo "Default Super Admin created:\n";
    echo "Username: superadmin\n";
    echo "Email: superadmin@fpog.edu.ng\n";
    echo "Password: admin123\n";
    echo "Please change the password after first login.\n";
    
} catch(PDOException $e) {
    echo "Error setting up database: " . $e->getMessage() . "\n";
    exit(1);
}
?>
