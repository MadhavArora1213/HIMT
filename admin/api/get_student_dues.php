<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

$student_id = $_GET['student_id'] ?? null;
if (!$student_id) {
    echo json_encode(['error' => 'Missing student ID']);
    exit();
}

try {
    // Student Info
    $stmt = $pdo->prepare("SELECT s.course_id, c.name as course_name FROM students s JOIN courses c ON s.course_id = c.id WHERE s.id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    if (!$student) {
        echo json_encode(['error' => 'Student not found']);
        exit();
    }

    // Dues (Fee Structures)
    $stmt = $pdo->prepare("SELECT id, fee_type, amount FROM fee_structures WHERE course_id = ? AND is_active = 1");
    $stmt->execute([$student['course_id']]);
    $structures = $stmt->fetchAll();

    $result = [];
    foreach ($structures as $s) {
        // Paid for this structure
        $stmt = $pdo->prepare("SELECT SUM(amount_paid) FROM fee_payments WHERE student_id = ? AND fee_structure_id = ? AND payment_status = 'Success'");
        $stmt->execute([$student_id, $s['id']]);
        $paid = $stmt->fetchColumn() ?: 0;
        
        $result[] = [
            'id' => $s['id'],
            'fee_type' => $s['fee_type'],
            'amount' => $s['amount'],
            'paid' => $paid,
            'balance' => $s['amount'] - $paid
        ];
    }

    echo json_encode(['student' => $student, 'dues' => $result]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
