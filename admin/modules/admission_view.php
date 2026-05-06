<?php
require_once '../includes/config.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: admissions.php"); exit(); }

$stmt = $pdo->prepare("SELECT a.*, c.name as course_name, c.department_id FROM admissions a JOIN courses c ON a.course_id = c.id WHERE a.id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch();

if (!$app) { die("Application not found."); }

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $remarks = $_POST['remarks'];
    $pdo->prepare("UPDATE admissions SET status = ?, remarks = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
        ->execute([$new_status, $remarks, $_SESSION['user_id'], $id]);
    header("Location: admission_view.php?id=$id&success=Status updated");
    exit();
}

// Handle Conversion to Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['convert_student'])) {
    try {
        $pdo->beginTransaction();
        
        // 1. Create User
        $hashed_pass = password_hash('123456', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
        $stmt->execute([$app['applicant_name'], $app['email'], $hashed_pass]);
        $user_id = $pdo->lastInsertId();
        
        // 2. Create Student Record
        $enrollment_no = 'HIMT' . date('Y') . str_pad($app['id'], 4, '0', STR_PAD_LEFT);
        $sql = "INSERT INTO students (user_id, department_id, course_id, enrollment_no, roll_number, admission_year, current_semester, current_year, date_of_birth, gender, category, permanent_address, parent_name, parent_phone, admission_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Regular')";
        $pdo->prepare($sql)->execute([
            $user_id, $app['department_id'], $app['course_id'], $enrollment_no, 'PENDING',
            date('Y'), 1, 1, $app['date_of_birth'], $app['gender'], $app['category'],
            $app['domicile_state'], 'Guardian of ' . $app['applicant_name'], $app['phone']
        ]);
        
        // 3. Update Admission Status
        $pdo->prepare("UPDATE admissions SET status = 'selected' WHERE id = ?")->execute([$id]);
        
        $pdo->commit();
        header("Location: students.php?success=Applicant converted to student successfully. Enrollment: $enrollment_no");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Conversion Failed: " . $e->getMessage();
    }
}

$page_title = 'Review Application';
include '../includes/header.php';
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="admissions.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Queue
        </a>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
            <h2 style="font-size: 1.5rem; font-weight: 700;">Review: <?php echo $app['applicant_name']; ?></h2>
            <span style="background: var(--accent); color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;">
                <?php echo strtoupper(str_replace('_', ' ', $app['status'])); ?>
            </span>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <div class="details-column">
            <div class="card" style="margin-bottom: 2rem;">
                <h3 style="font-size: 1rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Applicant Information</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <p style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Application No</p>
                        <p style="font-weight: 600; font-family: monospace; font-size: 1.1rem; color: var(--accent);"><?php echo $app['application_number']; ?></p>
                    </div>
                    <div>
                        <p style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Applied Course</p>
                        <p style="font-weight: 600;"><?php echo $app['course_name']; ?></p>
                    </div>
                    <div>
                        <p style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Email</p>
                        <p><?php echo $app['email']; ?></p>
                    </div>
                    <div>
                        <p style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Phone</p>
                        <p><?php echo $app['phone']; ?></p>
                    </div>
                    <div>
                        <p style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Date of Birth</p>
                        <p><?php echo date('M d, Y', strtotime($app['date_of_birth'])); ?></p>
                    </div>
                    <div>
                        <p style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Category</p>
                        <p><?php echo $app['category']; ?></p>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 style="font-size: 1rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Educational Background</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <p style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Qualifying Exam</p>
                        <p><?php echo $app['prev_qualification']; ?></p>
                    </div>
                    <div>
                        <p style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Percentage Scored</p>
                        <p style="font-size: 1.25rem; font-weight: 700; color: var(--success);"><?php echo $app['prev_percentage']; ?>%</p>
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <p style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 1rem;">Uploaded Documents</p>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem;">
                        <!-- Placeholder for documents -->
                        <div style="border: 1px solid var(--border); padding: 1rem; border-radius: 8px; text-align: center;">
                            <i data-lucide="file-text" size="24" style="color: var(--text-muted);"></i>
                            <p style="font-size: 0.7rem; margin-top: 5px;">Marksheet.pdf</p>
                        </div>
                        <div style="border: 1px solid var(--border); padding: 1rem; border-radius: 8px; text-align: center;">
                            <i data-lucide="file-text" size="24" style="color: var(--text-muted);"></i>
                            <p style="font-size: 0.7rem; margin-top: 5px;">Aadhar_Card.pdf</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="action-column">
            <div class="card" style="margin-bottom: 2rem;">
                <h3 style="font-size: 1rem; margin-bottom: 1.5rem;">Update Status</h3>
                <form action="admission_view.php?id=<?php echo $id; ?>" method="POST">
                    <div class="form-group">
                        <label style="font-size: 0.8125rem; font-weight: 600; display: block; margin-bottom: 8px;">Application Status</label>
                        <select name="status" class="form-control" required>
                            <option value="submitted" <?php echo ($app['status'] == 'submitted') ? 'selected' : ''; ?>>Submitted</option>
                            <option value="under_review" <?php echo ($app['status'] == 'under_review') ? 'selected' : ''; ?>>Under Review</option>
                            <option value="shortlisted" <?php echo ($app['status'] == 'shortlisted') ? 'selected' : ''; ?>>Shortlisted</option>
                            <option value="selected" <?php echo ($app['status'] == 'selected') ? 'selected' : ''; ?>>Selected for Admission</option>
                            <option value="rejected" <?php echo ($app['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label style="font-size: 0.8125rem; font-weight: 600; display: block; margin-bottom: 8px;">Review Remarks</label>
                        <textarea name="remarks" class="form-control" rows="4" placeholder="Internal remarks or message to applicant..."><?php echo $app['remarks'] ?? ''; ?></textarea>
                    </div>
                    <button type="submit" name="update_status" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 0.75rem;">
                        Save Status
                    </button>
                </form>
            </div>

            <?php if ($app['status'] == 'selected'): ?>
            <div class="card" style="border: 1px solid var(--success); background: rgba(16, 185, 129, 0.05);">
                <h3 style="font-size: 1rem; margin-bottom: 1rem; color: var(--success);">Final Enrollment</h3>
                <p style="font-size: 0.8125rem; color: var(--text-muted); margin-bottom: 1.5rem;">The applicant has been selected. You can now convert this application into a permanent student record.</p>
                <form action="admission_view.php?id=<?php echo $id; ?>" method="POST">
                    <button type="submit" name="convert_student" class="btn" style="width: 100%; background: var(--success); color: white; padding: 1rem; font-weight: 700;" onclick="return confirm('Confirm seat and create student account?')">
                        <i data-lucide="user-check" size="18"></i> Confirm & Enroll Student
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
