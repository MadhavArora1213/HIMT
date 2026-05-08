<?php
require_once '../includes/config.php';

$id = $_GET['id'] ?? null;
$placement = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM placements WHERE id = ?");
    $stmt->execute([$id]);
    $placement = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $company = $_POST['company_name'];
    $designation = $_POST['designation'];
    $package = $_POST['package_lpa'];
    $date = $_POST['placement_date'];
    $year = $_POST['academic_year'];

    try {
        if ($id) {
            $pdo->prepare("UPDATE placements SET student_id = ?, company_name = ?, designation = ?, package_lpa = ?, placement_date = ?, academic_year = ? WHERE id = ?")
                ->execute([$student_id, $company, $designation, $package, $date, $year, $id]);
            $msg = "Placement record updated successfully";
        } else {
            $pdo->prepare("INSERT INTO placements (student_id, company_name, designation, package_lpa, placement_date, academic_year) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$student_id, $company, $designation, $package, $date, $year]);
            $msg = "Placement record saved successfully";
        }
        log_action($pdo, ($id ? 'Updated' : 'Added') . ' Placement Record', 'Placements', $student_id);
        header("Location: placements.php?success=" . urlencode($msg));
        exit();
    } catch (Exception $e) {
        $error = db_error_message($e);
    }
}

$page_title = $id ? 'Edit Placement Record' : 'Record New Placement';
include '../includes/header.php';

$students = $pdo->query("SELECT s.id, u.name, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE s.is_active = 1 ORDER BY u.name ASC")->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="placements.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Placement Records
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;"><?php echo $page_title; ?></h2>
    </div>

    <?php if (isset($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2);">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" class="card" style="padding: 2rem; max-width: 800px;">
        <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
            <div class="form-group">
                <label>Select Student</label>
                <select name="student_id" class="form-control" required>
                    <option value="">-- Select Student --</option>
                    <?php foreach($students as $s): ?>
                        <option value="<?php echo $s['id']; ?>" <?php echo (isset($placement['student_id']) && $placement['student_id'] == $s['id']) ? 'selected' : ''; ?>>
                            <?php echo $s['name']; ?> (<?php echo $s['email']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label>Company Name</label>
                    <input type="text" name="company_name" class="form-control" placeholder="e.g. Google, Microsoft" value="<?php echo $placement['company_name'] ?? ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Designation</label>
                    <input type="text" name="designation" class="form-control" placeholder="e.g. Software Engineer" value="<?php echo $placement['designation'] ?? ''; ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label>Package (LPA)</label>
                    <input type="number" step="0.01" name="package_lpa" class="form-control" placeholder="e.g. 12.5" value="<?php echo $placement['package_lpa'] ?? ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Placement Date</label>
                    <input type="date" name="placement_date" class="form-control" value="<?php echo $placement['placement_date'] ?? date('Y-m-d'); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Academic Year</label>
                <input type="text" name="academic_year" class="form-control" placeholder="e.g. 2025-26" value="<?php echo $placement['academic_year'] ?? ''; ?>" required>
            </div>
        </div>

        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 1rem;">
            <a href="placements.php" class="btn" style="background: var(--background); border: 1px solid var(--border);">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i> <?php echo $id ? 'Update Record' : 'Save Placement'; ?>
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
