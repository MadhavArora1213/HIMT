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
 * Utility function to check permissions
 */
function has_permission($required_role) {
    if (!isset($_SESSION['role'])) return false;
    
    $roles = [
        'student' => 1,
        'faculty' => 2,
        'admin' => 3,
        'super_admin' => 4
    ];
    
    $user_level = $roles[$_SESSION['role']] ?? 0;
    $required_level = $roles[$required_role] ?? 5;
    
    return $user_level >= $required_level;
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
?>
