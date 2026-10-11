<?php
require_once '../config/database.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// ============================================
// STATS — for the dashboard stat cards + recent sales
// ============================================
if ($action === 'stats') {
    // Total sales count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sale WHERE status = 'Completed'");
    $totalSales = $stmt->fetch()['total'];
    
    // Total revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM sale WHERE status = 'Completed'");
    $totalRevenue = $stmt->fetch()['total'];
    
    // Sales today
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM sale WHERE DATE(sale_date) = CURDATE() AND status = 'Completed'");
    $salesToday = $stmt->fetch()['total'];
    
    // Best seller (by quantity sold)
    $stmt = $pdo->query("
        SELECT p.product_name, SUM(si.quantity) as total_qty 
        FROM sale_item si 
        JOIN sale s ON si.sale_id = s.sale_id 
        JOIN product p ON si.product_id = p.product_id 
        WHERE s.status = 'Completed' 
        GROUP BY p.product_name 
        ORDER BY total_qty DESC 
        LIMIT 1
    ");
    $bestSeller = $stmt->fetch();
    
    // Recent sales (5 most recent, with receipt number)
    $stmt = $pdo->query("
        SELECT 
            s.sale_id,
            s.total_amount,
            s.sale_date,
            st.name as staff_name,
            r.receipt_number
        FROM sale s 
        LEFT JOIN staff st ON s.staff_id = st.staff_id 
        LEFT JOIN receipt r ON s.sale_id = r.sale_id 
        WHERE s.status = 'Completed' 
        ORDER BY s.sale_date DESC 
        LIMIT 5
    ");
    $recentSales = $stmt->fetchAll();
    
    // Top items for doughnut chart (top 5)
    $stmt = $pdo->query("
        SELECT p.product_name, SUM(si.quantity) as total_qty 
        FROM sale_item si 
        JOIN sale s ON si.sale_id = s.sale_id 
        JOIN product p ON si.product_id = p.product_id 
        WHERE s.status = 'Completed' 
        GROUP BY p.product_name 
        ORDER BY total_qty DESC 
        LIMIT 5
    ");
    $topItems = $stmt->fetchAll();
    
    echo json_encode([
        'total_sales'    => $totalSales,
        'total_revenue'  => $totalRevenue,
        'sales_today'    => $salesToday,
        'best_seller'    => $bestSeller ? $bestSeller['product_name'] : null,
        'recent_sales'   => $recentSales,
        'top_items'      => $topItems
    ]);
    exit;
}

// ============================================
// CHART — sales data for day/week/month views
// ============================================
if ($action === 'chart') {
    $period = $_GET['period'] ?? 'day';
    
    if ($period === 'day') {
        // Hourly sales for today
        $stmt = $pdo->query("
            SELECT HOUR(sale_date) as hour, COALESCE(SUM(total_amount), 0) as total 
            FROM sale 
            WHERE DATE(sale_date) = CURDATE() AND status = 'Completed' 
            GROUP BY HOUR(sale_date) 
            ORDER BY hour
        ");
        $results = $stmt->fetchAll();
        
        $labels = [];
        $values = [];
        for ($i = 0; $i < 24; $i++) {
            $labels[] = sprintf('%02d:00', $i);
            $found = false;
            foreach ($results as $r) {
                if ($r['hour'] == $i) {
                    $values[] = floatval($r['total']);
                    $found = true;
                    break;
                }
            }
            if (!$found) $values[] = 0;
        }
    } elseif ($period === 'week') {
        // Daily sales for the current week
        $stmt = $pdo->query("
            SELECT DATE(sale_date) as date, COALESCE(SUM(total_amount), 0) as total 
            FROM sale 
            WHERE YEARWEEK(sale_date, 1) = YEARWEEK(CURDATE(), 1) AND status = 'Completed' 
            GROUP BY DATE(sale_date) 
            ORDER BY date
        ");
        $results = $stmt->fetchAll();
        
        $labels = [];
        $values = [];
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $startOfWeek = strtotime('monday this week');
        
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("+$i days", $startOfWeek));
            $labels[] = $days[$i] . ' ' . date('M d', strtotime($date));
            $found = false;
            foreach ($results as $r) {
                if ($r['date'] == $date) {
                    $values[] = floatval($r['total']);
                    $found = true;
                    break;
                }
            }
            if (!$found) $values[] = 0;
        }
    } else {
        // Daily sales for the current month
        $stmt = $pdo->query("
            SELECT DAY(sale_date) as day, COALESCE(SUM(total_amount), 0) as total 
            FROM sale 
            WHERE MONTH(sale_date) = MONTH(CURDATE()) 
              AND YEAR(sale_date) = YEAR(CURDATE()) 
              AND status = 'Completed' 
            GROUP BY DAY(sale_date) 
            ORDER BY day
        ");
        $results = $stmt->fetchAll();
        
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));
        $labels = [];
        $values = [];
        
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $labels[] = $i;
            $found = false;
            foreach ($results as $r) {
                if ($r['day'] == $i) {
                    $values[] = floatval($r['total']);
                    $found = true;
                    break;
                }
            }
            if (!$found) $values[] = 0;
        }
    }
    
    echo json_encode(['labels' => $labels, 'values' => $values]);
    exit;
}

// ============================================
// EXPENSES SUMMARY (for admin dashboard extension)
// ============================================
if ($action === 'expenses_summary') {
    // Total expenses this month
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM expense 
        WHERE MONTH(expense_date) = MONTH(CURDATE()) 
          AND YEAR(expense_date) = YEAR(CURDATE())
    ");
    $monthlyExpenses = $stmt->fetch()['total'];
    
    echo json_encode(['monthly_expenses' => $monthlyExpenses]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
?>