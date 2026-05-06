<?php
$page_title = 'Admission Settings';
include '../includes/header.php';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_open = isset($_POST['is_admission_open']) ? 1 : 0;
    $start_date = $_POST['admission_start_date'];
    $end_date = $_POST['admission_end_date'];
    $min_pct = $_POST['min_percentage'];
    $academic_year = $_POST['academic_year'];

    $stmt = $pdo->prepare("UPDATE admission_settings SET 
        is_admission_open = ?, 
        admission_start_date = ?, 
        admission_end_date = ?, 
        min_percentage = ?, 
        academic_year = ? 
        WHERE id = 1");
    
    if ($stmt->execute([$is_open, $start_date, $end_date, $min_pct, $academic_year])) {
        $success = "Settings updated successfully!";
    } else {
        $error = "Failed to update settings.";
    }
}

// Fetch current settings
$settings = $pdo->query("SELECT * FROM admission_settings WHERE id = 1")->fetch();
?>

<div style="padding: 2rem; max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 2rem;">
        <a href="admissions.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px; margin-bottom: 0.5rem;">
            <i data-lucide="arrow-left" size="16"></i> Back to Admissions
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700;">Admission Configuration</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem;">Control the online application process and eligibility criteria.</p>
    </div>

    <?php if (isset($success)): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2);">
            <i data-lucide="check-circle" size="18"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <form action="admission_settings.php" method="POST">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border); margin-bottom: 1.5rem;">
                <div>
                    <h3 style="font-size: 1.125rem; font-weight: 700;">Global Admission Switch</h3>
                    <p style="font-size: 0.8125rem; color: var(--text-muted);">Enable or disable the online application form instantly.</p>
                </div>
                <label class="switch">
                    <input type="checkbox" name="is_admission_open" <?php echo ($settings['is_admission_open'] ?? 0) ? 'checked' : ''; ?>>
                    <span class="slider round"></span>
                </label>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Current Academic Year</label>
                    <input type="text" name="academic_year" class="form-control" value="<?php echo $settings['academic_year'] ?? '2026-27'; ?>" placeholder="e.g. 2026-27">
                </div>
                <div class="form-group">
                    <label>Min. Eligibility Percentage (%)</label>
                    <input type="number" step="0.01" name="min_percentage" class="form-control" value="<?php echo $settings['min_percentage'] ?? '45.00'; ?>">
                </div>
                <div class="form-group">
                    <label>Admission Start Date</label>
                    <input type="date" name="admission_start_date" class="form-control" value="<?php echo $settings['admission_start_date'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label>Admission End Date</label>
                    <input type="date" name="admission_end_date" class="form-control" value="<?php echo $settings['admission_end_date'] ?? ''; ?>">
                </div>
            </div>

            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1; padding: 1rem;">
                    <i data-lucide="save"></i> Save Admission Rules
                </button>
            </div>
        </div>
    </form>

    <div class="card" style="margin-top: 2rem; background: #f8fafc; border-style: dashed;">
        <h4 style="font-size: 0.875rem; font-weight: 700; color: #475569; margin-bottom: 1rem;">Information</h4>
        <ul style="font-size: 0.8125rem; color: #64748b; line-height: 1.6; padding-left: 1.25rem;">
            <li>Closing admissions will hide the "Apply Now" button from the main website.</li>
            <li>Applications submitted after the end date will be marked as "Late" or blocked based on frontend logic.</li>
            <li>Ensure the academic year is updated before starting a new batch.</li>
        </ul>
    </div>
</div>

<style>
/* Toggle Switch Styling */
.switch {
  position: relative;
  display: inline-block;
  width: 60px;
  height: 34px;
}

.switch input { 
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #cbd5e1;
  -webkit-transition: .4s;
  transition: .4s;
}

.slider:before {
  position: absolute;
  content: "";
  height: 26px;
  width: 26px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  -webkit-transition: .4s;
  transition: .4s;
}

input:checked + .slider {
  background-color: var(--accent);
}

input:focus + .slider {
  box-shadow: 0 0 1px var(--accent);
}

input:checked + .slider:before {
  -webkit-transform: translateX(26px);
  -ms-transform: translateX(26px);
  transform: translateX(26px);
}

.slider.round {
  border-radius: 34px;
}

.slider.round:before {
  border-radius: 50%;
}
</style>

<?php include '../includes/footer.php'; ?>
