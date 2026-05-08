<?php
require_once 'includes/config.php';
$stmt = $pdo->query("SHOW CREATE TABLE faculty");
echo $stmt->fetch()['Create Table'];
?>
