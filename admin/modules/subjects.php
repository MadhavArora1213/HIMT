<?php
require_once '../includes/config.php';

// Handle Deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM subjects WHERE id = ?")->execute([$id]);
        header("Location: subjects.php?success=Subject deleted successfully");
    } catch (Exception $e) {
        header("Location: subjects.php?error=" . urlencode(db_error_message($e)));
    }
    exit();
}

$page_title = 'Subject Management';
include '../includes/header.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Fetch all subjects with course and department info
$sql = "SELECT s.*, c.name as course_name, d.name as dept_name 
        FROM subjects s
        JOIN courses c ON s.course_id = c.id
        JOIN departments d ON s.department_id = d.id
        ORDER BY d.name ASC, c.name ASC, s.semester ASC, s.name ASC";
$subjects = $pdo->query($sql)->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Subjects</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Manage curriculum and subject details for all courses.</p>
        </div>
        <a href="subject_edit.php" class="btn btn-primary">
            <i data-lucide="plus"></i> Add New Subject
        </a>
    </div>

    <?php if ($success): ?>
        <div id="success-alert" style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2); transition: opacity 0.5s;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div id="error-alert" style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2); transition: opacity 0.5s;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Subject Details</th>
                        <th>Code</th>
                        <th>Department & Course</th>
                        <th>Semester</th>
                        <th>Type</th>
                        <th>Credits</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subjects)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">No subjects found.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($subjects as $s): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600;"><?php echo $s['name']; ?></div>
                        </td>
                        <td>
                            <span style="font-family: monospace; font-weight: 600; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;"><?php echo $s['subject_code']; ?></span>
                        </td>
                        <td>
                            <div style="font-size: 0.875rem;"><?php echo $s['dept_name']; ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $s['course_name']; ?></div>
                        </td>
                        <td>
                            <span style="background: #eef2ff; color: #4338ca; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 600;"><?php echo $s['semester']; ?>th Sem</span>
                        </td>
                        <td>
                            <span style="font-size: 0.8125rem;"><?php echo $s['subject_type']; ?></span>
                        </td>
                        <td>
                            <span style="font-weight: 600;"><?php echo $s['credits']; ?></span>
                        </td>
                        <td>
                            <?php if ($s['is_active']): ?>
                                <span style="color: #10b981; font-size: 0.75rem; font-weight: 600;">Active</span>
                            <?php else: ?>
                                <span style="color: #f43f5e; font-size: 0.75rem; font-weight: 600;">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 10px;">
                                <a href="subject_edit.php?id=<?php echo $s['id']; ?>" class="btn-icon" title="Edit"><i data-lucide="edit-3" size="18"></i></a>
                                <a href="subjects.php?delete=<?php echo $s['id']; ?>" class="btn-icon text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this subject?');">
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
</style>

<?php include '../includes/footer.php'; ?>
