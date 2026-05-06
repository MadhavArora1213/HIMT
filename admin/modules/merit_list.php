<?php
$page_title = 'Merit List';
include '../includes/header.php';

// Filters
$course_filter = $_GET['course_id'] ?? '';
$min_marks = $_GET['min_marks'] ?? '';

// Build Query
$query = "SELECT a.*, c.name as course_name 
          FROM admissions a
          JOIN courses c ON a.course_id = c.id
          WHERE a.status != 'rejected'";

$params = [];
if ($course_filter) { $query .= " AND a.course_id = ?"; $params[] = $course_filter; }
if ($min_marks) { $query .= " AND a.prev_percentage >= ?"; $params[] = $min_marks; }

$query .= " ORDER BY a.prev_percentage DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$merit_list = $stmt->fetchAll();

$courses = $pdo->query("SELECT id, name FROM courses WHERE is_active = 1")->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <a href="admissions.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px; margin-bottom: 0.5rem;">
                <i data-lucide="arrow-left" size="16"></i> Back to Admissions
            </a>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Academic Merit List</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Students ranked by their previous qualifying marks.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn btn-primary" onclick="window.print()" style="background: white; color: var(--text-main); border: 1px solid var(--border);">
                <i data-lucide="printer"></i> Print List
            </a>
            <button class="btn btn-primary">
                <i data-lucide="download"></i> Export Excel
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
        <form action="merit_list.php" method="GET" class="filter-row">
            <div class="form-group">
                <label>Filter by Course</label>
                <select name="course_id" class="form-control">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($course_filter == $c['id']) ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Min. Percentage (%)</label>
                <input type="number" name="min_marks" class="form-control" placeholder="e.g. 60" value="<?php echo $min_marks; ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary" style="width: 120px;"><i data-lucide="refresh-cw" size="16"></i> Update</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">Rank</th>
                        <th>Applicant Details</th>
                        <th>Course</th>
                        <th>Marks (%)</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($merit_list)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 4rem;">
                                <i data-lucide="award" size="48" style="color: #cbd5e1; margin-bottom: 1rem;"></i>
                                <p style="color: var(--text-muted);">No students found for this merit list criteria.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php 
                    $rank = 1;
                    foreach ($merit_list as $app): 
                        $status_class = 'bg-blue';
                        if($app['status'] == 'selected') $status_class = 'bg-green';
                        if($app['status'] == 'shortlisted') $status_class = 'bg-amber';
                    ?>
                    <tr>
                        <td>
                            <div style="width: 32px; height: 32px; background: <?php echo $rank <= 3 ? 'rgba(245, 158, 11, 0.1)' : '#f1f5f9'; ?>; color: <?php echo $rank <= 3 ? '#b45309' : '#475569'; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                <?php echo $rank++; ?>
                            </div>
                        </td>
                        <td>
                            <p style="font-weight: 700;"><?php echo $app['applicant_name']; ?></p>
                            <p style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $app['email']; ?> | <?php echo $app['application_number']; ?></p>
                        </td>
                        <td><?php echo $app['course_name']; ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; width: 100px;">
                                    <div style="width: <?php echo $app['prev_percentage']; ?>%; height: 100%; background: var(--accent);"></div>
                                </div>
                                <span style="font-weight: 700;"><?php echo $app['prev_percentage']; ?>%</span>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $status_class; ?>" style="padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: capitalize;">
                                <?php echo str_replace('_', ' ', $app['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="admission_view.php?id=<?php echo $app['id']; ?>" class="btn" style="padding: 6px 12px; font-size: 0.75rem; border: 1px solid var(--border);">Manage</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .header, .filter-row, .btn, .filter-actions { display: none !important; }
    .main-wrapper { margin-left: 0 !important; width: 100% !important; }
    .card { border: none !important; box-shadow: none !important; padding: 0 !important; }
    .content-area { padding: 0 !important; }
    body { background: white !important; }
}
</style>

<?php include '../includes/footer.php'; ?>
