<?php
require_once '../includes/config.php';

$id = $_GET['id'] ?? null;
$dept = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
    $stmt->execute([$id]);
    $dept = $stmt->fetch();
}

// Fetch faculty for HOD selection
$faculty_list = $pdo->query("SELECT f.id, u.name FROM faculty f JOIN users u ON f.user_id = u.id WHERE f.is_active = 1")->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $short_name = $_POST['short_name'];
    $hod_id = !empty($_POST['hod_id']) ? $_POST['hod_id'] : null;
    $established_year = $_POST['established_year'];
    $intake_capacity = $_POST['intake_capacity'];
    $description = $_POST['description'];
    $lab_details = $_POST['lab_details'];
    $accreditation = $_POST['accreditation'];
    $sort_order = $_POST['sort_order'];
    $institute_id = 1; // Assuming single institute for now

    // Handle Image Upload
    $image_path = $dept['department_image'] ?? '';
    if (isset($_FILES['department_image']) && $_FILES['department_image']['error'] === 0) {
        $ext = pathinfo($_FILES['department_image']['name'], PATHINFO_EXTENSION);
        $new_name = 'dept_' . time() . '.' . $ext;
        $target = '../assets/img/uploads/' . $new_name;
        if (move_uploaded_file($_FILES['department_image']['tmp_name'], $target)) {
            $image_path = 'assets/img/uploads/' . $new_name;
        }
    }

    try {
        if ($id) {
            $sql = "UPDATE departments SET 
                    name = ?, short_name = ?, hod_id = ?, established_year = ?, 
                    intake_capacity = ?, description = ?, lab_details = ?, 
                    accreditation = ?, department_image = ?, sort_order = ?
                    WHERE id = ?";
            $pdo->prepare($sql)->execute([
                $name, $short_name, $hod_id, $established_year, 
                $intake_capacity, $description, $lab_details, 
                $accreditation, $image_path, $sort_order, $id
            ]);
        } else {
            $sql = "INSERT INTO departments (institute_id, name, short_name, hod_id, established_year, intake_capacity, description, lab_details, accreditation, department_image, sort_order) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([
                $institute_id, $name, $short_name, $hod_id, $established_year, 
                $intake_capacity, $description, $lab_details, $accreditation, $image_path, $sort_order
            ]);
        }
        header("Location: departments.php?success=Department saved successfully");
        exit();
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

$page_title = ($id ? 'Edit' : 'Add') . ' Department';
include '../includes/header.php';
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="departments.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Departments
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;"><?php echo $id ? 'Edit' : 'Add New'; ?> Department</h2>
    </div>

    <?php if (isset($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2);">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="department_edit.php<?php echo $id ? '?id='.$id : ''; ?>" method="POST" enctype="multipart/form-data">
        <div class="grid-layout" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <div class="left-column">
                <div class="card">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Department Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo $dept['name'] ?? ''; ?>" placeholder="e.g. Computer Science & Engineering" required>
                        </div>
                        <div class="form-group">
                            <label>Short Name</label>
                            <input type="text" name="short_name" class="form-control" value="<?php echo $dept['short_name'] ?? ''; ?>" placeholder="e.g. CSE" required>
                        </div>
                        <div class="form-group">
                            <label>Assign HOD</label>
                            <select name="hod_id" class="form-control">
                                <option value="">Select Faculty Member</option>
                                <?php foreach ($faculty_list as $f): ?>
                                    <option value="<?php echo $f['id']; ?>" <?php echo (isset($dept['hod_id']) && $dept['hod_id'] == $f['id']) ? 'selected' : ''; ?>><?php echo $f['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Established Year</label>
                            <input type="number" name="established_year" class="form-control" value="<?php echo $dept['established_year'] ?? ''; ?>" placeholder="2005">
                        </div>
                        <div class="form-group">
                            <label>Intake Capacity (per year)</label>
                            <input type="number" name="intake_capacity" class="form-control" value="<?php echo $dept['intake_capacity'] ?? '0'; ?>">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label>Department Description</label>
                        <textarea name="description" class="form-control" rows="5" placeholder="Introduction and details about the department..."><?php echo $dept['description'] ?? ''; ?></textarea>
                    </div>

                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label>Lab & Infrastructure Details</label>
                        <textarea name="lab_details" class="form-control" rows="5" placeholder="Details about labs, workshops, and equipment..."><?php echo $dept['lab_details'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>

            <div class="right-column">
                <div class="card">
                    <div class="form-group">
                        <label>Department Image/Banner</label>
                        <div style="border: 2px dashed var(--border); padding: 1.5rem; border-radius: 12px; text-align: center;">
                            <?php if (isset($dept['department_image']) && $dept['department_image']): ?>
                                <img src="../<?php echo $dept['department_image']; ?>" style="width: 100%; border-radius: 8px; margin-bottom: 1rem;">
                            <?php endif; ?>
                            <input type="file" name="department_image" class="form-control" style="font-size: 0.75rem;">
                            <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 8px;">Upload high-res JPG or PNG (16:9 ratio recommended)</p>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label>Department Accreditation</label>
                        <input type="text" name="accreditation" class="form-control" value="<?php echo $dept['accreditation'] ?? ''; ?>" placeholder="e.g. NBA Accredited">
                    </div>

                    <div class="form-group" style="margin-top: 1.5rem;">
                        <label>Display Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="<?php echo $dept['sort_order'] ?? '0'; ?>">
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">
                            <i data-lucide="save"></i> Save Department
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
