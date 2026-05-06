<?php
$page_title = 'Reports & Analytics';
include '../includes/header.php';

$report_types = [
    ['id' => 'students', 'name' => 'Student Master List', 'desc' => 'Complete enrollment data with contact info.', 'icon' => 'users'],
    ['id' => 'attendance', 'name' => 'Attendance Summary', 'desc' => 'Class-wise percentages and shortage list.', 'icon' => 'calendar-check'],
    ['id' => 'fees_collection', 'name' => 'Fee Collection Report', 'desc' => 'Daily/Monthly revenue and mode breakup.', 'icon' => 'banknote'],
    ['id' => 'fees_defaulters', 'name' => 'Fee Defaulter List', 'desc' => 'Students with outstanding dues.', 'icon' => 'alert-circle'],
    ['id' => 'exams_results', 'name' => 'Examination Results', 'desc' => 'Course-wise marks, grades, and pass/fail.', 'icon' => 'file-text'],
    ['id' => 'library_overdue', 'name' => 'Library Overdue Report', 'desc' => 'Books pending return and accumulated fines.', 'icon' => 'book-open'],
    ['id' => 'faculty_load', 'name' => 'Faculty Load Report', 'desc' => 'Assigned subjects and weekly hours.', 'icon' => 'user-square-2'],
    ['id' => 'admission_funnel', 'name' => 'Admission Funnel', 'desc' => 'Conversion stats from Applied to Enrolled.', 'icon' => 'filter'],
];
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700;">Reports & Insights</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem;">Download institution-wide data in PDF and Excel formats.</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
        <?php foreach ($report_types as $r): ?>
        <div class="card" style="padding: 1.5rem; display: flex; gap: 1.5rem; align-items: center;">
            <div style="width: 60px; height: 60px; background: rgba(37, 99, 235, 0.1); color: var(--accent); border-radius: 15px; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="<?php echo $r['icon']; ?>" size="32"></i>
            </div>
            <div style="flex: 1;">
                <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 5px;"><?php echo $r['name']; ?></h3>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1rem;"><?php echo $r['desc']; ?></p>
                <div style="display: flex; gap: 10px;">
                    <a href="report_generator.php?type=<?php echo $r['id']; ?>&format=excel" class="btn" style="background: #f1f5f9; color: #15803d; font-size: 0.75rem; font-weight: 700; text-decoration: none; padding: 4px 10px; border-radius: 4px; display: flex; align-items: center; gap: 5px;">
                        <i data-lucide="file-spreadsheet" size="14"></i> EXCEL
                    </a>
                    <a href="report_generator.php?type=<?php echo $r['id']; ?>&format=pdf" class="btn" style="background: #f1f5f9; color: #b91c1c; font-size: 0.75rem; font-weight: 700; text-decoration: none; padding: 4px 10px; border-radius: 4px; display: flex; align-items: center; gap: 5px;">
                        <i data-lucide="file-text" size="14"></i> PDF
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
