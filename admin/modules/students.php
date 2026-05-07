<?php
require_once '../includes/config.php';

// Handle Deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Fetch user_id first to delete from users table as well if needed
        $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
        $stmt->execute([$id]);
        $student = $stmt->fetch();
        
        if ($student) {
            $pdo->prepare("DELETE FROM students WHERE id = ?")->execute([$id]);
            // Optional: Also delete from users table? 
            // In this schema, faculty/students are linked to users. 
            // Usually we keep the user but deactivate, or delete if it's a hard delete.
            // Based on schema.sql, students_ibfk_1 is ON DELETE CASCADE for user_id.
            // So if we delete the user, the student is deleted. 
            // If we delete the student, the user remains.
            // Let's just delete the student record for now, or user if preferred.
            // The user requested "delete it not problem" earlier for departments.
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$student['user_id']]);
        }
        
        header("Location: students.php?success=Student record deleted successfully");
    } catch (PDOException $e) {
        header("Location: students.php?error=Error deleting student: " . $e->getMessage());
    }
    exit();
}

$page_title = 'Student Management';
include '../includes/header.php';

// Success/Error Messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Filters
$dept_filter = $_GET['dept_id'] ?? '';
$course_filter = $_GET['course_id'] ?? '';
$sem_filter = $_GET['semester'] ?? '';
$search = $_GET['search'] ?? '';

// Build Query
$query = "SELECT s.*, u.name, u.email, d.name as dept_name, c.name as course_name 
          FROM students s
          JOIN users u ON s.user_id = u.id
          JOIN departments d ON s.department_id = d.id
          JOIN courses c ON s.course_id = c.id
          WHERE 1=1";

$params = [];
if ($dept_filter) { $query .= " AND s.department_id = ?"; $params[] = $dept_filter; }
if ($course_filter) { $query .= " AND s.course_id = ?"; $params[] = $course_filter; }
if ($sem_filter) { $query .= " AND s.current_semester = ?"; $params[] = $sem_filter; }
if ($search) { 
    $query .= " AND (u.name LIKE ? OR s.enrollment_no LIKE ? OR s.roll_number LIKE ?)"; 
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}

