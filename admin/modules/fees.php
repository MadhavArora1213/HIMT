<?php
require_once '../includes/config.php';

// Handle Modal Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modal_payment'])) {
    $student_id = $_POST['student_id'];
    $fs_id = $_POST['fee_structure_id'];
    $amount = $_POST['amount'];
    $mode = $_POST['payment_mode'];
    $receipt_no = "RCP-" . time() . "-" . rand(100, 999);
    
    try {
        $sql = "INSERT INTO fee_payments (student_id, fee_structure_id, receipt_number, amount_paid, payment_date, payment_mode, payment_status, collected_by) 
                VALUES (?, ?, ?, ?, CURDATE(), ?, 'Success', ?)";
        $pdo->prepare($sql)->execute([$student_id, $fs_id, $receipt_no, $amount, $mode, $_SESSION['user_id']]);
        header("Location: fees.php?success=Payment of ₹$amount collected successfully. Receipt: $receipt_no");
        exit();
    } catch (Exception $e) {
        $error = db_error_message($e);
    }
}

// Stats Calculation
$total_revenue = $pdo->query("SELECT SUM(amount_paid) FROM fee_payments WHERE payment_status = 'Success'")->fetchColumn() ?: 0;
$today_collection = $pdo->query("SELECT SUM(amount_paid) FROM fee_payments WHERE payment_status = 'Success' AND DATE(payment_date) = CURDATE()")->fetchColumn() ?: 0;

