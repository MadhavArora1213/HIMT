<?php
require_once '../includes/config.php';

// Handle Deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM fee_structures WHERE id = ?")->execute([$id]);
    header("Location: fee_structure.php?success=Structure deleted successfully");
    exit();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_structure'])) {
    $course_id = $_POST['course_id'];
    $academic_year = $_POST['academic_year'];
    $semester = $_POST['semester'];
    $fee_type = $_POST['fee_type'];
    $amount = $_POST['amount'];
    $due_date = $_POST['due_date'];
    
    try {
        $sql = "INSERT INTO fee_structures (course_id, academic_year, semester, fee_type, amount, due_date) VALUES (?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$course_id, $academic_year, $semester, $fee_type, $amount, $due_date]);
        $success = "Fee structure added successfully";
    } catch (Exception $e) {
        $error = db_error_message($e);
    }
}

// Fetch Data
$structures = $pdo->query("
    SELECT fs.*, c.name as course_name 
    FROM fee_structures fs 
    JOIN courses c ON fs.course_id = c.id 
    ORDER BY fs.academic_year DESC, c.name ASC
")->fetchAll();

$courses = $pdo->query("SELECT id, name FROM courses WHERE is_active = 1")->fetchAll();

$page_title = 'Fee Structures';
include '../includes/header.php';
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="fees.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Fee Management
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;">Course Fee Structures</h2>
    </div>

    <?php 
    $display_success = $success ?? $_GET['success'] ?? null;
    if ($display_success): ?>
        <div id="success-alert" style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2);">
            <i data-lucide="check-circle" size="18" style="vertical-align: middle; margin-right: 8px;"></i> <?php echo htmlspecialchars($display_success); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
        <!-- Add Structure Form -->
        <div>
            <div class="card" style="padding: 1.5rem;">
                <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.5rem;">Define New Fee</h3>
                <form action="" method="POST">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label>Course</label>
                        <select name="course_id" class="form-control" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label>Academic Year</label>
                            <input type="text" name="academic_year" class="form-control" placeholder="2026-27" required>
                        </div>
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label>Semester</label>
                            <select name="semester" class="form-control">
                                <option value="">Entire Year</option>
                                <?php for($i=1; $i<=10; $i++): ?>
                                    <option value="<?php echo $i; ?>">Sem <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label>Fee Type</label>
                        <input type="text" name="fee_type" class="form-control" placeholder="Tuition Fee / Library Fee" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label>Amount (₹)</label>
                        <input type="number" name="amount" class="form-control" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label>Due Date</label>
                        <input type="date" name="due_date" class="form-control" required>
                    </div>
                    <button type="submit" name="add_structure" class="btn btn-primary" style="width: 100%; padding: 0.75rem;">
                        <i data-lucide="plus"></i> Add Structure
                    </button>
                </form>
            </div>
        </div>

        <!-- Existing Structures List -->
        <div>
            <div class="card" style="padding: 1.5rem;">
                <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.5rem;">Defined Fees</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Course & Year</th>
                                <th>Fee Type</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($structures)): ?>
                                <tr><td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">No fee structures defined yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($structures as $s): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?php echo $s['course_name']; ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        AY <?php echo $s['academic_year']; ?> 
                                        <?php echo $s['semester'] ? "• Sem ".$s['semester'] : ""; ?>
                                    </div>
                                </td>
                                <td><?php echo $s['fee_type']; ?></td>
                                <td style="font-weight: 700;">₹<?php echo number_format($s['amount'], 2); ?></td>
                                <td><?php echo date('d M, Y', strtotime($s['due_date'])); ?></td>
                                <td>
                                    <a href="?delete=<?php echo $s['id']; ?>" class="btn-icon text-danger" onclick="return confirm('Delete this structure?')"><i data-lucide="trash-2" size="16"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide alerts after 3 seconds
        setTimeout(function() {
            const successAlert = document.getElementById('success-alert');
            if (successAlert) {
                successAlert.style.transition = 'opacity 0.5s';
                successAlert.style.opacity = '0';
                setTimeout(() => successAlert.remove(), 500);
            }
            
            const url = new URL(window.location);
            url.searchParams.delete('success');
            window.history.replaceState({}, document.title, url);
        }, 3000);
    });
</script>
