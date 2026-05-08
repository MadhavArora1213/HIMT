<?php
require_once '../includes/config.php';

$dept_id = $_GET['dept_id'] ?? '';
$course_id = $_GET['course_id'] ?? '';
$semester = $_GET['semester'] ?? '';
$subject_id = $_GET['subject_id'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');

// Pagination settings
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Fetch students with dynamic filtering and pagination
$where = ["s.is_active = 1"];
$params = [];

if ($dept_id) { $where[] = "s.department_id = ?"; $params[] = $dept_id; }
if ($course_id) { $where[] = "s.course_id = ?"; $params[] = $course_id; }
if ($semester) { $where[] = "s.current_semester = ?"; $params[] = $semester; }

$where_sql = implode(" AND ", $where);

// Count total students for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM students s WHERE $where_sql");
$count_stmt->execute($params);
$total_students = $count_stmt->fetchColumn();
$total_pages = ceil($total_students / $limit);

// Fetch the student list for current page
$sql = "SELECT s.id, u.name, s.roll_number 
        FROM students s 
        JOIN users u ON s.user_id = u.id 
        WHERE $where_sql 
        ORDER BY s.roll_number ASC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Fetch departments and courses for filters
$depts = $pdo->query("SELECT id, name FROM departments WHERE is_active = 1")->fetchAll();
$courses = $pdo->query("SELECT id, name FROM courses WHERE is_active = 1")->fetchAll();

$subjects = [];
if ($course_id && $semester) {
    $stmt = $pdo->prepare("SELECT id, name, subject_code FROM subjects WHERE course_id = ? AND semester = ? AND is_active = 1");
    $stmt->execute([$course_id, $semester]);
    $subjects = $stmt->fetchAll();
}

// Handle Attendance Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    if (!$subject_id) {
        $error = "Please select a Subject to mark attendance.";
    } else {
        $attendance_data = $_POST['attendance']; // Array of student_id => status
        try {
            $pdo->beginTransaction();
            foreach ($attendance_data as $student_id => $status) {
            // Check if record exists
            $check = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND subject_id = ? AND attendance_date = ?");
            $check->execute([$student_id, $subject_id, $date]);
            if ($check->fetch()) {
                $pdo->prepare("UPDATE attendance SET status = ? WHERE student_id = ? AND subject_id = ? AND attendance_date = ?")
                    ->execute([ucfirst($status), $student_id, $subject_id, $date]);
            } else {
                $pdo->prepare("INSERT INTO attendance (student_id, subject_id, attendance_date, status) VALUES (?, ?, ?, ?)")
                    ->execute([$student_id, $subject_id, $date, ucfirst($status)]);
            }
        }
        $pdo->commit();
        $success = "Attendance marked successfully for $date";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = db_error_message($e);
    }
}
}

