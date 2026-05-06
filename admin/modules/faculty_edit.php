<?php
$page_title = 'Edit Faculty';
include '../includes/header.php';

$id = $_GET['id'] ?? null;
$faculty = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT f.*, u.name, u.email FROM faculty f JOIN users u ON f.user_id = u.id WHERE f.id = ?");
    $stmt->execute([$id]);
    $faculty = $stmt->fetch();
}

$depts = $pdo->query("SELECT id, name FROM departments WHERE is_active = 1")->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'] ?? '';
    
    $dept_id = $_POST['department_id'];
    $employee_code = $_POST['employee_code'];
    $designation = $_POST['designation'];
    $qualification = $_POST['qualification'];
    $specialization = $_POST['specialization'];
    $experience_years = $_POST['experience_years'];
    $date_of_joining = $_POST['date_of_joining'];
    $gender = $_POST['gender'];
    $is_hod = isset($_POST['is_hod']) ? 1 : 0;
    
    $linkedin_url = $_POST['linkedin_url'];
    $google_scholar_url = $_POST['google_scholar_url'];
    $research_interests = $_POST['research_interests'];

    try {
        $pdo->beginTransaction();

        if ($id) {
            // Update User
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $faculty['user_id']]);
            
            // Update Faculty
            $sql = "UPDATE faculty SET 
                    department_id = ?, employee_code = ?, designation = ?, 
                    qualification = ?, specialization = ?, experience_years = ?, 
                    date_of_joining = ?, gender = ?, is_hod = ?, 
                    linkedin_url = ?, google_scholar_url = ?, research_interests = ?
                    WHERE id = ?";
            $pdo->prepare($sql)->execute([
                $dept_id, $employee_code, $designation, $qualification, 
                $specialization, $experience_years, $date_of_joining, 
                $gender, $is_hod, $linkedin_url, $google_scholar_url, 
                $research_interests, $id
            ]);
            
            // If marked as HOD, update the department record too
            if ($is_hod) {
                $pdo->prepare("UPDATE departments SET hod_id = ? WHERE id = ?")->execute([$id, $dept_id]);
            }
        } else {
            // Create User
            $hashed_pass = password_hash($password ?: '123456', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'faculty')");
            $stmt->execute([$name, $email, $hashed_pass]);
            $user_id = $pdo->lastInsertId();
            
            // Create Faculty
            $sql = "INSERT INTO faculty (user_id, department_id, employee_code, designation, qualification, specialization, experience_years, date_of_joining, gender, is_hod, linkedin_url, google_scholar_url, research_interests) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([
                $user_id, $dept_id, $employee_code, $designation, 
                $qualification, $specialization, $experience_years, 
                $date_of_joining, $gender, $is_hod, $linkedin_url, 
                $google_scholar_url, $research_interests
            ]);
            
            $faculty_id = $pdo->lastInsertId();
            if ($is_hod) {
                $pdo->prepare("UPDATE departments SET hod_id = ? WHERE id = ?")->execute([$faculty_id, $dept_id]);
            }
        }

        $pdo->commit();
        header("Location: faculty.php?success=Faculty record saved successfully");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="faculty.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Faculty Directory
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;"><?php echo $id ? 'Edit Profile' : 'Add New Faculty'; ?></h2>
    </div>

    <?php if (isset($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2);">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="faculty_edit.php<?php echo $id ? '?id='.$id : ''; ?>" method="POST">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <div class="left-column">
                <!-- Account Info -->
                <div class="card" style="margin-bottom: 2rem;">
                    <h3 style="font-size: 1rem; margin-bottom: 1.5rem;">Account Information</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo $faculty['name'] ?? ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo $faculty['email'] ?? ''; ?>" required>
                        </div>
                        <?php if (!$id): ?>
                        <div class="form-group">
                            <label>Password (Default: 123456)</label>
                            <input type="password" name="password" class="form-control" placeholder="Set password">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Professional Profile -->
                <div class="card">
                    <h3 style="font-size: 1rem; margin-bottom: 1.5rem;">Professional Profile</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label>Department</label>
                            <select name="department_id" class="form-control" required>
                                <option value="">Select Dept</option>
                                <?php foreach ($depts as $d): ?>
                                    <option value="<?php echo $d['id']; ?>" <?php echo (isset($faculty['department_id']) && $faculty['department_id'] == $d['id']) ? 'selected' : ''; ?>><?php echo $d['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Employee Code</label>
                            <input type="text" name="employee_code" class="form-control" value="<?php echo $faculty['employee_code'] ?? ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Designation</label>
                            <input type="text" name="designation" class="form-control" value="<?php echo $faculty['designation'] ?? ''; ?>" placeholder="e.g. Assistant Professor" required>
                        </div>
                        <div class="form-group">
                            <label>Highest Qualification</label>
                            <input type="text" name="qualification" class="form-control" value="<?php echo $faculty['qualification'] ?? ''; ?>" placeholder="e.g. Ph.D. in CSE" required>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Specialization</label>
                            <input type="text" name="specialization" class="form-control" value="<?php echo $faculty['specialization'] ?? ''; ?>" placeholder="e.g. Data Structures, Machine Learning">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label>Research Interests</label>
                        <textarea name="research_interests" class="form-control" rows="4"><?php echo $faculty['research_interests'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>

            <div class="right-column">
                <!-- Additional Details -->
                <div class="card" style="margin-bottom: 2rem;">
                    <div class="form-group">
                        <label>Joining Date</label>
                        <input type="date" name="date_of_joining" class="form-control" value="<?php echo $faculty['date_of_joining'] ?? date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label>Experience (Years)</label>
                        <input type="number" name="experience_years" class="form-control" value="<?php echo $faculty['experience_years'] ?? '0'; ?>">
                    </div>
                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label>Gender</label>
                        <select name="gender" class="form-control">
                            <option value="Male" <?php echo (isset($faculty['gender']) && $faculty['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (isset($faculty['gender']) && $faculty['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                    <div style="margin-top: 2rem; background: #f8fafc; padding: 1rem; border-radius: 10px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" id="is_hod" name="is_hod" <?php echo (isset($faculty['is_hod']) && $faculty['is_hod']) ? 'checked' : ''; ?>>
                            <label for="is_hod" style="margin: 0; font-weight: 700; color: var(--accent);">Department HOD</label>
                        </div>
                        <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 5px;">Grant HOD administrative privileges for this department.</p>
                    </div>
                </div>

                <!-- Online Presence -->
                <div class="card">
                    <h3 style="font-size: 1rem; margin-bottom: 1.5rem;">Online Profiles</h3>
                    <div class="form-group">
                        <label><i data-lucide="linkedin"></i> LinkedIn</label>
                        <input type="url" name="linkedin_url" class="form-control" value="<?php echo $faculty['linkedin_url'] ?? ''; ?>">
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label><i data-lucide="graduation-cap"></i> Google Scholar</label>
                        <input type="url" name="google_scholar_url" class="form-control" value="<?php echo $faculty['google_scholar_url'] ?? ''; ?>">
                    </div>
                </div>

                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">
                        <i data-lucide="save"></i> Save Faculty Member
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
