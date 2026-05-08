<?php
require_once '../includes/config.php';

// Handle Return Logic
if (isset($_GET['return_id'])) {
    $issue_id = $_GET['return_id'];
    
    try {
        $pdo->beginTransaction();
        
        // 1. Get Issue Details
        $stmt = $pdo->prepare("SELECT * FROM book_issues WHERE id = ? AND status = 'issued'");
        $stmt->execute([$issue_id]);
        $issue = $stmt->fetch();
        
        if ($issue) {
            // 2. Mark as Returned
            $pdo->prepare("UPDATE book_issues SET return_date = CURDATE(), status = 'returned' WHERE id = ?")
                ->execute([$issue_id]);
                
            // 3. Increase Available Copies
            $pdo->prepare("UPDATE library_books SET available_copies = available_copies + 1 WHERE id = ?")
                ->execute([$issue['book_id']]);
                
            $pdo->commit();
            header("Location: library_returns.php?success=Book returned successfully.");
            exit();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Return Failed: " . $e->getMessage();
    }
}

$page_title = 'Return Books';
include '../includes/header.php';

// Fetch Active Issues
$query = "
    SELECT bi.*, lb.title, lb.accession_number, u.name as user_name, u.role
    FROM book_issues bi
    JOIN library_books lb ON bi.book_id = lb.id
    JOIN users u ON bi.user_id = u.id
    WHERE bi.status = 'issued'
    ORDER BY bi.due_date ASC
";
$active_issues = $pdo->query($query)->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="library.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Catalog
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;">Process Book Returns</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem;">View all currently issued books and record returns.</p>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div id="success-alert" style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2);">
            <i data-lucide="check-circle" size="18" style="vertical-align: middle; margin-right: 8px;"></i> <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Recipient</th>
                        <th>Book Details</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($active_issues)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 4rem; color: var(--text-muted);">No books are currently issued.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($active_issues as $i): 
                        $is_overdue = strtotime($i['due_date']) < time();
                    ?>
                    <tr>
                        <td>
                            <p style="font-weight: 700;"><?php echo $i['user_name']; ?></p>
                            <p style="font-size: 0.75rem; color: var(--text-muted);"><?php echo ucfirst($i['role']); ?></p>
                        </td>
                        <td>
                            <p style="font-weight: 600; font-size: 0.875rem;"><?php echo $i['title']; ?></p>
                            <p style="font-size: 0.75rem; color: var(--text-muted);">Acc #: <?php echo $i['accession_number']; ?></p>
                        </td>
                        <td><?php echo date('d M, Y', strtotime($i['issue_date'])); ?></td>
                        <td>
                            <p style="color: <?php echo $is_overdue ? 'var(--danger)' : 'inherit'; ?>; font-weight: <?php echo $is_overdue ? '700' : 'normal'; ?>;">
                                <?php echo date('d M, Y', strtotime($i['due_date'])); ?>
                            </p>
                        </td>
                        <td>
                            <?php if($is_overdue): ?>
                                <span style="background: rgba(239, 68, 68, 0.1); color: var(--danger); font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; font-weight: 700;">OVERDUE</span>
                            <?php else: ?>
                                <span style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; font-weight: 700;">ISSUED</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?return_id=<?php echo $i['id']; ?>" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.75rem;" onclick="return confirm('Confirm book return?')">
                                <i data-lucide="book-check" size="14"></i> Record Return
                            </a>
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
        setTimeout(function() {
            const alert = document.getElementById('success-alert');
            if (alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
            const url = new URL(window.location);
            url.searchParams.delete('success');
            window.history.replaceState({}, document.title, url);
        }, 3000);
    });
</script>
