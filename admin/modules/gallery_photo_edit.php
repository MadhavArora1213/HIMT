<?php
require_once '../includes/config.php';

$id = $_GET['id'] ?? null;
$photo = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM gallery WHERE id = ?");
    $stmt->execute([$id]);
    $photo = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cat_id = $_POST['category_id'];
    $caption = $_POST['caption'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    try {
        if ($id) {
            // SINGLE UPDATE
            $filename = $photo['image_path'];
            if (isset($_FILES['images']) && $_FILES['images']['error'][0] == 0) {
                $ext = strtolower(pathinfo($_FILES['images']['name'][0], PATHINFO_EXTENSION));
                $filename = 'gallery_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['images']['tmp_name'][0], '../assets/img/uploads/' . $filename);
            }
            $pdo->prepare("UPDATE gallery SET category_id = ?, image_path = ?, caption = ?, is_featured = ? WHERE id = ?")
                ->execute([$cat_id, $filename, $caption, $is_featured, $id]);
            $msg = "Photo updated successfully";
        } else {
            // MULTIPLE UPLOAD
            if (!isset($_FILES['images']) || $_FILES['images']['error'][0] != 0) {
                throw new Exception("Please select at least one photo to upload.");
            }

            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] == 0) {
                    $ext = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
                    $filename = 'gallery_' . time() . '_' . $key . '.' . $ext;
                    if (move_uploaded_file($tmp_name, '../assets/img/uploads/' . $filename)) {
                        $pdo->prepare("INSERT INTO gallery (category_id, image_path, caption, is_featured) VALUES (?, ?, ?, ?)")
                            ->execute([$cat_id, $filename, $caption, $is_featured]);
                    }
                }
            }
            $msg = "Photos uploaded to gallery successfully";
        }
        header("Location: gallery.php?success=" . urlencode($msg));
        exit();
    } catch (Exception $e) {
        $error = db_error_message($e);
    }
}

$page_title = $id ? 'Edit Gallery Photo' : 'Upload Photos';
include '../includes/header.php';

$categories = $pdo->query("SELECT * FROM gallery_categories ORDER BY name ASC")->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="gallery.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Gallery
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
                <label>Media Category</label>
                <select name="category_id" class="form-control" required>
                    <option value="">Select Category</option>
                    <?php foreach($categories as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo (isset($photo['category_id']) && $photo['category_id'] == $c['id']) ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Select Photos (JPG, PNG, WEBP) <?php echo $id ? '' : '— You can select multiple'; ?></label>
                <input type="file" name="images[]" class="form-control" accept="image/*" <?php echo $id ? '' : 'required multiple'; ?>>
                <?php if($id): ?>
                    <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 8px;">Leave blank to keep current photo.</p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Caption / Title <?php echo !$id ? '<span style="color:var(--text-muted)">(Applied to all selected photos)</span>' : ''; ?></label>
                <input type="text" name="caption" class="form-control" placeholder="e.g. Annual Sports Meet 2026" value="<?php echo $photo['caption'] ?? ''; ?>">
            </div>

            <div style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
                <input type="checkbox" name="is_featured" id="feat" style="width: 18px; height: 18px;" <?php echo ($photo['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                <label for="feat" style="margin: 0; cursor: pointer; font-weight: 600;">Feature on Institutional Homepage</label>
            </div>
        </div>

        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 1rem;">
            <a href="gallery.php" class="btn" style="background: var(--background); border: 1px solid var(--border);">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="upload-cloud"></i> <?php echo $id ? 'Update Media' : 'Publish to Gallery'; ?>
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
