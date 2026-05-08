<?php
/**
 * HIMT Admin Panel - Configuration
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'himt_db');

// App Configuration
define('APP_NAME', 'HIMT Admin Panel');
define('BASE_URL', 'http://localhost/HIMT/admin/');
define('ASSETS_URL', 'http://localhost/HIMT/admin/assets/');

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    // Session Security Hardening
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
session_start();

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Data Encryption (AES-256)
define('ENCRYPTION_KEY', 'HIMT-SECURE-KEY-2026-v1'); // Should be in env in production

function encrypt_data($data) {
    $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', ENCRYPTION_KEY, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function decrypt_data($data) {
    if (!$data) return null;
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    return openssl_decrypt($encrypted_data, 'aes-256-cbc', ENCRYPTION_KEY, 0, $iv);
}
}

// Error Reporting (Development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

/**
 * Utility function to check if user is logged in
 */
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
}

/**
 * Define all modules for granular permissions
 */
define('MODULE_PERMISSIONS', [
    'dashboard' => 'Dashboard Access',
    'admissions' => 'Admissions Management',
    'departments' => 'Department Management',
    'courses' => 'Course Management',
    'subjects' => 'Subject Management',
    'students' => 'Student Management',
    'faculty' => 'Faculty Management',
    'attendance' => 'Attendance Tracking',
    'fees' => 'Fee Management',
    'exams' => 'Exams & Results',
    'library' => 'Library Management',
    'placements' => 'Placement Management',
    'study_material' => 'Study Materials',
    'notices' => 'Notice Board',
    'events' => 'Campus Events',
    'gallery' => 'Photo Gallery',
    'inquiries' => 'Inquiries Management',
    'institute' => 'Institute Profile',
    'users' => 'User & Roles Management',
    'settings' => 'System Settings'
]);

/**
 * Utility function to check if user has permission for a specific module
 */
function has_permission($permission) {
    if (!isset($_SESSION['role'])) return false;
    
    // Super Admin bypass - robust check
    $current_role = strtolower(trim($_SESSION['role']));
    if ($current_role === 'super admin' || $current_role === 'super_admin') return true;
    
    // Check granular permissions
    if (!isset($_SESSION['permissions'])) {
        // Fetch permissions if not in session
        global $pdo;
        $stmt = $pdo->prepare("SELECT permissions FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        $_SESSION['permissions'] = ($user && $user['permissions']) ? json_decode($user['permissions'], true) : [];
    }
    
    $user_permissions = $_SESSION['permissions'];
    if (is_array($user_permissions) && in_array($permission, $user_permissions)) {
        return true;
    }
    
    return false;
}

/**
 * Log an administrative action
 */
function log_action($pdo, $action, $module, $item_id = null, $old = null, $new = null) {
    $sql = "INSERT INTO audit_logs (user_id, action, module, item_id, old_values, new_values, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $pdo->prepare($sql)->execute([
        $_SESSION['user_id'] ?? null,
        $action,
        $module,
        $item_id,
        $old ? json_encode($old) : null,
        $new ? json_encode($new) : null,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
}

/**
 * Queue a notification for delivery
 */
function queue_notification($pdo, $user_id, $type, $event, $recipient, $subject, $message) {
    $sql = "INSERT INTO notifications (user_id, type, event_type, recipient, subject, message, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
    $pdo->prepare($sql)->execute([$user_id, $type, $event, $recipient, $subject, $message]);
}

/**
 * Validate API Bearer Token
 */
function validate_api_token($pdo) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
        // In a real app, verify JWT or check db
        // For this task, we'll check against a fixed 'test_token' or session
        if ($token === 'HIMT_SECURE_API_2026') return true;
    }
    
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized Access']);
    exit();
}
/**
 * Utility to format database errors for users
 */
function db_error_message($e) {
    $code = $e->getCode();
    $message = $e->getMessage();
    
    // Integrity constraint violation (Duplicate entry, Foreign Key)
    if ($code == '23000') {
        if (strpos($message, 'Duplicate entry') !== false) {
            if (strpos($message, 'email') !== false) return "This email address is already in use by another account.";
            if (strpos($message, 'enrollment_no') !== false) return "This Enrollment Number is already registered.";
            if (strpos($message, 'roll_number') !== false) return "This Roll Number is already assigned to another student.";
            if (strpos($message, 'employee_code') !== false) return "This Employee Code is already in use.";
            if (strpos($message, 'for key \'name\'') !== false) return "A record with this name already exists.";
            return "A duplicate record already exists in the system.";
        }
        if (strpos($message, 'foreign key constraint fails') !== false) {
            return "Cannot complete action: This record is currently linked to other data in the system.";
        }
    }
    
    // Connection lost or other errors
    return "Database Error: " . $message;
}
?>
