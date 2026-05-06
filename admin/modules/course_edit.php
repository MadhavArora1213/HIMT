<?php
$page_title = 'Edit Course';
include '../includes/header.php';

$id = $_GET['id'] ?? null;
$course = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([id]);
    $course = $stmt->fetch();
}

// Fetch departments for selection
$departments = $pdo->query("SELECT id, name FROM departments WHERE is_active = 1")->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department_id = $_POST['department_id'];
    $name = $_POST['name'];
    $short_name = $_POST['short_name'];
    $course_type = $_POST['course_type'];
    $duration_years = $_POST['duration_years'];
    $total_semesters = $_POST['total_semesters'];
    $total_seats = $_POST['total_seats'];
    $annual_fee = $_POST['annual_fee'];
    $eligibility = $_POST['eligibility'];
    $affiliation = $_POST['affiliation'];
    $description = $_POST['description'];
    $career_prospects = $_POST['career_prospects'];
    $specializations = json_encode(array_filter(array_map('trim', explode(',', $_POST['specializations']))));
    $sort_order = $_POST['sort_order'];

    // Handle File Uploads
    $syllabus_path = $course['syllabus_pdf'] ?? '';
    $brochure_path = $course['brochure_pdf'] ?? '';

    if (isset($_FILES['syllabus_pdf']) && $_FILES['syllabus_pdf']['error'] === 0) {
        $target = '../assets/docs/syllabus_' . time() . '.pdf';
        if (move_uploaded_file($_FILES['syllabus_pdf']['tmp_name'], $target)) {
            $syllabus_path = 'assets/docs/' . basename($target);
        }
    }

    if (isset($_FILES['brochure_pdf']) && $_FILES['brochure_pdf']['error'] === 0) {
        $target = '../assets/docs/brochure_' . time() . '.pdf';
        if (move_uploaded_file($_FILES['brochure_pdf']['tmp_name'], $target)) {
            $brochure_path = 'assets/docs/' . basename($target);
        }
    }

    try {
        if ($id) {
            $sql = "UPDATE courses SET 
                    department_id = ?, name = ?, short_name = ?, course_type = ?, 
                    duration_years = ?, total_semesters = ?, total_seats = ?, 
                    annual_fee = ?, eligibility = ?, affiliation = ?, description = ?, 
                    career_prospects = ?, specializations = ?, syllabus_pdf = ?, 
                    brochure_pdf = ?, sort_order = ?
                    WHERE id = ?";
            $pdo->prepare($sql)->execute([
                $department_id, $name, $short_name, $course_type, 
                $duration_years, $total_semesters, $total_seats, 
                $annual_fee, $eligibility, $affiliation, $description, 
                $career_prospects, $specializations, $syllabus_path, 
                $brochure_path, $sort_order, $id
            ]);
        } else {
            $sql = "INSERT INTO courses (department_id, name, short_name, course_type, duration_years, total_semesters, total_seats, annual_fee, eligibility, affiliation, description, career_prospects, specializations, syllabus_pdf, brochure_pdf, sort_order) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([
                $department_id, $name, $short_name, $course_type, 
                $duration_years, $total_semesters, $total_seats, 
                $annual_fee, $eligibility, $affiliation, $description, 
                $career_prospects, $specializations, $syllabus_path, 
                $brochure_path, $sort_order
            ]);
        }
        header("Location: courses.php?success=Course saved successfully");
        exit();
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="courses.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Courses
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;"><?php echo $id ? 'Edit' : 'Add New'; ?> Course</h2>
    </div>

    <?php if (isset($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2);">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="course_edit.php<?php echo $id ? '?id='.$id : ''; ?>" method="POST" enctype="multipart/form-data">
        <div class="grid-layout" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <div class="left-column">
                <div class="card">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Course Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo $course['name'] ?? ''; ?>" placeholder="e.g. Bachelor of Computer Applications" required>
                        </div>
                        <div class="form-group">
                            <label>Short Name</label>
                            <input type="text" name="short_name" class="form-control" value="<?php echo $course['short_name'] ?? ''; ?>" placeholder="e.g. BCA" required>
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <select name="department_id" class="form-control" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo (isset($course['department_id']) && $course['department_id'] == $dept['id']) ? 'selected' : ''; ?>><?php echo $dept['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Course Type</label>
                            <select name="course_type" class="form-control" required>
                                <option value="UG" <?php echo (isset($course['course_type']) && $course['course_type'] == 'UG') ? 'selected' : ''; ?>>Undergraduate (UG)</option>
                                <option value="PG" <?php echo (isset($course['course_type']) && $course['course_type'] == 'PG') ? 'selected' : ''; ?>>Postgraduate (PG)</option>
                                <option value="Diploma" <?php echo (isset($course['course_type']) && $course['course_type'] == 'Diploma') ? 'selected' : ''; ?>>Diploma</option>
                                <option value="PhD" <?php echo (isset($course['course_type']) && $course['course_type'] == 'PhD') ? 'selected' : ''; ?>>PhD</option>
                                <option value="Certificate" <?php echo (isset($course['course_type']) && $course['course_type'] == 'Certificate') ? 'selected' : ''; ?>>Certificate</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Duration (Years)</label>
                            <input type="number" step="0.5" name="duration_years" class="form-control" value="<?php echo $course['duration_years'] ?? '3.0'; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Total Semesters</label>
                            <input type="number" name="total_semesters" class="form-control" value="<?php echo $course['total_semesters'] ?? '6'; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Approved Seats</label>
                            <input type="number" name="total_seats" class="form-control" value="<?php echo $course['total_seats'] ?? '60'; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Annual Fee (INR)</label>
                            <input type="number" name="annual_fee" class="form-control" value="<?php echo $course['annual_fee'] ?? ''; ?>" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label>Specializations (Comma separated)</label>
                        <?php 
                            $specs = '';
                            if (isset($course['specializations'])) {
                                $specs_array = json_decode($course['specializations'], true);
                                $specs = implode(', ', $specs_array);
                            }
                        ?>
                        <input type="text" name="specializations" class="form-control" value="<?php echo $specs; ?>" placeholder="e.g. AI & ML, Cloud Computing, Cyber Security">
                    </div>

                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label>Eligibility Criteria</label>
                        <textarea name="eligibility" class="form-control" rows="4" placeholder="Minimum qualifications required..."><?php echo $course['eligibility'] ?? ''; ?></textarea>
                    </div>

                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label>Career Prospects</label>
                        <textarea name="career_prospects" class="form-control" rows="4" placeholder="Job opportunities after this course..."><?php echo $course['career_prospects'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>

            <div class="right-column">
                <div class="card">
                    <div class="form-group">
                        <label>Affiliated University / Board</label>
                        <input type="text" name="affiliation" class="form-control" value="<?php echo $course['affiliation'] ?? ''; ?>" placeholder="e.g. AKTU, Lucknow">
                    </div>

                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label>Syllabus PDF</label>
                        <input type="file" name="syllabus_pdf" class="form-control" accept=".pdf">
                        <?php if (isset($course['syllabus_pdf'])): ?>
                            <a href="../<?php echo $course['syllabus_pdf']; ?>" target="_blank" style="font-size: 0.75rem; color: var(--accent); display: block; margin-top: 5px;">View Current Syllabus</a>
                        <?php endif; ?>
                    </div>

                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label>Course Brochure PDF</label>
                        <input type="file" name="brochure_pdf" class="form-control" accept=".pdf">
                        <?php if (isset($course['brochure_pdf'])): ?>
                            <a href="../<?php echo $course['brochure_pdf']; ?>" target="_blank" style="font-size: 0.75rem; color: var(--accent); display: block; margin-top: 5px;">View Current Brochure</a>
                        <?php endif; ?>
                    </div>

                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label>Display Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="<?php echo $course['sort_order'] ?? '0'; ?>">
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">
                            <i data-lucide="save"></i> Save Course
                        </button>
                    </div>
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
