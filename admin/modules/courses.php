<?php
$page_title = 'Course Management';
include '../includes/header.php';

// Success/Error Messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Fetch all courses with department info and student counts
$sql = "SELECT c.*, d.name as dept_name,
        (SELECT COUNT(*) FROM students WHERE course_id = c.id) as student_count
        FROM courses c
        JOIN departments d ON c.department_id = d.id
        ORDER BY d.name ASC, c.sort_order ASC";
$courses = $pdo->query($sql)->fetchAll();

// Handle Status Toggle
if (isset($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    $status = $_GET['status'];
    $new_status = ($status == 1) ? 0 : 1;
    $pdo->prepare("UPDATE courses SET is_active = ? WHERE id = ?")->execute([$new_status, $id]);
    header("Location: courses.php?success=Status updated successfully");
    exit();
}
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Academic Courses</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Manage UG, PG, and Diploma programs.</p>
        </div>
        <a href="course_edit.php" class="btn btn-primary">
            <i data-lucide="plus"></i> Add New Course
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
                        <th>Course Details</th>
                        <th>Department</th>
                        <th>Type</th>
                        <th>Seats & Enrollment</th>
                        <th>Annual Fee</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($courses)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">No courses found.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($courses as $course): 
                        $fill_percentage = ($course['total_seats'] > 0) ? ($course['student_count'] / $course['total_seats']) * 100 : 0;
                        $bar_color = ($fill_percentage > 90) ? 'var(--danger)' : (($fill_percentage > 70) ? 'var(--warning)' : 'var(--success)');
                    ?>
                    <tr>
                        <td>
                            <div>
                                <p style="font-weight: 600;"><?php echo $course['name']; ?></p>
                                <p style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $course['short_name']; ?> | <?php echo $course['duration_years']; ?> Years (<?php echo $course['total_semesters']; ?> Sem)</p>
                            </div>
                        </td>
                        <td>
                            <span style="font-size: 0.875rem; color: var(--text-muted);"><?php echo $course['dept_name']; ?></span>
                        </td>
                        <td>
                            <span style="background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;"><?php echo $course['course_type']; ?></span>
                        </td>
                        <td>
                            <div style="width: 150px;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 4px;">
                                    <span><?php echo $course['student_count']; ?> / <?php echo $course['total_seats']; ?></span>
                                    <span><?php echo round($fill_percentage); ?>%</span>
                                </div>
                                <div style="width: 100%; height: 6px; background: #eee; border-radius: 3px; overflow: hidden;">
                                    <div style="width: <?php echo $fill_percentage; ?>%; height: 100%; background: <?php echo $bar_color; ?>;"></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-weight: 600;">₹<?php echo number_format($course['annual_fee'], 0); ?></span>
                        </td>
                        <td>
                            <a href="courses.php?toggle_status=<?php echo $course['id']; ?>&status=<?php echo $course['is_active']; ?>" style="text-decoration: none;">
                                <?php if ($course['is_active']): ?>
                                    <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">Active</span>
                                <?php else: ?>
                                    <span style="background: rgba(244, 63, 94, 0.1); color: #f43f5e; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">Inactive</span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td>
                            <div style="display: flex; gap: 10px;">
                                <a href="course_edit.php?id=<?php echo $course['id']; ?>" class="btn-icon" title="Edit"><i data-lucide="edit-3" size="18"></i></a>
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