$page_title = 'Mark Attendance';
include '../includes/header.php';
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700;">Mark Attendance</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem;">Daily attendance logging for classes and subjects.</p>
    </div>

    <!-- Selection Bar -->
    <div class="card" style="margin-bottom: 2rem; padding: 1.25rem;">
        <form action="attendance_mark.php" method="GET" id="filterForm" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)) 140px; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; display: block;">Department</label>
                <select name="dept_id" class="form-control" required style="font-size: 0.875rem; height: 42px; padding: 0 0.75rem; padding-right: 2rem;">
                    <option value="">Select Dept</option>
                    <?php foreach ($depts as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo ($dept_id == $d['id']) ? 'selected' : ''; ?>><?php echo $d['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; display: block;">Course</label>
                <select name="course_id" class="form-control" required onchange="this.form.submit()" style="font-size: 0.875rem; height: 42px; padding: 0 0.75rem; padding-right: 2rem;">
                    <option value="">Select Course</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($course_id == $c['id']) ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; display: block;">Semester</label>
                <select name="semester" class="form-control" required onchange="this.form.submit()" style="font-size: 0.875rem; height: 42px; padding: 0 0.75rem; padding-right: 2rem;">
                    <option value="">Select Sem</option>
                    <?php for($i=1; $i<=10; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($semester == $i) ? 'selected' : ''; ?>><?php echo $i; ?><?php 
                            if($i==1) echo 'st'; 
                            elseif($i==2) echo 'nd'; 
                            elseif($i==3) echo 'rd'; 
                            else echo 'th'; 
                        ?> Sem</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; display: block;">Subject</label>
                <select name="subject_id" class="form-control" required style="font-size: 0.875rem; height: 42px; padding: 0 0.75rem; padding-right: 2rem;">
                    <?php if (empty($subjects)): ?>
                        <option value="">No Subjects</option>
                    <?php else: ?>
                        <option value="">Select Sub</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo ($subject_id == $s['id']) ? 'selected' : ''; ?>><?php echo $s['name']; ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; display: block;">Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo $date; ?>" required style="font-size: 0.875rem; height: 42px; padding: 0 0.75rem;">
            </div>
            <button type="submit" class="btn btn-primary" style="height: 42px; width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 0.875rem; border-radius: 10px;">
                <i data-lucide="search" size="18"></i> Load Class
            </button>
        </form>
        <?php if ($course_id && $semester && empty($subjects)): ?>
            <p style="color: var(--danger); font-size: 0.7rem; margin-top: 8px; text-align: center;">
                <i data-lucide="alert-circle" size="10"></i> No subjects found. <a href="subject_edit.php" style="color: var(--accent); font-weight: 600;">Add Subjects</a>
            </p>
        <?php endif; ?>
    </div>

    <?php if (isset($success)): ?>
        <div id="success-alert" style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2); transition: opacity 0.5s;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div id="error-alert" style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2); transition: opacity 0.5s;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($students): ?>
    <form action="attendance_mark.php?dept_id=<?php echo $dept_id; ?>&course_id=<?php echo $course_id; ?>&semester=<?php echo $semester; ?>&subject_id=<?php echo $subject_id; ?>&date=<?php echo $date; ?>&page=<?php echo $page; ?>" method="POST">
        <div class="card">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 1rem;">Student List (<?php echo count($students); ?> students)</h3>
                <div style="font-size: 0.8125rem; color: var(--text-muted);">
                    Date: <strong><?php echo date('d M Y', strtotime($date)); ?></strong>
                </div>
            </div>
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
                            $stmt = $pdo->prepare("SELECT status FROM attendance WHERE student_id = ? AND subject_id = ? AND attendance_date = ?");
                            $stmt->execute([$s['id'], $subject_id, $date]);
                            $existing = $stmt->fetch();
                            $status = $existing['status'] ?? 'Present';
                        ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--accent);"><?php echo $s['roll_number']; ?></td>
                            <td>
                                <div style="font-weight: 600;"><?php echo $s['name']; ?></div>
                            </td>
                            <td style="text-align: center;"><input type="radio" name="attendance[<?php echo $s['id']; ?>]" value="present" <?php echo (strtolower($status) == 'present') ? 'checked' : ''; ?>></td>
                            <td style="text-align: center;"><input type="radio" name="attendance[<?php echo $s['id']; ?>]" value="absent" <?php echo (strtolower($status) == 'absent') ? 'checked' : ''; ?>></td>
                            <td style="text-align: center;"><input type="radio" name="attendance[<?php echo $s['id']; ?>]" value="late" <?php echo (strtolower($status) == 'late') ? 'checked' : ''; ?>></td>
                            <td style="text-align: center;"><input type="radio" name="attendance[<?php echo $s['id']; ?>]" value="medical" <?php echo (strtolower($status) == 'medical') ? 'checked' : ''; ?>></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="padding: 1.5rem 2rem; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
                <div style="font-size: 0.875rem; color: var(--text-muted);">
                    Showing page <strong><?php echo $page; ?></strong> of <strong><?php echo $total_pages; ?></strong> (Total <?php echo $total_students; ?> students)
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <div class="pagination" style="display: flex; gap: 5px;">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="btn" style="padding: 0.5rem 1rem; background: white; border: 1px solid var(--border); font-size: 0.8125rem;"><i data-lucide="chevron-left" size="16"></i> Previous</a>
                        <?php endif; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="btn" style="padding: 0.5rem 1rem; background: white; border: 1px solid var(--border); font-size: 0.8125rem;">Next <i data-lucide="chevron-right" size="16"></i></a>
                        <?php endif; ?>
                    </div>
                    <button type="submit" name="save_attendance" class="btn btn-primary" style="padding: 0.75rem 2rem; <?php echo !$subject_id ? 'opacity: 0.5; cursor: not-allowed;' : ''; ?>" <?php echo !$subject_id ? 'disabled' : ''; ?>>
                        <i data-lucide="check-circle"></i> Save Attendance
                    </button>
                </div>
            </div>
            <?php if (!$subject_id): ?>
                <div style="padding: 1rem; background: #fffbeb; color: #92400e; font-size: 0.8125rem; text-align: center; border-top: 1px solid #fef3c7;">
                    <i data-lucide="alert-triangle" size="14" style="vertical-align: middle;"></i> Please select a <strong>Subject</strong> above to enable attendance marking.
                </div>
            <?php endif; ?>
        </div>
    </form>
    <?php elseif ($dept_id && $subject_id): ?>
        <div class="card" style="text-align: center; padding: 4rem;">
            <div style="background: #f1f5f9; width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: var(--text-muted);">
                <i data-lucide="users-2" size="32"></i>
            </div>
            <h3 style="font-size: 1.125rem; color: var(--text-main); margin-bottom: 0.5rem;">No Students Found</h3>
            <p style="color: var(--text-muted); font-size: 0.875rem; max-width: 400px; margin: 0 auto;">
                We couldn't find any active students for the selected Department, Course, and Semester. 
            </p>
        </div>
    <?php elseif ($dept_id): ?>
        <div class="card" style="text-align: center; padding: 4rem; border: 1px dashed var(--border);">
            <p style="color: var(--text-muted);">Please select a <strong>Subject</strong> to load the student list.</p>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide alerts after 3 seconds
        setTimeout(function() {
            const successAlert = document.getElementById('success-alert');
            const errorAlert = document.getElementById('error-alert');
            if (successAlert) {
                successAlert.style.opacity = '0';
                setTimeout(() => successAlert.remove(), 500);
            }
            if (errorAlert) {
                errorAlert.style.opacity = '0';
                setTimeout(() => errorAlert.remove(), 500);
            }
        }, 3000);
    });
</script>
