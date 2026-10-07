<?php
require_once 'config/database.php';
requireLogin();

$message = '';
$messageType = '';

// ============================================
// ADD EXPENSE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $expenseName = trim($_POST['expense_name']);
    $amount = floatval($_POST['amount']);
    $expenseDate = $_POST['expense_date'];
    $description = trim($_POST['description']);
    
    if (empty($expenseName) || $amount <= 0 || empty($expenseDate)) {
        $message = 'Please fill in all required fields correctly';
        $messageType = 'error';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO expense (staff_id, expense_name, amount, expense_date, description) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['staff_id'],
            $expenseName,
            $amount,
            $expenseDate,
            $description
        ]);
        $message = 'Expense recorded successfully!';
        $messageType = 'success';
    }
}

// ============================================
// UPDATE EXPENSE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_expense'])) {
    $expenseId = intval($_POST['expense_id']);
    $expenseName = trim($_POST['expense_name']);
    $amount = floatval($_POST['amount']);
    $expenseDate = $_POST['expense_date'];
    $description = trim($_POST['description']);
    
    if ($expenseId <= 0 || empty($expenseName) || $amount <= 0) {
        $message = 'Please fill in all required fields correctly';
        $messageType = 'error';
    } else {
        $stmt = $pdo->prepare("
            UPDATE expense 
            SET expense_name = ?, amount = ?, expense_date = ?, description = ? 
            WHERE expense_id = ?
        ");
        $stmt->execute([$expenseName, $amount, $expenseDate, $description, $expenseId]);
        $message = 'Expense updated successfully!';
        $messageType = 'success';
    }
}

// ============================================
// DELETE EXPENSE
// ============================================
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM expense WHERE expense_id = ?");
    $stmt->execute([intval($_GET['delete'])]);
    header('Location: expenses.php?deleted=1');
    exit;
}

// ============================================
// LOAD DATA
// ============================================
// Load expense being edited (if any)
$editExpense = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM expense WHERE expense_id = ?");
    $stmt->execute([intval($_GET['edit'])]);
    $editExpense = $stmt->fetch();
}

