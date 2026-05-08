<?php
require_once '../includes/config.php';

$page_title = 'Transaction History';

// CSV Export Logic
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Clear any previous output
    ob_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Fee_Transactions_'.date('Y-m-d').'.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Receipt No', 'Date', 'Student Name', 'Enrollment', 'Course', 'Mode', 'Transaction ID', 'Amount', 'Collected By', 'Status']);
    
    // Build Export Query (No pagination for export)
    $where_export = ["1=1"];
    $params_export = [];
    if (isset($_GET['search']) && $_GET['search']) { 
        $where_export[] = "(u.name LIKE ? OR fp.receipt_number LIKE ?)"; 
        $params_export[] = "%".$_GET['search']."%"; $params_export[] = "%".$_GET['search']."%"; 
    }
    if (isset($_GET['mode']) && $_GET['mode']) { $where_export[] = "fp.payment_mode = ?"; $params_export[] = $_GET['mode']; }
    if (isset($_GET['date_from']) && $_GET['date_from']) { $where_export[] = "fp.payment_date >= ?"; $params_export[] = $_GET['date_from']; }
    if (isset($_GET['date_to']) && $_GET['date_to']) { $where_export[] = "fp.payment_date <= ?"; $params_export[] = $_GET['date_to']; }
    
    $where_sql_export = implode(" AND ", $where_export);
    $stmt_export = $pdo->prepare("
        SELECT fp.*, u.name as student_name, s.enrollment_no, c.short_name as course_name, uc.name as collected_by_name
        FROM fee_payments fp
        JOIN students s ON fp.student_id = s.id
        JOIN users u ON s.user_id = u.id
        JOIN courses c ON s.course_id = c.id
        LEFT JOIN users uc ON fp.collected_by = uc.id
        WHERE $where_sql_export
        ORDER BY fp.payment_date DESC
    ");
    $stmt_export->execute($params_export);
    
    while ($row = $stmt_export->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['receipt_number'],
            $row['payment_date'],
            $row['student_name'],
            $row['enrollment_no'],
            $row['course_name'],
            $row['payment_mode'],
            $row['transaction_id'],
            $row['amount_paid'],
            $row['collected_by_name'] ?: 'System',
            $row['payment_status']
        ]);
    }
    fclose($output);
    exit();
}

include '../includes/header.php';

