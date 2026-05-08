<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['query'])) {
    echo json_encode([]);
    exit();
}

$query = "%" . $_GET['query'] . "%";
$stmt = $pdo->prepare("
    SELECT s.id, u.name, s.enrollment_no, c.short_name as course 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    JOIN courses c ON s.course_id = c.id 
    WHERE u.name LIKE ? OR s.enrollment_no LIKE ? 
    LIMIT 10
");
$stmt->execute([$query, $query]);
echo json_encode($stmt->fetchAll());
?>
