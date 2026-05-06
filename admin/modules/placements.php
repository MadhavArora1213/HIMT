<?php
$page_title = 'Placement Records';
include '../includes/header.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Handle New Placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_placement'])) {
    $student_id = $_POST['student_id'];
    $company = $_POST['company_name'];
    $designation = $_POST['designation'];
    $package = $_POST['package_lpa'];
    $date = $_POST['placement_date'];
    $year = $_POST['academic_year'];
    
    $pdo->prepare("INSERT INTO placements (student_id, company_name, designation, package_lpa, placement_date, academic_year) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$student_id, $company, $designation, $package, $date, $year]);
        
    log_action($pdo, 'Added Placement Record', 'Placements', $student_id);
    header("Location: placements.php?success=Placement record saved");
    exit();
}

$placements = $pdo->query("SELECT p.*, u.name as student_name, c.name as course_name FROM placements p JOIN students s ON p.student_id = s.id JOIN users u ON s.user_id = u.id JOIN courses c ON s.course_id = c.id ORDER BY p.placement_date DESC")->fetchAll();
$students = $pdo->query("SELECT s.id, u.name, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE s.is_active = 1")->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Career Placements</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Track student recruitment, corporate tie-ups, and salary packages.</p>
        </div>
        <button class="btn btn-primary" onclick="document.getElementById('placeModal').style.display='block'">
            <i data-lucide="award"></i> Record New Placement
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
                        <th>Student Name</th>
                        <th>Company</th>
                        <th>Designation</th>
                        <th>Package (LPA)</th>
                        <th>Date</th>
                        <th>Academic Year</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($placements)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">No placement records found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($placements as $p): ?>
                    <tr>
                        <td>
                            <p style="font-weight: 700;"><?php echo $p['student_name']; ?></p>
                            <p style="font-size: 0.7rem; color: var(--text-muted);"><?php echo $p['course_name']; ?></p>
                        </td>
                        <td><p style="font-weight: 600; color: var(--accent);"><?php echo $p['company_name']; ?></p></td>
                        <td><?php echo $p['designation']; ?></td>
                        <td><span style="background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-weight: 700; color: #15803d;"><?php echo $p['package_lpa']; ?> LPA</span></td>
                        <td><span style="font-size: 0.8125rem;"><?php echo date('M d, Y', strtotime($p['placement_date'])); ?></span></td>
                        <td><?php echo $p['academic_year']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div id="placeModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:100; display:flex; align-items:center; justify-content:center;">
    <div class="card" style="width:500px; padding:2rem;">
        <h3 style="margin-bottom:1.5rem;">Record Placement</h3>
        <form method="POST">
            <div class="form-group">
                <label>Select Student</label>
                <select name="student_id" class="form-control" required>
                    <option value="">-- Select Student --</option>
                    <?php foreach($students as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?> (<?php echo $s['email']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                <div class="form-group">
                    <label>Company Name</label>
                    <input type="text" name="company_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Designation</label>
                    <input type="text" name="designation" class="form-control" required>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                <div class="form-group">
                    <label>Package (LPA)</label>
                    <input type="number" step="0.01" name="package_lpa" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="placement_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            <div class="form-group" style="margin-top: 1rem;">
                <label>Academic Year</label>
                <input type="text" name="academic_year" class="form-control" value="2025-26" required>
            </div>
            <div style="margin-top:2rem; display:flex; gap:10px;">
                <button type="submit" name="add_placement" class="btn btn-primary" style="flex:1;">Save Record</button>
                <button type="button" onclick="this.closest('#placeModal').style.display='none'" class="btn" style="flex:1; background:#f1f5f9;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
