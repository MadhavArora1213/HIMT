<?php
require_once '../includes/config.php';

$id = $_GET['id'] ?? null;
$category = null;
$gallery = [];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM gallery_categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();
    
    // Fetch all photos in this category
    $gallery = $pdo->prepare("SELECT * FROM gallery WHERE category_id = ? ORDER BY created_at DESC");
    $gallery->execute([$id]);
    $gallery = $gallery->fetchAll();
}

// Handle Photo Delete from within Category
if (isset($_GET['delete_gallery_id'])) {
    $img_id = $_GET['delete_gallery_id'];
    $stmt = $pdo->prepare("SELECT image_path FROM gallery WHERE id = ?");
    $stmt->execute([$img_id]);
    $img = $stmt->fetch();
    if ($img && file_exists('../assets/img/uploads/' . $img['image_path'])) {
        unlink('../assets/img/uploads/' . $img['image_path']);
    }
    $pdo->prepare("DELETE FROM gallery WHERE id = ?")->execute([$img_id]);
    header("Location: gallery_category_edit.php?id=$id&success=Media removed");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    
    try {
        if ($id) {
            $pdo->prepare("UPDATE gallery_categories SET name = ? WHERE id = ?")->execute([$name, $id]);
            $category_id = $id;
            $msg = "Category updated successfully";
        } else {
            $pdo->prepare("INSERT INTO gallery_categories (name) VALUES (?)")->execute([$name]);
            $category_id = $pdo->lastInsertId();
            $msg = "Category created successfully";
        }

        // Handle Multiple Photo Uploads
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] == 0) {
                    $ext = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
                    $filename = 'gal_' . time() . '_' . $key . '.' . $ext;
                    if (move_uploaded_file($tmp_name, '../assets/img/uploads/' . $filename)) {
                        $pdo->prepare("INSERT INTO gallery (category_id, image_path, caption) VALUES (?, ?, ?)")
                            ->execute([$category_id, $filename, $name]); // Default caption as category name
                    }
                }
            }
        }

        header("Location: gallery.php?success=" . urlencode($msg));
        exit();
    } catch (Exception $e) {
        $error = db_error_message($e);
    }
}

$page_title = $id ? 'Manage Collection' : 'New Gallery Category';
include '../includes/header.php';
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

    <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 2rem; align-items: start;">
        <!-- Category Details -->
        <form action="" method="POST" enctype="multipart/form-data" class="card" style="padding: 2rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem;">Collection Details</h3>
            <div class="form-group">
                <label>Category Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Graduation Ceremony 2026" value="<?php echo $category['name'] ?? ''; ?>" required>
            </div>

            <div class="form-group" style="margin-top: 1.5rem;">
                <label>Add New Photos (Images only, Multiple selection allowed)</label>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 5px;">Upload one or more photos to this collection.</p>
            </div>

            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i data-lucide="save"></i> <?php echo $id ? 'Update Collection' : 'Create & Upload'; ?>
                </button>
            </div>
        </form>

        <!-- Media Gallery -->
        <?php if($id): ?>
        <div class="card" style="padding: 2rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem;">Collection Media (<?php echo count($gallery); ?>)</h3>
            <?php if(empty($gallery)): ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No photos in this collection.</p>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem;">
                    <?php foreach($gallery as $img): ?>
                        <div style="position: relative;">
                            <img src="../assets/img/uploads/<?php echo $img['image_path']; ?>" style="width: 100%; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border);">
                            <a href="?id=<?php echo $id; ?>&delete_gallery_id=<?php echo $img['id']; ?>" 
                               style="position: absolute; top: -5px; right: -5px; background: var(--danger); color: white; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                               onclick="return confirm('Delete this photo?')">
                               ×
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
