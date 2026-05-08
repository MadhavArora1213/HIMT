<?php
require_once '../includes/config.php';

// Handle Delete
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM placements WHERE id = ?")->execute([$_GET['delete_id']]);
    header("Location: placements.php?success=Record deleted successfully");
    exit();
}

$placements = $pdo->query("SELECT p.*, u.name as student_name, c.name as course_name FROM placements p JOIN students s ON p.student_id = s.id JOIN users u ON s.user_id = u.id JOIN courses c ON s.course_id = c.id ORDER BY p.placement_date DESC")->fetchAll();

$page_title = 'Placement Records';
include '../includes/header.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Career Placements</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Track student recruitment, corporate tie-ups, and salary packages.</p>
        </div>
        <a href="placement_edit.php" class="btn btn-primary">
            <i data-lucide="plus"></i> Record New Placement
        </a>
    </div>

    <?php if ($success): ?>
        <div id="success-alert" style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2);">
            <i data-lucide="check-circle" size="18" style="vertical-align: middle; margin-right: 8px;"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Company</th>
                        <th>Designation</th>
                        <th>Package (LPA)</th>
                        <th>Date</th>
                        <th>Academic Year</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($placements)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-muted);">No placement records found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($placements as $p): ?>
                    <tr>
                        <td>
                            <p style="font-weight: 700;"><?php echo $p['student_name']; ?></p>
                            <p style="font-size: 0.7rem; color: var(--text-muted);"><?php echo $p['course_name']; ?></p>
                        </td>
                        <td><p style="font-weight: 600; color: var(--accent);"><?php echo $p['company_name']; ?></p></td>
                        <td><?php echo $p['designation']; ?></td>
                        <td><span style="background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-weight: 700; color: #15803d;"><?php echo $p['package_lpa']; ?> LPA</span></td>
                        <td><span style="font-size: 0.8125rem;"><?php echo date('M d, Y', strtotime($p['placement_date'])); ?></span></td>
                        <td><?php echo $p['academic_year']; ?></td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <a href="placement_edit.php?id=<?php echo $p['id']; ?>" class="btn-icon" title="Edit">
                                    <i data-lucide="edit-3" size="16"></i>
                                </a>
                                <a href="?delete_id=<?php echo $p['id']; ?>" class="btn-icon" style="color: var(--danger);" onclick="return confirm('Delete this record?')" title="Delete">
                                    <i data-lucide="trash-2" size="16"></i>
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
