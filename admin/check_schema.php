<?php
require_once 'includes/config.php';
$stmt = $pdo->query("SELECT id, name FROM courses");
echo json_encode($stmt->fetchAll());
?>
