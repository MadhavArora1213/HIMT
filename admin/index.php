<?php
$page_title = 'Dashboard';
include 'includes/header.php';

// Fetch Summary Stats
$total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$total_faculty = $pdo->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
$pending_admissions = $pdo->query("SELECT COUNT(*) FROM admissions WHERE status = 'submitted'")->fetchColumn();
$total_departments = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();

// Latest Notices
$recent_notices = $pdo->query("SELECT title, created_at, notice_type FROM notices ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Upcoming Exams
$upcoming_exams = $pdo->query("SELECT exam_name, start_date FROM examinations WHERE start_date >= CURDATE() ORDER BY start_date ASC LIMIT 3")->fetchAll();

// Enrollment Data for Chart
$enrollment_data = $pdo->query("SELECT admission_year, COUNT(*) as count FROM students GROUP BY admission_year ORDER BY admission_year ASC LIMIT 5")->fetchAll();
$years = array_column($enrollment_data, 'admission_year');
$counts = array_column($enrollment_data, 'count');
?>

<div style="padding: 2rem;">
    <div style="margin-bottom: 2.5rem;">
        <h2 style="font-size: 1.75rem; font-weight: 800; color: var(--text-main);">Welcome back, <?php echo $_SESSION['name'] ?? 'Admin'; ?>!</h2>
        <p style="color: var(--text-muted); font-size: 0.9375rem;">Here's what's happening at HIMT today.</p>
    </div>

    <!-- Summary Widgets -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        <div class="card" style="padding: 1.5rem; position: relative; overflow: hidden;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="font-size: 0.8125rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Total Students</p>
                    <h3 style="font-size: 2rem; font-weight: 800; color: var(--text-main);"><?php echo number_format($total_students); ?></h3>
                </div>
                <div style="background: rgba(37, 99, 235, 0.1); color: var(--accent); padding: 10px; border-radius: 12px;">
                    <i data-lucide="users" size="24"></i>
                </div>
            </div>
            <div style="margin-top: 1rem; font-size: 0.8125rem; color: var(--success); display: flex; align-items: center; gap: 4px;">
                <i data-lucide="trending-up" size="14"></i> <span>+12% vs last year</span>
            </div>
        </div>

        <div class="card" style="padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="font-size: 0.8125rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Total Faculty</p>
                    <h3 style="font-size: 2rem; font-weight: 800; color: var(--text-main);"><?php echo number_format($total_faculty); ?></h3>
                </div>
                <div style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 10px; border-radius: 12px;">
                    <i data-lucide="user-square-2" size="24"></i>
                </div>
            </div>
            <div style="margin-top: 1rem; font-size: 0.8125rem; color: var(--text-muted);">98% Active in classes</div>
        </div>

        <div class="card" style="padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="font-size: 0.8125rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Pending Admissions</p>
                    <h3 style="font-size: 2rem; font-weight: 800; color: var(--danger);"><?php echo number_format($pending_admissions); ?></h3>
                </div>
                <div style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 10px; border-radius: 12px;">
                    <i data-lucide="clipboard-list" size="24"></i>
                </div>
            </div>
            <a href="modules/admissions.php" style="margin-top: 1rem; display: block; font-size: 0.8125rem; color: var(--accent); text-decoration: none; font-weight: 600;">View application queue &rarr;</a>
        </div>

        <div class="card" style="padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <p style="font-size: 0.8125rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Today's Collection</p>
                    <h3 style="font-size: 2rem; font-weight: 800; color: var(--text-main);">₹1.2M</h3>
                </div>
                <div style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6; padding: 10px; border-radius: 12px;">
                    <i data-lucide="banknote" size="24"></i>
                </div>
            </div>
            <div style="margin-top: 1rem; font-size: 0.8125rem; color: var(--success); display: flex; align-items: center; gap: 4px;">
                <i data-lucide="check-circle" size="14"></i> <span>85% target reached</span>
            </div>
        </div>
    </div>

    <!-- Charts and Secondary Widgets -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <div class="card" style="padding: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h3 style="font-size: 1.125rem; font-weight: 800;">Enrollment Growth</h3>
                <select class="form-control" style="width: auto; font-size: 0.75rem;">
                    <option>Last 5 Years</option>
                    <option>Last 10 Years</option>
                </select>
            </div>
            <canvas id="enrollmentChart" height="120"></canvas>
        </div>

        <div style="display: flex; flex-direction: column; gap: 2rem;">
            <!-- Upcoming Exams -->
            <div class="card" style="padding: 1.5rem;">
                <h3 style="font-size: 1rem; font-weight: 800; margin-bottom: 1.5rem;">Upcoming Exams</h3>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php if(empty($upcoming_exams)): ?>
                        <p style="font-size: 0.8125rem; color: var(--text-muted);">No exams scheduled.</p>
                    <?php endif; ?>
                    <?php foreach ($upcoming_exams as $exam): ?>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <div style="background: #f1f5f9; padding: 8px; border-radius: 8px; text-align: center; min-width: 50px;">
                            <p style="font-size: 0.65rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;"><?php echo date('M', strtotime($exam['start_date'])); ?></p>
                            <p style="font-size: 1rem; font-weight: 800; color: var(--text-main);"><?php echo date('d', strtotime($exam['start_date'])); ?></p>
                        </div>
                        <div>
                            <p style="font-size: 0.875rem; font-weight: 700;"><?php echo $exam['exam_name']; ?></p>
                            <p style="font-size: 0.75rem; color: var(--text-muted);">Begins at 09:00 AM</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Notices -->
            <div class="card" style="padding: 1.5rem;">
                <h3 style="font-size: 1rem; font-weight: 800; margin-bottom: 1.5rem;">Latest Notices</h3>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach ($recent_notices as $notice): ?>
                    <div style="border-left: 3px solid var(--accent); padding-left: 12px;">
                        <p style="font-size: 0.8125rem; font-weight: 700; margin-bottom: 2px;"><?php echo $notice['title']; ?></p>
                        <p style="font-size: 0.7rem; color: var(--text-muted);"><?php echo date('d M, Y', strtotime($notice['created_at'])); ?> • <?php echo $notice['notice_type']; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('enrollmentChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($years); ?>,
            datasets: [{
                label: 'Total Students',
                data: <?php echo json_encode($counts); ?>,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#2563eb',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                x: { grid: { display: false } }
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>