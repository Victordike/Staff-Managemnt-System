<?php
require_once 'config/config.php';

/**
 * Staff Manager Pro - Leave Management Database Setup
 */

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h2>Initializing Leave Management Database Setup...</h2>";

    // 1. Leave Types table
    echo "Creating 'leave_types' table... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS leave_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        default_days INT NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Done.<br>";

    // 2. Leave Applications table
    echo "Creating 'leave_applications' table... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS leave_applications (
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

    // 3. Leave Balances table
    echo "Creating 'leave_balances' table... ";
    $pdo->exec("CREATE TABLE IF NOT EXISTS leave_balances (
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

    // Insert Default Leave Types
    echo "Inserting default leave types... ";
    $leave_types = [
        ['Annual Leave', 'Standard yearly leave entitlement', 30],
        ['Sick Leave', 'Leave for medical reasons', 15],
        ['Casual Leave', 'Short-term leave for personal reasons', 7],
        ['Maternity Leave', 'Leave for childbirth and childcare', 120],
        ['Paternity Leave', 'Leave for male staff for childbirth and childcare', 15],
        ['Study Leave', 'Leave for educational purposes', 0],
        ['Examination Leave', 'Leave for taking examinations', 0],
        ['Compassionate Leave', 'Leave for family emergencies or bereavement', 5]
    ];

    $stmt = $pdo->prepare("INSERT INTO leave_types (name, description, default_days) VALUES (?, ?, ?)");
    foreach ($leave_types as $type) {
        $stmt->execute($type);
    }
    echo "Done.<br>";

    echo "<h3 style='color: green;'>✓ Leave Management database setup completed successfully!</h3>";

} catch(PDOException $e) {
    echo "<h3 style='color: red;'>Error setting up leave management database:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    exit(1);
}
?>
