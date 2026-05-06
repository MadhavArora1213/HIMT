<?php
$page_title = 'Database Backup';
include '../includes/header.php';

if (!has_permission('super_admin')) {
    die("Access Denied.");
}

$success = $_GET['success'] ?? '';
$backup_dir = '../../backups/';

if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// Handle Backup Trigger
if (isset($_POST['trigger_backup'])) {
    $filename = 'himt_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backup_dir . $filename;
    
    // Simple PHP-based backup (since we might not have mysqldump access)
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $output = "-- HIMT College Backup\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        $row = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $output .= "\n\n" . $row['Create Table'] . ";\n\n";
        
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $output .= "INSERT INTO `$table` VALUES (";
            $values = array_map(function($v) use ($pdo) {
                return $v === null ? 'NULL' : $pdo->quote($v);
            }, array_values($row));
            $output .= implode(', ', $values) . ");\n";
        }
    }
    
    file_put_contents($filepath, $output);
    log_action($pdo, 'Triggered DB Backup', 'Security', null, null, ['file' => $filename]);
    header("Location: backup.php?success=Backup created successfully: $filename");
    exit();
}

$backups = array_diff(scandir($backup_dir), array('.', '..'));
rsort($backups);
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">System Backups</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Manage database restoration points and automated snapshots.</p>
        </div>
        <form method="POST">
            <button type="submit" name="trigger_backup" class="btn btn-primary">
                <i data-lucide="database-backup"></i> Run One-Click Backup
            </button>
        </form>
    </div>

    <?php if ($success): ?>
        <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3 style="font-size: 1rem; margin-bottom: 1.5rem; padding: 1.5rem; border-bottom: 1px solid var(--border);">Available Restore Points</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Backup Filename</th>
                        <th>Created Date</th>
                        <th>Size</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($backups)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-muted);">No backups found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($backups as $file): 
                        $path = $backup_dir . $file;
                        $size = round(filesize($path) / 1024, 2) . ' KB';
                    ?>
                    <tr>
                        <td><span style="font-family: monospace; font-weight: 700; color: var(--accent);"><?php echo $file; ?></span></td>
                        <td><?php echo date('M d, Y H:i:s', filemtime($path)); ?></td>
                        <td><?php echo $size; ?></td>
                        <td>
                            <div style="display: flex; gap: 10px;">
                                <a href="<?php echo $path; ?>" download class="btn-icon" title="Download"><i data-lucide="download" size="18"></i></a>
                                <a href="#" class="text-danger" title="Delete"><i data-lucide="trash-2" size="18"></i></a>
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
