<?php
require_once '../includes/config.php';

// Handle Category Delete
if (isset($_GET['delete_cat_id'])) {
    $cat_id = $_GET['delete_cat_id'];
    
    // Fetch all images to unlink files
    $stmt = $pdo->prepare("SELECT image_path FROM gallery WHERE category_id = ?");
    $stmt->execute([$cat_id]);
    $images = $stmt->fetchAll();
    foreach($images as $img) {
        if (file_exists('../assets/img/uploads/' . $img['image_path'])) {
            unlink('../assets/img/uploads/' . $img['image_path']);
        }
    }
    
    $pdo->prepare("DELETE FROM gallery_categories WHERE id = ?")->execute([$cat_id]);
    header("Location: gallery.php?success=Collection removed successfully");
    exit();
}

// Fetch Categories with cover image and count
$categories = $pdo->query("
    SELECT c.*, 
    (SELECT image_path FROM gallery WHERE category_id = c.id ORDER BY created_at DESC LIMIT 1) as cover_image,
    (SELECT COUNT(*) FROM gallery WHERE category_id = c.id) as total_items
    FROM gallery_categories c 
    ORDER BY c.id DESC
")->fetchAll();

$page_title = 'Gallery Management';
include '../includes/header.php';

$success = $_GET['success'] ?? '';
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Media Gallery</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Manage event collections, campus life albums, and featured media.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="gallery_category_edit.php" class="btn btn-primary" style="display: flex; align-items: center; gap: 5px; text-decoration: none;">
                <i data-lucide="plus-square" size="18"></i> Create New Collection
            </a>
        </div>
    </div>

    <?php if ($success): ?>
        <div id="success-alert" style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2);">
            <i data-lucide="check-circle" size="18" style="vertical-align: middle; margin-right: 8px;"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 2rem;">
        <?php if (empty($categories)): ?>
            <p style="color: var(--text-muted); grid-column: span 3; text-align: center; padding: 4rem;">No media collections created yet.</p>
        <?php endif; ?>
        <?php foreach ($categories as $c): ?>
        <div class="card" style="overflow: hidden; border-radius: 15px; transition: transform 0.2s;">
            <div style="position: relative; height: 200px; background: #f8fafc;">
                <?php if($c['cover_image']): ?>
                    <img src="../assets/img/uploads/<?php echo $c['cover_image']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <div style="height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-muted);">
                        <i data-lucide="image" size="48" style="opacity: 0.2;"></i>
                        <p style="font-size: 0.75rem; margin-top: 10px;">Empty Collection</p>
                    </div>
                <?php endif; ?>
                <div style="position: absolute; bottom: 15px; left: 15px; background: rgba(0,0,0,0.6); color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700;">
                    <?php echo $c['total_items']; ?> Photos
                </div>
            </div>
            
            <div style="padding: 1.5rem;">
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-main);"><?php echo $c['name']; ?></h3>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1.5rem;">Created on <?php echo date('M d, Y', time()); // Simplified ?></p>
                
                <div style="display: flex; gap: 10px; border-top: 1px solid var(--border); padding-top: 1.25rem;">
                    <a href="gallery_category_edit.php?id=<?php echo $c['id']; ?>" class="btn" style="flex: 2; background: var(--accent); color: white; font-size: 0.8rem; text-decoration: none; text-align: center; font-weight: 600;">Manage Gallery</a>
                    <a href="?delete_cat_id=<?php echo $c['id']; ?>" class="btn" style="flex: 1; background: rgba(239, 68, 68, 0.1); color: var(--danger); font-size: 0.8rem; text-decoration: none; text-align: center;" onclick="return confirm('Delete this entire collection and all its photos?')"><i data-lucide="trash-2" size="16"></i></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide alerts after 3 seconds
        setTimeout(function() {
            const successAlert = document.getElementById('success-alert');
            if (successAlert) {
                successAlert.style.transition = 'opacity 0.5s';
                successAlert.style.opacity = '0';
                setTimeout(() => successAlert.remove(), 500);
            }
            
            const url = new URL(window.location);
            url.searchParams.delete('success');
            window.history.replaceState({}, document.title, url);
        }, 3000);
    });
</script>
