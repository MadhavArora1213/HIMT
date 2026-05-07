<?php
require_once '../includes/config.php';

// Handle Deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $pdo->beginTransaction();
        
        // Get user_id first
        $stmt = $pdo->prepare("SELECT user_id FROM faculty WHERE id = ?");
        $stmt->execute([$id]);
        $faculty = $stmt->fetch();
        
        if ($faculty) {
            // Delete faculty record
            $pdo->prepare("DELETE FROM faculty WHERE id = ?")->execute([$id]);
            // Delete user record
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$faculty['user_id']]);
        }
        
        $pdo->commit();
        header("Location: faculty.php?success=Faculty record deleted successfully");
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        header("Location: faculty.php?error=" . urlencode(db_error_message($e)));
    }
    exit();
}

// Handle Status Toggle
if (isset($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    $status = $_GET['status'];
    $new_status = ($status == 1) ? 0 : 1;
    $pdo->prepare("UPDATE faculty SET is_active = ? WHERE id = ?")->execute([$new_status, $id]);
    header("Location: faculty.php?success=Faculty status updated");
    exit();
}

$page_title = 'Faculty Management';
include '../includes/header.php';

// Success/Error Messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Fetch all faculty with department info
$sql = "SELECT f.*, u.name, u.email, d.name as dept_name 
        FROM faculty f
        JOIN users u ON f.user_id = u.id
        JOIN departments d ON f.department_id = d.id
        ORDER BY d.name ASC, u.name ASC";
$faculty_list = $pdo->query($sql)->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Faculty Directory</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Manage academic staff, designations, and departmental roles.</p>
        </div>
        <a href="faculty_edit.php" class="btn btn-primary">
            <i data-lucide="user-plus"></i> Add New Faculty
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
                        <th>Faculty Details</th>
                        <th>Employee ID</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Qualification</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($faculty_list)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">No faculty found.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($faculty_list as $f): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 40px; height: 40px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--accent);">
                                    <?php echo strtoupper(substr($f['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p style="font-weight: 600;"><?php echo $f['name']; ?> <?php if($f['is_hod']) echo '<span style="background: #fef3c7; color: #92400e; font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; margin-left: 5px;">HOD</span>'; ?></p>
                                    <p style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $f['email']; ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-family: monospace; font-weight: 600;"><?php echo $f['employee_code']; ?></span>
                        </td>
                        <td>
                            <span style="font-size: 0.875rem;"><?php echo $f['dept_name']; ?></span>
                        </td>
                        <td>
                            <span style="font-size: 0.875rem; color: var(--text-muted);"><?php echo $f['designation']; ?></span>
                        </td>
                        <td>
                            <span style="font-size: 0.8125rem;"><?php echo $f['qualification']; ?></span>
                        </td>
                        <td>
                            <a href="faculty.php?toggle_status=<?php echo $f['id']; ?>&status=<?php echo $f['is_active']; ?>" style="text-decoration: none;">
                                <?php if ($f['is_active']): ?>
                                    <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">Active</span>
                                <?php else: ?>
                                    <span style="background: rgba(244, 63, 94, 0.1); color: #f43f5e; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">Inactive</span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td>
                            <div style="display: flex; gap: 10px;">
                                <a href="faculty_edit.php?id=<?php echo $f['id']; ?>" class="btn-icon" title="Edit"><i data-lucide="edit-3" size="18"></i></a>
                                <a href="faculty.php?delete=<?php echo $f['id']; ?>" class="btn-icon text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this faculty member? This will also delete their login account.');">
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
                successAlert.style.opacity = '0';
                setTimeout(() => successAlert.remove(), 500);
            }
            if (errorAlert) {
                errorAlert.style.opacity = '0';
                setTimeout(() => errorAlert.remove(), 500);
            }
            
            // Clean URL parameters
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
