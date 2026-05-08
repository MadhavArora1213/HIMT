<?php
require_once 'c:/xampp/htdocs/HIMT/admin/includes/config.php';

try {
    echo "--- STUDY_MATERIALS TABLE ---\n";
    $q = $pdo->query("DESCRIBE study_materials");
    while($row = $q->fetch()) {
        print_r($row);
    }
    
    echo "\n--- FACULTY TABLE (FIRST 5) ---\n";
    $q = $pdo->query("SELECT * FROM faculty LIMIT 5");
    while($row = $q->fetch()) {
        print_r($row);
    }

    echo "\n--- CURRENT SESSION USER ID ---\n";
    session_start();
    echo "User ID: " . ($_SESSION['user_id'] ?? 'NONE') . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
