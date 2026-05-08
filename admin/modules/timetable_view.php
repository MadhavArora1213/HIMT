<?php
require_once '../includes/config.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: timetable.php");
    exit();
}

// Fetch Timetable Header
$stmt = $pdo->prepare("SELECT t.*, c.name as course_name FROM timetables t JOIN courses c ON t.course_id = c.id WHERE t.id = ?");
$stmt->execute([$id]);
$timetable = $stmt->fetch();

if (!$timetable) {
    header("Location: timetable.php?error=Timetable not found");
    exit();
}

// Fetch Timetable Slots
$stmt = $pdo->prepare("SELECT ts.*, s.name as subject_name, f.full_name as faculty_name 
                      FROM timetable_slots ts 
                      LEFT JOIN subjects s ON ts.subject_id = s.id 
                      LEFT JOIN faculty f ON ts.faculty_id = f.id 
                      WHERE ts.timetable_id = ? 
                      ORDER BY ts.start_time ASC");
$stmt->execute([$id]);
$slots = $stmt->fetchAll();

// Organize slots by day
$schedule = [
    'Monday' => [], 'Tuesday' => [], 'Wednesday' => [], 
    'Thursday' => [], 'Friday' => [], 'Saturday' => []
];
foreach ($slots as $slot) {
    $schedule[$slot['day_of_week']][] = $slot;
}

$page_title = 'View Timetable - ' . $timetable['course_name'];
include '../includes/header.php';
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <a href="timetable.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
                <i data-lucide="arrow-left" size="16"></i> Back to Timetables
            </a>
            <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;"><?php echo $timetable['course_name']; ?> - Semester <?php echo $timetable['semester']; ?></h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Academic Session: <?php echo $timetable['academic_year']; ?></p>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn" style="background: white; border: 1px solid var(--border);">
                <i data-lucide="printer"></i> Print Schedule
            </button>
            <a href="timetable_edit.php?id=<?php echo $id; ?>" class="btn btn-primary">
                <i data-lucide="edit-3"></i> Edit Timetable
            </a>
        </div>
    </div>

    <div class="card" style="padding: 0; overflow-x: auto;">
        <table class="table" style="width: 100%; border-collapse: collapse; min-width: 1000px;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid var(--border);">
                    <th style="padding: 1.25rem; text-align: center; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); width: 150px; border-right: 1px solid var(--border);">Day</th>
                    <th style="padding: 1.25rem; text-align: left; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted);">Schedule Slots</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedule as $day => $day_slots): ?>
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 1.5rem; text-align: center; font-weight: 700; background: #fdfdfe; border-right: 1px solid var(--border); color: var(--text-main);">
                        <?php echo $day; ?>
                    </td>
                    <td style="padding: 1rem;">
                        <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                            <?php if (empty($day_slots)): ?>
                                <span style="color: var(--text-muted); font-size: 0.8125rem; font-style: italic;">No classes scheduled</span>
                            <?php else: ?>
                                <?php foreach ($day_slots as $slot): ?>
                                <div style="background: white; border: 1px solid var(--border); border-left: 4px solid var(--accent); padding: 12px 16px; border-radius: 8px; min-width: 200px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                    <div style="font-size: 0.7rem; font-weight: 800; color: var(--accent); margin-bottom: 4px;">
                                        <?php echo date('h:i A', strtotime($slot['start_time'])); ?> - <?php echo date('h:i A', strtotime($slot['end_time'])); ?>
                                    </div>
                                    <div style="font-weight: 700; font-size: 0.875rem; color: var(--text-main);"><?php echo $slot['subject_name'] ?: 'No Subject'; ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; display: flex; align-items: center; gap: 4px;">
                                        <i data-lucide="user" size="12"></i> <?php echo $slot['faculty_name'] ?: 'TBA'; ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px; margin-top: 2px;">
                                        <i data-lucide="map-pin" size="12"></i> Room <?php echo $slot['room_number'] ?: 'N/A'; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
