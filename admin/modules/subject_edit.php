<?php
require_once '../includes/config.php';

$id = $_GET['id'] ?? null;
$subject = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([$id]);
    $subject = $stmt->fetch();
}

$depts = $pdo->query("SELECT id, name FROM departments WHERE is_active = 1")->fetchAll();
$courses = $pdo->query("SELECT id, name FROM courses WHERE is_active = 1")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $code = $_POST['subject_code'];
    $dept_id = $_POST['department_id'];
    $course_id = $_POST['course_id'];
    $semester = $_POST['semester'];
    $type = $_POST['subject_type'];
    $credits = $_POST['credits'];
    $max_internal = $_POST['max_internal_marks'];
    $max_external = $_POST['max_external_marks'];
    $passing = $_POST['passing_marks'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    try {
        if ($id) {
            $sql = "UPDATE subjects SET 
                    department_id = ?, course_id = ?, name = ?, subject_code = ?, 
                    semester = ?, subject_type = ?, credits = ?, 
                    max_internal_marks = ?, max_external_marks = ?, 
                    passing_marks = ?, is_active = ?
                    WHERE id = ?";
            $pdo->prepare($sql)->execute([
                $dept_id, $course_id, $name, $code, $semester, $type, 
                $credits, $max_internal, $max_external, $passing, $is_active, $id
            ]);
        } else {
            $sql = "INSERT INTO subjects (department_id, course_id, name, subject_code, semester, subject_type, credits, max_internal_marks, max_external_marks, passing_marks, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([
                $dept_id, $course_id, $name, $code, $semester, $type, 
                $credits, $max_internal, $max_external, $passing, $is_active
            ]);
        }
        header("Location: subjects.php?success=Subject saved successfully");
        exit();
    } catch (Exception $e) {
        $error = db_error_message($e);
    }
}

$page_title = ($id ? 'Edit' : 'Add') . ' Subject';
include '../includes/header.php';
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="subjects.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Subjects
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;"><?php echo $id ? 'Edit' : 'Add New'; ?> Subject</h2>
    </div>

    <?php if (isset($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2);">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="subject_edit.php<?php echo $id ? '?id='.$id : ''; ?>" method="POST">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <div class="left-column">
                <div class="card">
                    <h3 style="font-size: 1rem; margin-bottom: 1.5rem;">General Information</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Subject Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo $subject['name'] ?? ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Subject Code</label>
                            <input type="text" name="subject_code" class="form-control" value="<?php echo $subject['subject_code'] ?? ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Subject Type</label>
                            <select name="subject_type" class="form-control" required>
                                <option value="Theory" <?php echo (isset($subject['subject_type']) && $subject['subject_type'] == 'Theory') ? 'selected' : ''; ?>>Theory</option>
                                <option value="Practical" <?php echo (isset($subject['subject_type']) && $subject['subject_type'] == 'Practical') ? 'selected' : ''; ?>>Practical</option>
                                <option value="Project" <?php echo (isset($subject['subject_type']) && $subject['subject_type'] == 'Project') ? 'selected' : ''; ?>>Project</option>
                                <option value="Elective" <?php echo (isset($subject['subject_type']) && $subject['subject_type'] == 'Elective') ? 'selected' : ''; ?>>Elective</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <select name="department_id" class="form-control" required>
                                <option value="">Select Dept</option>
                                <?php foreach ($depts as $d): ?>
                                    <option value="<?php echo $d['id']; ?>" <?php echo (isset($subject['department_id']) && $subject['department_id'] == $d['id']) ? 'selected' : ''; ?>><?php echo $d['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Course</label>
                            <select name="course_id" class="form-control" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo (isset($subject['course_id']) && $subject['course_id'] == $c['id']) ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Semester</label>
                            <select name="semester" class="form-control" required>
                                <?php for($i=1; $i<=10; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo (isset($subject['semester']) && $subject['semester'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?>th Sem</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Credits</label>
                            <input type="number" name="credits" class="form-control" value="<?php echo $subject['credits'] ?? '4'; ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right-column">
                <div class="card" style="margin-bottom: 2rem;">
                    <h3 style="font-size: 1rem; margin-bottom: 1.5rem;">Marking Scheme</h3>
                    <div class="form-group">
                        <label>Max Internal Marks</label>
                        <input type="number" name="max_internal_marks" class="form-control" value="<?php echo $subject['max_internal_marks'] ?? '30'; ?>">
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label>Max External Marks</label>
                        <input type="number" name="max_external_marks" class="form-control" value="<?php echo $subject['max_external_marks'] ?? '70'; ?>">
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label>Passing Marks</label>
                        <input type="number" name="passing_marks" class="form-control" value="<?php echo $subject['passing_marks'] ?? '40'; ?>">
                    </div>
                    <div style="margin-top: 1.5rem; display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="is_active" id="is_active" <?php echo (!isset($subject['is_active']) || $subject['is_active']) ? 'checked' : ''; ?>>
                        <label for="is_active" style="margin:0;">Active Status</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">
                    <i data-lucide="save"></i> Save Subject
                </button>
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
