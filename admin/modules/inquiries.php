<?php
$page_title = 'Inquiries & Grievances';
include '../includes/header.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Handle Status Updates
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    if ($_GET['action'] == 'close_inquiry') {
        $pdo->prepare("UPDATE inquiries SET status = 'Closed' WHERE id = ?")->execute([$id]);
        header("Location: inquiries.php?success=Inquiry closed"); exit();
    }
    if ($_GET['action'] == 'resolve_grievance') {
        $pdo->prepare("UPDATE grievances SET status = 'Resolved' WHERE id = ?")->execute([$id]);
        header("Location: inquiries.php?success=Grievance resolved"); exit();
    }
}

$inquiries = $pdo->query("SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 50")->fetchAll();
$grievances = $pdo->query("SELECT * FROM grievances ORDER BY created_at DESC LIMIT 50")->fetchAll();
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700;">Communication Hub</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem;">Monitor public inquiries and official grievances from students, faculty, and parents.</p>
    </div>

    <?php if ($success): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr; gap: 2.5rem;">
        <!-- Inquiries Table -->
        <div class="card">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 1.125rem; font-weight: 700;">Public Inquiries</h3>
                <span style="background: #f1f5f9; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;"><?php echo count($inquiries); ?> Total</span>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Sender</th>
                            <th>Subject & Message</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($inquiries)): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">No inquiries yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($inquiries as $inq): ?>
                        <tr>
                            <td>
                                <p style="font-weight: 700;"><?php echo $inq['name']; ?></p>
                                <p style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $inq['email']; ?> | <?php echo $inq['phone']; ?></p>
                            </td>
                            <td>
                                <p style="font-weight: 600; font-size: 0.875rem;"><?php echo $inq['subject']; ?></p>
                                <p style="font-size: 0.8125rem; color: var(--text-muted);"><?php echo substr($inq['message'], 0, 80); ?>...</p>
                            </td>
                            <td>
                                <span style="font-size: 0.7rem; font-weight: 700; color: <?php echo $inq['status'] == 'Pending' ? '#f59e0b' : 'var(--success)'; ?>">
                                    <?php echo strtoupper($inq['status']); ?>
                                </span>
                            </td>
                            <td><span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($inq['created_at'])); ?></span></td>
                            <td>
                                <a href="inquiries.php?action=close_inquiry&id=<?php echo $inq['id']; ?>" class="btn" style="padding: 4px 8px; font-size: 0.7rem; background: #f1f5f9; color: var(--text-main); text-decoration: none;">Close</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Grievances Table -->
        <div class="card">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: rgba(239, 68, 68, 0.03);">
                <h3 style="font-size: 1.125rem; font-weight: 700; color: #b91c1c;">Redressal / Grievances</h3>
                <span style="background: #fee2e2; color: #b91c1c; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;"><?php echo count($grievances); ?> Pending</span>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Complainant</th>
                            <th>Category & Issue</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($grievances)): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">No grievances reported.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($grievances as $g): ?>
                        <tr>
                            <td>
                                <p style="font-weight: 700;"><?php echo $g['name']; ?></p>
                                <span style="font-size: 0.65rem; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;"><?php echo strtoupper($g['user_type']); ?></span>
                            </td>
                            <td>
                                <p style="font-weight: 600; font-size: 0.875rem;"><?php echo $g['category']; ?></p>
                                <p style="font-size: 0.8125rem; color: var(--text-muted);"><?php echo substr($g['description'], 0, 80); ?>...</p>
                            </td>
                            <td>
                                <span style="font-size: 0.7rem; font-weight: 700; color: #b91c1c;">
                                    <?php echo strtoupper($g['status']); ?>
                                </span>
                            </td>
                            <td><span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($g['created_at'])); ?></span></td>
                            <td>
                                <a href="inquiries.php?action=resolve_grievance&id=<?php echo $g['id']; ?>" class="btn" style="padding: 4px 8px; font-size: 0.7rem; background: var(--success); color: white; text-decoration: none;">Resolve</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