$query .= " ORDER BY s.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Fetch departments and courses for filters
$depts = $pdo->query("SELECT id, name FROM departments WHERE is_active = 1")->fetchAll();
$courses = $pdo->query("SELECT id, name FROM courses WHERE is_active = 1")->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Student Directory</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Manage complete student lifecycle and academic records.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn btn-primary" onclick="location.href='student_import.php'" style="background: white; color: var(--text-main); border: 1px solid var(--border);">
                <i data-lucide="upload"></i> Bulk Import
            </button>
            <button class="btn btn-primary" onclick="location.href='student_edit.php'">
                <i data-lucide="user-plus"></i> New Enrollment
            </button>
        </div>
    </div>

    <!-- Advanced Filters -->
    <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
        <form action="students.php" method="GET" class="filter-row">
            <div class="form-group">
                <label style="font-size: 0.75rem; font-weight: 600; margin-bottom: 5px; display: block;">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name or Enrollment..." value="<?php echo $search; ?>">
            </div>
            <div class="form-group">
                <label style="font-size: 0.75rem; font-weight: 600; margin-bottom: 5px; display: block;">Department</label>
                <select name="dept_id" class="form-control">
                    <option value="">All Departments</option>
                    <?php foreach ($depts as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo ($dept_filter == $d['id']) ? 'selected' : ''; ?>><?php echo $d['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label style="font-size: 0.75rem; font-weight: 600; margin-bottom: 5px; display: block;">Course</label>
                <select name="course_id" class="form-control">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($course_filter == $c['id']) ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label style="font-size: 0.75rem; font-weight: 600; margin-bottom: 5px; display: block;">Semester</label>
                <select name="semester" class="form-control">
                    <option value="">All</option>
                    <?php for($i=1; $i<=10; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($sem_filter == $i) ? 'selected' : ''; ?>><?php echo $i; ?><?php 
                            if($i==1) echo 'st'; 
                            elseif($i==2) echo 'nd'; 
                            elseif($i==3) echo 'rd'; 
                            else echo 'th'; 
                        ?> Sem</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary" style="flex: 1;"><i data-lucide="search" size="16"></i></button>
                <a href="students.php" class="btn" style="background: #f1f5f9; color: var(--text-main);"><i data-lucide="rotate-ccw" size="16"></i></a>
            </div>
        </form>
    </div>

    <?php if ($success): ?>
        <div id="success-alert" style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2);">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div id="error-alert" style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2);">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Student Info</th>
                        <th>Academic Details</th>
                        <th>Contact & Parent</th>
                        <th>Admission</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem;">
                                <i data-lucide="users" size="48" style="color: #cbd5e1; margin-bottom: 1rem;"></i>
                                <p style="color: var(--text-muted);">No students found matching your criteria.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($students as $s): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 45px; height: 45px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--accent);">
                                    <?php echo strtoupper(substr($s['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p style="font-weight: 700; font-size: 0.9375rem;"><?php echo $s['name']; ?></p>
                                    <p style="font-size: 0.75rem; color: var(--text-muted);">ENR: <?php echo $s['enrollment_no']; ?></p>
                                    <p style="font-size: 0.75rem; color: var(--text-muted);">Roll: <?php echo $s['roll_number']; ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <p style="font-size: 0.875rem; font-weight: 600;"><?php echo $s['course_name']; ?></p>
                            <p style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $s['dept_name']; ?></p>
                            <span style="background: var(--accent); color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem;"><?php echo $s['current_semester']; ?>th Sem</span>
                        </td>
                        <td>
                            <p style="font-size: 0.8125rem;"><i data-lucide="phone" size="12"></i> <?php echo $s['parent_phone']; ?> (P)</p>
                            <p style="font-size: 0.8125rem; color: var(--text-muted);"><?php echo $s['parent_name']; ?></p>
                        </td>
                        <td>
                            <p style="font-size: 0.8125rem;"><?php echo $s['admission_year']; ?> Batch</p>
                            <p style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $s['admission_type']; ?></p>
                        </td>
                        <td>
                            <?php if ($s['is_active']): ?>
                                <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">Active</span>
                            <?php else: ?>
                                <span style="background: rgba(244, 63, 94, 0.1); color: #f43f5e; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <a href="student_profile.php?id=<?php echo $s['id']; ?>" class="btn-icon" title="View Profile"><i data-lucide="external-link" size="18"></i></a>
                                <a href="student_edit.php?id=<?php echo $s['id']; ?>" class="btn-icon" title="Edit"><i data-lucide="edit-3" size="18"></i></a>
                                <a href="students.php?delete=<?php echo $s['id']; ?>" class="btn-icon text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this student? This will permanently remove their academic records, attendance, and login account.');">
                                    <i data-lucide="trash-2" size="18"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide alerts after 3 seconds
        setTimeout(function() {
            const successAlert = document.getElementById('success-alert');
            const errorAlert = document.getElementById('error-alert');
            if (successAlert) {
                successAlert.style.transition = 'opacity 0.5s';
                successAlert.style.opacity = '0';
                setTimeout(() => successAlert.remove(), 500);
            }
            if (errorAlert) {
                errorAlert.style.transition = 'opacity 0.5s';
                errorAlert.style.opacity = '0';
                setTimeout(() => errorAlert.remove(), 500);
            }
            
            // Remove query parameters from URL without refreshing
            const url = new URL(window.location);
            url.searchParams.delete('success');
            url.searchParams.delete('error');
            window.history.replaceState({}, document.title, url);
        }, 3000);
    });
</script>

<style>
    .btn-icon {
        background: transparent;
        border: none;
        cursor: pointer;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.2s;
        text-decoration: none;
    }
    .btn-icon:hover { color: var(--accent); }
    .text-danger:hover { color: var(--danger); }
    .form-group label { color: var(--text-muted); }
</style>

<?php include '../includes/footer.php'; ?>
