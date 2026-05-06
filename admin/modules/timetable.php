<?php
$page_title = 'Class Timetables';
include '../includes/header.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Fetch all timetables
$timetables = $pdo->query("SELECT t.*, c.name as course_name FROM timetables t JOIN courses c ON t.course_id = c.id ORDER BY t.academic_year DESC, c.name ASC")->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Academic Timetables</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Manage lecture schedules and faculty assignments.</p>
        </div>
        <a href="timetable_edit.php" class="btn btn-primary">
            <i data-lucide="plus"></i> Create New Timetable
        </a>
    </div>

    <?php if ($success): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div class="grid-layout" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
        <?php if (empty($timetables)): ?>
            <p style="color: var(--text-muted); grid-column: span 3; text-align: center; padding: 4rem;">No timetables created yet.</p>
        <?php endif; ?>
        <?php foreach ($timetables as $t): ?>
        <div class="card" style="padding: 1.5rem; position: relative; overflow: hidden;">
            <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--accent);"></div>
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                <div>
                    <h3 style="font-size: 1.125rem; font-weight: 700;"><?php echo $t['course_name']; ?></h3>
                    <p style="font-size: 0.75rem; color: var(--text-muted); uppercase; font-weight: 600;">Sem <?php echo $t['semester']; ?> | <?php echo $t['academic_year']; ?></p>
                </div>
                <?php if($t['is_published']): ?>
                    <span style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 4px 8px; border-radius: 4px; font-size: 0.65rem; font-weight: 700;">PUBLISHED</span>
                <?php else: ?>
                    <span style="background: #f1f5f9; color: var(--text-muted); padding: 4px 8px; border-radius: 4px; font-size: 0.65rem; font-weight: 700;">DRAFT</span>
                <?php endif; ?>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 1.5rem;">
                <a href="timetable_view.php?id=<?php echo $t['id']; ?>" class="btn" style="flex: 1; background: #f1f5f9; color: var(--text-main); font-size: 0.8125rem; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 5px;">
                    <i data-lucide="eye" size="16"></i> View
                </a>
                <a href="timetable_edit.php?id=<?php echo $t['id']; ?>" class="btn" style="flex: 1; background: var(--accent); color: white; font-size: 0.8125rem; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 5px;">
                    <i data-lucide="edit-3" size="16"></i> Edit
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
