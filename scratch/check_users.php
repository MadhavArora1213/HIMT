<?php
require_once 'admin/includes/config.php';
$stmt = $pdo->query("SELECT name, email, role FROM users");
print_r($stmt->fetchAll());
