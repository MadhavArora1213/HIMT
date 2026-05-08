<?php
require_once '../includes/config.php';

$id = $_GET['id'] ?? null;
$notice = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM notices WHERE id = ?");
    $stmt->execute([$id]);
    $notice = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $type = $_POST['notice_type'];
    $dept_id = $_POST['department_id'] ?: null;
    $course_id = $_POST['course_id'] ?: null;
    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
    $expires_at = $_POST['expires_at'] ?: null;

    try {
        if ($id) {
            $pdo->prepare("UPDATE notices SET title = ?, content = ?, notice_type = ?, department_id = ?, course_id = ?, is_pinned = ?, expires_at = ? WHERE id = ?")
                ->execute([$title, $content, $type, $dept_id, $course_id, $is_pinned, $expires_at, $id]);
            $msg = "Notice updated successfully";
        } else {
            $pdo->prepare("INSERT INTO notices (title, content, notice_type, department_id, course_id, is_pinned, posted_by, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$title, $content, $type, $dept_id, $course_id, $is_pinned, $_SESSION['user_id'], $expires_at]);
            $msg = "Notice posted successfully";
        }
        log_action($pdo, ($id ? 'Updated' : 'Posted') . ' Notice', 'Notices', $id);
        header("Location: notices.php?success=" . urlencode($msg));
        exit();
    } catch (Exception $e) {
        $error = db_error_message($e);
    }
}

$page_title = $id ? 'Edit Notice' : 'Post New Notice';
include '../includes/header.php';

$depts = $pdo->query("SELECT id, name FROM departments WHERE is_active = 1")->fetchAll();
$courses = $pdo->query("SELECT id, name FROM courses WHERE is_active = 1")->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="notices.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Notice Board
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;"><?php echo $page_title; ?></h2>
    </div>

    <?php if (isset($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2);">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" class="card" style="padding: 2rem; max-width: 900px;">
        <div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
            <div class="form-group">
                <label>Notice Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Mid-Term Examination Schedule" value="<?php echo $notice['title'] ?? ''; ?>" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label>Notice Category</label>
                    <select name="notice_type" class="form-control" required>
                        <option value="General" <?php echo (isset($notice['notice_type']) && $notice['notice_type'] == 'General') ? 'selected' : ''; ?>>General</option>
                        <option value="Academic" <?php echo (isset($notice['notice_type']) && $notice['notice_type'] == 'Academic') ? 'selected' : ''; ?>>Academic</option>
                        <option value="Exam" <?php echo (isset($notice['notice_type']) && $notice['notice_type'] == 'Exam') ? 'selected' : ''; ?>>Examination</option>
                        <option value="Urgent" <?php echo (isset($notice['notice_type']) && $notice['notice_type'] == 'Urgent') ? 'selected' : ''; ?>>Urgent Alert</option>
                        <option value="Holiday" <?php echo (isset($notice['notice_type']) && $notice['notice_type'] == 'Holiday') ? 'selected' : ''; ?>>Holiday</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Target Department (Optional)</label>
                    <select name="department_id" class="form-control">
                        <option value="">All Departments</option>
                        <?php foreach($depts as $d): ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo (isset($notice['department_id']) && $notice['department_id'] == $d['id']) ? 'selected' : ''; ?>><?php echo $d['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Target Course (Optional)</label>
                    <select name="course_id" class="form-control">
                        <option value="">All Courses</option>
                        <?php foreach($courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo (isset($notice['course_id']) && $notice['course_id'] == $c['id']) ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Notice Content</label>
                <textarea name="content" class="form-control" rows="8" placeholder="Type your detailed announcement here..." required><?php echo $notice['content'] ?? ''; ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: center;">
                <div class="form-group">
                    <label>Expiry Date (Optional)</label>
                    <input type="date" name="expires_at" class="form-control" value="<?php echo $notice['expires_at'] ?? ''; ?>">
                </div>
                <div style="display: flex; align-items: center; gap: 10px; margin-top: 20px;">
                    <input type="checkbox" name="is_pinned" id="is_pinned" style="width: 18px; height: 18px;" <?php echo (isset($notice['is_pinned']) && $notice['is_pinned']) ? 'checked' : ''; ?>>
                    <label for="is_pinned" style="margin: 0; cursor: pointer; font-weight: 600;">Pin to top of Notice Board</label>
                </div>
            </div>
        </div>

        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 1rem;">
            <a href="notices.php" class="btn" style="background: var(--background); border: 1px solid var(--border);">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="send"></i> <?php echo $id ? 'Update Notice' : 'Publish Notice'; ?>
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
