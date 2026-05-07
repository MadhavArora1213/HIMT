<?php
require_once '../includes/config.php';

// Handle Template Download
if (isset($_GET['template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=student_full_import_template.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Name', 'Email', 'Enrollment', 'Roll', 'DeptID', 'CourseID', 
        'AdmissionYear', 'CurrentSem', 'CurrentYear', 'DOB(YYYY-MM-DD)', 
        'Gender', 'Category', 'Aadhar', 'Address', 'ParentName', 'ParentPhone', 'AdmissionType'
    ]);
    // Sample data
    fputcsv($output, [
        'John Doe', 'john@example.com', 'ENR001', '101', '1', '1', 
        date('Y'), '1', '1', '2005-01-01', 
        'Male', 'General', '123456789012', '123 Street, City', 'Robert Doe', '9876543210', 'Regular'
    ]);
    exit();
}

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
            if (count($data) < 17) continue; 
            
            // Map data
            $name = $data[0]; $email = $data[1]; $enrollment = $data[2]; $roll = $data[3];
            $dept_id = $data[4]; $course_id = $data[5]; $adm_year = $data[6]; $sem = $data[7];
            $year = $data[8]; $dob = $data[9]; $gender = $data[10]; $cat = $data[11];
            $aadhar = $data[12]; $addr = $data[13]; $p_name = $data[14]; $p_phone = $data[15];
            $adm_type = $data[16];
            
            // Check if user already exists
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) continue; 
            
            // 1. Create User
            $hashed_pass = password_hash('123456', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
            $stmt->execute([$name, $email, $hashed_pass]);
            $user_id = $pdo->lastInsertId();
            
            // 2. Create Student
            $sql = "INSERT INTO students (user_id, department_id, course_id, enrollment_no, roll_number, admission_year, current_semester, current_year, date_of_birth, gender, category, aadhar_number, permanent_address, parent_name, parent_phone, admission_type) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([
                $user_id, $dept_id, $course_id, $enrollment, $roll, 
                $adm_year, $sem, $year, $dob, $gender, $cat, $aadhar, $addr, $p_name, $p_phone, $adm_type
            ]);
        }
        
        $pdo->commit();
        $success = "All students imported with complete profiles successfully!";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "Import Failed: " . db_error_message($e);
    }
    fclose($handle);
}

$page_title = 'Bulk Import Students';
include '../includes/header.php';
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
                    <input type="file" name="student_csv" id="student_csv" accept=".csv" required style="display: block; margin: 0 auto;">
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
                <li>Download the <strong>Full CSV Template</strong> below.</li>
                <li>Ensure all <strong>17 mandatory fields</strong> are filled (Name, Email, DOB, etc.).</li>
                <li>Use YYYY-MM-DD format for dates (e.g., 2005-01-01).</li>
                <li>Use valid <strong>Department ID</strong> and <strong>Course ID</strong> from the reference below.</li>
                <li>Passwords for all imported students will be set to <strong>123456</strong>.</li>
                <li>The system will automatically skip rows with existing email addresses.</li>
            </ul>
            <a href="student_import.php?template=1" class="btn" style="background: #f1f5f9; color: var(--text-main); display: inline-flex; align-items: center; gap: 10px; margin-bottom: 2rem;">
                <i data-lucide="download"></i> Download CSV Template
            </a>

            <div style="border-top: 1px solid var(--border); padding-top: 1.5rem;">
                <h4 style="font-size: 0.875rem; margin-bottom: 1rem; color: var(--text-main);">Database Reference (IDs)</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <p style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 5px;">Departments</p>
                        <div style="max-height: 150px; overflow-y: auto; font-size: 0.75rem; background: #f8fafc; border-radius: 6px; padding: 10px; border: 1px solid var(--border);">
                            <?php 
                            $ref_depts = $pdo->query("SELECT id, name FROM departments WHERE is_active = 1")->fetchAll();
                            foreach($ref_depts as $rd) echo "ID {$rd['id']}: {$rd['name']}<br>";
                            ?>
                        </div>
                    </div>
                    <div>
                        <p style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 5px;">Courses</p>
                        <div style="max-height: 150px; overflow-y: auto; font-size: 0.75rem; background: #f8fafc; border-radius: 6px; padding: 10px; border: 1px solid var(--border);">
                            <?php 
                            $ref_courses = $pdo->query("SELECT id, name FROM courses WHERE is_active = 1")->fetchAll();
                            foreach($ref_courses as $rc) echo "ID {$rc['id']}: {$rc['name']}<br>";
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
