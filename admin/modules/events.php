<?php
$page_title = 'Events & News Management';
include '../includes/header.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Fetch events
$events = $pdo->query("SELECT * FROM events ORDER BY event_date DESC")->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Institutional Events & News</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Manage upcoming fests, seminars, cultural events, and press coverage.</p>
        </div>
        <a href="event_edit.php" class="btn btn-primary">
            <i data-lucide="calendar-plus"></i> Schedule New Event
        </a>
    </div>

    <?php if ($success): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
        <?php if (empty($events)): ?>
            <p style="color: var(--text-muted); grid-column: span 3; text-align: center; padding: 4rem;">No events scheduled yet.</p>
        <?php endif; ?>
        <?php foreach ($events as $e): 
            $is_past = strtotime($e['event_date']) < time();
            $type_colors = [
                'Academic' => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
                'Cultural' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                'Sports' => ['bg' => '#dcfce7', 'text' => '#15803d'],
                'Seminar' => ['bg' => '#f1f5f9', 'text' => '#475569'],
            ];
            $colors = $type_colors[$e['event_type']] ?? $type_colors['Seminar'];
        ?>
        <div class="card" style="overflow: hidden; border: <?php echo $e['is_featured'] ? '2px solid var(--accent)' : '1px solid var(--border)'; ?>;">
            <?php if($e['banner_image']): ?>
                <img src="../assets/img/uploads/<?php echo $e['banner_image']; ?>" style="width: 100%; height: 180px; object-fit: cover;">
            <?php else: ?>
                <div style="width: 100%; height: 180px; background: #f8fafc; display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                    <i data-lucide="image" size="48"></i>
                </div>
            <?php endif; ?>
            
            <div style="padding: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <span style="background: <?php echo $colors['bg']; ?>; color: <?php echo $colors['text']; ?>; padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase;">
                        <?php echo $e['event_type']; ?>
                    </span>
                    <?php if($is_past): ?>
                        <span style="font-size: 0.65rem; font-weight: 700; color: var(--text-muted);">PAST EVENT</span>
                    <?php else: ?>
                        <span style="font-size: 0.65rem; font-weight: 700; color: var(--success);">UPCOMING</span>
                    <?php endif; ?>
                </div>
                
                <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $e['title']; ?></h3>
                <p style="font-size: 0.8125rem; color: var(--text-muted); display: flex; align-items: center; gap: 5px; margin-bottom: 1rem;">
                    <i data-lucide="calendar" size="14"></i> <?php echo date('M d, Y', strtotime($e['event_date'])); ?>
                    <i data-lucide="map-pin" size="14" style="margin-left: 10px;"></i> <?php echo $e['location'] ?: 'Campus'; ?>
                </p>
                
                <div style="display: flex; gap: 10px; border-top: 1px solid var(--border); padding-top: 1rem; margin-top: 1rem;">
                    <a href="event_edit.php?id=<?php echo $e['id']; ?>" class="btn" style="flex: 1; background: #f1f5f9; color: var(--text-main); font-size: 0.8125rem; text-decoration: none; text-align: center; padding: 6px;">Edit</a>
                    <a href="#" class="btn" style="flex: 1; background: var(--danger); color: white; font-size: 0.8125rem; text-decoration: none; text-align: center; padding: 6px;">Delete</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
