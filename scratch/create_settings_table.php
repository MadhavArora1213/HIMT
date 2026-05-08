<?php
require_once 'admin/includes/config.php';

try {
    // 1. Create settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "Settings table created/verified.\n";
    
    // 2. Seed default settings
    $default_settings = [
        'maintenance_mode' => '0',
        'admin_email' => 'admin@thehimt.com',
        'current_academic_year' => '2025-26',
        'timezone' => 'Asia/Kolkata',
        'site_title' => 'HIMT',
        'meta_keywords' => 'HIMT, Education, College',
        'meta_description' => 'HIMT College Administration Portal'
    ];
    
    foreach ($default_settings as $key => $value) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }
    echo "Default settings seeded.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
