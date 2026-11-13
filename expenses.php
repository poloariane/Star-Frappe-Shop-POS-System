<?php
require_once 'config/database.php';
requireLogin();

$message = ''; $messageType = '';
$currentPage = basename($_SERVER['PHP_SELF']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $expenseName = trim($_POST['expense_name']); $amount = floatval($_POST['amount']); $expenseDate = $_POST['expense_date']; $description = trim($_POST['description']);
    if (empty($expenseName) || $amount <= 0 || empty($expenseDate)) { $message = 'Please fill in all required fields correctly'; $messageType = 'error'; } 
    else {
        $stmt = $pdo->prepare("INSERT INTO expense (staff_id, expense_name, amount, expense_date, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['staff_id'], $expenseName, $amount, $expenseDate, $description]);
        $message = 'Expense recorded successfully!'; $messageType = 'success';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_expense'])) {
    $expenseId = intval($_POST['expense_id']); $expenseName = trim($_POST['expense_name']); $amount = floatval($_POST['amount']); $expenseDate = $_POST['expense_date']; $description = trim($_POST['description']);
    if ($expenseId <= 0 || empty($expenseName) || $amount <= 0) { $message = 'Please fill in all required fields correctly'; $messageType = 'error'; } 
    else {
        $stmt = $pdo->prepare("UPDATE expense SET expense_name = ?, amount = ?, expense_date = ?, description = ? WHERE expense_id = ?");
        $stmt->execute([$expenseName, $amount, $expenseDate, $description, $expenseId]);
        $message = 'Expense updated successfully!'; $messageType = 'success';
    }
}

if (isset($_GET['delete'])) { $stmt = $pdo->prepare("DELETE FROM expense WHERE expense_id = ?"); $stmt->execute([intval($_GET['delete'])]); header('Location: expenses.php?deleted=1'); exit; }

$editExpense = null;
if (isset($_GET['edit'])) { $stmt = $pdo->prepare("SELECT * FROM expense WHERE expense_id = ?"); $stmt->execute([intval($_GET['edit'])]); $editExpense = $stmt->fetch(); }

$totalThisMonth = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM expense WHERE MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())")->fetch()['total'];
$totalAllTime = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM expense")->fetch()['total'];
$totalThisYear = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM expense WHERE YEAR(expense_date) = YEAR(CURDATE())")->fetch()['total'];

$filterFrom = $_GET['from'] ?? date('Y-m-01');
$filterTo = $_GET['to'] ?? date('Y-m-d');
foreach ([$filterFrom, $filterTo] as $filterDate) {
    $dateParts = explode('-', (string)$filterDate);
    if (count($dateParts) !== 3 || !checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0]) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$filterDate)) {
        http_response_code(400);
        exit('Invalid expense date filter');
    }
}
if ($filterFrom > $filterTo) {
    http_response_code(400);
    exit('The start date must be on or before the end date');
}

