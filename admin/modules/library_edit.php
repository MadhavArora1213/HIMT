<?php
require_once '../includes/config.php';

$id = $_GET['id'] ?? null;
$book = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM library_books WHERE id = ?");
    $stmt->execute([$id]);
    $book = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $isbn = $_POST['isbn'];
    $accession_number = $_POST['accession_number'];
    $category = $_POST['category'];
    $total_copies = $_POST['total_copies'];
    $rack_number = $_POST['rack_number'];
    
    // For new books, available_copies = total_copies
    // For edits, we need to adjust available_copies based on the change in total_copies
    $available_copies = $_POST['available_copies'] ?? $total_copies;

    try {
        if ($id) {
            $diff = $total_copies - $book['total_copies'];
            $new_available = $book['available_copies'] + $diff;
            
            $stmt = $pdo->prepare("UPDATE library_books SET title = ?, author = ?, isbn = ?, accession_number = ?, category = ?, total_copies = ?, available_copies = ?, rack_number = ? WHERE id = ?");
            $stmt->execute([$title, $author, $isbn, $accession_number, $category, $total_copies, $new_available, $rack_number, $id]);
            $msg = "Book updated successfully";
        } else {
            $stmt = $pdo->prepare("INSERT INTO library_books (title, author, isbn, accession_number, category, total_copies, available_copies, rack_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $author, $isbn, $accession_number, $category, $total_copies, $total_copies, $rack_number]);
            $msg = "Book added to catalog successfully";
        }
        header("Location: library.php?success=" . urlencode($msg));
        exit();
    } catch (Exception $e) {
        $error = db_error_message($e);
    }
}

$page_title = $id ? 'Edit Book' : 'Add New Book';
include '../includes/header.php';
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="library.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Catalog
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;"><?php echo $page_title; ?></h2>
    </div>

    <?php if (isset($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2);">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" class="card" style="padding: 2rem; max-width: 800px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div class="form-group" style="grid-column: span 2;">
                <label>Book Title</label>
                <input type="text" name="title" class="form-control" placeholder="Full title of the book" value="<?php echo $book['title'] ?? ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Author</label>
                <input type="text" name="author" class="form-control" placeholder="Author name" value="<?php echo $book['author'] ?? ''; ?>" required>
            </div>

            <div class="form-group">
                <label>ISBN</label>
                <input type="text" name="isbn" class="form-control" placeholder="10 or 13 digit ISBN" value="<?php echo $book['isbn'] ?? ''; ?>">
            </div>

            <div class="form-group">
                <label>Accession Number</label>
                <input type="text" name="accession_number" class="form-control" placeholder="Unique ID for library tracking" value="<?php echo $book['accession_number'] ?? ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Category</label>
                <input type="text" name="category" class="form-control" placeholder="e.g. Computer Science, Physics" value="<?php echo $book['category'] ?? ''; ?>" required>
            </div>

            <div class="form-group">
                <label>Total Copies</label>
                <input type="number" name="total_copies" class="form-control" min="1" value="<?php echo $book['total_copies'] ?? '1'; ?>" required>
            </div>

            <div class="form-group">
                <label>Rack Number / Location</label>
                <input type="text" name="rack_number" class="form-control" placeholder="e.g. A-12, Science Section" value="<?php echo $book['rack_number'] ?? ''; ?>">
            </div>
        </div>

        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 1rem;">
            <a href="library.php" class="btn" style="background: var(--background); border: 1px solid var(--border);">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i> <?php echo $id ? 'Update Book' : 'Save Book'; ?>
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
