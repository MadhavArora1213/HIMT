<?php
require_once '../includes/config.php';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }
    header("Location: settings.php?success=System configurations updated");
    exit();
}

// Fetch all settings
$settings_raw = $pdo->query("SELECT * FROM settings")->fetchAll();
$settings = [];
foreach($settings_raw as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}

$page_title = 'System Configuration';
include '../includes/header.php';

$success = $_GET['success'] ?? '';
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.5rem; font-weight: 700;">Global System Settings</h2>
        <p style="color: var(--text-muted); font-size: 0.875rem;">Configure core behaviors, security parameters, and communication nodes.</p>
    </div>

    <?php if ($success): ?>
        <div id="success-alert" style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2);">
            <i data-lucide="check-circle" size="18" style="vertical-align: middle; margin-right: 8px;"></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form action="settings.php" method="POST">
        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem;">
            <!-- Main Settings -->
            <div class="card" style="padding: 2rem;">
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 2rem; display: flex; align-items: center; gap: 10px;">
                    <i data-lucide="settings-2" class="text-accent"></i> General Configuration
                </h3>
                
                <div class="form-group">
                    <label>Maintenance Mode</label>
                    <select name="settings[maintenance_mode]" class="form-control">
                        <option value="0" <?php echo ($settings['maintenance_mode'] ?? '0') == '0' ? 'selected' : ''; ?>>Disabled (Site Live)</option>
                        <option value="1" <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'selected' : ''; ?>>Enabled (Show Maintenance Page)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-top: 1.5rem;">
                    <label>Primary Email for Notifications</label>
                    <input type="email" name="settings[admin_email]" class="form-control" value="<?php echo $settings['admin_email'] ?? ''; ?>">
                </div>

                <div class="form-group" style="margin-top: 1.5rem;">
                    <label>Academic Year (Current)</label>
                    <input type="text" name="settings[current_academic_year]" class="form-control" value="<?php echo $settings['current_academic_year'] ?? '2025-26'; ?>">
                </div>

                <div class="form-group" style="margin-top: 1.5rem;">
                    <label>System Timezone</label>
                    <select name="settings[timezone]" class="form-control">
                        <option value="Asia/Kolkata">Asia/Kolkata (IST)</option>
                        <option value="UTC">UTC</option>
                    </select>
                </div>
            </div>

            <!-- SEO & Meta -->
            <div class="card" style="padding: 2rem;">
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 2rem; display: flex; align-items: center; gap: 10px;">
                    <i data-lucide="search" class="text-accent"></i> SEO & Metadata
                </h3>
                
                <div class="form-group">
                    <label>Site Title Prefix</label>
                    <input type="text" name="settings[site_title]" class="form-control" value="<?php echo $settings['site_title'] ?? 'HIMT'; ?>">
                </div>

                <div class="form-group" style="margin-top: 1.5rem;">
                    <label>Meta Keywords</label>
                    <textarea name="settings[meta_keywords]" class="form-control" rows="3"><?php echo $settings['meta_keywords'] ?? ''; ?></textarea>
                </div>

                <div class="form-group" style="margin-top: 1.5rem;">
                    <label>Meta Description</label>
                    <textarea name="settings[meta_description]" class="form-control" rows="4"><?php echo $settings['meta_description'] ?? ''; ?></textarea>
                </div>
            </div>
        </div>

        <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="padding: 1rem 4rem;">
                <i data-lucide="save"></i> Deploy Settings
            </button>
        </div>
    </form>
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

<style>
    .text-accent { color: var(--accent); }
</style>
