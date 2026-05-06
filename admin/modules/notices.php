<?php
$page_title = 'Notice Board';
include '../includes/header.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Fetch all notices
$query = "SELECT n.*, u.name as posted_by_name, d.name as dept_name, c.name as course_name 
          FROM notices n 
          LEFT JOIN users u ON n.posted_by = u.id 
          LEFT JOIN departments d ON n.department_id = d.id 
          LEFT JOIN courses c ON n.course_id = c.id 
          ORDER BY n.is_pinned DESC, n.created_at DESC";
$notices = $pdo->query($query)->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Notice Board</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Post announcements, circulars, and urgent alerts.</p>
        </div>
        <a href="notice_edit.php" class="btn btn-primary">
            <i data-lucide="megaphone"></i> Post New Notice
        </a>
    </div>

    <?php if ($success): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div class="grid-layout" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
        <?php if (empty($notices)): ?>
            <p style="color: var(--text-muted); grid-column: span 3; text-align: center; padding: 4rem;">No notices posted yet.</p>
        <?php endif; ?>
        <?php foreach ($notices as $n): 
            $type_colors = [
                'Urgent' => ['bg' => '#fee2e2', 'text' => '#b91c1c'],
                'Exam' => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
                'Academic' => ['bg' => '#dcfce7', 'text' => '#15803d'],
                'Holiday' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                'General' => ['bg' => '#f1f5f9', 'text' => '#475569'],
            ];
            $colors = $type_colors[$n['notice_type']] ?? $type_colors['General'];
        ?>
        <div class="card" style="padding: 1.5rem; position: relative; border: <?php echo $n['is_pinned'] ? '2px solid var(--accent)' : '1px solid var(--border)'; ?>;">
            <?php if($n['is_pinned']): ?>
                <span style="position: absolute; top: -10px; right: 20px; background: var(--accent); color: white; padding: 2px 10px; border-radius: 4px; font-size: 0.65rem; font-weight: 700;">PINNED</span>
            <?php endif; ?>
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                <span style="background: <?php echo $colors['bg']; ?>; color: <?php echo $colors['text']; ?>; padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase;">
                    <?php echo $n['notice_type']; ?>
                </span>
                <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($n['created_at'])); ?></span>
            </div>
            
            <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-main);"><?php echo $n['title']; ?></h3>
            <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1.5rem; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                <?php echo strip_tags($n['content']); ?>
            </p>
            
            <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 1rem; border-top: 1px solid var(--border); font-size: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 5px; color: var(--text-muted);">
                    <i data-lucide="user" size="14"></i> <?php echo $n['posted_by_name']; ?>
                </div>
                <div style="display: flex; gap: 8px;">
                    <a href="notice_edit.php?id=<?php echo $n['id']; ?>" style="color: var(--accent);"><i data-lucide="edit-2" size="16"></i></a>
                    <a href="#" style="color: var(--danger);"><i data-lucide="trash-2" size="16"></i></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
