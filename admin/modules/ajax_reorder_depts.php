<?php
require_once '../includes/config.php';

// Only allow super_admin or admin
if (!in_array($_SESSION['role'] ?? '', ['super_admin', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['order']) && is_array($data['order'])) {
    try {
        $pdo->beginTransaction();
        foreach ($data['order'] as $index => $id) {
            $stmt = $pdo->prepare("UPDATE departments SET sort_order = ? WHERE id = ?");
            $stmt->execute([$index, $id]);
        }
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>
