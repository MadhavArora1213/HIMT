<?php
$page_title = 'Edit Timetable';
include '../includes/header.php';

$id = $_GET['id'] ?? null;
$timetable = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM timetables WHERE id = ?");
    $stmt->execute([$id]);
    $timetable = $stmt->fetch();
}

$courses = $pdo->query("SELECT id, name FROM courses WHERE is_active = 1")->fetchAll();

// Handle Timetable Header Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_header'])) {
    $course_id = $_POST['course_id'];
    $semester = $_POST['semester'];
    $academic_year = $_POST['academic_year'];
    
    if ($id) {
        $pdo->prepare("UPDATE timetables SET course_id = ?, semester = ?, academic_year = ? WHERE id = ?")
            ->execute([$course_id, $semester, $academic_year, $id]);
    } else {
        $pdo->prepare("INSERT INTO timetables (course_id, semester, academic_year) VALUES (?, ?, ?)")
            ->execute([$course_id, $semester, $academic_year]);
        $id = $pdo->lastInsertId();
    }
    header("Location: timetable_edit.php?id=$id&success=Timetable details updated");
    exit();
}

// Handle Slot Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_slot'])) {
    $day = $_POST['day_of_week'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $subject_id = $_POST['subject_id'];
    $faculty_id = $_POST['faculty_id'];
    $room = $_POST['room_number'];
    
    // Conflict Detection (Simple)
    $stmt = $pdo->prepare("SELECT id FROM timetable_slots WHERE faculty_id = ? AND day_of_week = ? AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))");
    $stmt->execute([$faculty_id, $day, $start, $start, $end, $end]);
    if ($stmt->fetch()) {
        $error = "Conflict Detected: This faculty is already assigned to another class during this time.";
    } else {
        $pdo->prepare("INSERT INTO timetable_slots (timetable_id, subject_id, faculty_id, day_of_week, start_time, end_time, room_number) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$id, $subject_id, $faculty_id, $day, $start, $end, $room]);
        header("Location: timetable_edit.php?id=$id&success=Slot added");
        exit();
    }
}

// Fetch Slots
$slots = [];
if ($id) {
    $stmt = $pdo->prepare("SELECT ts.*, s.name as subject_name, u.name as faculty_name 
                          FROM timetable_slots ts 
                          JOIN subjects s ON ts.subject_id = s.id 
                          JOIN faculty f ON ts.faculty_id = f.id 
                          JOIN users u ON f.user_id = u.id 
                          WHERE ts.timetable_id = ? ORDER BY FIELD(ts.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), ts.start_time");
    $stmt->execute([$id]);
    $slots = $stmt->fetchAll();
}

// Dropdowns for slots
$subjects = $id ? $pdo->prepare("SELECT id, name FROM subjects WHERE course_id = ? AND semester = ?")->execute([$timetable['course_id'], $timetable['semester']]) ? $pdo->prepare("SELECT id, name FROM subjects WHERE course_id = ? AND semester = ?") : [] : [];
// Re-fetching subjects properly
if ($id) {
    $stmt = $pdo->prepare("SELECT id, name FROM subjects WHERE course_id = ? AND semester = ?");
    $stmt->execute([$timetable['course_id'], $timetable['semester']]);
    $subjects = $stmt->fetchAll();
}
$faculties = $pdo->query("SELECT f.id, u.name FROM faculty f JOIN users u ON f.user_id = u.id")->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="timetable.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Timetables
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;"><?php echo $id ? 'Edit' : 'Create'; ?> Timetable</h2>
    </div>

    <?php if (isset($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
        <div class="header-column">
            <div class="card" style="margin-bottom: 2rem;">
                <h3 style="font-size: 1rem; margin-bottom: 1.5rem;">Timetables Details</h3>
                <form action="timetable_edit.php<?php echo $id ? '?id='.$id : ''; ?>" method="POST">
                    <div class="form-group">
                        <label>Course</label>
                        <select name="course_id" class="form-control" required>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo (isset($timetable['course_id']) && $timetable['course_id'] == $c['id']) ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label>Semester</label>
                        <select name="semester" class="form-control" required>
                            <?php for($i=1; $i<=8; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo (isset($timetable['semester']) && $timetable['semester'] == $i) ? 'selected' : ''; ?>>Sem <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label>Academic Year</label>
                        <input type="text" name="academic_year" class="form-control" value="<?php echo $timetable['academic_year'] ?? '2026-27'; ?>" required>
                    </div>
                    <button type="submit" name="save_header" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">Update Base Details</button>
                </form>
            </div>

            <?php if ($id): ?>
            <div class="card">
                <h3 style="font-size: 1rem; margin-bottom: 1.5rem;">Add Time Slot</h3>
                <form action="timetable_edit.php?id=<?php echo $id; ?>" method="POST">
                    <div class="form-group">
                        <label>Day</label>
                        <select name="day_of_week" class="form-control" required>
                            <option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option><option>Saturday</option>
                        </select>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem;">
                        <div class="form-group">
                            <label>Start Time</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>End Time</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label>Subject</label>
                        <select name="subject_id" class="form-control" required>
                            <?php foreach ($subjects as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label>Faculty</label>
                        <select name="faculty_id" class="form-control" required>
                            <?php foreach ($faculties as $f): ?>
                                <option value="<?php echo $f['id']; ?>"><?php echo $f['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label>Room Number</label>
                        <input type="text" name="room_number" class="form-control" placeholder="Room 101">
                    </div>
                    <button type="submit" name="add_slot" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; background: var(--accent);">Add To Schedule</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <div class="slots-column">
            <div class="card">
                <h3 style="font-size: 1rem; margin-bottom: 1.5rem;">Current Schedule</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Day & Time</th>
                                <th>Subject</th>
                                <th>Faculty</th>
                                <th>Room</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($slots)): ?>
                                <tr><td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">No slots added yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($slots as $sl): ?>
                            <tr>
                                <td>
                                    <p style="font-weight: 700; font-size: 0.8125rem;"><?php echo $sl['day_of_week']; ?></p>
                                    <p style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('h:i A', strtotime($sl['start_time'])); ?> - <?php echo date('h:i A', strtotime($sl['end_date'])); ?></p>
                                </td>
                                <td><p style="font-size: 0.875rem; font-weight: 600;"><?php echo $sl['subject_name']; ?></p></td>
                                <td><p style="font-size: 0.8125rem;"><?php echo $sl['faculty_name']; ?></p></td>
                                <td><span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem;"><?php echo $sl['room_number']; ?></span></td>
                                <td>
                                    <a href="#" class="text-danger"><i data-lucide="trash-2" size="16"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
