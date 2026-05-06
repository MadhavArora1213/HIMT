<?php
header('Content-Type: application/json');
require_once '../../admin/includes/config.php';

// Authenticate Request
validate_api_token($pdo);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        // Fetch real-time dashboard data
        $stats = [
            'total_students' => $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn(),
            'total_faculty' => $pdo->query("SELECT COUNT(*) FROM faculty")->fetchColumn(),
            'pending_admissions' => $pdo->query("SELECT COUNT(*) FROM admissions WHERE status = 'submitted'")->fetchColumn(),
            'total_revenue' => $pdo->query("SELECT SUM(amount_paid) FROM fee_payments")->fetchColumn() ?: 0,
            'attendance_avg' => 85.5 // Placeholder for complex calc
        ];
        
        $recent_activity = $pdo->query("SELECT action, module, created_at FROM audit_logs ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data' => [
                'summary' => $stats,
                'recent_activity' => $recent_activity
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}
?>
