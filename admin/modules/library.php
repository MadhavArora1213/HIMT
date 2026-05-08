<?php
$page_title = 'Library Catalog';
include '../includes/header.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Search & Filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$query = "SELECT * FROM library_books WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (title LIKE ? OR author LIKE ? OR isbn LIKE ? OR accession_number LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($category) {
    $query .= " AND category = ?";
    $params[] = $category;
}

$query .= " ORDER BY title ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$books = $stmt->fetchAll();

$categories = $pdo->query("SELECT DISTINCT category FROM library_books")->fetchAll(PDO::FETCH_COLUMN);
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Library Management</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Manage book inventory, issue records, and cataloging.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="library_returns.php" class="btn btn-primary" style="background: white; color: var(--text-main); border: 1px solid var(--border);">
                <i data-lucide="book-check"></i> Return Book
            </a>
            <a href="library_edit.php" class="btn btn-primary">
                <i data-lucide="plus"></i> Add New Book
            </a>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="card" style="margin-bottom: 2rem; padding: 1.25rem;">
        <form action="library.php" method="GET" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: end;">
            <div class="form-group">
                <label style="font-size: 0.75rem; font-weight: 600; margin-bottom: 5px; display: block;">Search Catalog</label>
                <input type="text" name="search" class="form-control" placeholder="Title, Author, ISBN or Accession #..." value="<?php echo $search; ?>">
            </div>
            <div class="form-group">
                <label style="font-size: 0.75rem; font-weight: 600; margin-bottom: 5px; display: block;">Category</label>
                <select name="category" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo ($category == $cat) ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i data-lucide="search" size="18"></i> Filter</button>
        </form>
    </div>

    <?php if ($success): ?>
        <div id="success-alert" style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2);">
            <i data-lucide="check-circle" size="18" style="vertical-align: middle; margin-right: 8px;"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Accession #</th>
                        <th>Book Details</th>
                        <th>Category</th>
                        <th>Availability</th>
                        <th>Rack</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($books)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-muted);">No books found in the catalog.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($books as $b): 
                        $is_low = $b['available_copies'] <= 0;
                    ?>
                    <tr>
                        <td><span style="font-family: monospace; font-weight: 700; color: var(--accent);"><?php echo $b['accession_number']; ?></span></td>
                        <td>
                            <p style="font-weight: 700; font-size: 0.9375rem;"><?php echo $b['title']; ?></p>
                            <p style="font-size: 0.75rem; color: var(--text-muted);">By <?php echo $b['author']; ?> | ISBN: <?php echo $b['isbn'] ?: 'N/A'; ?></p>
                        </td>
                        <td><span style="background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;"><?php echo $b['category']; ?></span></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-weight: 700; color: <?php echo $is_low ? 'var(--danger)' : 'var(--success)'; ?>">
                                    <?php echo $b['available_copies']; ?> / <?php echo $b['total_copies']; ?>
                                </span>
                                <?php if($is_low): ?>
                                    <span style="background: rgba(239, 68, 68, 0.1); color: var(--danger); font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; font-weight: 700;">OUT OF STOCK</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><p style="font-size: 0.8125rem; font-weight: 600;"><?php echo $b['rack_number'] ?: 'TBA'; ?></p></td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <a href="library_issue.php?book_id=<?php echo $b['id']; ?>" class="btn btn-primary <?php echo $is_low ? 'disabled' : ''; ?>" style="padding: 4px 12px; font-size: 0.75rem; <?php echo $is_low ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
                                    <i data-lucide="book-up" size="14"></i> Issue
                                </a>
                                <a href="library_edit.php?id=<?php echo $b['id']; ?>" class="btn-icon" title="Edit Book">
                                    <i data-lucide="edit-3" size="16"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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
