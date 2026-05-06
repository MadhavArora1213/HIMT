<?php
$page_title = 'Gallery Management';
include '../includes/header.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Category Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = $_POST['category_name'];
    $pdo->prepare("INSERT INTO gallery_categories (name) VALUES (?)")->execute([$name]);
    header("Location: gallery.php?success=Category added");
    exit();
}

// Handle Image Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
    $cat_id = $_POST['category_id'];
    $caption = $_POST['caption'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'gallery_' . time() . '.' . $ext;
        $target = '../assets/img/uploads/' . $filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $pdo->prepare("INSERT INTO gallery (category_id, image_path, caption, is_featured) VALUES (?, ?, ?, ?)")
                ->execute([$cat_id, $filename, $caption, $is_featured]);
            header("Location: gallery.php?success=Image uploaded to gallery");
            exit();
        }
    }
}

$categories = $pdo->query("SELECT * FROM gallery_categories")->fetchAll();
$gallery_items = $pdo->query("SELECT g.*, c.name as category_name FROM gallery g JOIN gallery_categories c ON g.category_id = c.id ORDER BY g.created_at DESC")->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Media Gallery</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Manage event photos, campus life, and featured homepage media.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn btn-primary" onclick="document.getElementById('catModal').style.display='block'" style="background: white; color: var(--text-main); border: 1px solid var(--border);">
                <i data-lucide="folder-plus"></i> New Category
            </button>
            <button class="btn btn-primary" onclick="document.getElementById('uploadModal').style.display='block'">
                <i data-lucide="image-plus"></i> Upload Photos
            </button>
        </div>
    </div>

    <?php if ($success): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem;">
        <?php foreach ($gallery_items as $item): ?>
        <div class="card" style="overflow: hidden; position: relative; border-radius: 15px;">
            <img src="../assets/img/uploads/<?php echo $item['image_path']; ?>" style="width: 100%; height: 200px; object-fit: cover;">
            <div style="padding: 1rem;">
                <span style="font-size: 0.65rem; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);"><?php echo $item['category_name']; ?></span>
                <p style="font-size: 0.875rem; font-weight: 600; margin-top: 5px; color: var(--text-main);"><?php echo $item['caption'] ?: 'Untitled Image'; ?></p>
                <?php if($item['is_featured']): ?>
                    <span style="color: var(--success); font-size: 0.7rem; font-weight: 700; display: flex; align-items: center; gap: 4px; margin-top: 5px;">
                        <i data-lucide="star" size="12"></i> Featured
                    </span>
                <?php endif; ?>
            </div>
            <div style="position: absolute; top: 10px; right: 10px; display: flex; gap: 5px;">
                <a href="#" class="btn-icon" style="background: rgba(255,255,255,0.9); border-radius: 50%; width: 32px; height: 32px;"><i data-lucide="trash-2" size="16" color="#ef4444"></i></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modals (Simple implementations) -->
<div id="catModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:100; display:flex; align-items:center; justify-content:center;">
    <div class="card" style="width:400px; padding:2rem;">
        <h3 style="margin-bottom:1.5rem;">Add Category</h3>
        <form method="POST">
            <input type="text" name="category_name" class="form-control" placeholder="e.g. Annual Fest 2026" required>
            <div style="margin-top:1.5rem; display:flex; gap:10px;">
                <button type="submit" name="add_category" class="btn btn-primary" style="flex:1;">Save</button>
                <button type="button" onclick="this.closest('#catModal').style.display='none'" class="btn" style="flex:1; background:#f1f5f9;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="uploadModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:100; display:flex; align-items:center; justify-content:center;">
    <div class="card" style="width:500px; padding:2rem;">
        <h3 style="margin-bottom:1.5rem;">Upload Photos</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Select Category</label>
                <select name="category_id" class="form-control" required>
                    <?php foreach($categories as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-top:1rem;">
                <label>Photo</label>
                <input type="file" name="image" class="form-control" required>
            </div>
            <div class="form-group" style="margin-top:1rem;">
                <label>Caption</label>
                <input type="text" name="caption" class="form-control">
            </div>
            <div style="margin-top:1rem; display:flex; align-items:center; gap:10px;">
                <input type="checkbox" name="is_featured" id="feat">
                <label for="feat" style="margin:0;">Feature on Homepage</label>
            </div>
            <div style="margin-top:1.5rem; display:flex; gap:10px;">
                <button type="submit" name="upload_image" class="btn btn-primary" style="flex:1;">Upload</button>
                <button type="button" onclick="this.closest('#uploadModal').style.display='none'" class="btn" style="flex:1; background:#f1f5f9;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Simple Modal Toggle
    document.querySelectorAll('[onclick*="display="]').forEach(btn => {
        const modalId = btn.getAttribute('onclick').match(/'([^']+)'/)[1];
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = 'none'; // Ensure hidden initially
    });
</script>

<?php include '../includes/footer.php'; ?>
