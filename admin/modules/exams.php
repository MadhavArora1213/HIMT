<?php
$page_title = 'Examination Schedules';
include '../includes/header.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Fetch all exams
$exams = $pdo->query("SELECT e.*, c.name as course_name FROM examinations e JOIN courses c ON e.course_id = c.id ORDER BY e.start_date DESC")->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Examination Management</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Schedule exams, manage hall tickets, and process results.</p>
        </div>
        <a href="exam_edit.php" class="btn btn-primary">
            <i data-lucide="calendar-plus"></i> Create New Exam
        </a>
    </div>

    <?php if ($success): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Exam Name</th>
                        <th>Course & Sem</th>
                        <th>Date Range</th>
                        <th>Status</th>
                        <th>Result Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($exams)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">No exams scheduled yet.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($exams as $e): ?>
                    <tr>
                        <td>
                            <p style="font-weight: 700;"><?php echo $e['exam_name']; ?></p>
                            <p style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $e['exam_type']; ?> Exam</p>
                        </td>
                        <td>
                            <p style="font-size: 0.875rem;"><?php echo $e['course_name']; ?></p>
                            <span style="font-size: 0.75rem; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">Sem <?php echo $e['semester']; ?></span>
                        </td>
                        <td>
                            <p style="font-size: 0.8125rem;"><i data-lucide="calendar" size="12"></i> <?php echo date('M d', strtotime($e['start_date'])); ?> - <?php echo date('M d, Y', strtotime($e['end_date'])); ?></p>
                        </td>
                        <td>
                            <?php if ($e['is_published']): ?>
                                <span style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700;">PUBLISHED</span>
                            <?php else: ?>
                                <span style="background: rgba(245, 158, 11, 0.1); color: #d97706; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700;">SCHEDULED</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <p style="font-size: 0.8125rem;"><?php echo $e['result_date'] ? date('M d, Y', strtotime($e['result_date'])) : 'TBA'; ?></p>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <a href="marks_entry.php?exam_id=<?php echo $e['id']; ?>" class="btn-icon" title="Marks Entry"><i data-lucide="file-signature" size="18"></i></a>
                                <a href="exam_edit.php?id=<?php echo $e['id']; ?>" class="btn-icon" title="Edit Schedule"><i data-lucide="edit-3" size="18"></i></a>
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
