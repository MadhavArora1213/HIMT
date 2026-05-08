<?php
$page_title = 'Attendance Report';
include '../includes/header.php';

$dept_id = $_GET['dept_id'] ?? '';
$course_id = $_GET['course_id'] ?? '';
$semester = $_GET['semester'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$report = [];
if ($dept_id && $course_id && $semester) {
    // SQL to count total days and present days per student
    $sql = "SELECT s.id, u.name, s.roll_number,
            COUNT(a.id) as total_days,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_days,
            SUM(CASE WHEN a.status = 'medical' THEN 1 ELSE 0 END) as medical_days
            FROM students s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN attendance a ON s.id = a.student_id AND a.date BETWEEN ? AND ?
            WHERE s.department_id = ? AND s.course_id = ? AND s.current_semester = ?
            GROUP BY s.id
            ORDER BY s.roll_number ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date, $dept_id, $course_id, $semester]);
    $report = $stmt->fetchAll();
}

$depts = $pdo->query("SELECT id, name FROM departments WHERE is_active = 1")->fetchAll();
$courses = $pdo->query("SELECT id, name FROM courses WHERE is_active = 1")->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Attendance Reports</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Analyze attendance trends and identify shortage cases.</p>
        </div>
        <button class="btn btn-primary" onclick="window.print()">
            <i data-lucide="printer"></i> Print Report
        </button>
    </div>

    <div class="card" style="margin-bottom: 2rem; padding: 1.25rem;">
        <form action="attendance_report.php" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)) 160px; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; display: block;">Department</label>
                <select name="dept_id" class="form-control" required style="font-size: 0.875rem; height: 42px; padding: 0 0.75rem; padding-right: 2rem;">
                    <option value="">Select Dept</option>
                    <?php foreach ($depts as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo ($dept_id == $d['id']) ? 'selected' : ''; ?>><?php echo $d['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; display: block;">Course</label>
                <select name="course_id" class="form-control" required style="font-size: 0.875rem; height: 42px; padding: 0 0.75rem; padding-right: 2rem;">
                    <option value="">Select Course</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($course_id == $c['id']) ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; display: block;">From Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>" style="font-size: 0.875rem; height: 42px; padding: 0 0.75rem;">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; display: block;">To Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>" style="font-size: 0.875rem; height: 42px; padding: 0 0.75rem;">
            </div>
            <button type="submit" class="btn btn-primary" style="height: 42px; width: 100%; border-radius: 10px; font-size: 0.875rem;">
                <i data-lucide="bar-chart-2"></i> Generate Report
            </button>
        </form>
    </div>

    <?php if ($report): ?>
    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Student Name</th>
                        <th style="text-align: center;">Total Days</th>
                        <th style="text-align: center;">Present</th>
                        <th style="text-align: center;">Absent</th>
                        <th style="text-align: center;">Medical/Late</th>
                        <th style="text-align: center;">Percentage</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report as $r): 
                        $percentage = ($r['total_days'] > 0) ? ($r['present_days'] / $r['total_days']) * 100 : 0;
                        $is_low = $percentage < 75;
                    ?>
                    <tr style="<?php echo $is_low ? 'background: rgba(239, 68, 68, 0.03);' : ''; ?>">
                        <td style="font-weight: 700;"><?php echo $r['roll_number']; ?></td>
                        <td><?php echo $r['name']; ?></td>
                        <td style="text-align: center;"><?php echo $r['total_days']; ?></td>
                        <td style="text-align: center; color: var(--success); font-weight: 600;"><?php echo $r['present_days']; ?></td>
                        <td style="text-align: center; color: var(--danger);"><?php echo $r['absent_days']; ?></td>
                        <td style="text-align: center; color: var(--text-muted);"><?php echo $r['medical_days'] + $r['late_days']; ?></td>
                        <td style="text-align: center;">
                            <span style="font-weight: 700; color: <?php echo $is_low ? 'var(--danger)' : 'var(--success)'; ?>">
                                <?php echo round($percentage, 1); ?>%
                            </span>
                        </td>
                        <td>
                            <?php if ($is_low): ?>
                                <span style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700;">SHORTAGE</span>
                            <?php else: ?>
                                <span style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700;">OK</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
