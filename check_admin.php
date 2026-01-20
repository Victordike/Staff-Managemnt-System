<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'fpog_sms';

try {
    $pdo = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Current Super Admin Users:</h2>";
    $stmt = $pdo->query('SELECT id, username, email, password, role FROM users WHERE role = "superadmin"');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($results)) {
        echo "<p>No super admin users found!</p>";
    } else {
        foreach ($results as $row) {
            echo "<p><strong>ID:</strong> {$row['id']}</p>";
            echo "<p><strong>Username:</strong> {$row['username']}</p>";
            echo "<p><strong>Email:</strong> {$row['email']}</p>";
            echo "<p><strong>Password Hash:</strong> {$row['password']}</p>";
            echo "<p><strong>Role:</strong> {$row['role']}</p>";
            echo "<hr>";
        }
    }
    
    echo "<h2>Creating/Updating Super Admin User:</h2>";
    $username = 'superadmin';
    $password = 'admin123';
    $email = 'superadmin@fpog.edu.ng';
    $firstname = 'Super';
    $lastname = 'Admin';
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if user exists
    $checkStmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND role = "superadmin"');
    $checkStmt->execute([$username]);
    $exists = $checkStmt->fetch();
    
    if ($exists) {
        // Update existing user
        $updateStmt = $pdo->prepare('UPDATE users SET password = ?, email = ?, firstname = ?, lastname = ? WHERE id = ?');
        $updateStmt->execute([$hashedPassword, $email, $firstname, $lastname, $exists['id']]);
        echo "<p style='color: green;'><strong>✓ Super Admin Updated Successfully!</strong></p>";
    } else {
        // Insert new user
        $insertStmt = $pdo->prepare('INSERT INTO users (username, email, password, firstname, lastname, role) VALUES (?, ?, ?, ?, ?, "superadmin")');
        $insertStmt->execute([$username, $email, $hashedPassword, $firstname, $lastname]);
        echo "<p style='color: green;'><strong>✓ Super Admin Created Successfully!</strong></p>";
    }
    
    echo "<p><strong>Username:</strong> $username</p>";
    echo "<p><strong>Password:</strong> $password</p>";
    echo "<p><strong>Email:</strong> $email</p>";
    
} catch (Exception $e) {
    echo '<p style="color: red;"><strong>Error:</strong> ' . $e->getMessage() . '</p>';
}
?>
