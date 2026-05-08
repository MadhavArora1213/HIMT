<?php
require_once '../includes/config.php';

$id = $_GET['id'] ?? null;
$material = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM study_materials WHERE id = ?");
    $stmt->execute([$id]);
    $material = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $course_id = $_POST['course_id'];
    $subject_id = $_POST['subject_id'];
    $desc = $_POST['description'];
    
    $filename = $material['file_path'] ?? '';

    // Handle File Upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'zip'];
        
        if (!in_array($ext, $allowed)) {
            $error = "Invalid file type. Only PDF, DOC, and ZIP files are allowed.";
        } else {
            $filename = 'study_' . time() . '.' . $ext;
            $target = '../assets/docs/' . $filename;
            
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
                $error = "File upload failed.";
            }
        }
    }

    if (!isset($error)) {
        try {
            if ($id) {
                $pdo->prepare("UPDATE study_materials SET course_id = ?, subject_id = ?, title = ?, file_path = ?, description = ? WHERE id = ?")
                    ->execute([$course_id, $subject_id, $title, $filename, $desc, $id]);
                $msg = "Study material updated successfully";
            } else {
            // Find faculty_id for current user
            $stmt = $pdo->prepare("SELECT id FROM faculty WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $faculty = $stmt->fetch();
            
            if ($faculty) {
                $faculty_id = $faculty['id'];
            } else {
                // If admin, find the first available faculty ID
                $f_check = $pdo->query("SELECT id FROM faculty LIMIT 1")->fetch();
                if (!$f_check) {
                    throw new Exception("Cannot upload material: No faculty members found in the system. Please create a faculty account first.");
                }
                $faculty_id = $f_check['id'];
            }
            
            $pdo->prepare("INSERT INTO study_materials (faculty_id, course_id, subject_id, title, file_path, description) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$faculty_id, $course_id, $subject_id, $title, $filename, $desc]);
            $msg = "Study material uploaded successfully";
        }
        header("Location: study_materials.php?success=" . urlencode($msg));
        exit();
    } catch (Exception $e) {
        $error = db_error_message($e);
    }
}
}

$page_title = $id ? 'Edit Study Material' : 'Upload Study Material';
include '../includes/header.php';

$courses = $pdo->query("SELECT id, name FROM courses WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
$subjects = $pdo->query("SELECT id, name FROM subjects WHERE is_active = 1 ORDER BY name ASC")->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="study_materials.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Materials
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;"><?php echo $page_title; ?></h2>
    </div>

    <?php if (isset($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2);">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" class="card" style="padding: 2rem; max-width: 800px;">
        <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
            <div class="form-group">
                <label>Resource Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Unit 1: Data Structures Overview" value="<?php echo $material['title'] ?? ''; ?>" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label>Course</label>
                    <select name="course_id" class="form-control" required>
                        <option value="">Select Course</option>
                        <?php foreach($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo (isset($material['course_id']) && $material['course_id'] == $c['id']) ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <select name="subject_id" class="form-control" required>
                        <option value="">Select Subject</option>
                        <?php foreach($subjects as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo (isset($material['subject_id']) && $material['subject_id'] == $s['id']) ? 'selected' : ''; ?>><?php echo $s['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>File (PDF/DOC/ZIP) <?php echo isset($material['file_path']) ? '<span style="color:var(--success)">(Current: ' . $material['file_path'] . ')</span>' : ''; ?></label>
                <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx,.zip" <?php echo $id ? '' : 'required'; ?>>
                <?php if($id): ?>
                    <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 5px;">Leave blank to keep the current file.</p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Brief Description</label>
                <textarea name="description" class="form-control" rows="4" placeholder="Briefly describe the contents of this resource..."><?php echo $material['description'] ?? ''; ?></textarea>
            </div>
        </div>

        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 1rem;">
            <a href="study_materials.php" class="btn" style="background: var(--background); border: 1px solid var(--border);">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="upload-cloud"></i> <?php echo $id ? 'Update Resource' : 'Publish Resource'; ?>
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
