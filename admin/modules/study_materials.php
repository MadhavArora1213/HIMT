<?php
require_once '../includes/config.php';

// Handle Delete
if (isset($_GET['delete_id'])) {
    // Optional: Delete physical file too
    $stmt = $pdo->prepare("SELECT file_path FROM study_materials WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    $file = $stmt->fetch();
    if ($file && file_exists('../assets/docs/' . $file['file_path'])) {
        unlink('../assets/docs/' . $file['file_path']);
    }

    $pdo->prepare("DELETE FROM study_materials WHERE id = ?")->execute([$_GET['delete_id']]);
    header("Location: study_materials.php?success=Resource removed successfully");
    exit();
}

$materials = $pdo->query("SELECT sm.*, c.name as course_name, s.name as subject_name, u.name as faculty_name 
                         FROM study_materials sm 
                         JOIN courses c ON sm.course_id = c.id 
                         JOIN subjects s ON sm.subject_id = s.id 
                         LEFT JOIN faculty f ON sm.faculty_id = f.id 
                         LEFT JOIN users u ON f.user_id = u.id 
                         ORDER BY sm.created_at DESC")->fetchAll();

$page_title = 'Study Materials';
include '../includes/header.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Academic E-Resources</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Upload and manage study materials, syllabus copies, and lecture notes.</p>
        </div>
        <a href="study_material_edit.php" class="btn btn-primary">
            <i data-lucide="file-up"></i> Upload Materials
        </a>
    </div>

    <?php if ($success): ?>
        <div id="success-alert" style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2);">
            <i data-lucide="check-circle" size="18" style="vertical-align: middle; margin-right: 8px;"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="grid-layout" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
        <?php if (empty($materials)): ?>
            <p style="color: var(--text-muted); grid-column: span 3; text-align: center; padding: 4rem;">No study materials uploaded yet.</p>
        <?php endif; ?>
        <?php foreach ($materials as $m): ?>
        <div class="card" style="padding: 1.5rem; border-top: 4px solid var(--accent);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                <div style="width: 40px; height: 40px; background: #f1f5f9; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--accent);">
                    <i data-lucide="file-text"></i>
                </div>
                <div style="display: flex; gap: 5px;">
                    <a href="study_material_edit.php?id=<?php echo $m['id']; ?>" class="btn-icon" style="padding: 4px;" title="Edit"><i data-lucide="edit-3" size="14"></i></a>
                    <a href="?delete_id=<?php echo $m['id']; ?>" class="btn-icon" style="padding: 4px; color: var(--danger);" onclick="return confirm('Delete this resource?')" title="Delete"><i data-lucide="trash-2" size="14"></i></a>
                </div>
            </div>
            <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $m['title']; ?></h3>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.5rem;"><?php echo $m['description'] ?: 'No description provided.'; ?></p>
            <span style="font-size: 0.65rem; background: rgba(37, 99, 235, 0.1); color: var(--accent); padding: 2px 8px; border-radius: 4px; font-weight: 700; margin-bottom: 1.5rem; display: inline-block;"><?php echo $m['subject_name']; ?></span>
            
            <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 1rem; border-top: 1px solid var(--border);">
                <div style="font-size: 0.7rem; color: var(--text-muted);">
                    <p style="font-weight: 600; color: var(--text-main);"><?php echo $m['faculty_name'] ? 'Prof. ' . $m['faculty_name'] : 'Administrator'; ?></p>
                    <p><?php echo date('d M, Y', strtotime($m['created_at'])); ?></p>
                </div>
                <a href="../assets/docs/<?php echo $m['file_path']; ?>" download class="btn" style="background: var(--accent); color: white; padding: 4px 10px; font-size: 0.75rem; text-decoration: none; border-radius: 4px;">
                    <i data-lucide="download" size="14"></i> Get PDF
                </a>
            </div>
        </div>
        <?php endforeach; ?>
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
