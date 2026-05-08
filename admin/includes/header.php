<?php
require_once __DIR__ . '/config.php';

// Security Headers
// Security Headers
// header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:;");
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), camera=(), microphone=()");

check_login();

// Module Permission Enforcement
$module_mapping = [
    'admissions.php' => 'admissions',
    'departments.php' => 'departments',
    'courses.php' => 'courses',
    'subjects.php' => 'subjects',
    'students.php' => 'students',
    'faculty.php' => 'faculty',
    'attendance_mark.php' => 'attendance',
    'fees.php' => 'fees',
    'exams.php' => 'exams',
    'library.php' => 'library',
    'placements.php' => 'placements',
    'study_materials.php' => 'study_material',
    'notices.php' => 'notices',
    'events.php' => 'events',
    'gallery.php' => 'gallery',
    'inquiries.php' => 'inquiries',
    'institute.php' => 'institute',
    'users.php' => 'users',
    'settings.php' => 'settings'
];

$current_filename = basename($_SERVER['PHP_SELF']);
if (isset($module_mapping[$current_filename])) {
    if (!has_permission($module_mapping[$current_filename])) {
        echo "<div style='padding: 5rem; text-align: center; background: white; height: 100vh;'>
                <div style='max-width: 400px; margin: 0 auto; padding: 2rem; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid var(--border);'>
                    <div style='width: 64px; height: 64px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;'>
                        <svg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z'/><path d='M12 9v4'/><path d='M12 17h.01'/></svg>
                    </div>
                    <h2 style='font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;'>Access Denied</h2>
                    <p style='color: var(--text-muted); margin-bottom: 2rem;'>You don't have the necessary permissions to access this administrative module.</p>
                    <a href='index.php' style='display: block; padding: 0.75rem; background: var(--accent); color: white; text-decoration: none; border-radius: 10px; font-weight: 600;'>Return to Dashboard</a>
                </div>
              </div>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Dashboard'; ?> | HIMT Admin</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css?v=2.6">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- SortableJS -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-wrapper">
            <header class="header">
                <div class="header-left">
                    <button id="toggle-sidebar" class="btn" style="background: transparent; color: var(--text-muted);">
                        <i data-lucide="menu"></i>
                    </button>
                    <h1 style="font-size: 1.25rem; font-weight: 600;"><?php echo $page_title ?? 'Dashboard'; ?></h1>
                </div>
                
                <div class="header-right">
                    <div class="user-profile-wrapper" style="position: relative;">
                        <div class="user-profile" id="profile-toggle" style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 5px 10px; border-radius: 8px; transition: background 0.3s;">
                            <div style="text-align: right;">
                                <p style="font-weight: 600; font-size: 0.875rem;"><?php echo $_SESSION['name'] ?? 'Admin User'; ?></p>
                                <p style="font-size: 0.75rem; color: var(--text-muted); text-transform: capitalize;"><?php echo str_replace('_', ' ', $_SESSION['role'] ?? 'super_admin'); ?></p>
                            </div>
                            <div style="width: 40px; height: 40px; background: var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                <?php echo strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1)); ?>
                            </div>
                        </div>
                        
                        <div class="profile-dropdown" id="profile-dropdown" style="display: none; position: absolute; top: 100%; right: 0; mt: 10px; background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 180px; z-index: 1000; border: 1px solid var(--border); overflow: hidden;">
                            <a href="<?php echo BASE_URL; ?>logout.php" style="display: flex; align-items: center; gap: 10px; padding: 12px 15px; color: #ef4444; text-decoration: none; font-weight: 500; transition: background 0.3s;">
                                <i data-lucide="log-out" style="width: 18px; height: 18px;"></i>
                                <span>Sign Out</span>
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <main class="content-area">