// Stats
$totalThisMonth = $pdo->query("
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM expense 
    WHERE MONTH(expense_date) = MONTH(CURDATE()) 
      AND YEAR(expense_date) = YEAR(CURDATE())
")->fetch()['total'];

$totalAllTime = $pdo->query("
    SELECT COALESCE(SUM(amount), 0) as total FROM expense
")->fetch()['total'];

$totalThisYear = $pdo->query("
    SELECT COALESCE(SUM(amount), 0) as total 
    FROM expense 
    WHERE YEAR(expense_date) = YEAR(CURDATE())
")->fetch()['total'];

// Filter by month if requested
$filterMonth = $_GET['month'] ?? '';
$whereClause = '';
$params = [];

if ($filterMonth) {
    $whereClause = "WHERE DATE_FORMAT(expense_date, '%Y-%m') = ?";
    $params[] = $filterMonth;
}

// Load expenses
$stmt = $pdo->prepare("
    SELECT 
        e.*,
        s.name as staff_name
    FROM expense e 
    LEFT JOIN staff s ON e.staff_id = s.staff_id 
    $whereClause
    ORDER BY e.expense_date DESC, e.expense_id DESC
");
$stmt->execute($params);
$expenses = $stmt->fetchAll();

// Build list of months that have expenses (for filter dropdown)
$months = $pdo->query("
    SELECT DISTINCT DATE_FORMAT(expense_date, '%Y-%m') as ym,
           DATE_FORMAT(expense_date, '%M %Y') as month_label
    FROM expense 
    ORDER BY ym DESC
")->fetchAll();

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
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7f5;
            min-height: 100vh;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .header h1 {
            color: #1a472a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .back-btn {
            padding: 10px 20px;
            background: #1a472a;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .back-btn:hover { background: #2d5a27; }
        
        /* Stats */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-box {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .stat-box .icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .stat-box h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .stat-box p {
            font-size: 26px;
            font-weight: 700;
            color: #1a1a1a;
        }
        
        /* Grid */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 25px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 25px;
        }
        
        .card h2 {
            color: #1a1a1a;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }
        
        .card h2 i { color: #1a472a; }
        
        .form-group { margin-bottom: 15px; }
        
        .form-group label {
            display: block;
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 13px;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e8f0e8;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background: #f8faf8;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1a472a;
            background: white;
            box-shadow: 0 0 0 3px rgba(26, 71, 42, 0.1);
        }
        
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1a472a 0%, #2d5a27 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(26, 71, 42, 0.3);
        }
        
        .btn-cancel {
            width: 100%;
            padding: 12px;
            background: #f0f0f0;
            color: #666;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-cancel:hover { background: #e0e0e0; }
        
        /* Filter */
        .list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .list-header h2 {
            margin-bottom: 0;
        }
        
        .filter-form {
            display: flex;
            gap: 8px;
        }
        
        .filter-form select {
            padding: 8px 12px;
            border: 2px solid #e8f0e8;
            border-radius: 8px;
            font-size: 13px;
            background: white;
            cursor: pointer;
        }
        
        /* Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            text-align: left;
            padding: 12px;
            background: #f8faf8;
            color: #666;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .data-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #f0f0f0;
            color: #1a1a1a;
            font-size: 14px;
        }
        
        .data-table tr:hover { background: #f8faf8; }
        
        .amount-cell {
            color: #dc3545;
            font-weight: 700;
            font-size: 15px;
        }
        
        .btn-action {
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-right: 4px;
        }
        
        .btn-edit {
            background: #e8f0e8;
            color: #1a472a;
        }
        
        .btn-edit:hover { background: #1a472a; color: white; }
        
        .btn-delete {
            background: #f8d7da;
            color: #dc3545;
        }
        
        .btn-delete:hover { background: #dc3545; color: white; }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert.success { background: #d4edda; color: #1a472a; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #dc3545; border: 1px solid #f5c6cb; }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 50px;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        .edit-mode-badge {
            display: inline-block;
            background: #ffd700;
            color: #1a1a1a;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 10px;
        }
        
        @media (max-width: 992px) {
            .grid-2 { grid-template-columns: 1fr; }
            .stats-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-money-bill-transfer"></i> Expenses</h1>
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    
    <?php if ($message): ?>
        <div class="alert <?= $messageType ?>">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert success">
            <i class="fas fa-check-circle"></i>
            Expense deleted successfully!
        </div>
    <?php endif; ?>
    
    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-box">
            <div class="icon" style="background: #dc3545;">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div>
                <h3>This Month</h3>
                <p>₱<?= number_format($totalThisMonth, 2) ?></p>
            </div>
        </div>
        <div class="stat-box">
            <div class="icon" style="background: #ffc107; color: #1a1a1a;">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div>
                <h3>This Year</h3>
                <p>₱<?= number_format($totalThisYear, 2) ?></p>
            </div>
        </div>
        <div class="stat-box">
            <div class="icon" style="background: #1a472a;">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <h3>All Time</h3>
                <p>₱<?= number_format($totalAllTime, 2) ?></p>
            </div>
        </div>
    </div>
    
    <div class="grid-2">
        <!-- ============================================
             FORM (Add or Edit)
        ============================================ -->
        <div class="card">
            <h2>
                <i class="fas fa-<?= $editExpense ? 'edit' : 'plus-circle' ?>"></i>
                <?= $editExpense ? 'Edit Expense' : 'Add New Expense' ?>
                <?php if ($editExpense): ?>
                    <span class="edit-mode-badge">EDITING #<?= $editExpense['expense_id'] ?></span>
                <?php endif; ?>
            </h2>
            
            <form method="POST">
                <?php if ($editExpense): ?>
                    <input type="hidden" name="expense_id" value="<?= $editExpense['expense_id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Expense Name *</label>
                    <input type="text" name="expense_name" 
                           placeholder="e.g., Electricity Bill, Rent, Supplies" 
                           value="<?= htmlspecialchars($editExpense['expense_name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Amount (₱) *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" 
                           placeholder="0.00" 
                           value="<?= $editExpense['amount'] ?? '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Expense Date *</label>
                    <input type="date" name="expense_date" 
                           value="<?= htmlspecialchars($editExpense['expense_date'] ?? $today) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Description / Notes</label>
                    <textarea name="description" rows="3" 
                              placeholder="Optional details..."><?= htmlspecialchars($editExpense['description'] ?? '') ?></textarea>
                </div>
                
                <?php if ($editExpense): ?>
                    <button type="submit" name="update_expense" class="btn-submit">
                        <i class="fas fa-save"></i> Update Expense
                    </button>
                    <a href="expenses.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel Edit
                    </a>
                <?php else: ?>
                    <button type="submit" name="add_expense" class="btn-submit">
                        <i class="fas fa-plus"></i> Add Expense
                    </button>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- ============================================
             LIST
        ============================================ -->
        <div class="card">
            <div class="list-header">
                <h2><i class="fas fa-list"></i> Expense Records (<?= count($expenses) ?>)</h2>
                
                <form method="GET" class="filter-form">
                    <select name="month" onchange="this.form.submit()">
                        <option value="">All Months</option>
                        <?php foreach ($months as $m): ?>
                            <option value="<?= $m['ym'] ?>" <?= $filterMonth === $m['ym'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['month_label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($filterMonth): ?>
                        <a href="expenses.php" class="btn-action btn-edit" style="text-decoration: none;">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <?php if (empty($expenses)): ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <p>No expenses recorded yet.</p>
                    <p style="font-size: 13px; margin-top: 10px;">Use the form to add your first expense.</p>
                </div>
            <?php else: ?>
            <div style="max-height: 600px; overflow-y: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Expense</th>
                            <th>Amount</th>
                            <th>Recorded By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $e): ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($e['expense_date'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($e['expense_name']) ?></strong>
                                <?php if ($e['description']): ?>
                                    <div style="font-size: 12px; color: #999; margin-top: 3px;">
                                        <?= htmlspecialchars($e['description']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="amount-cell">₱<?= number_format($e['amount'], 2) ?></td>
                            <td style="font-size: 12px; color: #666;">
                                <?= htmlspecialchars($e['staff_name'] ?? 'N/A') ?>
                            </td>
                            <td>
                                <a href="?edit=<?= $e['expense_id'] ?>" class="btn-action btn-edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                               <?php if (isAdmin()): ?>
<a href="?delete=<?= $e['expense_id'] ?>" class="btn-action btn-delete"
   onclick="return confirm('Delete this expense?')">
    <i class="fas fa-trash"></i>
</a>
<?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>