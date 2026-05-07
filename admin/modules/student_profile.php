<?php
require_once '../includes/config.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: students.php");
    exit();
}

// Fetch student comprehensive data
$sql = "SELECT s.*, u.name, u.email, u.phone as user_phone, u.profile_photo,
        d.name as dept_name, c.name as course_name 
        FROM students s
        JOIN users u ON s.user_id = u.id
        JOIN departments d ON s.department_id = d.id
        JOIN courses c ON s.course_id = c.id
        WHERE s.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    die("Student not found.");
}

$page_title = 'Student Profile - ' . $student['name'];
include '../includes/header.php';
?>

<div class="profile-container" style="padding: 2rem; max-width: 1400px; margin: 0 auto;">
    <!-- Top Action Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <a href="students.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 8px; transition: color 0.2s;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-muted)'">
            <i data-lucide="arrow-left" size="18"></i> Back to Directory
        </a>
        <div style="display: flex; gap: 12px;">
            <button class="btn" style="background: white; border: 1px solid var(--border); box-shadow: var(--shadow-sm);" onclick="window.print()">
                <i data-lucide="printer"></i> Print Profile
            </button>
            <a href="student_edit.php?id=<?php echo $id; ?>" class="btn btn-primary" style="box-shadow: 0 4px 12px rgba(var(--accent-rgb), 0.3);">
                <i data-lucide="edit-3"></i> Edit Details
            </a>
        </div>
    </div>

    <div class="profile-grid">
        <!-- Sidebar: Identity Card -->
        <div class="profile-sidebar">
            <div class="card shadow-premium" style="position: sticky; top: 2rem; padding: 0; overflow: hidden; border-radius: 24px;">
                <div style="height: 100px; background: linear-gradient(135deg, var(--accent), #4f46e5);"></div>
                <div style="padding: 0 1.5rem 2rem 1.5rem; margin-top: -50px; text-align: center;">
                    <div style="width: 100px; height: 100px; border-radius: 30px; background: white; margin: 0 auto 1.5rem auto; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 4px solid white; box-shadow: var(--shadow-lg);">
                        <?php if ($student['profile_photo']): ?>
                            <img src="../../<?php echo $student['profile_photo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <span style="font-size: 2.5rem; font-weight: 800; color: var(--accent);"><?php echo strtoupper(substr($student['name'], 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 5px; color: var(--text-main);"><?php echo $student['name']; ?></h2>
                    <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1.5rem;"><?php echo $student['enrollment_no']; ?></p>
                    
                    <div style="display: inline-block; background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 6px 16px; border-radius: 30px; font-size: 0.75rem; font-weight: 700; border: 1px solid rgba(16, 185, 129, 0.2);">
                        <i data-lucide="check-circle" size="14" style="vertical-align: middle; margin-right: 4px;"></i> Active Student
                    </div>

                    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border); display: flex; flex-direction: column; gap: 1rem; text-align: left;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                                <i data-lucide="mail" size="16"></i>
                            </div>
                            <div style="overflow: hidden;">
                                <p style="font-size: 0.65rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); margin-bottom: 2px;">Email</p>
                                <p style="font-size: 0.8125rem; font-weight: 600; text-overflow: ellipsis; overflow: hidden;"><?php echo $student['email']; ?></p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                                <i data-lucide="phone" size="16"></i>
                            </div>
                            <div>
                                <p style="font-size: 0.65rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); margin-bottom: 2px;">Phone</p>
                                <p style="font-size: 0.8125rem; font-weight: 600;"><?php echo $student['user_phone'] ?: $student['parent_phone']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content: Tabbed View -->
        <div class="profile-main">
            <!-- Stats Row -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div class="card shadow-premium" style="padding: 1.25rem; border-left: 4px solid var(--accent);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <p style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Course</p>
                        <i data-lucide="book-open" size="16" style="color: var(--accent);"></i>
                    </div>
                    <p style="font-size: 1.125rem; font-weight: 800;"><?php echo $student['course_name']; ?></p>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Dept of <?php echo $student['dept_name']; ?></p>
                </div>
                <div class="card shadow-premium" style="padding: 1.25rem; border-left: 4px solid #f59e0b;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <p style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Current Academic</p>
                        <i data-lucide="calendar" size="16" style="color: #f59e0b;"></i>
                    </div>
                    <p style="font-size: 1.125rem; font-weight: 800;"><?php echo $student['current_semester']; ?>th Semester</p>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;"><?php echo $student['current_year']; ?>st Year</p>
                </div>
                <div class="card shadow-premium" style="padding: 1.25rem; border-left: 4px solid #10b981;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <p style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Batch Year</p>
                        <i data-lucide="award" size="16" style="color: #10b981;"></i>
                    </div>
                    <p style="font-size: 1.125rem; font-weight: 800;"><?php echo $student['admission_year']; ?> Batch</p>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;"><?php echo $student['admission_type']; ?> Admission</p>
                </div>
            </div>

            <div class="card shadow-premium" style="padding: 0; border-radius: 24px; overflow: hidden;">
                <!-- Tab Navigation -->
                <div class="profile-tabs">
                    <button class="tab-btn active" onclick="showTab('personal')"><i data-lucide="user" size="16"></i> Personal</button>
                    <button class="tab-btn" onclick="showTab('family')"><i data-lucide="users" size="16"></i> Family</button>
                    <button class="tab-btn" onclick="showTab('academic')"><i data-lucide="graduation-cap" size="16"></i> Academic</button>
                </div>

                <div style="padding: 2.5rem;">
                    <!-- Personal Info Tab -->
                    <div id="personal-tab" class="tab-content active">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2.5rem;">
                            <div class="info-group">
                                <h3>Identity & Bio</h3>
                                <div class="info-grid">
                                    <div class="item">
                                        <label>Date of Birth</label>
                                        <p><?php echo date('d F, Y', strtotime($student['date_of_birth'])); ?></p>
                                    </div>
                                    <div class="item">
                                        <label>Gender</label>
                                        <p><?php echo $student['gender']; ?></p>
                                    </div>
                                    <div class="item">
                                        <label>Blood Group</label>
                                        <p><?php echo $student['blood_group'] ?: 'Not Specified'; ?></p>
                                    </div>
                                    <div class="item">
                                        <label>Category</label>
                                        <p><?php echo $student['category']; ?></p>
                                    </div>
                                    <div class="item">
                                        <label>Aadhar Number</label>
                                        <p><?php echo $student['aadhar_number'] ?: '--- --- ---'; ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="info-group">
                                <h3>Address Details</h3>
                                <div class="info-grid">
                                    <div class="item" style="grid-column: span 2;">
                                        <label><i data-lucide="map-pin" size="14"></i> Local Address</label>
                                        <p style="line-height: 1.6;"><?php echo $student['local_address'] ?: $student['permanent_address']; ?></p>
                                    </div>
                                    <div class="item" style="grid-column: span 2; margin-top: 1rem;">
                                        <label><i data-lucide="home" size="14"></i> Permanent Address</label>
                                        <p style="line-height: 1.6;"><?php echo $student['permanent_address']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Family Tab -->
                    <div id="family-tab" class="tab-content">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2.5rem;">
                            <div class="info-group">
                                <h3>Parent/Guardian</h3>
                                <div class="info-grid">
                                    <div class="item">
                                        <label>Father/Guardian Name</label>
                                        <p><?php echo $student['parent_name']; ?></p>
                                    </div>
                                    <div class="item">
                                        <label>Contact Phone</label>
                                        <p style="color: var(--accent); font-weight: 700;"><?php echo $student['parent_phone']; ?></p>
                                    </div>
                                    <div class="item">
                                        <label>Occupation</label>
                                        <p><?php echo $student['parent_occupation'] ?: 'Private Sector'; ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="info-group">
                                <h3>Socio-Economic</h3>
                                <div class="info-grid">
                                    <div class="item">
                                        <label>Annual Family Income</label>
                                        <p>₹<?php echo number_format((float)($student['annual_income'] ?? 0), 0); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Tab -->
                    <div id="academic-tab" class="tab-content">
                        <div class="info-group">
                            <h3>Past Academic Record</h3>
                            <div class="table-container shadow-premium" style="margin-top: 1.5rem; border-radius: 16px;">
                                <table style="margin: 0;">
                                    <tr style="background: #f8fafc;">
                                        <th style="padding: 1rem; text-align: left;">Institution Name</th>
                                        <th style="padding: 1rem;">Previous Course</th>
                                        <th style="padding: 1rem;">Percentage / CGPA</th>
                                    </tr>
                                    <tr>
                                        <td style="padding: 1.25rem; font-weight: 600;"><?php echo $student['prev_school'] ?: 'Not Provided'; ?></td>
                                        <td style="padding: 1.25rem; text-align: center; color: var(--text-muted);">Higher Secondary / Diploma</td>
                                        <td style="padding: 1.25rem; text-align: center;">
                                            <span style="background: rgba(var(--accent-rgb), 0.1); color: var(--accent); padding: 4px 12px; border-radius: 12px; font-weight: 800;">
                                                <?php echo $student['prev_percentage'] ?: '0.00'; ?>%
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .profile-grid {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 2.5rem;
        align-items: start;
    }

    .shadow-premium {
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(226, 232, 240, 0.8);
    }

    .profile-tabs {
        display: flex;
        background: #f8fafc;
        border-bottom: 1px solid var(--border);
        padding: 0 1rem;
    }

    .tab-btn {
        padding: 1.25rem 2rem;
        border: none;
        background: transparent;
        font-weight: 700;
        font-size: 0.875rem;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.2s;
        border-bottom: 3px solid transparent;
        display: flex;
        align-items: center; gap: 8px;
    }

    .tab-btn:hover { color: var(--accent); }
    .tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); }

    .tab-content { display: none; animation: fadeIn 0.4s ease; }
    .tab-content.active { display: block; }

    .info-group h3 {
        font-size: 0.9375rem; font-weight: 800; text-transform: uppercase;
        letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 1.5rem;
        display: flex; align-items: center; gap: 10px;
    }

    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
    .info-grid .item label { display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; }
    .info-grid .item p { font-size: 0.9375rem; font-weight: 600; color: var(--text-main); }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 1024px) {
        .profile-grid { grid-template-columns: 1fr; }
        .profile-sidebar .card { position: relative; top: 0; }
    }
</style>

<script>
    function showTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(tabId + '-tab').classList.add('active');
        event.currentTarget.classList.add('active');
    }
</script>

<?php include '../includes/footer.php'; ?>
