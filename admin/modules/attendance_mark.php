<?php
$page_title = 'Mark Attendance';
include '../includes/header.php';

$dept_id = $_GET['dept_id'] ?? '';
$course_id = $_GET['course_id'] ?? '';
$semester = $_GET['semester'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');

$students = [];
if ($dept_id && $course_id && $semester) {
    $stmt = $pdo->prepare("SELECT s.id, u.name, s.roll_number FROM students s JOIN users u ON s.user_id = u.id WHERE s.department_id = ? AND s.course_id = ? AND s.current_semester = ? AND s.is_active = 1 ORDER BY s.roll_number ASC");
    $stmt->execute([$dept_id, $course_id, $semester]);
    $students = $stmt->fetchAll();
}

// Fetch departments and courses
$depts = $pdo->query("SELECT id, name FROM departments WHERE is_active = 1")->fetchAll();
$courses = $pdo->query("SELECT id, name FROM courses WHERE is_active = 1")->fetchAll();

// Handle Attendance Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $attendance_data = $_POST['attendance']; // Array of student_id => status
    try {
        $pdo->beginTransaction();
        foreach ($attendance_data as $student_id => $status) {
            // Check if record exists
            $check = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND date = ?");
            $check->execute([$student_id, $date]);
            if ($check->fetch()) {
                $pdo->prepare("UPDATE attendance SET status = ?, marked_by = ? WHERE student_id = ? AND date = ?")
                    ->execute([$status, $_SESSION['user_id'], $student_id, $date]);
            } else {
                $pdo->prepare("INSERT INTO attendance (student_id, course_id, semester, date, status, marked_by) VALUES (?, ?, ?, ?, ?, ?)")
                    ->execute([$student_id, $course_id, $semester, $date, $status, $_SESSION['user_id']]);
            }
        }
        $pdo->commit();
        $success = "Attendance marked successfully for $date";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700;">Mark Attendance</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem;">Daily attendance logging for classes and subjects.</p>
    </div>

    <!-- Selection Bar -->
    <div class="card" style="margin-bottom: 2rem; padding: 1.5rem;">
        <form action="attendance_mark.php" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1.5rem; align-items: end;">
            <div class="form-group">
                <label>Department</label>
                <select name="dept_id" class="form-control" required>
                    <option value="">Select Dept</option>
                    <?php foreach ($depts as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo ($dept_id == $d['id']) ? 'selected' : ''; ?>><?php echo $d['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Course</label>
                <select name="course_id" class="form-control" required>
                    <option value="">Select Course</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($course_id == $c['id']) ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Semester</label>
                <select name="semester" class="form-control" required>
                    <?php for($i=1; $i<=8; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($semester == $i) ? 'selected' : ''; ?>><?php echo $i; ?>th Sem</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo $date; ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Load Class List</button>
        </form>
    </div>

    <?php if (isset($success)): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($students): ?>
    <form action="attendance_mark.php?dept_id=<?php echo $dept_id; ?>&course_id=<?php echo $course_id; ?>&semester=<?php echo $semester; ?>&date=<?php echo $date; ?>" method="POST">
        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 100px;">Roll No</th>
                            <th>Student Name</th>
                            <th style="text-align: center;">Present</th>
                            <th style="text-align: center;">Absent</th>
                            <th style="text-align: center;">Late</th>
                            <th style="text-align: center;">Medical</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): 
                            // Fetch existing status if any
                            $stmt = $pdo->prepare("SELECT status FROM attendance WHERE student_id = ? AND date = ?");
                            $stmt->execute([$s['id'], $date]);
                            $existing = $stmt->fetch();
                            $status = $existing['status'] ?? 'present';
                        ?>
                        <tr>
                            <td style="font-weight: 700;"><?php echo $s['roll_number']; ?></td>
                            <td><?php echo $s['name']; ?></td>
                            <td style="text-align: center;"><input type="radio" name="attendance[<?php echo $s['id']; ?>]" value="present" <?php echo ($status == 'present') ? 'checked' : ''; ?>></td>
                            <td style="text-align: center;"><input type="radio" name="attendance[<?php echo $s['id']; ?>]" value="absent" <?php echo ($status == 'absent') ? 'checked' : ''; ?>></td>
                            <td style="text-align: center;"><input type="radio" name="attendance[<?php echo $s['id']; ?>]" value="late" <?php echo ($status == 'late') ? 'checked' : ''; ?>></td>
                            <td style="text-align: center;"><input type="radio" name="attendance[<?php echo $s['id']; ?>]" value="medical" <?php echo ($status == 'medical') ? 'checked' : ''; ?>></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="padding: 2rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end;">
                <button type="submit" name="save_attendance" class="btn btn-primary" style="padding: 1rem 3rem;">
                    <i data-lucide="check-circle"></i> Save Attendance
                </button>
            </div>
        </div>
    </form>
    <?php elseif ($dept_id): ?>
        <div class="card" style="text-align: center; padding: 4rem;">
            <p style="color: var(--text-muted);">No students found in this class.</p>
        </div>
    <?php endif; ?>
</div>

<style>
    input[type="radio"] {
        width: 18px;
        height: 18px;
        accent-color: var(--accent);
        cursor: pointer;
    }
</style>

<?php include '../includes/footer.php'; ?>
