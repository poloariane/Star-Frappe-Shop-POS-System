<?php
require_once 'config/database.php';
requireAdmin();

$today = date('Y-m-d');
$startOfMonth = date('Y-m-01');
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Star Frappe Shop - Revenue Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7f5; min-height: 100vh; color: #1a1a1a; }
        
        .sidebar { position: fixed; left: 0; top: 0; width: 260px; height: 100vh; background: linear-gradient(180deg, #1a472a 0%, #0d2b18 100%); padding: 20px 0; z-index: 1000; overflow-y: auto; }
        .sidebar-brand { display: flex; align-items: center; padding: 0 25px 25px; color: white; font-size: 22px; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-brand img { width: 48px; height: 48px; object-fit: contain; margin-right: 12px; flex-shrink: 0; }
        .sidebar-nav { padding: 15px 0; }
        .sidebar-nav a { display: flex; align-items: center; padding: 14px 25px; color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent; font-size: 15px; }
        .sidebar-nav a i { width: 25px; margin-right: 12px; font-size: 18px; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.1); color: white; border-left-color: #ffd700; }
        .sidebar-nav .logout-link { margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; }

        .main-wrapper { margin-left: 260px; padding: 20px 30px; }
        
        .header { margin-bottom: 25px; }
        .header h1 { font-size: 24px; color: #1a472a; display: flex; align-items: center; gap: 10px; margin-bottom: 5px; }
        .header p { font-size: 14px; color: #666; }
        .header .updated-time { font-size: 12px; color: #999; margin-top: 5px; display: block; }
        
        .filter-bar { background: white; padding: 15px 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        .date-inputs { display: flex; gap: 15px; align-items: center; }
        .date-group { display: flex; flex-direction: column; }
        .date-group label { font-size: 12px; color: #666; font-weight: 600; margin-bottom: 5px; }
        .date-group input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; color: #333; }
        .btn-apply { background: #1a472a; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s; margin-top: 15px; }
        .btn-apply:hover { background: #2d5a27; }

        .stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-card .label { font-size: 12px; color: #666; font-weight: 600; margin-bottom: 8px; display: flex; align-items: center; gap: 5px; }
        .stat-card .label i { color: #1a472a; }
        .stat-card .value { font-size: 24px; font-weight: 700; color: #1a1a1a; }

        .report-section { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .report-section h3 { font-size: 16px; color: #1a1a1a; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .empty-msg { text-align: center; color: #999; padding: 20px 0; font-size: 14px; }

        .data-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .data-table th { text-align: left; padding: 10px; background: #f8faf8; color: #666; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .data-table td { padding: 12px 10px; border-bottom: 1px solid #f0f0f0; }
        
        @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 992px) { 
            .sidebar { width: 70px; } 
            .sidebar-brand span, .sidebar-nav a span { display: none; } 
            .sidebar-nav a i { margin-right: 0; font-size: 22px; } 
            .main-wrapper { margin-left: 70px; } 
        }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .date-inputs { flex-direction: column; align-items: stretch; width: 100%; }
            .btn-apply { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <img src="assets/logo.png" alt="Star Frappe logo">
            <span>Star Frappe</span>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="<?= $currentPage == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-chart-pie"></i> <span>Dashboard</span></a>
            <?php if (isStaff()): ?><a href="pos.php" class="<?= $currentPage == 'pos.php' ? 'active' : '' ?>"><i class="fas fa-cash-register"></i> <span>POS System</span></a><?php endif; ?>
            <?php if (isAdmin()): ?><a href="manage.php" class="<?= $currentPage == 'manage.php' ? 'active' : '' ?>"><i class="fas fa-users-cog"></i> <span>Manage Staff/Products</span></a><?php endif; ?>
            <a href="inventory.php" class="<?= $currentPage == 'inventory.php' ? 'active' : '' ?>"><i class="fas fa-boxes"></i> <span>Inventory</span></a>
            <a href="expenses.php" class="<?= $currentPage == 'expenses.php' ? 'active' : '' ?>"><i class="fas fa-money-bill-transfer"></i> <span>Expenses</span></a>
            <a href="receipt.php" class="<?= $currentPage == 'receipt.php' ? 'active' : '' ?>"><i class="fas fa-receipt"></i> <span>Receipts</span></a>
            <?php if (isAdmin()): ?><a href="revenue_reports.php" class="<?= $currentPage == 'revenue_reports.php' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> <span>Revenue Reports</span></a><?php endif; ?>
            
            <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </nav>
    </div>

    <div class="main-wrapper">
        <div class="header">
            <h1><i class="fas fa-chart-line"></i> Revenue Reports</h1>
            <p>Shop-wide revenue, payment, product, and expense performance. (Net figure is sales less recorded expenses only.)</p>
            <span class="updated-time" id="last-updated">Updated just now &middot; refreshes every 20 seconds while this page is open.</span>
        </div>

        <div class="filter-bar">
            <form id="filterForm" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; width: 100%;">
                <div class="date-inputs">
                    <div class="date-group"><label>From:</label><input type="date" name="from" id="fromDate" value="<?= $startOfMonth ?>"></div>
                    <div class="date-group"><label>To:</label><input type="date" name="to" id="toDate" value="<?= $today ?>"></div>
                </div>
                <button type="button" class="btn-apply" onclick="loadReports()"><i class="fas fa-filter"></i> Apply</button>
            </form>
        </div>

        <div class="stats-grid" id="summaryStats">
            <div class="stat-card"><div class="label"><i class="fas fa-shopping-bag"></i> Shop Sales</div><div class="value" id="val-shop-sales">0</div></div>
            <div class="stat-card"><div class="label"><i class="fas fa-money-bill-wave"></i> Gross Revenue</div><div class="value" id="val-gross-revenue">₱0.00</div></div>
            <div class="stat-card"><div class="label"><i class="fas fa-receipt"></i> Shop Expenses</div><div class="value" id="val-expenses">₱0.00</div></div>
            <div class="stat-card"><div class="label"><i class="fas fa-chart-pie"></i> Revenue After Expenses</div><div class="value" id="val-net">₱0.00</div></div>
            <div class="stat-card"><div class="label"><i class="fas fa-calculator"></i> Average Sale</div><div class="value" id="val-average">₱0.00</div></div>
        </div>

        <div class="report-section">
            <h3>Daily Revenue</h3>
            <div id="daily-revenue-container"><p class="empty-msg">No sales found for this period.</p></div>
        </div>

        <div class="report-section">
            <h3>Revenue by Payment Method</h3>
            <div id="payment-method-container"><p class="empty-msg">No payment data found for this period.</p></div>
        </div>

        <div class="report-section">
            <h3>Top-Selling Products</h3>
            <div id="top-products-container"><p class="empty-msg">No product sales found for this period.</p></div>
        </div>

        <div class="report-section">
            <h3>Shop Sales</h3>
            <div id="sales-list-container"><p class="empty-msg">No completed sales found for this period.</p></div>
        </div>

        <div class="report-section">
            <h3>Shop Expenses</h3>
            <div id="expenses-list-container"><p class="empty-msg">No expenses recorded for this period.</p></div>
        </div>
    </div>

    <script>
        async function loadReports() {
            const from = document.getElementById('fromDate').value;
            const to = document.getElementById('toDate').value;
            
            document.getElementById('last-updated').textContent = 'Updating...';

            try {
                const res = await fetch(`api/revenue_reports.php?from=${from}&to=${to}`);
                const data = await res.json();

                document.getElementById('val-shop-sales').textContent = data.summary.shop_sales;
                document.getElementById('val-gross-revenue').textContent = '₱' + parseFloat(data.summary.gross_revenue).toLocaleString('en-US', {minimumFractionDigits: 2});
                document.getElementById('val-expenses').textContent = '₱' + parseFloat(data.summary.total_expenses).toLocaleString('en-US', {minimumFractionDigits: 2});
                document.getElementById('val-net').textContent = '₱' + parseFloat(data.summary.revenue_after_expenses).toLocaleString('en-US', {minimumFractionDigits: 2});
                document.getElementById('val-average').textContent = '₱' + parseFloat(data.summary.average_sale).toLocaleString('en-US', {minimumFractionDigits: 2});

                const daily = data.daily_revenue;
                if (daily.length > 0) {
                    let html = '<table class="data-table"><thead><tr><th>Date</th><th>Revenue</th></tr></thead><tbody>';
                    daily.forEach(d => { html += `<tr><td>${new Date(d.date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'})}</td><td>₱${parseFloat(d.revenue).toLocaleString('en-US', {minimumFractionDigits: 2})}</td></tr>`; });
                    document.getElementById('daily-revenue-container').innerHTML = html + '</tbody></table>';
                } else { document.getElementById('daily-revenue-container').innerHTML = '<p class="empty-msg">No sales found for this period.</p>'; }

                const pm = data.payment_methods;
                if (pm.length > 0) {
                    let html = '<table class="data-table"><thead><tr><th>Payment Method</th><th>Total Revenue</th></tr></thead><tbody>';
                    pm.forEach(p => { html += `<tr><td>${p.payment_method}</td><td>₱${parseFloat(p.total).toLocaleString('en-US', {minimumFractionDigits: 2})}</td></tr>`; });
                    document.getElementById('payment-method-container').innerHTML = html + '</tbody></table>';
                } else { document.getElementById('payment-method-container').innerHTML = '<p class="empty-msg">No payment data found for this period.</p>'; }

                const tp = data.top_products;
                if (tp.length > 0) {
                    let html = '<table class="data-table"><thead><tr><th>Product</th><th>Qty Sold</th><th>Revenue</th></tr></thead><tbody>';
                    tp.forEach(p => { html += `<tr><td>${p.product_name}</td><td>${p.qty_sold}</td><td>₱${parseFloat(p.total_revenue).toLocaleString('en-US', {minimumFractionDigits: 2})}</td></tr>`; });
                    document.getElementById('top-products-container').innerHTML = html + '</tbody></table>';
                } else { document.getElementById('top-products-container').innerHTML = '<p class="empty-msg">No product sales found for this period.</p>'; }

                const sl = data.sales_list;
                if (sl.length > 0) {
                    let html = '<table class="data-table"><thead><tr><th>Receipt #</th><th>Date</th><th>Total Amount</th></tr></thead><tbody>';
                    sl.forEach(s => { html += `<tr><td>${s.receipt_number || 'N/A'}</td><td>${new Date(s.sale_date).toLocaleDateString()}</td><td>₱${parseFloat(s.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td></tr>`; });
                    document.getElementById('sales-list-container').innerHTML = html + '</tbody></table>';
                } else { document.getElementById('sales-list-container').innerHTML = '<p class="empty-msg">No completed sales found for this period.</p>'; }

                const el = data.expenses_list;
                if (el.length > 0) {
                    let html = '<table class="data-table"><thead><tr><th>Expense</th><th>Date</th><th>Amount</th></tr></thead><tbody>';
                    el.forEach(e => { html += `<tr><td>${e.expense_name}</td><td>${new Date(e.expense_date).toLocaleDateString()}</td><td>₱${parseFloat(e.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td></tr>`; });
                    document.getElementById('expenses-list-container').innerHTML = html + '</tbody></table>';
                } else { document.getElementById('expenses-list-container').innerHTML = '<p class="empty-msg">No expenses recorded for this period.</p>'; }

                document.getElementById('last-updated').textContent = 'Updated ' + new Date().toLocaleTimeString() + ' · refreshes every 20 seconds while this page is open.';
            } catch (e) {
                console.error('Error loading reports:', e);
                document.getElementById('last-updated').textContent = 'Error updating data.';
            }
        }

        loadReports();
        setInterval(loadReports, 20000);
    </script>
</body>
</html>