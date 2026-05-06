<?php
$page_title = 'System Audit Logs';
include '../includes/header.php';

// Check for Super Admin
if (!has_permission('super_admin')) {
    die("<div style='padding: 2rem;'>Access Denied. Super Admin privileges required.</div>");
}

$search = $_GET['search'] ?? '';
$module = $_GET['module'] ?? '';

$query = "SELECT al.*, u.name as user_name 
          FROM audit_logs al 
          LEFT JOIN users u ON al.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (al.action LIKE ? OR al.ip_address LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
if ($module) {
    $query .= " AND al.module = ?";
    $params[] = $module;
}

$query .= " ORDER BY al.created_at DESC LIMIT 100";
$logs = $pdo->prepare($query);
$logs->execute($params);
$log_entries = $logs->fetchAll();

$modules = $pdo->query("SELECT DISTINCT module FROM audit_logs")->fetchAll(PDO::FETCH_COLUMN);
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Security Audit Trail</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Monitor system-wide administrative actions and security events.</p>
        </div>
        <button class="btn btn-primary" style="background: white; color: var(--text-main); border: 1px solid var(--border);">
            <i data-lucide="download"></i> Export Logs
        </button>
    </div>

    <!-- Filters -->
    <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
        <form action="audit_logs.php" method="GET" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 1rem; align-items: end;">
            <div class="form-group">
                <label style="font-size: 0.75rem; font-weight: 600; margin-bottom: 5px; display: block;">Search Logs</label>
                <input type="text" name="search" class="form-control" placeholder="Action or IP Address..." value="<?php echo $search; ?>">
            </div>
            <div class="form-group">
                <label style="font-size: 0.75rem; font-weight: 600; margin-bottom: 5px; display: block;">Module</label>
                <select name="module" class="form-control">
                    <option value="">All Modules</option>
                    <?php foreach ($modules as $mod): ?>
                        <option value="<?php echo $mod; ?>" <?php echo ($module == $mod) ? 'selected' : ''; ?>><?php echo $mod; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i data-lucide="search" size="16"></i> Filter</button>
        </form>
    </div>

    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>IP Address</th>
                        <th>Payload</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($log_entries)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">No activity logs found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($log_entries as $l): ?>
                    <tr>
                        <td><span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo date('M d, H:i:s', strtotime($l['created_at'])); ?></span></td>
                        <td>
                            <p style="font-weight: 700; font-size: 0.8125rem;"><?php echo $l['user_name'] ?? 'System'; ?></p>
                        </td>
                        <td><span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem;"><?php echo $l['module']; ?></span></td>
                        <td><p style="font-size: 0.8125rem; font-weight: 600;"><?php echo $l['action']; ?></p></td>
                        <td><span style="font-family: monospace; font-size: 0.75rem;"><?php echo $l['ip_address']; ?></span></td>
                        <td>
                            <?php if ($l['new_values']): ?>
                                <button onclick='alert(<?php echo json_encode($l['new_values']); ?>)' class="btn" style="padding: 2px 8px; font-size: 0.65rem; background: var(--accent); color: white;">View Data</button>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-size: 0.65rem;">None</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
