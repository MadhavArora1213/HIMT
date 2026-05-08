<?php
require_once 'admin/includes/config.php';

try {
    // Force update the default admin user to Super Admin role
    $pdo->exec("UPDATE users SET role = 'Super Admin', permissions = '[\"all\"]', is_active = 1 WHERE email = 'admin@thehimt.com' OR id = 1 OR name = 'Super Admin'");
    echo "Administrative roles updated.\n";
    
    $stmt = $pdo->query("SELECT id, name, email, role FROM users LIMIT 5");
    print_r($stmt->fetchAll());

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
