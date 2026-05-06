<?php
$page_title = 'Admissions Management';
include '../includes/header.php';

// Success/Error Messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Filters
$status_filter = $_GET['status'] ?? '';
$course_filter = $_GET['course_id'] ?? '';
$search = $_GET['search'] ?? '';

// Build Query
$query = "SELECT a.*, c.name as course_name 
          FROM admissions a
          JOIN courses c ON a.course_id = c.id
          WHERE 1=1";

$params = [];
if ($status_filter) { $query .= " AND a.status = ?"; $params[] = $status_filter; }
if ($course_filter) { $query .= " AND a.course_id = ?"; $params[] = $course_filter; }
if ($search) { 
    $query .= " AND (a.applicant_name LIKE ? OR a.application_number LIKE ? OR a.email LIKE ?)"; 
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}

$query .= " ORDER BY a.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$courses = $pdo->query("SELECT id, name FROM courses WHERE is_active = 1")->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Admissions Queue</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Review and process new student applications.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="merit_list.php" class="btn btn-primary" style="background: white; color: var(--text-main); border: 1px solid var(--border);">
                <i data-lucide="list"></i> Merit List
            </a>
            <a href="admission_settings.php" class="btn btn-primary" style="background: white; color: var(--text-main); border: 1px solid var(--border);">
                <i data-lucide="settings"></i> Settings
            </a>
        </div>
    </div>

    <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
        <form action="admissions.php" method="GET" class="filter-row">
            <div class="form-group">
                <label style="font-size: 0.75rem; font-weight: 600; margin-bottom: 5px; display: block;">Search Applicant</label>
                <input type="text" name="search" class="form-control" placeholder="Name or App No..." value="<?php echo $search; ?>">
            </div>
            <div class="form-group">
                <label style="font-size: 0.75rem; font-weight: 600; margin-bottom: 5px; display: block;">Course</label>
                <select name="course_id" class="form-control">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($course_filter == $c['id']) ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label style="font-size: 0.75rem; font-weight: 600; margin-bottom: 5px; display: block;">Status</label>
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="submitted" <?php echo ($status_filter == 'submitted') ? 'selected' : ''; ?>>Submitted</option>
                    <option value="under_review" <?php echo ($status_filter == 'under_review') ? 'selected' : ''; ?>>Under Review</option>
                    <option value="shortlisted" <?php echo ($status_filter == 'shortlisted') ? 'selected' : ''; ?>>Shortlisted</option>
                    <option value="selected" <?php echo ($status_filter == 'selected') ? 'selected' : ''; ?>>Selected</option>
                    <option value="rejected" <?php echo ($status_filter == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary" style="flex: 1;"><i data-lucide="search" size="16"></i> Filter</button>
            </div>
        </form>
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
                        <th>Applicant</th>
                        <th>App No.</th>
                        <th>Applied Course</th>
                        <th>Marks (%)</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applications)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-muted);">No applications found.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($applications as $app): 
                        $status_colors = [
                            'submitted' => ['bg' => '#f1f5f9', 'text' => '#475569'],
                            'under_review' => ['bg' => '#fef3c7', 'text' => '#92400e'],
                            'shortlisted' => ['bg' => '#dcfce7', 'text' => '#15803d'],
                            'selected' => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
                            'rejected' => ['bg' => '#fee2e2', 'text' => '#b91c1c'],
                        ];
                        $colors = $status_colors[$app['status']] ?? ['bg' => '#eee', 'text' => '#333'];
                    ?>
                    <tr>
                        <td>
                            <p style="font-weight: 700;"><?php echo $app['applicant_name']; ?></p>
                            <p style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $app['email']; ?></p>
                        </td>
                        <td>
                            <span style="font-family: monospace; font-weight: 600; color: var(--accent);"><?php echo $app['application_number']; ?></span>
                        </td>
                        <td>
                            <p style="font-size: 0.875rem;"><?php echo $app['course_name']; ?></p>
                        </td>
                        <td>
                            <span style="font-weight: 700; color: <?php echo $app['prev_percentage'] >= 80 ? 'var(--success)' : 'var(--text-main)'; ?>"><?php echo $app['prev_percentage']; ?>%</span>
                        </td>
                        <td>
                            <span style="background: <?php echo $colors['bg']; ?>; color: <?php echo $colors['text']; ?>; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;">
                                <?php echo str_replace('_', ' ', $app['status']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($app['created_at'])); ?></span>
                        </td>
                        <td>
                            <a href="admission_view.php?id=<?php echo $app['id']; ?>" class="btn btn-primary" style="padding: 4px 12px; font-size: 0.75rem;">Review</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
