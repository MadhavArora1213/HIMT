<?php
require_once '../includes/config.php';

$book_id = $_GET['book_id'] ?? '';
$book = null;
if ($book_id) {
    $stmt = $pdo->prepare("SELECT * FROM library_books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();
}

if (!$book || $book['available_copies'] <= 0) {
    header("Location: library.php?error=Book not available for issue.");
    exit();
}

// Fetch users (Students and Faculty)
$users = $pdo->query("SELECT id, name, role FROM users WHERE role IN ('student', 'faculty') AND is_active = 1 ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_book'])) {
    $user_id = $_POST['user_id'];
    $issue_date = date('Y-m-d');
    $due_date = $_POST['due_date'];
    
    try {
        $pdo->beginTransaction();
        
        // 1. Create Issue Record
        $pdo->prepare("INSERT INTO book_issues (book_id, user_id, issue_date, due_date, status, issued_by) VALUES (?, ?, ?, ?, 'issued', ?)")
            ->execute([$book_id, $user_id, $issue_date, $due_date, $_SESSION['user_id']]);
            
        // 2. Reduce Available Copies
        $pdo->prepare("UPDATE library_books SET available_copies = available_copies - 1 WHERE id = ?")
            ->execute([$book_id]);
            
        $pdo->commit();
        header("Location: library.php?success=Book issued successfully to user.");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Issue Failed: " . $e->getMessage();
    }
}

$page_title = 'Issue Book';
include '../includes/header.php';
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="library.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Catalog
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;">Issue Book</h2>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div class="card">
            <h3 style="font-size: 1rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Book Details</h3>
            <p style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Title</p>
            <p style="font-size: 1.125rem; font-weight: 700; margin-bottom: 1rem;"><?php echo $book['title']; ?></p>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <p style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Author</p>
                    <p><?php echo $book['author']; ?></p>
                </div>
                <div>
                    <p style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Accession #</p>
                    <p style="font-family: monospace; font-weight: 700;"><?php echo $book['accession_number']; ?></p>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="font-size: 1rem; margin-bottom: 1.5rem;">Issuance Form</h3>
            <form action="library_issue.php?book_id=<?php echo $book_id; ?>" method="POST">
                <div class="form-group">
                    <label>Select Recipient (Student/Faculty)</label>
                    <select name="user_id" class="form-control" required>
                        <option value="">-- Search User --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>"><?php echo $u['name']; ?> (<?php echo ucfirst($u['role']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-top: 1.5rem;">
                    <label>Due Date</label>
                    <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>" required>
                    <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 5px;">Standard loan period is 14 days.</p>
                </div>
                <button type="submit" name="issue_book" class="btn btn-primary" style="width: 100%; margin-top: 2rem; padding: 1rem;">
                    <i data-lucide="book-up"></i> Confirm Issue
                </button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