// Filters
$search = $_GET['search'] ?? '';
$mode = $_GET['mode'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build Query
$where = ["1=1"];
$params = [];

if ($search) { 
    $where[] = "(u.name LIKE ? OR fp.receipt_number LIKE ?)"; 
    $params[] = "%$search%"; $params[] = "%$search%"; 
}
if ($mode) { $where[] = "fp.payment_mode = ?"; $params[] = $mode; }
if ($date_from) { $where[] = "fp.payment_date >= ?"; $params[] = $date_from; }
if ($date_to) { $where[] = "fp.payment_date <= ?"; $params[] = $date_to; }

$where_sql = implode(" AND ", $where);

// Count Total
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM fee_payments fp JOIN students s ON fp.student_id = s.id JOIN users u ON s.user_id = u.id WHERE $where_sql");
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch Transactions
$query = "
    SELECT fp.*, u.name as student_name, s.enrollment_no, c.short_name as course_name, uc.name as collected_by_name
    FROM fee_payments fp
    JOIN students s ON fp.student_id = s.id
    JOIN users u ON s.user_id = u.id
    JOIN courses c ON s.course_id = c.id
    LEFT JOIN users uc ON fp.collected_by = uc.id
    WHERE $where_sql
    ORDER BY fp.payment_date DESC, fp.id DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();
?>

<div style="padding: 2rem;">
    <div class="no-print" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <a href="fees.php" style="text-decoration: none; color: var(--text-muted); font-size: 0.875rem; display: flex; align-items: center; gap: 5px;">
                <i data-lucide="arrow-left" size="16"></i> Back to Fees
            </a>
            <h2 style="font-size: 1.5rem; font-weight: 700; margin-top: 1rem;">Transaction History</h2>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn no-print" style="background: white; border: 1px solid var(--border); color: var(--text-main); text-decoration: none; display: flex; align-items: center; gap: 8px;">
                <i data-lucide="download"></i> Export CSV
            </a>
        </div>
    </div>

    <!-- Advanced Filters -->
    <div class="card no-print" style="margin-bottom: 2rem; padding: 1.5rem;">
        <form action="" method="GET" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; display: block;">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Receipt or Student Name..." value="<?php echo $search; ?>" style="height: 42px;">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; display: block;">Mode</label>
                <select name="mode" class="form-control" style="height: 42px;">
                    <option value="">All Modes</option>
                    <option value="Cash" <?php echo $mode == 'Cash' ? 'selected' : ''; ?>>Cash</option>
                    <option value="Online" <?php echo $mode == 'Online' ? 'selected' : ''; ?>>Online</option>
                    <option value="DD" <?php echo $mode == 'DD' ? 'selected' : ''; ?>>DD</option>
                    <option value="Cheque" <?php echo $mode == 'Cheque' ? 'selected' : ''; ?>>Cheque</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; display: block;">From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>" style="height: 42px;">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; display: block;">To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>" style="height: 42px;">
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="height: 42px; padding: 0 1.25rem;"><i data-lucide="filter"></i></button>
                <a href="fee_transactions.php" class="btn" style="height: 42px; padding: 0 1.25rem; background: var(--background); border: 1px solid var(--border); display: flex; align-items: center;"><i data-lucide="rotate-ccw"></i></a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-container">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; background: #f8fafc; border-bottom: 2px solid var(--border);">
                        <th style="padding: 1.25rem;">Receipt & Date</th>
                        <th style="padding: 1.25rem;">Student Details</th>
                        <th style="padding: 1.25rem;">Payment Mode</th>
                        <th style="padding: 1.25rem;">Amount</th>
                        <th style="padding: 1.25rem;">Collected By</th>
                        <th style="padding: 1.25rem;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 4rem; color: var(--text-muted);">No transactions found matching your filters.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($transactions as $t): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1.25rem;">
                            <div style="font-weight: 700; color: var(--primary);"><?php echo $t['receipt_number']; ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;"><?php echo date('d M, Y', strtotime($t['payment_date'])); ?></div>
                        </td>
                        <td style="padding: 1.25rem;">
                            <div style="font-weight: 600;"><?php echo $t['student_name']; ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $t['enrollment_no']; ?> • <?php echo $t['course_name']; ?></div>
                        </td>
                        <td style="padding: 1.25rem;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo $t['payment_mode'] == 'Cash' ? '#10b981' : '#3b82f6'; ?>;"></span>
                                <span style="font-weight: 500;"><?php echo $t['payment_mode']; ?></span>
                            </div>
                            <?php if ($t['transaction_id']): ?>
                                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 4px;">ID: <?php echo $t['transaction_id']; ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1.25rem;">
                            <div style="font-weight: 800; color: var(--success); font-size: 1.05rem;">₹<?php echo number_format($t['amount_paid'], 2); ?></div>
                        </td>
                        <td style="padding: 1.25rem;">
                            <div style="font-size: 0.875rem;"><?php echo $t['collected_by_name'] ?: 'System'; ?></div>
                        </td>
                        <td style="padding: 1.25rem;">
                            <span style="padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                <?php echo $t['payment_status']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="no-print" style="padding: 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
            <div style="font-size: 0.875rem; color: var(--text-muted);">
                Showing <strong><?php echo min($total_records, $offset + 1); ?>-<?php echo min($total_records, $offset + $limit); ?></strong> of <strong><?php echo $total_records; ?></strong> transactions
            </div>
            <div class="pagination" style="display: flex; gap: 8px;">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="btn" style="padding: 0.5rem 1rem; background: white; border: 1px solid var(--border); font-size: 0.8125rem;"><i data-lucide="chevron-left" size="16"></i> Previous</a>
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="btn" style="padding: 0.5rem 1rem; background: white; border: 1px solid var(--border); font-size: 0.8125rem;">Next <i data-lucide="chevron-right" size="16"></i></a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<style>
    @media print {
        /* Hide everything with no-print class */
        .sidebar, .header, .no-print, .btn {
            display: none !important;
        }
        
        body {
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .admin-content {
            margin-left: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            width: 100% !important;
        }

        table {
            width: 100% !important;
            border: 1px solid #eee !important;
        }

        th {
            background: #f1f5f9 !important;
            color: black !important;
            -webkit-print-color-adjust: exact;
        }

        /* Show a print-only title */
        .card::before {
            content: "HIMT College - Fee Transaction Report";
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: center;
        }
    }
</style>
