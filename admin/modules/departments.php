<?php
$page_title = 'Department Management';
include '../includes/header.php';

// Success/Error Messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Fetch all departments with faculty and student counts
$sql = "SELECT d.*, 
        f_user.name as hod_name,
        (SELECT COUNT(*) FROM faculty WHERE department_id = d.id) as faculty_count,
        (SELECT COUNT(*) FROM students WHERE department_id = d.id) as student_count
        FROM departments d
        LEFT JOIN faculty f ON d.hod_id = f.id
        LEFT JOIN users f_user ON f.user_id = f_user.id
        ORDER BY d.sort_order ASC";
$departments = $pdo->query($sql)->fetchAll();

// Handle Deactivation
if (isset($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    $status = $_GET['status'];
    $new_status = ($status == 1) ? 0 : 1;
    $pdo->prepare("UPDATE departments SET is_active = ? WHERE id = ?")->execute([$new_status, $id]);
    header("Location: departments.php?success=Status updated successfully");
    exit();
}
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Departments</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Manage academic and non-academic departments.</p>
        </div>
        <a href="department_edit.php" class="btn btn-primary">
            <i data-lucide="plus"></i> Add New Department
        </a>
    </div>

    <?php if ($success): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2);">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">Order</th>
                        <th>Department Info</th>
                        <th>HOD</th>
                        <th>Counts</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="sortable-departments">
                    <?php if (empty($departments)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">No departments found.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($departments as $dept): ?>
                    <tr data-id="<?php echo $dept['id']; ?>">
                        <td>
                            <div class="drag-handle" style="cursor: move; color: var(--text-muted);">
                                <i data-lucide="grip-vertical" size="18"></i>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <?php if ($dept['department_image']): ?>
                                    <img src="../<?php echo $dept['department_image']; ?>" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 40px; height: 40px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--accent);">
                                        <i data-lucide="building-2" size="20"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <p style="font-weight: 600;"><?php echo $dept['name']; ?></p>
                                    <p style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $dept['short_name']; ?> | Est. <?php echo $dept['established_year'] ?? 'N/A'; ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($dept['hod_name']): ?>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 24px; height: 24px; background: var(--accent); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; font-weight: 700;">
                                        <?php echo strtoupper(substr($dept['hod_name'], 0, 1)); ?>
                                    </div>
                                    <span style="font-size: 0.875rem;"><?php echo $dept['hod_name']; ?></span>
                                </div>
                            <?php else: ?>
                                <span style="font-size: 0.8125rem; color: var(--danger);">Not Assigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 10px;">
                                <span title="Faculty" style="font-size: 0.75rem; background: #f1f5f9; padding: 2px 8px; border-radius: 10px; display: flex; align-items: center; gap: 4px;">
                                    <i data-lucide="user-square-2" size="12"></i> <?php echo $dept['faculty_count']; ?>
                                </span>
                                <span title="Students" style="font-size: 0.75rem; background: #f1f5f9; padding: 2px 8px; border-radius: 10px; display: flex; align-items: center; gap: 4px;">
                                    <i data-lucide="users" size="12"></i> <?php echo $dept['student_count']; ?>
                                </span>
                            </div>
                        </td>
                        <td>
                            <span style="font-weight: 600;"><?php echo $dept['intake_capacity']; ?></span>
                            <span style="font-size: 0.75rem; color: var(--text-muted);"> Seats</span>
                        </td>
                        <td>
                            <a href="departments.php?toggle_status=<?php echo $dept['id']; ?>&status=<?php echo $dept['is_active']; ?>" style="text-decoration: none;">
                                <?php if ($dept['is_active']): ?>
                                    <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">Active</span>
                                <?php else: ?>
                                    <span style="background: rgba(244, 63, 94, 0.1); color: #f43f5e; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">Inactive</span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td>
                            <div style="display: flex; gap: 10px;">
                                <a href="department_edit.php?id=<?php echo $dept['id']; ?>" class="btn-icon" title="Edit"><i data-lucide="edit-3" size="18"></i></a>
                                <a href="#" class="btn-icon text-danger" title="Delete"><i data-lucide="trash-2" size="18"></i></a>
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
        const el = document.getElementById('sortable-departments');
        Sortable.create(el, {
            animation: 150,
            handle: '.drag-handle',
            onEnd: function() {
                const order = Array.from(el.querySelectorAll('tr')).map(tr => tr.dataset.id);
                fetch('ajax_reorder_depts.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order: order })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        console.log('Order updated successfully');
                        // Optional: Show a small toast notification here
                    } else {
                        alert('Failed to update order: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('An error occurred while reordering.');
                });
            }
        });
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
