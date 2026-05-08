<?php
require_once 'admin/includes/config.php';

try {
    // 1. Change role column from ENUM to VARCHAR to support new roles
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL");
    echo "Role column converted to VARCHAR.\n";
    
    // 2. Map old enum values to new strings if needed, or just force the admin
    $pdo->exec("UPDATE users SET role = 'Super Admin' WHERE role = 'super_admin'");
    $pdo->exec("UPDATE users SET role = 'Admin' WHERE role = 'admin'");
    
    // 3. Force ID 1 and admin email to Super Admin
    $pdo->exec("UPDATE users SET role = 'Super Admin', permissions = '[\"all\"]', is_active = 1 WHERE email = 'admin@thehimt.com' OR id = 1");
    
    echo "Administrative roles updated successfully.\n";
    
    // Check results
    $stmt = $pdo->query("SELECT id, name, email, role FROM users LIMIT 5");
    print_r($stmt->fetchAll());

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