$stmt = $pdo->prepare("SELECT e.*, s.name as staff_name FROM expense e LEFT JOIN staff s ON e.staff_id = s.staff_id WHERE DATE(e.expense_date) BETWEEN ? AND ? ORDER BY e.expense_date DESC, e.expense_id DESC");
$stmt->execute([$filterFrom, $filterTo]);
$expenses = $stmt->fetchAll();
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Star Frappe Shop - Expenses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7f5; min-height: 100vh; }
        
        .sidebar { position: fixed; left: 0; top: 0; width: 260px; height: 100vh; background: linear-gradient(180deg, #1a472a 0%, #0d2b18 100%); padding: 20px 0; z-index: 1000; overflow-y: auto; }
        .sidebar-brand { display: flex; align-items: center; padding: 0 25px 25px; color: white; font-size: 22px; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-brand > .shop-logo-trigger { display: flex; align-items: center; color: inherit; text-decoration: none; }
        .sidebar-brand img { width: 48px; height: 48px; object-fit: contain; margin-right: 12px; flex-shrink: 0; }
        .sidebar-nav { padding: 15px 0; }
        .sidebar-nav a { display: flex; align-items: center; padding: 14px 25px; color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent; font-size: 15px; }
        .sidebar-nav a i { width: 25px; margin-right: 12px; font-size: 18px; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.1); color: white; border-left-color: #ffd700; }
        .sidebar-nav .logout-link { margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; }

        .main-wrapper { margin-left: 260px; padding: 20px 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: white; padding: 20px 30px; border-radius: 15px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .header h1 { color: #1a472a; display: flex; align-items: center; gap: 10px; }
        
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-box { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 20px; }
        .stat-box .icon { width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
        .stat-box h3 { font-size: 14px; color: #666; margin-bottom: 5px; }
        .stat-box p { font-size: 26px; font-weight: 700; color: #1a1a1a; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1.5fr; gap: 25px; }
        .card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 25px; }
        .card h2 { color: #1a1a1a; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 18px; }
        .card h2 i { color: #1a472a; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; color: #1a1a1a; font-weight: 600; margin-bottom: 6px; font-size: 13px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px 14px; border: 2px solid #e8f0e8; border-radius: 8px; font-size: 14px; transition: all 0.3s; background: #f8faf8; font-family: inherit; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: #1a472a; background: white; box-shadow: 0 0 0 3px rgba(26, 71, 42, 0.1); }
        
        .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #1a472a 0%, #2d5a27 100%); color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(26, 71, 42, 0.3); }
        .btn-cancel { width: 100%; padding: 12px; background: #f0f0f0; color: #666; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; margin-top: 8px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-cancel:hover { background: #e0e0e0; }
        
        .list-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .list-header h2 { margin-bottom: 0; }
        .filter-form { display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap; }
        .filter-form label { display: flex; flex-direction: column; gap: 4px; color: #666; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .filter-form input { padding: 8px 10px; border: 2px solid #e8f0e8; border-radius: 8px; font-size: 13px; background: white; }
        .filter-form button { padding: 9px 13px; border: 0; border-radius: 8px; background: #1a472a; color: white; cursor: pointer; font-weight: 600; }
        
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { text-align: left; padding: 12px; background: #f8faf8; color: #666; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .data-table td { padding: 14px 12px; border-bottom: 1px solid #f0f0f0; color: #1a1a1a; font-size: 14px; }
        .data-table tr:hover { background: #f8faf8; }
        .amount-cell { color: #dc3545; font-weight: 700; font-size: 15px; }
        
        .btn-action { padding: 6px 10px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; transition: all 0.3s; text-decoration: none; display: inline-block; margin-right: 4px; }
        .btn-edit { background: #e8f0e8; color: #1a472a; }
        .btn-edit:hover { background: #1a472a; color: white; }
        .btn-delete { background: #f8d7da; color: #dc3545; }
        .btn-delete:hover { background: #dc3545; color: white; }
        
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert.success { background: #d4edda; color: #1a472a; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #dc3545; border: 1px solid #f5c6cb; }
        
        .empty-state { text-align: center; padding: 40px; color: #999; }
        .empty-state i { font-size: 50px; margin-bottom: 15px; color: #ddd; }
        .edit-mode-badge { display: inline-block; background: #ffd700; color: #1a1a1a; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; margin-left: 10px; }
        
        @media (max-width: 992px) { 
            .sidebar { width: 70px; } 
            .sidebar-brand span, .sidebar-nav a span { display: none; } 
            .sidebar-nav a i { margin-right: 0; font-size: 22px; } 
            .main-wrapper { margin-left: 70px; } 
            .grid-2 { grid-template-columns: 1fr; } 
            .stats-row { grid-template-columns: 1fr; } 
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <a href="#shop-about" class="shop-logo-trigger" aria-label="About Star Frappe"><img src="assets/logo.png" alt="Star Frappe logo"><span>Star Frappe</span></a>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="<?= $currentPage == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-chart-pie"></i> <span>Dashboard</span></a>
            <?php if (isStaff()): ?><a href="pos.php" class="<?= $currentPage == 'pos.php' ? 'active' : '' ?>"><i class="fas fa-cash-register"></i> <span>POS System</span></a><?php endif; ?>
            <?php if (isAdmin()): ?><a href="manage.php" class="<?= $currentPage == 'manage.php' ? 'active' : '' ?>"><i class="fas fa-users-cog"></i> <span>Manage Staff/Products</span></a><?php endif; ?>
            <a href="inventory.php" class="<?= $currentPage == 'inventory.php' ? 'active' : '' ?>"><i class="fas fa-boxes"></i> <span>Inventory</span></a>
            <a href="expenses.php" class="<?= $currentPage == 'expenses.php' ? 'active' : '' ?>"><i class="fas fa-money-bill-transfer"></i> <span>Expenses</span></a>
            <a href="receipt.php" class="<?= $currentPage == 'receipt.php' ? 'active' : '' ?>"><i class="fas fa-receipt"></i> <span>Receipts</span></a>
            <a href="revenue_reports.php" class="<?= $currentPage == 'revenue_reports.php' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> <span><?= isAdmin() ? 'Revenue Reports' : 'Daily Sales Report' ?></span></a>
            
            <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </nav>
        <?php require 'includes/shop_sidebar_account.php'; ?>
    </div>
    <?php require 'includes/mobile_navigation.php'; ?>

    <div class="main-wrapper">
        <div class="header">
            <h1><i class="fas fa-money-bill-transfer"></i> Expenses</h1>
        </div>
        
        <?php if ($message): ?><div class="alert <?= $messageType ?>"><i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i> <?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?><div class="alert success"><i class="fas fa-check-circle"></i> Expense deleted successfully!</div><?php endif; ?>
        
        <div class="stats-row">
            <div class="stat-box"><div class="icon" style="background: #dc3545;"><i class="fas fa-calendar-day"></i></div><div><h3>This Month</h3><p>₱<?= number_format($totalThisMonth, 2) ?></p></div></div>
            <div class="stat-box"><div class="icon" style="background: #ffc107; color: #1a1a1a;"><i class="fas fa-calendar-alt"></i></div><div><h3>This Year</h3><p>₱<?= number_format($totalThisYear, 2) ?></p></div></div>
            <div class="stat-box"><div class="icon" style="background: #1a472a;"><i class="fas fa-chart-line"></i></div><div><h3>All Time</h3><p>₱<?= number_format($totalAllTime, 2) ?></p></div></div>
        </div>
        
        <div class="grid-2">
            <div class="card">
                <h2>
                    <i class="fas fa-<?= $editExpense ? 'edit' : 'plus-circle' ?>"></i>
                    <?= $editExpense ? 'Edit Expense' : 'Add New Expense' ?>
                    <?php if ($editExpense): ?><span class="edit-mode-badge">EDITING #<?= $editExpense['expense_id'] ?></span><?php endif; ?>
                </h2>
                <form method="POST">
                    <?php if ($editExpense): ?><input type="hidden" name="expense_id" value="<?= $editExpense['expense_id'] ?>"><?php endif; ?>
                    <div class="form-group"><label>Expense Name *</label><input type="text" name="expense_name" placeholder="e.g., Electricity Bill" value="<?= htmlspecialchars($editExpense['expense_name'] ?? '') ?>" required></div>
                    <div class="form-group"><label>Amount (₱) *</label><input type="number" name="amount" step="0.01" min="0.01" placeholder="0.00" value="<?= $editExpense['amount'] ?? '' ?>" required></div>
                    <div class="form-group"><label>Expense Date *</label><input type="date" name="expense_date" value="<?= htmlspecialchars($editExpense['expense_date'] ?? $today) ?>" required></div>
                    <div class="form-group"><label>Description / Notes</label><textarea name="description" rows="3" placeholder="Optional details..."><?= htmlspecialchars($editExpense['description'] ?? '') ?></textarea></div>
                    <?php if ($editExpense): ?>
                        <button type="submit" name="update_expense" class="btn-submit"><i class="fas fa-save"></i> Update Expense</button>
                        <a href="expenses.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel Edit</a>
                    <?php else: ?>
                        <button type="submit" name="add_expense" class="btn-submit"><i class="fas fa-plus"></i> Add Expense</button>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="card">
                <div class="list-header">
                    <h2><i class="fas fa-list"></i> Expense Records (<?= count($expenses) ?>)</h2>
                    <form method="GET" class="filter-form">
                        <label>From<input type="date" name="from" value="<?= htmlspecialchars($filterFrom) ?>" required></label>
                        <label>To<input type="date" name="to" value="<?= htmlspecialchars($filterTo) ?>" required></label>
                        <button type="submit"><i class="fas fa-filter"></i> Filter</button>
                        <a href="expenses.php" class="btn-action btn-edit" style="text-decoration: none;"><i class="fas fa-rotate-left"></i> This Month</a>
                    </form>
                </div>
                <?php if (empty($expenses)): ?>
                    <div class="empty-state"><i class="fas fa-receipt"></i><p>No expenses recorded yet.</p></div>
                <?php else: ?>
                <div style="max-height: 600px; overflow-y: auto;">
                    <table class="data-table">
                        <thead><tr><th>Date</th><th>Expense</th><th>Amount</th><th>Recorded By</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($expenses as $e): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($e['expense_date'])) ?></td>
                                <td><strong><?= htmlspecialchars($e['expense_name']) ?></strong><?php if ($e['description']): ?><div style="font-size: 12px; color: #999; margin-top: 3px;"><?= htmlspecialchars($e['description']) ?></div><?php endif; ?></td>
                                <td class="amount-cell">₱<?= number_format($e['amount'], 2) ?></td>
                                <td style="font-size: 12px; color: #666;"><?= htmlspecialchars($e['staff_name'] ?? 'N/A') ?></td>
                                <td>
                                    <a href="?edit=<?= $e['expense_id'] ?>" class="btn-action btn-edit"><i class="fas fa-edit"></i></a>
                                    <?php if (isAdmin()): ?><a href="?delete=<?= $e['expense_id'] ?>" class="btn-action btn-delete" onclick="return confirm('Delete this expense?')"><i class="fas fa-trash"></i></a><?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php require 'includes/shop_about.php'; ?>
</body>
</html>