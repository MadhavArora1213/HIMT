<?php
require_once '../includes/config.php';

// Handle User Deletion
if (isset($_GET['delete_id'])) {
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$_GET['delete_id']]);
    header("Location: users.php?success=User account removed");
    exit();
}

// Fetch Administrative Users
$roles_to_show = ['Super Admin', 'Admin', 'Staff', 'Developer'];
$placeholders = implode(',', array_fill(0, count($roles_to_show), '?'));
$stmt = $pdo->prepare("SELECT * FROM users WHERE role IN ($placeholders) ORDER BY role DESC, name ASC");
$stmt->execute($roles_to_show);
$users = $stmt->fetchAll();

$page_title = 'User & Roles Management';
include '../includes/header.php';

$success = $_GET['success'] ?? '';
?>

<div style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Administrative Users</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Manage access controls and staff roles for the portal.</p>
        </div>
        <a href="user_edit.php" class="btn btn-primary">
            <i data-lucide="user-plus"></i> Create New User
        </a>
    </div>

    <?php if ($success): ?>
        <div id="success-alert" style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2);">
            <i data-lucide="check-circle" size="18" style="vertical-align: middle; margin-right: 8px;"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="card" style="padding: 0; overflow: hidden;">
        <table class="table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid var(--border);">
                    <th style="padding: 1rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">User</th>
                    <th style="padding: 1rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Role</th>
                    <th style="padding: 1rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Last Login</th>
                    <th style="padding: 1rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Status</th>
                    <th style="padding: 1rem 1.5rem; text-align: right; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted);">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): 
                    $role_colors = [
                        'Super Admin' => ['bg' => 'rgba(239, 68, 68, 0.1)', 'text' => '#ef4444'],
                        'Admin' => ['bg' => 'rgba(37, 99, 235, 0.1)', 'text' => '#2563eb'],
                        'Developer' => ['bg' => 'rgba(139, 92, 246, 0.1)', 'text' => '#8b5cf6'],
                        'Staff' => ['bg' => 'rgba(107, 114, 128, 0.1)', 'text' => '#6b7280']
                    ];
                    $colors = $role_colors[$u['role']] ?? ['bg' => 'rgba(0,0,0,0.05)', 'text' => 'var(--text-muted)'];
                ?>
                <tr style="border-bottom: 1px solid var(--border); transition: background 0.2s;">
                    <td style="padding: 1.25rem 1.5rem;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 36px; height: 36px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--accent);">
                                <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 0.9rem;"><?php echo $u['name']; ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $u['email']; ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="padding: 1.25rem 1.5rem;">
                        <span style="background: <?php echo $colors['bg']; ?>; color: <?php echo $colors['text']; ?>; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700;">
                            <?php echo strtoupper($u['role']); ?>
                        </span>
                    </td>
                    <td style="padding: 1.25rem 1.5rem; font-size: 0.85rem; color: var(--text-muted);">
                        <?php echo !empty($u['last_login']) ? date('M d, Y H:i', strtotime($u['last_login'])) : 'Never'; ?>
                    </td>
                    <td style="padding: 1.25rem 1.5rem;">
                        <span style="display: flex; align-items: center; gap: 6px; font-size: 0.85rem; font-weight: 600; color: var(--success);">
                            <span style="width: 8px; height: 8px; background: var(--success); border-radius: 50%;"></span> Active
                        </span>
                    </td>
                    <td style="padding: 1.25rem 1.5rem; text-align: right;">
                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                            <a href="user_edit.php?id=<?php echo $u['id']; ?>" class="btn-icon" style="background: #f8fafc;"><i data-lucide="edit-3" size="16"></i></a>
                            <a href="?delete_id=<?php echo $u['id']; ?>" class="btn-icon" style="background: rgba(239, 68, 68, 0.05); color: var(--danger);" onclick="return confirm('Suspend this user account?')"><i data-lucide="trash-2" size="16"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const successAlert = document.getElementById('success-alert');
            if (successAlert) {
                successAlert.style.transition = 'opacity 0.5s';
                successAlert.style.opacity = '0';
                setTimeout(() => successAlert.remove(), 500);
            }
        }, 3000);
    });
</script>
