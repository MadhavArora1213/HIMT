<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) { die("Access Denied."); }

$type = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'excel';

if ($type == 'students') {
    $sql = "SELECT s.enrollment_no, u.name, d.short_name as dept, c.short_name as course, s.current_semester, u.email, u.phone 
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            JOIN departments d ON s.department_id = d.id 
            JOIN courses c ON s.course_id = c.id 
            ORDER BY s.enrollment_no ASC";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $filename = "student_master_list_" . date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Enrollment No', 'Name', 'Department', 'Course', 'Semester', 'Email', 'Phone']);
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    log_action($pdo, 'Exported Report: ' . $type, 'Reports');
    fclose($output);
    exit();
} else {
    // Other reports follow similar logic
    header("Location: reports.php?error=Report generator for this type is coming soon.");
}
?>
