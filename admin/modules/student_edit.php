<?php
$page_title = 'Enroll Student';
include '../includes/header.php';

$id = $_GET['id'] ?? null;
$student = null;
$user = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT s.*, u.name, u.email, u.phone as user_phone FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
}

$depts = $pdo->query("SELECT id, name FROM departments WHERE is_active = 1")->fetchAll();
$courses = $pdo->query("SELECT id, name FROM courses WHERE is_active = 1")->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'] ?? '';
    
    // Academic Info
    $dept_id = $_POST['department_id'];
    $course_id = $_POST['course_id'];
    $enrollment_no = $_POST['enrollment_no'];
    $roll_number = $_POST['roll_number'];
    $admission_year = $_POST['admission_year'];
    $current_semester = $_POST['current_semester'];
    $current_year = $_POST['current_year'];
    
    // Personal Info
    $dob = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $category = $_POST['category'];
    $aadhar_number = $_POST['aadhar_number'];
    $permanent_address = $_POST['permanent_address'];
    $parent_name = $_POST['parent_name'];
    $parent_phone = $_POST['parent_phone'];
    $admission_type = $_POST['admission_type'];

    try {
        $pdo->beginTransaction();

        if ($id) {
            // Update User
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $student['user_id']]);
            
            // Update Student
            $sql = "UPDATE students SET 
                    department_id = ?, course_id = ?, enrollment_no = ?, roll_number = ?, 
                    admission_year = ?, current_semester = ?, current_year = ?,
                    date_of_birth = ?, gender = ?, category = ?, aadhar_number = ?,
                    permanent_address = ?, parent_name = ?, parent_phone = ?, admission_type = ?
                    WHERE id = ?";
            $pdo->prepare($sql)->execute([
                $dept_id, $course_id, $enrollment_no, $roll_number,
                $admission_year, $current_semester, $current_year,
                $dob, $gender, $category, $aadhar_number,
                $permanent_address, $parent_name, $parent_phone, $admission_type,
                $id
            ]);
        } else {
            // Create User
            $hashed_pass = password_hash($password ?: '123456', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
            $stmt->execute([$name, $email, $hashed_pass]);
            $user_id = $pdo->lastInsertId();
            
            // Create Student
            $sql = "INSERT INTO students (user_id, department_id, course_id, enrollment_no, roll_number, admission_year, current_semester, current_year, date_of_birth, gender, category, aadhar_number, permanent_address, parent_name, parent_phone, admission_type) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([
                $user_id, $dept_id, $course_id, $enrollment_no, $roll_number,
                $admission_year, $current_semester, $current_year,
                $dob, $gender, $category, $aadhar_number,
                $permanent_address, $parent_name, $parent_phone, $admission_type
            ]);
        }

        $pdo->commit();
        header("Location: students.php?success=Student record saved successfully");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="students.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Student Directory
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;"><?php echo $id ? 'Edit Profile:' : 'New Enrollment'; ?> <?php echo $student['name'] ?? ''; ?></h2>
    </div>

    <?php if (isset($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2);">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="student_edit.php<?php echo $id ? '?id='.$id : ''; ?>" method="POST">
        <!-- 1. Authentication Info -->
        <div class="card" style="margin-bottom: 2rem;">
            <h3 style="font-size: 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                <i data-lucide="lock" size="18" style="color: var(--accent);"></i> Account Credentials
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo $student['name'] ?? ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Login Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo $student['email'] ?? ''; ?>" required>
                </div>
                <?php if (!$id): ?>
                <div class="form-group">
                    <label>Password (Default: 123456)</label>
                    <input type="password" name="password" class="form-control" placeholder="Leave blank for default">
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <div class="main-fields">
                <!-- 2. Academic Information -->
                <div class="card" style="margin-bottom: 2rem;">
                    <h3 style="font-size: 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                        <i data-lucide="graduation-cap" size="18" style="color: var(--accent);"></i> Academic Record
                    </h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label>Department</label>
                            <select name="department_id" class="form-control" required>
                                <option value="">Select Dept</option>
                                <?php foreach ($depts as $d): ?>
                                    <option value="<?php echo $d['id']; ?>" <?php echo (isset($student['department_id']) && $student['department_id'] == $d['id']) ? 'selected' : ''; ?>><?php echo $d['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Course</label>
                            <select name="course_id" class="form-control" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo (isset($student['course_id']) && $student['course_id'] == $c['id']) ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Enrollment Number</label>
                            <input type="text" name="enrollment_no" class="form-control" value="<?php echo $student['enrollment_no'] ?? ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Roll Number</label>
                            <input type="text" name="roll_number" class="form-control" value="<?php echo $student['roll_number'] ?? ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Admission Year</label>
                            <input type="number" name="admission_year" class="form-control" value="<?php echo $student['admission_year'] ?? date('Y'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Current Semester</label>
                            <select name="current_semester" class="form-control">
                                <?php for($i=1; $i<=8; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo (isset($student['current_semester']) && $student['current_semester'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?>th Sem</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 3. Personal Details -->
                <div class="card">
                    <h3 style="font-size: 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                        <i data-lucide="user" size="18" style="color: var(--accent);"></i> Personal Information
                    </h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" value="<?php echo $student['date_of_birth'] ?? ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" class="form-control">
                                <option value="Male" <?php echo (isset($student['gender']) && $student['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (isset($student['gender']) && $student['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo (isset($student['gender']) && $student['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" class="form-control">
                                <option value="General" <?php echo (isset($student['category']) && $student['category'] == 'General') ? 'selected' : ''; ?>>General</option>
                                <option value="OBC" <?php echo (isset($student['category']) && $student['category'] == 'OBC') ? 'selected' : ''; ?>>OBC</option>
                                <option value="SC" <?php echo (isset($student['category']) && $student['category'] == 'SC') ? 'selected' : ''; ?>>SC</option>
                                <option value="ST" <?php echo (isset($student['category']) && $student['category'] == 'ST') ? 'selected' : ''; ?>>ST</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Aadhar Number</label>
                            <input type="text" name="aadhar_number" class="form-control" value="<?php echo $student['aadhar_number'] ?? ''; ?>" placeholder="12 Digit Number">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label>Permanent Address</label>
                        <textarea name="permanent_address" class="form-control" rows="3"><?php echo $student['permanent_address'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>

            <div class="sidebar-fields">
                <!-- 4. Parent/Guardian -->
                <div class="card" style="margin-bottom: 2rem;">
                    <h3 style="font-size: 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;">
                        <i data-lucide="users" size="18" style="color: var(--accent);"></i> Parent/Guardian
                    </h3>
                    <div class="form-group">
                        <label>Father/Guardian Name</label>
                        <input type="text" name="parent_name" class="form-control" value="<?php echo $student['parent_name'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label>Parent Mobile</label>
                        <input type="text" name="parent_phone" class="form-control" value="<?php echo $student['parent_phone'] ?? ''; ?>" required>
                    </div>
                </div>

                <!-- 5. Status & Submission -->
                <div class="card">
                    <div class="form-group">
                        <label>Admission Type</label>
                        <select name="admission_type" class="form-control">
                            <option value="Regular" <?php echo (isset($student['admission_type']) && $student['admission_type'] == 'Regular') ? 'selected' : ''; ?>>Regular</option>
                            <option value="Lateral" <?php echo (isset($student['admission_type']) && $student['admission_type'] == 'Lateral') ? 'selected' : ''; ?>>Lateral Entry</option>
                            <option value="Management" <?php echo (isset($student['admission_type']) && $student['admission_type'] == 'Management') ? 'selected' : ''; ?>>Management Quota</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label>Current Year</label>
                        <select name="current_year" class="form-control">
                            <option value="1" <?php echo (isset($student['current_year']) && $student['current_year'] == 1) ? 'selected' : ''; ?>>1st Year</option>
                            <option value="2" <?php echo (isset($student['current_year']) && $student['current_year'] == 2) ? 'selected' : ''; ?>>2nd Year</option>
                            <option value="3" <?php echo (isset($student['current_year']) && $student['current_year'] == 3) ? 'selected' : ''; ?>>3rd Year</option>
                            <option value="4" <?php echo (isset($student['current_year']) && $student['current_year'] == 4) ? 'selected' : ''; ?>>4th Year</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 2rem; padding: 1rem;">
                        <i data-lucide="save"></i> Complete Enrollment
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        font-size: 0.875rem;
    }
</style>

<?php include '../includes/footer.php'; ?>
