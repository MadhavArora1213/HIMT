<?php
require_once 'admin/includes/config.php';
$stmt = $pdo->query("DESCRIBE users role");
print_r($stmt->fetch());
