<?php
require_once '../includes/config.php';

$id = $_GET['id'] ?? '';
$event = null;
$gallery = [];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch();
    
    $gallery = $pdo->prepare("SELECT * FROM event_gallery WHERE event_id = ?");
    $gallery->execute([$id]);
    $gallery = $gallery->fetchAll();
}

// Handle Gallery Image Delete
if (isset($_GET['delete_gallery_id'])) {
    $img_id = $_GET['delete_gallery_id'];
    $stmt = $pdo->prepare("SELECT image_path FROM event_gallery WHERE id = ?");
    $stmt->execute([$img_id]);
    $img = $stmt->fetch();
    if ($img && file_exists('../assets/img/uploads/' . $img['image_path'])) {
        unlink('../assets/img/uploads/' . $img['image_path']);
    }
    $pdo->prepare("DELETE FROM event_gallery WHERE id = ?")->execute([$img_id]);
    header("Location: event_edit.php?id=$id&success=Gallery image removed");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $event_date = $_POST['event_date'];
    $location = $_POST['location'];
    $event_type = $_POST['event_type'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    $banner_image = $event['banner_image'] ?? '';
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] == 0) {
        $ext = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);
        $filename = 'event_banner_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['banner']['tmp_name'], '../assets/img/uploads/' . $filename)) {
            $banner_image = $filename;
        }
    }
    
    if ($id) {
        $sql = "UPDATE events SET title=?, description=?, event_date=?, location=?, event_type=?, banner_image=?, is_featured=? WHERE id=?";
        $pdo->prepare($sql)->execute([$title, $description, $event_date, $location, $event_type, $banner_image, $is_featured, $id]);
        $event_id = $id;
        $msg = "Event updated successfully";
    } else {
        $sql = "INSERT INTO events (title, description, event_date, location, event_type, banner_image, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$title, $description, $event_date, $location, $event_type, $banner_image, $is_featured]);
        $event_id = $pdo->lastInsertId();
        $msg = "Event scheduled successfully";
    }

    // Handle Multiple Gallery Images
    if (isset($_FILES['gallery']) && !empty($_FILES['gallery']['name'][0])) {
        foreach ($_FILES['gallery']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['gallery']['error'][$key] == 0) {
                $ext = pathinfo($_FILES['gallery']['name'][$key], PATHINFO_EXTENSION);
                $filename = 'event_gal_' . time() . '_' . $key . '.' . $ext;
                if (move_uploaded_file($tmp_name, '../assets/img/uploads/' . $filename)) {
                    $pdo->prepare("INSERT INTO event_gallery (event_id, image_path) VALUES (?, ?)")
                        ->execute([$event_id, $filename]);
                }
            }
        }
    }
    
    log_action($pdo, ($id ? 'Updated' : 'Added') . ' Event', 'Events', $event_id);
    header("Location: events.php?success=" . urlencode($msg));
    exit();
}

$page_title = $id ? 'Edit Event' : 'Schedule New Event';
include '../includes/header.php';
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="events.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Events
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;"><?php echo $page_title; ?></h2>
    </div>

    <form action="event_edit.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data" class="card" style="max-width: 900px; padding: 2rem;">
        <div class="form-group">
            <label>Event Title</label>
            <input type="text" name="title" class="form-control" value="<?php echo $event['title'] ?? ''; ?>" required>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
            <div class="form-group">
                <label>Event Date</label>
                <input type="date" name="event_date" class="form-control" value="<?php echo $event['event_date'] ?? ''; ?>" required>
            </div>
            <div class="form-group">
                <label>Event Type</label>
                <select name="event_type" class="form-control" required>
                    <?php 
                    $types = ['Academic','Cultural','Sports','Seminar','Workshop','Other'];
                    foreach($types as $t): ?>
                        <option value="<?php echo $t; ?>" <?php echo (($event['event_type'] ?? '') == $t) ? 'selected' : ''; ?>><?php echo $t; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group" style="margin-top: 1.5rem;">
            <label>Location</label>
            <input type="text" name="location" class="form-control" value="<?php echo $event['location'] ?? 'College Auditorium'; ?>" required>
        </div>

        <div class="form-group" style="margin-top: 1.5rem;">
            <label>Detailed Description</label>
            <textarea name="description" class="form-control" rows="6" required><?php echo $event['description'] ?? ''; ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
            <div class="form-group">
                <label>Main Banner Image (Images only)</label>
                <input type="file" name="banner" class="form-control" accept="image/*">
                <?php if($event['banner_image'] ?? ''): ?>
                    <div style="margin-top: 10px;">
                        <img src="../assets/img/uploads/<?php echo $event['banner_image']; ?>" style="width: 120px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border);">
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Gallery Images (Multiple Allowed)</label>
                <input type="file" name="gallery[]" class="form-control" accept="image/*" multiple>
                <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 5px;">Upload multiple photos of the event.</p>
            </div>
        </div>

        <?php if(!empty($gallery)): ?>
        <div style="margin-top: 2rem;">
            <label style="display: block; margin-bottom: 1rem; font-weight: 600;">Existing Gallery</label>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 1rem;">
                <?php foreach($gallery as $img): ?>
                    <div style="position: relative; group;">
                        <img src="../assets/img/uploads/<?php echo $img['image_path']; ?>" style="width: 100%; height: 80px; object-fit: cover; border-radius: 8px;">
                        <a href="?id=<?php echo $id; ?>&delete_gallery_id=<?php echo $img['id']; ?>" 
                           style="position: absolute; top: -5px; right: -5px; background: var(--danger); color: white; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 10px;"
                           onclick="return confirm('Delete this image?')">
                           ×
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div style="display: flex; align-items: center; gap: 10px; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border);">
            <input type="checkbox" name="is_featured" id="feat" style="width: 18px; height: 18px;" <?php echo ($event['is_featured'] ?? 0) ? 'checked' : ''; ?>>
            <label for="feat" style="margin:0; font-weight: 600; cursor: pointer;">Feature on Institutional Homepage</label>
        </div>

        <div style="margin-top: 3rem; display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2.5rem;">
                <i data-lucide="check-circle"></i> Save Event Details
            </button>
            <a href="events.php" class="btn" style="background: #f1f5f9; padding: 0.75rem 2.5rem; text-decoration: none; color: var(--text-main);">Cancel</a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
