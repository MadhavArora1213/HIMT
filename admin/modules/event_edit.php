<?php
$page_title = isset($_GET['id']) ? 'Edit Event' : 'Schedule New Event';
include '../includes/header.php';

$id = $_GET['id'] ?? '';
$event = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch();
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
        $filename = 'event_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['banner']['tmp_name'], '../assets/img/uploads/' . $filename)) {
            $banner_image = $filename;
        }
    }
    
    if ($id) {
        $sql = "UPDATE events SET title=?, description=?, event_date=?, location=?, event_type=?, banner_image=?, is_featured=? WHERE id=?";
        $pdo->prepare($sql)->execute([$title, $description, $event_date, $location, $event_type, $banner_image, $is_featured, $id]);
        $msg = "Event updated successfully";
    } else {
        $sql = "INSERT INTO events (title, description, event_date, location, event_type, banner_image, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$title, $description, $event_date, $location, $event_type, $banner_image, $is_featured]);
        $msg = "Event scheduled successfully";
    }
    
    log_action($pdo, ($id ? 'Updated' : 'Added') . ' Event', 'Events');
    header("Location: events.php?success=" . urlencode($msg));
    exit();
}
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="events.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Events
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;"><?php echo $page_title; ?></h2>
    </div>

    <form action="event_edit.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data" class="card" style="max-width: 800px; padding: 2rem;">
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
            <textarea name="description" class="form-control" rows="8" required><?php echo $event['description'] ?? ''; ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem; align-items: center;">
            <div class="form-group">
                <label>Banner Image</label>
                <input type="file" name="banner" class="form-control">
                <?php if($event['banner_image'] ?? ''): ?>
                    <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 5px;">Current: <?php echo $event['banner_image']; ?></p>
                <?php endif; ?>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" name="is_featured" id="feat" <?php echo ($event['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                <label for="feat" style="margin:0;">Feature on Homepage</label>
            </div>
        </div>

        <div style="margin-top: 3rem; display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">Save Event Details</button>
            <a href="events.php" class="btn" style="background: #f1f5f9; padding: 0.75rem 2rem; text-decoration: none; color: var(--text-main);">Cancel</a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
