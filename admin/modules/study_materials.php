<?php
$page_title = 'Study Materials';
include '../includes/header.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Material Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_material'])) {
    $title = $_POST['title'];
    $course_id = $_POST['course_id'];
    $subject_id = $_POST['subject_id'];
    $desc = $_POST['description'];
    
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $filename = 'study_' . time() . '.' . $ext;
        $target = '../assets/docs/' . $filename;
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            // Find faculty_id for current user
            $stmt = $pdo->prepare("SELECT id FROM faculty WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $faculty = $stmt->fetch();
            $faculty_id = $faculty['id'] ?? 1; // Default to 1 if admin uploads
            
            $pdo->prepare("INSERT INTO study_materials (faculty_id, course_id, subject_id, title, file_path, description) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$faculty_id, $course_id, $subject_id, $title, $filename, $desc]);
                
            header("Location: study_materials.php?success=Material uploaded successfully");
            exit();
        }
    }
}

$materials = $pdo->query("SELECT sm.*, c.name as course_name, s.name as subject_name, u.name as faculty_name 
                         FROM study_materials sm 
                         JOIN courses c ON sm.course_id = c.id 
                         JOIN subjects s ON sm.subject_id = s.id 
                         JOIN faculty f ON sm.faculty_id = f.id 
                         JOIN users u ON f.user_id = u.id 
                         ORDER BY sm.created_at DESC")->fetchAll();

$courses = $pdo->query("SELECT id, name FROM courses WHERE is_active = 1")->fetchAll();
$subjects = $pdo->query("SELECT id, name FROM subjects WHERE is_active = 1")->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Academic E-Resources</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Upload and manage study materials, syllabus copies, and lecture notes.</p>
        </div>
        <button class="btn btn-primary" onclick="document.getElementById('uploadModal').style.display='block'">
            <i data-lucide="file-up"></i> Upload Materials
        </button>
    </div>

    <?php if ($success): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div class="grid-layout" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
        <?php if (empty($materials)): ?>
            <p style="color: var(--text-muted); grid-column: span 3; text-align: center; padding: 4rem;">No study materials uploaded yet.</p>
        <?php endif; ?>
        <?php foreach ($materials as $m): ?>
        <div class="card" style="padding: 1.5rem; border-top: 4px solid var(--accent);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                <div style="width: 40px; height: 40px; background: #f1f5f9; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--accent);">
                    <i data-lucide="file-text"></i>
                </div>
                <span style="font-size: 0.65rem; background: rgba(37, 99, 235, 0.1); color: var(--accent); padding: 2px 8px; border-radius: 4px; font-weight: 700;"><?php echo $m['subject_name']; ?></span>
            </div>
            <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo $m['title']; ?></h3>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1.5rem;"><?php echo $m['description'] ?: 'No description provided.'; ?></p>
            
            <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 1rem; border-top: 1px solid var(--border);">
                <div style="font-size: 0.7rem; color: var(--text-muted);">
                    <p style="font-weight: 600; color: var(--text-main);">Prof. <?php echo $m['faculty_name']; ?></p>
                    <p><?php echo date('d M, Y', strtotime($m['created_at'])); ?></p>
                </div>
                <a href="../assets/docs/<?php echo $m['file_path']; ?>" download class="btn" style="background: var(--accent); color: white; padding: 4px 10px; font-size: 0.75rem; text-decoration: none; border-radius: 4px;">
                    <i data-lucide="download" size="14"></i> Get PDF
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:100; display:flex; align-items:center; justify-content:center;">
    <div class="card" style="width:500px; padding:2rem;">
        <h3 style="margin-bottom:1.5rem;">Upload Study Material</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Resource Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Unit 1: Data Structures Overview" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                <div class="form-group">
                    <label>Course</label>
                    <select name="course_id" class="form-control" required>
                        <?php foreach($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <select name="subject_id" class="form-control" required>
                        <?php foreach($subjects as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group" style="margin-top: 1rem;">
                <label>File (PDF/DOC/ZIP)</label>
                <input type="file" name="file" class="form-control" required>
            </div>
            <div class="form-group" style="margin-top: 1rem;">
                <label>Brief Description</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <div style="margin-top:2rem; display:flex; gap:10px;">
                <button type="submit" name="upload_material" class="btn btn-primary" style="flex:1;">Publish Resource</button>
                <button type="button" onclick="this.closest('#uploadModal').style.display='none'" class="btn" style="flex:1; background:#f1f5f9;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