// Total Expected (Sum of fee_structure amount * students in that course)
$total_expected = $pdo->query("
    SELECT SUM(fs.amount) 
    FROM fee_structures fs
    JOIN students s ON fs.course_id = s.course_id
    WHERE fs.is_active = 1
")->fetchColumn() ?: 0;

$total_pending = max(0, $total_expected - $total_revenue);

// Filters
$dept_filter = $_GET['dept_id'] ?? '';
$course_filter = $_GET['course_id'] ?? '';
$search = $_GET['search'] ?? '';

// Build Query for Students & Dues
$student_query = "
    SELECT 
        s.id, s.enrollment_no, s.roll_number, u.name, d.name as dept_name, c.name as course_name,
        (SELECT SUM(fs.amount) FROM fee_structures fs WHERE fs.course_id = s.course_id AND fs.is_active = 1) as total_due,
        (SELECT SUM(fp.amount_paid) FROM fee_payments fp WHERE fp.student_id = s.id AND fp.payment_status = 'Success') as total_paid
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN departments d ON s.department_id = d.id
    JOIN courses c ON s.course_id = c.id
    WHERE 1=1
";

$params = [];
if ($dept_filter) { $student_query .= " AND s.department_id = ?"; $params[] = $dept_filter; }
if ($course_filter) { $student_query .= " AND s.course_id = ?"; $params[] = $course_filter; }
if ($search) { 
    $student_query .= " AND (u.name LIKE ? OR s.enrollment_no LIKE ?)"; 
    $params[] = "%$search%"; $params[] = "%$search%"; 
}

$student_query .= " ORDER BY u.name ASC LIMIT 50";
$stmt = $pdo->prepare($student_query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Recent Payments (Limited to 2 for dashboard clarity)
$recent_payments = $pdo->query("
    SELECT fp.*, u.name as student_name, c.short_name as course_name
    FROM fee_payments fp
    JOIN students s ON fp.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN courses c ON s.course_id = c.id
    ORDER BY fp.payment_date DESC, fp.id DESC
    LIMIT 2
")->fetchAll();

// Fetch Departments and Courses for Filters
$depts = $pdo->query("SELECT id, name FROM departments WHERE is_active = 1")->fetchAll();
$courses = $pdo->query("SELECT id, name FROM courses WHERE is_active = 1")->fetchAll();

$page_title = 'Fee Management';
include '../includes/header.php';
?>

<div style="padding: 2rem;">
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size: 1.5rem; font-weight: 700;">Fee Management</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Track student payments, dues, and revenue analytics.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn btn-primary" onclick="location.href='fee_structure.php'" style="background: white; color: var(--text-main); border: 1px solid var(--border);">
                <i data-lucide="layers"></i> Fee Structures
            </button>
            <button class="btn btn-primary" onclick="openCollectionModal()">
                <i data-lucide="plus-circle"></i> Collect Fee
            </button>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div id="success-alert" style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.2);">
            <i data-lucide="check-circle" size="18" style="vertical-align: middle; margin-right: 8px;"></i> <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <!-- Stats Row -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="card" style="padding: 1.5rem; border-left: 4px solid var(--accent);">
            <p style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Total Collection</p>
            <h3 style="font-size: 1.5rem; font-weight: 700;">₹<?php echo number_format($total_revenue, 2); ?></h3>
            <p style="font-size: 0.75rem; color: var(--success); margin-top: 0.5rem;"><i data-lucide="trending-up" size="14"></i> Lifetime Revenue</p>
        </div>
        <div class="card" style="padding: 1.5rem; border-left: 4px solid var(--success);">
            <p style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Today's Collection</p>
            <h3 style="font-size: 1.5rem; font-weight: 700;">₹<?php echo number_format($today_collection, 2); ?></h3>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem;">Collected on <?php echo date('d M, Y'); ?></p>
        </div>
        <div class="card" style="padding: 1.5rem; border-left: 4px solid var(--warning);">
            <p style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Pending Payments</p>
            <h3 style="font-size: 1.5rem; font-weight: 700;">₹<?php echo number_format($total_pending, 2); ?></h3>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem;">Estimated outstanding dues</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: minmax(0, 2.2fr) minmax(300px, 1fr); gap: 1.5rem;">
        <!-- Left Column: Student Dues -->
        <div>
            <div class="card" style="padding: 1.5rem; margin-bottom: 1.5rem; overflow: hidden;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <h3 style="font-size: 1.1rem; font-weight: 700;">Student Dues Summary</h3>
                    <form action="" method="GET" style="display: flex; gap: 0.5rem; flex: 1; max-width: 300px;">
                        <input type="text" name="search" placeholder="Search student..." class="form-control" style="width: 100%; height: 40px; font-size: 0.875rem;" value="<?php echo $search; ?>">
                        <button type="submit" class="btn btn-primary" style="padding: 0 1rem; height: 40px;"><i data-lucide="search" size="18"></i></button>
                    </form>
                </div>

                <div class="table-container" style="overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch;">
                    <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                        <thead>
                            <tr style="text-align: left; background: #f8fafc; border-bottom: 1px solid var(--border);">
                                <th style="padding: 1rem; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Student</th>
                                <th style="padding: 1rem; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Course</th>
                                <th style="padding: 1rem; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Expected</th>
                                <th style="padding: 1rem; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Paid</th>
                                <th style="padding: 1rem; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Balance</th>
                                <th style="padding: 1rem; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status</th>
                                <th style="padding: 1rem; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): 
                                $paid = $s['total_paid'] ?: 0;
                                $due = $s['total_due'] ?: 0;
                                $balance = $due - $paid;
                                $status_color = $balance <= 0 ? 'var(--success)' : ($paid > 0 ? 'var(--warning)' : 'var(--danger)');
                                $status_text = $balance <= 0 ? 'Clear' : ($paid > 0 ? 'Partial' : 'Pending');
                            ?>
                            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                                <td style="padding: 1rem;">
                                    <div style="font-weight: 600; color: var(--text-main);"><?php echo $s['name']; ?></div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;"><?php echo $s['enrollment_no']; ?></div>
                                </td>
                                <td style="padding: 1rem; font-size: 0.875rem; color: var(--text-muted); line-height: 1.4; max-width: 150px;"><?php echo $s['course_name']; ?></td>
                                <td style="padding: 1rem; font-weight: 500;">₹<?php echo number_format($due, 0); ?></td>
                                <td style="padding: 1rem; font-weight: 500; color: var(--success);">₹<?php echo number_format($paid, 0); ?></td>
                                <td style="padding: 1rem;">
                                    <div style="font-weight: 700; color: <?php echo $balance > 0 ? 'var(--danger)' : 'var(--success)'; ?>;">₹<?php echo number_format($balance, 0); ?></div>
                                </td>
                                <td style="padding: 1rem;">
                                    <span style="padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; background: <?php echo $status_color; ?>10; color: <?php echo $status_color; ?>; display: inline-block;">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem;">
                                    <a href="fee_payment.php?student_id=<?php echo $s['id']; ?>" class="btn" style="padding: 6px 12px; font-size: 0.75rem; background: white; border: 1px solid var(--border); text-decoration: none; color: var(--text-main); font-weight: 600; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                        <i data-lucide="plus" size="14"></i> Record
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column: Recent Activity -->
        <div>
            <div class="card" style="padding: 1.5rem;">
                <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.5rem;">Recent Payments</h3>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php if (empty($recent_payments)): ?>
                        <p style="text-align: center; color: var(--text-muted); padding: 2rem;">No recent transactions found.</p>
                    <?php endif; ?>
                    <?php foreach ($recent_payments as $rp): ?>
                    <div style="display: flex; gap: 1.25rem; padding: 1.25rem; border: 1px solid var(--border); border-radius: 12px; transition: all 0.2s; background: white;">
                        <div style="width: 44px; height: 44px; background: var(--success)10; color: var(--success); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i data-lucide="receipt" size="22"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <span style="font-weight: 700; font-size: 0.9375rem; color: var(--text-main); line-height: 1.2;"><?php echo $rp['student_name']; ?></span>
                                <span style="font-weight: 800; font-size: 0.9375rem; color: var(--success);">+ ₹<?php echo number_format($rp['amount_paid'], 0); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
                                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;"><?php echo $rp['course_name']; ?> • <?php echo $rp['payment_mode']; ?></span>
                                <span style="font-size: 0.75rem; color: var(--text-muted); background: var(--background); padding: 2px 6px; border-radius: 4px;"><?php echo date('d M', strtotime($rp['payment_date'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <a href="fee_transactions.php" class="btn" style="display: block; text-align: center; margin-top: 1.5rem; background: var(--background); border: 1px solid var(--border); font-size: 0.875rem; padding: 0.875rem; text-decoration: none; color: var(--text-main); font-weight: 600; border-radius: 8px;">View All Transactions</a>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- Collection Modal -->
<div id="collectionModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div class="card" style="width: 100%; max-width: 500px; padding: 0; overflow: hidden; animation: modalIn 0.3s ease-out;">
        <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 1.1rem; font-weight: 700;">Collect Fee</h3>
            <button onclick="closeCollectionModal()" style="background: none; border: none; cursor: pointer; color: var(--text-muted);"><i data-lucide="x"></i></button>
        </div>
        
        <!-- Step 1: Search -->
        <div id="modalStep1" style="padding: 1.5rem;">
            <div class="form-group">
                <label>Search Student</label>
                <div style="position: relative;">
                    <input type="text" id="studentSearchInput" class="form-control" placeholder="Name or Enrollment No..." oninput="searchStudents(this.value)">
                    <div id="searchResults" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid var(--border); border-radius: 8px; margin-top: 5px; max-height: 200px; overflow-y: auto; display: none; z-index: 10; box-shadow: var(--shadow);">
                        <!-- Results injected here -->
                    </div>
                </div>
            </div>
            <p style="font-size: 0.8125rem; color: var(--text-muted); margin-top: 1rem;">Type at least 3 characters to search for a student.</p>
        </div>

        <!-- Step 2: Payment Form -->
        <form id="modalStep2" style="display: none; padding: 1.5rem;" method="POST">
            <input type="hidden" name="modal_payment" value="1">
            <input type="hidden" name="student_id" id="modal_student_id">
            
            <div id="studentDetailsBox" style="background: var(--background); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <!-- Student info injected here -->
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Select Fee Heading</label>
                <select name="fee_structure_id" id="modal_fee_structure" class="form-control" required>
                    <option value="">Choose Heading</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Amount to Collect (₹)</label>
                <input type="number" name="amount" id="modal_amount" class="form-control" placeholder="0.00" required>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label>Payment Mode</label>
                <select name="payment_mode" class="form-control" required>
                    <option value="Cash">Cash</option>
                    <option value="Online">Online / UPI</option>
                    <option value="DD">Demand Draft</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="resetModal()" class="btn" style="flex: 1; background: var(--background); border: 1px solid var(--border);">Back</button>
                <button type="submit" class="btn btn-primary" style="flex: 2;">Collect Payment</button>
            </div>
        </form>
    </div>
</div>

<style>
    @keyframes modalIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .search-result-item {
        padding: 10px 15px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.2s;
    }
    .search-result-item:hover {
        background: #f8fafc;
    }
</style>

<script>
function openCollectionModal() {
    document.getElementById('collectionModal').style.display = 'flex';
    document.getElementById('studentSearchInput').focus();
    if(typeof lucide !== 'undefined') lucide.createIcons();
}

function closeCollectionModal() {
    document.getElementById('collectionModal').style.display = 'none';
    resetModal();
}

function resetModal() {
    document.getElementById('modalStep1').style.display = 'block';
    document.getElementById('modalStep2').style.display = 'none';
    document.getElementById('studentSearchInput').value = '';
    document.getElementById('searchResults').style.display = 'none';
}

function searchStudents(query) {
    const resultsDiv = document.getElementById('searchResults');
    if (query.length < 3) {
        resultsDiv.style.display = 'none';
        return;
    }

    fetch('../api/search_students.php?query=' + encodeURIComponent(query))
        .then(res => res.json())
        .then(data => {
            resultsDiv.innerHTML = '';
            if (data.length > 0) {
                data.forEach(s => {
                    const item = document.createElement('div');
                    item.className = 'search-result-item';
                    item.innerHTML = `
                        <div style="font-weight: 600; font-size: 0.875rem;">${s.name}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">${s.enrollment_no} • ${s.course}</div>
                    `;
                    item.onclick = () => selectStudent(s.id, s.name, s.enrollment_no);
                    resultsDiv.appendChild(item);
                });
                resultsDiv.style.display = 'block';
            } else {
                resultsDiv.innerHTML = '<div style="padding: 15px; text-align: center; color: var(--text-muted); font-size: 0.875rem;">No students found</div>';
                resultsDiv.style.display = 'block';
            }
        });
}

function selectStudent(id, name, enrollment) {
    document.getElementById('modal_student_id').value = id;
    document.getElementById('studentDetailsBox').innerHTML = `
        <div style="font-weight: 700; color: var(--primary);">${name}</div>
        <div style="font-size: 0.8125rem; color: var(--text-muted);">${enrollment}</div>
    `;

    // Fetch Dues
    fetch('../api/get_student_dues.php?student_id=' + id)
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('modal_fee_structure');
            select.innerHTML = '<option value="">Choose Heading</option>';
            if (data.dues && data.dues.length > 0) {
                data.dues.forEach(d => {
                    if (d.balance > 0) {
                        const opt = document.createElement('option');
                        opt.value = d.id;
                        opt.textContent = `${d.fee_type} (Due: ₹${d.balance})`;
                        opt.dataset.balance = d.balance;
                        select.appendChild(opt);
                    }
                });
            }
            
            document.getElementById('modalStep1').style.display = 'none';
            document.getElementById('modalStep2').style.display = 'block';
        });
}

// Auto-fill amount based on selected heading
document.getElementById('modal_fee_structure').onchange = function() {
    const opt = this.options[this.selectedIndex];
    if (opt.dataset.balance) {
        document.getElementById('modal_amount').value = opt.dataset.balance;
    }
};
</script>

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
            
            // Remove query parameters from URL without refreshing
            const url = new URL(window.location);
            url.searchParams.delete('success');
            url.searchParams.delete('error');
            window.history.replaceState({}, document.title, url);
        }, 3000);
    });
</script>
