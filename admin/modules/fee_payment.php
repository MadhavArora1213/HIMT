<?php
require_once '../includes/config.php';

$student_id = $_GET['student_id'] ?? null;
if (!$student_id) {
    header("Location: fees.php");
    exit();
}

// Fetch Student Info
$stmt = $pdo->prepare("
    SELECT s.*, u.name, u.email, c.name as course_name 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    JOIN courses c ON s.course_id = c.id 
    WHERE s.id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) die("Student not found.");

// Fetch Dues (Fee Structures for this student's course)
$stmt = $pdo->prepare("SELECT * FROM fee_structures WHERE course_id = ? AND is_active = 1");
$stmt->execute([$student['course_id']]);
$dues = $stmt->fetchAll();

// Handle Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['collect_payment'])) {
    $fs_id = $_POST['fee_structure_id'];
    $amount = $_POST['amount'];
    $mode = $_POST['payment_mode'];
    $transaction_id = $_POST['transaction_id'];
    $receipt_no = "RCP-" . time() . "-" . rand(100, 999);
    $date = date('Y-m-d');
    
    try {
        $sql = "INSERT INTO fee_payments (student_id, fee_structure_id, receipt_number, amount_paid, payment_date, payment_mode, transaction_id, payment_status, collected_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Success', ?)";
        $pdo->prepare($sql)->execute([$student_id, $fs_id, $receipt_no, $amount, $date, $mode, $transaction_id, $_SESSION['user_id']]);
        header("Location: fees.php?success=Payment recorded successfully. Receipt: $receipt_no");
        exit();
    } catch (Exception $e) {
        $error = db_error_message($e);
    }
}

$page_title = 'Collect Payment';
include '../includes/header.php';
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="fees.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Fee Management
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;">Record Payment: <?php echo $student['name']; ?></h2>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <!-- Left: Student Info & Dues -->
        <div class="card" style="padding: 1.5rem;">
            <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.5rem;">Payment Summary</h3>
            <div style="background: var(--background); padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <p style="font-size: 0.875rem; color: var(--text-muted);">Course: <strong><?php echo $student['course_name']; ?></strong></p>
                <p style="font-size: 0.875rem; color: var(--text-muted);">Enrollment: <strong><?php echo $student['enrollment_no']; ?></strong></p>
            </div>

            <h4 style="font-size: 0.875rem; font-weight: 700; margin-bottom: 1rem; color: var(--text-muted); text-transform: uppercase;">Available Fee Headings</h4>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($dues as $d): 
                    // Calculate already paid for this structure
                    $stmt = $pdo->prepare("SELECT SUM(amount_paid) FROM fee_payments WHERE student_id = ? AND fee_structure_id = ? AND payment_status = 'Success'");
                    $stmt->execute([$student_id, $d['id']]);
                    $paid = $stmt->fetchColumn() ?: 0;
                    $remaining = $d['amount'] - $paid;
                ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border: 1px solid var(--border); border-radius: 8px;">
                    <div>
                        <div style="font-weight: 600;"><?php echo $d['fee_type']; ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">Total: ₹<?php echo number_format($d['amount'], 2); ?> • Due: <?php echo date('d M, Y', strtotime($d['due_date'])); ?></div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 700; color: <?php echo $remaining > 0 ? 'var(--danger)' : 'var(--success)'; ?>">
                            <?php echo $remaining > 0 ? "₹".number_format($remaining, 2) : "Paid"; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right: Collection Form -->
        <div class="card" style="padding: 1.5rem;">
            <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.5rem;">Collect New Payment</h3>
            <form action="" method="POST">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Select Fee Heading</label>
                    <select name="fee_structure_id" class="form-control" required>
                        <option value="">Choose Structure</option>
                        <?php foreach ($dues as $d): ?>
                            <option value="<?php echo $d['id']; ?>"><?php echo $d['fee_type']; ?> (Max ₹<?php echo number_format($d['amount'], 0); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Amount Paid (₹)</label>
                    <input type="number" name="amount" class="form-control" placeholder="0.00" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label>Payment Mode</label>
                        <select name="payment_mode" class="form-control" required>
                            <option value="Cash">Cash</option>
                            <option value="Online">Online / UPI</option>
                            <option value="DD">Demand Draft</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label>Ref/Transaction ID</label>
                        <input type="text" name="transaction_id" class="form-control" placeholder="Optional">
                    </div>
                </div>
                <button type="submit" name="collect_payment" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 1rem;">
                    <i data-lucide="check-circle"></i> Confirm Payment Collection
                </button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
