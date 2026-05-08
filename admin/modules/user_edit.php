<?php
require_once '../includes/config.php';

$id = $_GET['id'] ?? null;
$user_data = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user_data = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = $_POST['password'];
    $permissions = isset($_POST['permissions']) ? json_encode($_POST['permissions']) : null;

    try {
        if ($id) {
            if (!empty($password)) {
                $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, password = ?, permissions = ? WHERE id = ?")
                    ->execute([$name, $email, $role, $hashed_pw, $permissions, $id]);
            } else {
                $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, permissions = ? WHERE id = ?")
                    ->execute([$name, $email, $role, $permissions, $id]);
            }
            $msg = "User updated successfully";
        } else {
            $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (name, email, role, password, permissions) VALUES (?, ?, ?, ?, ?)")
                ->execute([$name, $email, $role, $hashed_pw, $permissions]);
            $msg = "User account created successfully";
        }
        header("Location: users.php?success=" . urlencode($msg));
        exit();
    } catch (Exception $e) {
        $error = db_error_message($e);
    }
}

$user_perms = ($user_data && isset($user_data['permissions'])) ? json_decode($user_data['permissions'], true) : [];
if (!is_array($user_perms)) $user_perms = [];

$page_title = $id ? 'Edit User' : 'New User Account';
include '../includes/header.php';
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2rem;">
        <a href="users.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
            <i data-lucide="arrow-left" size="16"></i> Back to Users
        </a>
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;"><?php echo $page_title; ?></h2>
    </div>

    <?php if (isset($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2);">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" class="card" style="padding: 2rem; max-width: 600px;">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo $user_data['name'] ?? ''; ?>" required>
        </div>

        <div class="form-group" style="margin-top: 1.5rem;">
            <label>Email Address</label>
            <input type="email" name="email" class="form-control" value="<?php echo $user_data['email'] ?? ''; ?>" required>
        </div>

        <div class="form-group" style="margin-top: 1.5rem;">
            <label>Access Role</label>
            <select name="role" class="form-control" required>
                <option value="Staff" <?php echo ($user_data['role'] ?? '') == 'Staff' ? 'selected' : ''; ?>>Staff (Limited Access)</option>
                <option value="Developer" <?php echo ($user_data['role'] ?? '') == 'Developer' ? 'selected' : ''; ?>>Developer (Custom Access)</option>
                <option value="Admin" <?php echo ($user_data['role'] ?? '') == 'Admin' ? 'selected' : ''; ?>>Administrator (Full Control)</option>
                <option value="Super Admin" <?php echo ($user_data['role'] ?? '') == 'Super Admin' ? 'selected' : ''; ?>>Super Administrator</option>
            </select>
        </div>

        <div class="form-group" style="margin-top: 1.5rem;">
            <label><?php echo $id ? 'New Password (Leave blank to keep current)' : 'Password'; ?></label>
            <input type="password" name="password" class="form-control" <?php echo $id ? '' : 'required'; ?>>
        </div>

        <div id="permissions-section" style="margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1.5rem; <?php echo ($user_data['role'] ?? '') == 'Super Admin' ? 'display:none;' : ''; ?>">
            <label style="font-weight: 700; display: block; margin-bottom: 1rem;">Module Permissions</label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <?php foreach (MODULE_PERMISSIONS as $key => $label): ?>
                    <label style="display: flex; align-items: center; gap: 10px; font-weight: 500; font-size: 0.85rem; cursor: pointer;">
                        <input type="checkbox" name="permissions[]" value="<?php echo $key; ?>" <?php echo in_array($key, $user_perms) ? 'checked' : ''; ?>>
                        <?php echo $label; ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 1rem;">Super Admins automatically have access to all modules.</p>
        </div>

        <script>
            document.querySelector('select[name="role"]').addEventListener('change', function() {
                const section = document.getElementById('permissions-section');
                if (this.value === 'Super Admin') {
                    section.style.display = 'none';
                } else {
                    section.style.display = 'block';
                }
            });
        </script>

        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 1rem;">
            <a href="users.php" class="btn" style="background: var(--background); border: 1px solid var(--border);">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i> <?php echo $id ? 'Update Account' : 'Create Account'; ?>
            </button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
