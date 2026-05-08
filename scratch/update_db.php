<?php
require_once 'admin/includes/config.php';

try {
    // Add columns if they don't exist
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS permissions TEXT NULL AFTER role");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 AFTER password");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_at DATETIME NULL AFTER is_active");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_ip VARCHAR(45) NULL AFTER last_login_at");
    
    echo "Columns added/verified.\n";
    
    // Check for admin user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'Super Admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if (!$admin) {
        $name = "Super Admin";
        $email = "admin@thehimt.com";
        $password = password_hash("admin123", PASSWORD_DEFAULT);
        $role = "Super Admin";
        $permissions = json_encode(['all']);
        
        $pdo->prepare("INSERT INTO users (name, email, password, role, permissions, is_active) VALUES (?, ?, ?, ?, ?, 1)")
            ->execute([$name, $email, $password, $role, $permissions]);
        echo "Created Super Admin: admin@thehimt.com / admin123\n";
    } else {
        echo "Super Admin already exists.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
