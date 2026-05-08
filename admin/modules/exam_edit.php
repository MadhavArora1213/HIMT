<?php
require_once '../includes/config.php';

$id = $_GET['id'] ?? null;
$exam = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM examinations WHERE id = ?");
    $stmt->execute([$id]);
    $exam = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $semester = $_POST['semester'];
    $exam_type = $_POST['exam_type'];
    $exam_name = $_POST['exam_name'];
    $academic_year = $_POST['academic_year'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $result_date = $_POST['result_date'] ?: null;
    $is_published = isset($_POST['is_published']) ? 1 : 0;

    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE examinations SET course_id = ?, semester = ?, exam_type = ?, exam_name = ?, academic_year = ?, start_date = ?, end_date = ?, result_date = ?, is_published = ? WHERE id = ?");
            $stmt->execute([$course_id, $semester, $exam_type, $exam_name, $academic_year, $start_date, $end_date, $result_date, $is_published, $id]);
            $msg = "Examination updated successfully";
        } else {
            $stmt = $pdo->prepare("INSERT INTO examinations (course_id, semester, exam_type, exam_name, academic_year, start_date, end_date, result_date, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$course_id, $semester, $exam_type, $exam_name, $academic_year, $start_date, $end_date, $result_date, $is_published]);
            $msg = "Examination scheduled successfully";
        }
        header("Location: exams.php?success=" . urlencode($msg));
        exit();
    } catch (Exception $e) {
        $error = db_error_message($e);
    }
}

$page_title = $id ? 'Edit Examination' : 'Create New Examination';
include '../includes/header.php';

$courses = $pdo->query("SELECT id, name FROM courses WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="exams.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Examinations
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;"><?php echo $page_title; ?></h2>
    </div>

    <?php if (isset($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2);">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" class="card" style="padding: 2rem; max-width: 800px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div class="form-group" style="grid-column: span 2;">
                <label>Examination Name</label>
                <input type="text" name="exam_name" class="form-control" placeholder="e.g. End Semester Theory Examination May 2026" value="<?php echo $exam['exam_name'] ?? ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Course</label>
                <select name="course_id" class="form-control" required>
                    <option value="">Select Course</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo (isset($exam['course_id']) && $exam['course_id'] == $c['id']) ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Semester</label>
                <select name="semester" class="form-control" required>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo (isset($exam['semester']) && $exam['semester'] == $i) ? 'selected' : ''; ?>>Semester <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Examination Type</label>
                <select name="exam_type" class="form-control" required>
                    <option value="Internal" <?php echo (isset($exam['exam_type']) && $exam['exam_type'] == 'Internal') ? 'selected' : ''; ?>>Internal Assessment</option>
                    <option value="Semester End" <?php echo (isset($exam['exam_type']) && $exam['exam_type'] == 'Semester End') ? 'selected' : ''; ?>>Semester End Exam</option>
                    <option value="Practical" <?php echo (isset($exam['exam_type']) && $exam['exam_type'] == 'Practical') ? 'selected' : ''; ?>>Practical Exam</option>
                    <option value="Viva" <?php echo (isset($exam['exam_type']) && $exam['exam_type'] == 'Viva') ? 'selected' : ''; ?>>Viva Voce</option>
                </select>
            </div>

            <div class="form-group">
                <label>Academic Year</label>
                <input type="text" name="academic_year" class="form-control" placeholder="2025-26" value="<?php echo $exam['academic_year'] ?? ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $exam['start_date'] ?? ''; ?>" required>
            </div>

            <div class="form-group">
                <label>End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $exam['end_date'] ?? ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Expected Result Date</label>
                <input type="date" name="result_date" class="form-control" value="<?php echo $exam['result_date'] ?? ''; ?>">
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 1rem;">
                <input type="checkbox" name="is_published" id="is_published" <?php echo (isset($exam['is_published']) && $exam['is_published']) ? 'checked' : ''; ?> style="width: 20px; height: 20px;">
                <label for="is_published" style="margin-bottom: 0;">Publish results to students</label>
            </div>
        </div>

        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 1rem;">
            <a href="exams.php" class="btn" style="background: var(--background); border: 1px solid var(--border);">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i> <?php echo $id ? 'Update Schedule' : 'Schedule Exam'; ?>
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
