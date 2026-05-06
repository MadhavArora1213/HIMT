<?php
$page_title = 'Bulk Import Students';
include '../includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['student_csv'])) {
    $file = $_FILES['student_csv']['tmp_name'];
    $handle = fopen($file, "r");
    
    // Skip header
    fgetcsv($handle);
    
    try {
        $pdo->beginTransaction();
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // CSV columns: Name, Email, Enrollment, Roll, DeptID, CourseID, parent_phone
            $name = $data[0];
            $email = $data[1];
            $enrollment = $data[2];
            $roll = $data[3];
            $dept_id = $data[4];
            $course_id = $data[5];
            $parent_phone = $data[6];
            
            // 1. Create User
            $hashed_pass = password_hash('123456', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
            $stmt->execute([$name, $email, $hashed_pass]);
            $user_id = $pdo->lastInsertId();
            
            // 2. Create Student
            $sql = "INSERT INTO students (user_id, department_id, course_id, enrollment_no, roll_number, admission_year, current_semester, current_year, parent_name, parent_phone, admission_type, date_of_birth, gender, category, permanent_address) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([
                $user_id, $dept_id, $course_id, $enrollment, $roll, 
                date('Y'), 1, 1, 'Imported', $parent_phone, 'Regular',
                '2000-01-01', 'Male', 'General', 'Imported Address'
            ]);
        }
        
        $pdo->commit();
        $success = "Students imported successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Import Failed: " . $e->getMessage();
    }
    fclose($handle);
}
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="students.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Student Directory
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;">Bulk Import Students</h2>
    </div>

    <?php if ($success): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2);">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2);">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="grid-layout" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div class="card">
            <h3 style="font-size: 1rem; margin-bottom: 1.5rem;">Upload CSV File</h3>
            <form action="student_import.php" method="POST" enctype="multipart/form-data">
                <div style="border: 2px dashed var(--border); padding: 3rem; text-align: center; border-radius: 12px; background: #f8fafc;">
                    <i data-lucide="file-text" size="48" style="color: var(--accent); margin-bottom: 1rem;"></i>
                    <input type="file" name="student_csv" accept=".csv" required style="display: block; margin: 0 auto;">
                    <p style="font-size: 0.8125rem; color: var(--text-muted); margin-top: 1rem;">Only .csv files are supported. Max size 2MB.</p>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 1rem;">
                    Start Import Process
                </button>
            </form>
        </div>

        <div class="card">
            <h3 style="font-size: 1rem; margin-bottom: 1rem;">Instructions & Template</h3>
            <ul style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.8; margin-bottom: 2rem;">
                <li>Download the sample CSV template below.</li>
                <li>Ensure all mandatory fields (Name, Email, Enrollment) are filled.</li>
                <li>Use valid Department and Course IDs from the system.</li>
                <li>Passwords for imported students will be set to <strong>123456</strong> by default.</li>
            </ul>
            <a href="#" class="btn" style="background: #f1f5f9; color: var(--text-main); display: inline-flex; align-items: center; gap: 10px;">
                <i data-lucide="download"></i> Download CSV Template
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
