<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'fpog_sms';

try {
    $pdo = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=utf8mb4', $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $username = 'superadmin';
    $password = 'admin123';
    $email = 'superadmin@fpog.edu.ng';
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Try to update first
    $updateStmt = $pdo->prepare('UPDATE users SET password = ?, email = ? WHERE username = ? AND role = "superadmin"');
    $result = $updateStmt->execute([$hashedPassword, $email, $username]);
    
    if ($updateStmt->rowCount() > 0) {
        echo "✓ Super Admin password updated successfully!<br>";
    } else {
        // Insert if not exists
        $insertStmt = $pdo->prepare('INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, "superadmin")');
        $insertStmt->execute([$username, $email, $hashedPassword]);
        echo "✓ Super Admin user created successfully!<br>";
    }
    
    echo "<br><strong>Login Credentials:</strong><br>";
    echo "Username: " . $username . "<br>";
    echo "Password: " . $password . "<br>";
    echo "Email: " . $email . "<br>";
    echo "<br><a href='superadmin_login.php'>Go to Super Admin Login</a>";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
    exit(1);
}
?>
