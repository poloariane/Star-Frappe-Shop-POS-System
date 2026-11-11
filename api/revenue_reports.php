<?php
require_once '../config/database.php';
requireLogin();
requireAdmin(); // Siguraduhing admin lang ang pwedeng makakita ng reports

header('Content-Type: application/json');

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

// 1. Shop Sales & Gross Revenue
$stmt = $pdo->prepare("
    SELECT 
        COUNT(sale_id) as total_sales,
        COALESCE(SUM(total_amount), 0) as gross_revenue
    FROM sale 
    WHERE status = 'Completed' AND DATE(sale_date) BETWEEN ? AND ?
");
$stmt->execute([$from, $to]);
$salesData = $stmt->fetch();

// 2. Expenses
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) as total_expenses
    FROM expense 
    WHERE DATE(expense_date) BETWEEN ? AND ?
");
$stmt->execute([$from, $to]);
$expensesData = $stmt->fetch();
$totalExpenses = $expensesData['total_expenses'];

// 3. Averages
$grossRevenue = $salesData['gross_revenue'];
$totalSales = $salesData['total_sales'];
$revenueAfterExpenses = $grossRevenue - $totalExpenses;
$averageSale = $totalSales > 0 ? ($grossRevenue / $totalSales) : 0;

// 4. Daily Revenue
$stmt = $pdo->prepare("
    SELECT DATE(sale_date) as date, COALESCE(SUM(total_amount), 0) as revenue 
    FROM sale 
    WHERE status = 'Completed' AND DATE(sale_date) BETWEEN ? AND ?
    GROUP BY DATE(sale_date)
    ORDER BY date DESC
");
$stmt->execute([$from, $to]);
$dailyRevenue = $stmt->fetchAll();

// 5. Revenue by Payment Method
$stmt = $pdo->prepare("
    SELECT p.payment_method, COALESCE(SUM(s.total_amount), 0) as total
    FROM sale s
    JOIN payment p ON s.sale_id = p.sale_id
    WHERE s.status = 'Completed' AND DATE(s.sale_date) BETWEEN ? AND ?
    GROUP BY p.payment_method
");
$stmt->execute([$from, $to]);
$paymentMethods = $stmt->fetchAll();

// 6. Top Selling Products
$stmt = $pdo->prepare("
    SELECT pr.product_name, SUM(si.quantity) as qty_sold, SUM(si.subtotal) as total_revenue
    FROM sale_item si
    JOIN sale s ON si.sale_id = s.sale_id
    JOIN product pr ON si.product_id = pr.product_id
    WHERE s.status = 'Completed' AND DATE(s.sale_date) BETWEEN ? AND ?
    GROUP BY pr.product_name
    ORDER BY qty_sold DESC
    LIMIT 10
");
$stmt->execute([$from, $to]);
$topProducts = $stmt->fetchAll();

// 7. Shop Sales List
$stmt = $pdo->prepare("
    SELECT s.sale_id, s.sale_date, s.total_amount, r.receipt_number 
    FROM sale s
    LEFT JOIN receipt r ON s.sale_id = r.sale_id
    WHERE s.status = 'Completed' AND DATE(s.sale_date) BETWEEN ? AND ?
    ORDER BY s.sale_date DESC
");
$stmt->execute([$from, $to]);
$salesList = $stmt->fetchAll();

// 8. Shop Expenses List
$stmt = $pdo->prepare("
    SELECT expense_name, amount, expense_date 
    FROM expense 
    WHERE DATE(expense_date) BETWEEN ? AND ?
    ORDER BY expense_date DESC
");
$stmt->execute([$from, $to]);
$expensesList = $stmt->fetchAll();

echo json_encode([
    'summary' => [
        'shop_sales' => $totalSales,
        'gross_revenue' => $grossRevenue,
        'total_expenses' => $totalExpenses,
        'revenue_after_expenses' => $revenueAfterExpenses,
        'average_sale' => $averageSale
    ],
    'daily_revenue' => $dailyRevenue,
    'payment_methods' => $paymentMethods,
    'top_products' => $topProducts,
    'sales_list' => $salesList,
    'expenses_list' => $expensesList
]);
?>