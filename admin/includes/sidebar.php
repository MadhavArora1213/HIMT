<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">HIMT</div>
    </div>
    
    <nav class="sidebar-menu">
        <?php
        $current_page = basename($_SERVER['PHP_SELF']);
        
        function is_active($page, $current_page) {
            return $page == $current_page ? 'active' : '';
        }
        ?>
        
        <div class="menu-label">Main</div>
        <a href="<?php echo BASE_URL; ?>index.php" class="menu-item <?php echo is_active('index.php', $current_page); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
            <span>Dashboard</span>
        </a>
        
        <?php if (has_permission('admissions') || has_permission('departments') || has_permission('courses') || has_permission('subjects') || has_permission('students') || has_permission('faculty')): ?>
        <div class="menu-label">Academic Management</div>
        <?php if (has_permission('admissions')): ?>
        <a href="<?php echo BASE_URL; ?>modules/admissions.php" class="menu-item <?php echo is_active('admissions.php', $current_page); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/></svg>
            <span>Admissions</span>
        </a>
        <?php endif; ?>
        <?php if (has_permission('departments')): ?>
        <a href="<?php echo BASE_URL; ?>modules/departments.php" class="menu-item <?php echo is_active('departments.php', $current_page); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
            <span>Departments</span>
        </a>
        <?php endif; ?>
        <?php if (has_permission('courses')): ?>
        <a href="<?php echo BASE_URL; ?>modules/courses.php" class="menu-item <?php echo is_active('courses.php', $current_page); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            <span>Courses</span>
        </a>
        <?php endif; ?>
        <?php if (has_permission('subjects')): ?>
        <a href="<?php echo BASE_URL; ?>modules/subjects.php" class="menu-item <?php echo is_active('subjects.php', $current_page); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M8 7h6"/><path d="M8 11h8"/></svg>
            <span>Subjects</span>
        </a>
        <?php endif; ?>
        <?php if (has_permission('students')): ?>
        <a href="<?php echo BASE_URL; ?>modules/students.php" class="menu-item <?php echo is_active('students.php', $current_page); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span>Students</span>
        </a>
        <?php endif; ?>
        <?php if (has_permission('faculty')): ?>
        <a href="<?php echo BASE_URL; ?>modules/faculty.php" class="menu-item <?php echo is_active('faculty.php', $current_page); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="10" cy="10" r="2.5"/><path d="M3 21c0-2.1 1.7-3.9 3.8-4h6.3c2.1.1 3.8 1.9 3.8 4"/></svg>
            <span>Faculty</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>
        
        <?php if (has_permission('attendance') || has_permission('fees') || has_permission('exams') || has_permission('library') || has_permission('placements') || has_permission('study_material')): ?>
        <div class="menu-label">Operations</div>
        <?php if (has_permission('attendance')): ?>
        <a href="<?php echo BASE_URL; ?>modules/attendance_mark.php" class="menu-item <?php echo is_active('attendance_mark.php', $current_page); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>
            <span>Attendance</span>
        </a>
        <?php endif; ?>
        <?php if (has_permission('fees')): ?>
        <a href="<?php echo BASE_URL; ?>modules/fees.php" class="menu-item <?php echo is_active('fees.php', $current_page); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
            <span>Fee Management</span>
        </a>
        <?php endif; ?>
        <?php if (has_permission('exams')): ?>
        <a href="<?php echo BASE_URL; ?>modules/exams.php" class="menu-item <?php echo is_active('exams.php', $current_page); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M15 2H9a1 1 0 0 0-1 1v2.5a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1Z"/><path d="M9 10h6"/><path d="M9 14h6"/><path d="M9 18h4"/></svg>
            <span>Exams & Results</span>
        </a>
        <?php endif; ?>
        <?php if (has_permission('library')): ?>
        <a href="<?php echo BASE_URL; ?>modules/library.php" class="menu-item <?php echo is_active('library.php', $current_page); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            <span>Library</span>
        </a>
        <?php endif; ?>
        <?php if (has_permission('placements')): ?>
        <a href="<?php echo BASE_URL; ?>modules/placements.php" class="menu-item <?php echo is_active('placements.php', $current_page); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.596-2.686a.5.5 0 0 0-.572 0l-3.596 2.686a.5.5 0 0 1-.81-.47l1.515-8.526"/><circle cx="12" cy="8" r="6"/></svg>
            <span>Placements</span>
        </a>
        <?php endif; ?>
        <?php if (has_permission('study_material')): ?>
        <a href="<?php echo BASE_URL; ?>modules/study_materials.php" class="menu-item <?php echo is_active('study_materials.php', $current_page); ?>">
            <i data-lucide="folder-kanban"></i>
            <span>Study Materials</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>
        
        <?php if (has_permission('notices') || has_permission('events') || has_permission('gallery') || has_permission('inquiries')): ?>
        <div class="menu-label">Media & News</div>
        <?php if (has_permission('notices')): ?>
        <a href="<?php echo BASE_URL; ?>modules/notices.php" class="menu-item <?php echo is_active('notices.php', $current_page); ?>">
            <i data-lucide="megaphone"></i>
            <span>Notice Board</span>
        </a>
        <?php endif; ?>
        <?php if (has_permission('events')): ?>
        <a href="<?php echo BASE_URL; ?>modules/events.php" class="menu-item <?php echo is_active('events.php', $current_page); ?>">
            <i data-lucide="calendar-days"></i>
            <span>Campus Events</span>
        </a>
        <?php endif; ?>
        <?php if (has_permission('gallery')): ?>
        <a href="<?php echo BASE_URL; ?>modules/gallery.php" class="menu-item <?php echo is_active('gallery.php', $current_page); ?>">
            <i data-lucide="image"></i>
            <span>Photo Gallery</span>
        </a>
        <?php endif; ?>
        <?php if (has_permission('inquiries')): ?>
        <a href="<?php echo BASE_URL; ?>modules/inquiries.php" class="menu-item <?php echo is_active('inquiries.php', $current_page); ?>">
            <i data-lucide="message-square-more"></i>
            <span>Inquiries</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>
        
        <?php if (has_permission('institute') || has_permission('users') || has_permission('settings')): ?>
        <div class="menu-label">System Settings</div>
        <?php if (has_permission('institute')): ?>
        <a href="<?php echo BASE_URL; ?>modules/institute.php" class="menu-item <?php echo is_active('institute.php', $current_page); ?>">
            <i data-lucide="building"></i>
            <span>Institute Profile</span>
        </a>
        <?php endif; ?>
        <?php if (has_permission('users')): ?>
        <a href="<?php echo BASE_URL; ?>modules/users.php" class="menu-item <?php echo is_active('users.php', $current_page); ?>">
            <i data-lucide="user-cog"></i>
            <span>User & Roles</span>
        </a>
        <?php endif; ?>
        <?php if (has_permission('settings')): ?>
        <a href="<?php echo BASE_URL; ?>modules/settings.php" class="menu-item <?php echo is_active('settings.php', $current_page); ?>">
            <i data-lucide="settings"></i>
            <span>Settings</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>logout.php" class="menu-item" style="color: var(--danger); margin-top: 2rem; background: rgba(239, 68, 68, 0.05);">
            <i data-lucide="log-out"></i>
            <span>Logout</span>
        </a>
    </nav>
    
    <script>
        // Emergency Fallback for Icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        } else {
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        }
    </script>
</aside>
