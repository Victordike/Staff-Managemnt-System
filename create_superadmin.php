<?php
require_once 'includes/db.php';

try {
    $db = Database::getInstance();
    $username = 'superadmin';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $email = 'superadmin@fpog.edu.ng';
    $firstname = 'Super';
    $lastname = 'Admin';
    $role = 'superadmin';

    // Check if exists
    $user = $db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);

    if ($user) {
        $db->query(
            "UPDATE users SET password = ?, email = ?, firstname = ?, lastname = ?, role = ? WHERE id = ?",
            [$password, $email, $firstname, $lastname, $role, $user['id']]
        );
        echo "Superadmin account updated successfully!";
    } else {
        $db->query(
            "INSERT INTO users (username, password, email, firstname, lastname, role) VALUES (?, ?, ?, ?, ?, ?)",
            [$username, $password, $email, $firstname, $lastname, $role]
        );
        echo "Superadmin account created successfully!";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
