<?php
require_once '../includes/config.php';

// Handle Bulk Send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_bulk'])) {
    $target_role = $_POST['target_role'];
    $notify_type = $_POST['notify_type'];
    $subject = $_POST['subject'] ?? 'Notification from HIMT';
    $message = $_POST['message'];
    
    // Fetch recipients
    $sql = "SELECT id, name, email, phone FROM users WHERE role = ? AND is_active = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$target_role]);
    $recipients = $stmt->fetchAll();
    
    foreach ($recipients as $r) {
        $to = ($notify_type == 'Email') ? $r['email'] : $r['phone'];
        queue_notification($pdo, $r['id'], $notify_type, 'BulkManual', $to, $subject, $message);
    }
    
    log_action($pdo, 'Sent Bulk ' . $notify_type, 'Notifications', null, null, ['target' => $target_role, 'count' => count($recipients)]);
    header("Location: notifications.php?success=" . urlencode(count($recipients) . " notifications queued successfully."));
    exit();
}

// Fetch History
$history = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10")->fetchAll();

$page_title = 'Notifications Center';
include '../includes/header.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Notification Center</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Send bulk SMS/Email alerts and track delivery status.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <div style="background: rgba(37, 99, 235, 0.1); color: var(--accent); padding: 10px 20px; border-radius: 12px; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="mail"></i> <span style="font-weight: 700;">SMTP Active</span>
            </div>
            <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 10px 20px; border-radius: 12px; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="message-square"></i> <span style="font-weight: 700;">SMS Gateway Ready</span>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div id="success-alert" style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2);">
            <i data-lucide="check-circle" size="18" style="vertical-align: middle; margin-right: 8px;"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
        <div class="compose-column">
            <div class="card">
                <h3 style="font-size: 1rem; margin-bottom: 1.5rem;">Compose Message</h3>
                <form action="notifications.php" method="POST">
                    <div class="form-group">
                        <label>Target Audience</label>
                        <select name="target_role" class="form-control" required>
                            <option value="student">All Students</option>
                            <option value="faculty">All Faculty</option>
                            <option value="admin">Administrators</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label>Channel</label>
                        <select name="notify_type" class="form-control" required>
                            <option value="Email">Email Delivery</option>
                            <option value="SMS">SMS Gateway</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label>Subject (for Email)</label>
                        <input type="text" name="subject" class="form-control" placeholder="e.g. Important Academic Update">
                    </div>
                    <div class="form-group" style="margin-top: 1rem;">
                        <label>Message Content</label>
                        <textarea name="message" class="form-control" rows="6" placeholder="Type your message here..." required></textarea>
                        <p style="font-size: 0.7rem; color: var(--text-muted); margin-top: 5px;">SMS limit: 160 characters per unit.</p>
                    </div>
                    <button type="submit" name="send_bulk" class="btn btn-primary" style="width: 100%; margin-top: 2rem; padding: 1rem;">
                        <i data-lucide="send"></i> Dispatch Notifications
                    </button>
                </form>
            </div>
        </div>

        <div class="history-column">
            <div class="card">
                <h3 style="font-size: 1rem; margin-bottom: 1.5rem;">Delivery History</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Recipient</th>
                                <th>Type</th>
                                <th>Event</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($history)): ?>
                                <tr><td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">No history available.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($history as $h): ?>
                            <tr>
                                <td>
                                    <p style="font-size: 0.8125rem; font-weight: 600;"><?php echo $h['recipient']; ?></p>
                                </td>
                                <td>
                                    <span style="font-size: 0.75rem; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;"><?php echo $h['type']; ?></span>
                                </td>
                                <td><p style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $h['event_type']; ?></p></td>
                                <td>
                                    <?php if($h['status'] == 'Pending'): ?>
                                        <span style="color: #f59e0b; font-weight: 700; font-size: 0.7rem;">PENDING</span>
                                    <?php elseif($h['status'] == 'Sent'): ?>
                                        <span style="color: var(--success); font-weight: 700; font-size: 0.7rem;">DELIVERED</span>
                                    <?php else: ?>
                                        <span style="color: var(--danger); font-weight: 700; font-size: 0.7rem;">FAILED</span>
                                    <?php endif; ?>
                                </td>
                                <td><span style="font-size: 0.7rem; color: var(--text-muted);"><?php echo date('M d, H:i', strtotime($h['created_at'])); ?></span></td>
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
