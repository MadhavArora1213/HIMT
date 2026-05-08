<?php
$page_title = 'Marks Entry';
include '../includes/header.php';

$exam_id = $_GET['exam_id'] ?? '';
$subject_id = $_GET['subject_id'] ?? '';

$exam = null;
if ($exam_id) {
    $stmt = $pdo->prepare("SELECT e.*, c.name as course_name FROM examinations e JOIN courses c ON e.course_id = c.id WHERE e.id = ?");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch();
}

$subjects = [];
if ($exam) {
    $stmt = $pdo->prepare("SELECT id, name, subject_code FROM subjects WHERE course_id = ? AND semester = ?");
    $stmt->execute([$exam['course_id'], $exam['semester']]);
    $subjects = $stmt->fetchAll();
}

$students = [];
if ($subject_id) {
    $stmt = $pdo->prepare("SELECT s.id, u.name, s.roll_number FROM students s JOIN users u ON s.user_id = u.id WHERE s.course_id = ? AND s.current_semester = ? ORDER BY s.roll_number ASC");
    $stmt->execute([$exam['course_id'], $exam['semester']]);
    $students = $stmt->fetchAll();
}

// Handle Marks Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
    $marks_data = $_POST['marks']; // student_id => [internal, external]
    try {
        $pdo->beginTransaction();
        foreach ($marks_data as $student_id => $m) {
            $internal = $m['internal'] ?: 0;
            $external = $m['external'] ?: 0;
            $total = $internal + $external;
            
            // Basic Grade Calculation (Threshold: 40%)
            $result = ($total >= 40) ? 'Pass' : 'Fail';
            
            $check = $pdo->prepare("SELECT id FROM marks WHERE student_id = ? AND subject_id = ? AND exam_id = ?");
            $check->execute([$student_id, $subject_id, $exam_id]);
            
            if ($check->fetch()) {
                $pdo->prepare("UPDATE marks SET internal_marks = ?, external_marks = ?, total_marks = ?, result = ?, entered_by = ? WHERE student_id = ? AND subject_id = ? AND exam_id = ?")
                    ->execute([$internal, $external, $total, $result, $_SESSION['user_id'], $student_id, $subject_id, $exam_id]);
            } else {
                $pdo->prepare("INSERT INTO marks (student_id, subject_id, exam_id, internal_marks, external_marks, total_marks, result, entered_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$student_id, $subject_id, $exam_id, $internal, $external, $total, $result, $_SESSION['user_id']]);
            }
        }
        $pdo->commit();
        $success = "Marks updated successfully.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="exams.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Exams
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;">Marks Entry: <?php echo $exam['exam_name'] ?? ''; ?></h2>
        <p style="color: var(--text-muted); font-size: 0.875rem;"><?php echo $exam['course_name'] ?? ''; ?> - Semester <?php echo $exam['semester'] ?? ''; ?></p>
    </div>

    <!-- Selection -->
    <div class="card" style="margin-bottom: 2rem; padding: 1.5rem;">
        <form action="marks_entry.php" method="GET">
            <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
            <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; display: block;">Select Subject</label>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <select name="subject_id" class="form-control" required style="flex: 1; height: 48px;">
                    <option value="">-- Select Subject --</option>
                    <?php foreach ($subjects as $sub): ?>
                        <option value="<?php echo $sub['id']; ?>" <?php echo ($subject_id == $sub['id']) ? 'selected' : ''; ?>><?php echo $sub['subject_code']; ?> - <?php echo $sub['name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary" style="height: 48px; padding: 0 2rem;">Load Marksheet</button>
            </div>
        </form>
    </div>

    <?php if (isset($success)): ?>
        <div id="success-alert" style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2);">
            <i data-lucide="check-circle" size="18" style="vertical-align: middle; margin-right: 8px;"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($students): ?>
    <form action="marks_entry.php?exam_id=<?php echo $exam_id; ?>&subject_id=<?php echo $subject_id; ?>" method="POST">
        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 100px;">Roll No</th>
                            <th>Student Name</th>
                            <th style="text-align: center; width: 150px;">Internal (Max 30)</th>
                            <th style="text-align: center; width: 150px;">External (Max 70)</th>
                            <th style="text-align: center; width: 120px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): 
                            $stmt = $pdo->prepare("SELECT internal_marks, external_marks, total_marks FROM marks WHERE student_id = ? AND subject_id = ? AND exam_id = ?");
                            $stmt->execute([$s['id'], $subject_id, $exam_id]);
                            $m = $stmt->fetch();
                        ?>
                        <tr>
                            <td style="font-weight: 700;"><?php echo $s['roll_number']; ?></td>
                            <td><?php echo $s['name']; ?></td>
                            <td><input type="number" name="marks[<?php echo $s['id']; ?>][internal]" class="form-control" style="text-align: center;" value="<?php echo $m['internal_marks'] ?? ''; ?>"></td>
                            <td><input type="number" name="marks[<?php echo $s['id']; ?>][external]" class="form-control" style="text-align: center;" value="<?php echo $m['external_marks'] ?? ''; ?>"></td>
                            <td style="text-align: center; font-weight: 700; color: var(--accent);"><?php echo $m['total_marks'] ?? '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="padding: 2rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end;">
                <button type="submit" name="save_marks" class="btn btn-primary" style="padding: 1rem 3rem;">
                    <i data-lucide="save"></i> Save Marksheet
                </button>
            </div>
        </div>
    </form>
    <?php endif; ?>
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
