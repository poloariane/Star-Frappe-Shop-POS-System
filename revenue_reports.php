<?php
require_once 'config/database.php';
requireLogin();

$isAdmin = isAdmin();
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
        
        /* SIDEBAR (Sticky) */
        .sidebar { position: fixed; left: 0; top: 0; width: 260px; height: 100vh; background: linear-gradient(180deg, #1a472a 0%, #0d2b18 100%); padding: 20px 0; z-index: 1000; overflow-y: auto; }
        .sidebar-brand { display: flex; align-items: center; padding: 0 25px 25px; color: white; font-size: 22px; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-brand > .shop-logo-trigger { display: flex; align-items: center; color: inherit; text-decoration: none; }
        .sidebar-brand img { width: 48px; height: 48px; object-fit: contain; margin-right: 12px; flex-shrink: 0; }
        .sidebar-nav { padding: 15px 0; }
        .sidebar-nav a { display: flex; align-items: center; padding: 14px 25px; color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent; font-size: 15px; }
        .sidebar-nav a i { width: 25px; margin-right: 12px; font-size: 18px; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.1); color: white; border-left-color: #ffd700; }
        .sidebar-nav .logout-link { margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; }

        /* MAIN CONTENT */
        .main-wrapper { margin-left: 260px; padding: 20px 30px; }
        
        /* HEADER */
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; background: white; padding: 25px 30px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); }
        .dashboard-header h1 { font-size: 24px; color: #1a472a; display: flex; align-items: center; gap: 10px; margin-bottom: 5px; }
        .dashboard-header p { font-size: 14px; color: #666; }
        .dashboard-header .updated-time { font-size: 12px; color: #999; margin-top: 5px; display: block; font-style: italic; }
        
        /* FILTER BAR */
        .filter-bar { background: white; padding: 20px 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .date-inputs { display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap; }
        .date-group { display: flex; flex-direction: column; }
        .date-group label { font-size: 12px; color: #666; font-weight: 600; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .date-group input { padding: 10px 15px; border: 2px solid #e8f0e8; border-radius: 8px; font-family: inherit; color: #333; outline: none; transition: all 0.3s; background: #f8faf8; }
        .date-group input:focus { border-color: #1a472a; background: white; box-shadow: 0 0 0 3px rgba(26, 71, 42, 0.1); }
        .btn-apply { background: #1a472a; color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s; margin-top: 0; }
        .btn-apply:hover { background: #2d5a27; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(26, 71, 42, 0.2); }

        /* STATS GRID */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px 20px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); position: relative; overflow: hidden; transition: all 0.3s; border-bottom: 4px solid transparent; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
        
        /* Color coding for stats */
        .stat-card:nth-child(1) { border-bottom-color: #1a472a; }
        .stat-card:nth-child(2) { border-bottom-color: #ffd700; }
        .stat-card:nth-child(3) { border-bottom-color: #dc3545; }
        .stat-card:nth-child(4) { border-bottom-color: #4a7c4a; }
        .stat-card:nth-child(5) { border-bottom-color: #2d5a27; }

        .stat-card .label { font-size: 12px; color: #666; font-weight: 600; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card .label i { font-size: 16px; color: #1a472a; background: #f0f5f0; padding: 6px; border-radius: 8px; }
        .stat-card .value { font-size: 26px; font-weight: 800; color: #1a1a1a; letter-spacing: -0.5px; }

        /* REPORT SECTIONS (Tables) */
        .report-section { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 25px; }
        .report-section h3 { font-size: 18px; color: #1a1a1a; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f0f5f0; display: flex; align-items: center; gap: 10px; }
        .report-section h3::before { content: ''; display: block; width: 5px; height: 20px; background: #1a472a; border-radius: 3px; }
        .empty-msg { text-align: center; color: #999; padding: 30px 0; font-size: 14px; font-style: italic; background: #f8faf8; border-radius: 10px; border: 1px dashed #ddd; }

        /* TABLES */
        .data-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .data-table th { text-align: left; padding: 15px 12px; background: #f8faf8; color: #1a472a; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e8f0e8; }
        .data-table td { padding: 15px 12px; border-bottom: 1px solid #f0f0f0; color: #333; font-weight: 500; transition: background 0.2s; }
        .data-table tr:hover td { background: #f8faf8; color: #1a472a; }
        
        /* Make amounts bold and dark green */
        .data-table td:last-child { font-weight: 700; color: #1a1a1a; }
        .filter-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .report-date-row { display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap; width: 100%; }
        .report-export-row { width: 100%; }
        .btn-export { background: #2d5a27; color: white; border: none; padding: 12px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-export:hover { background: #1a472a; }
        .sales-summary { grid-template-columns: repeat(3, minmax(180px, 280px)); }
        .report-location { margin-top: 6px; color: #777 !important; font-size: 13px !important; }
        .report-modal { display: none; position: fixed; inset: 0; z-index: 3000; align-items: center; justify-content: center; padding: 20px; overflow-y: auto; overscroll-behavior: contain; background: rgba(0,0,0,.5); }
        .report-modal.show { display: flex; }
        .report-modal-card { width: min(440px, 100%); max-height: 100%; overflow-y: auto; padding: 26px; border-radius: 14px; background: white; box-shadow: 0 15px 50px rgba(0,0,0,.2); }
        .report-modal-card h2 { margin-bottom: 8px; color: #1a472a; font-size: 21px; }
        .report-modal-card p { margin-bottom: 20px; color: #687168; font-size: 13px; }
        .report-modal-card label { display: block; margin: 14px 0 6px; color: #555; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .report-modal-card select, .report-modal-card input { width: 100%; padding: 11px 12px; border: 2px solid #e8f0e8; border-radius: 8px; font: inherit; }
        .report-modal-actions { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-top: 22px; }
        .report-modal-actions button { min-width: 0; justify-content: center; margin: 0; white-space: normal; }
        .report-modal-actions .btn-cancel { padding: 12px 16px; border: 0; border-radius: 8px; background: #edf1ed; color: #485248; font-weight: 600; cursor: pointer; }
        
        /* RESPONSIVE */
        @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 992px) { 
            .sidebar { width: 70px; } 
            .sidebar-brand span, .sidebar-nav a span { display: none; } 
            .sidebar-nav a i { margin-right: 0; font-size: 22px; } 
            .main-wrapper { margin-left: 70px; } 
        }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .date-inputs { flex-direction: column; align-items: stretch; }
            .date-group, .date-group input { width: 100%; }
            .dashboard-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .report-date-row { align-items: stretch; }
            .report-date-row .btn-apply { justify-content: center; }
            .report-modal { align-items: flex-start; padding: max(12px, env(safe-area-inset-top)) 12px max(12px, env(safe-area-inset-bottom)); }
            .report-modal-card { width: 100%; max-height: calc(100vh - 24px); max-height: calc(100dvh - 24px); margin: auto 0; padding: 22px 18px; -webkit-overflow-scrolling: touch; }
            .report-modal-actions { grid-template-columns: 1fr 1fr; }
            .report-modal-actions .btn-cancel { grid-column: 1 / -1; }
            .report-export-row .filter-actions, .report-export-row .btn-export { width: 100%; justify-content: center; }
        }
        @media (max-width: 420px) {
            .report-modal { padding-right: 8px; padding-left: 8px; }
            .report-modal-card { padding: 18px 14px; border-radius: 12px; }
            .report-modal-card h2 { font-size: 19px; }
            .report-modal-actions { grid-template-columns: 1fr; }
            .report-modal-actions .btn-cancel { grid-column: auto; }
        }
        @media (max-height: 560px) and (max-width: 900px) {
            .report-modal { align-items: flex-start; padding-top: 8px; padding-bottom: 8px; }
            .report-modal-card { max-height: calc(100vh - 16px); max-height: calc(100dvh - 16px); margin: 0; }
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
            <a href="revenue_reports.php" class="<?= $currentPage == 'revenue_reports.php' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> <span><?= $isAdmin ? 'Revenue Reports' : 'Daily Sales Report' ?></span></a>
            
            <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </nav>
        <?php require 'includes/shop_sidebar_account.php'; ?>
    </div>
    <?php require 'includes/mobile_navigation.php'; ?>

    <div class="main-wrapper">
        <!-- Header -->
        <div class="dashboard-header">
            <div>
                <h1><i class="fas fa-chart-line"></i> <?= $isAdmin ? 'Revenue Reports' : 'Daily Sales Report' ?></h1>
                <p><?= $isAdmin ? 'Shop-wide revenue, payment, product, and expense performance. (Net figure is sales less recorded expenses only.)' : 'Review completed sales for a selected day.' ?></p>
                <p class="report-location"><i class="fas fa-location-dot"></i> 135 Asinas Avenue, UEP 1, Catarman, Northern Samar</p>
            </div>
            <span class="updated-time" id="last-updated"><i class="fas fa-sync-alt fa-spin"></i> Updating data...</span>
        </div>

        <!-- Filter -->
        <div class="filter-bar">
            <form id="filterForm" style="display: flex; flex-direction: column; gap: 12px; align-items: flex-start; flex-wrap: wrap; width: 100%;">
                <div class="report-date-row">
                <div class="date-inputs">
                    <?php if ($isAdmin): ?>
                    <div class="date-group"><label>From Date</label><input type="date" name="from" id="fromDate" value="<?= $startOfMonth ?>"></div>
                    <div class="date-group"><label>To Date</label><input type="date" name="to" id="toDate" value="<?= $today ?>"></div>
                    <?php else: ?>
                    <div class="date-group"><label>Sales Date</label><input type="date" name="date" id="salesDate" value="<?= $today ?>" max="<?= $today ?>"></div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn-apply" onclick="loadReports()"><i class="fas fa-filter"></i> Apply Filter</button>
                </div>
                <?php if ($isAdmin): ?>
                <div class="report-export-row">
                    <div class="filter-actions">
                    <button type="button" class="btn-export" onclick="openReportModal()"><i class="fas fa-file-arrow-down"></i> Print / Export Report</button>
                </div>
                </div>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($isAdmin): ?>
        <div class="report-modal" id="reportModal" role="dialog" aria-modal="true" aria-labelledby="reportModalTitle">
            <div class="report-modal-card">
                <h2 id="reportModalTitle">Sales report options</h2>
                <p>Choose the report period and whether to print or download a PDF.</p>
                <label for="reportPeriod">Report period</label>
                <select id="reportPeriod">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="annual">Annual</option>
                </select>
                <label for="reportDate">Report date</label>
                <input type="date" id="reportDate" value="<?= $today ?>" max="<?= $today ?>">
                <div class="report-modal-actions">
                    <button type="button" class="btn-export" onclick="openReportOutput('print')"><i class="fas fa-print"></i> Print</button>
                    <button type="button" class="btn-export" onclick="openReportOutput('pdf')"><i class="fas fa-file-pdf"></i> Export PDF</button>
                    <button type="button" class="btn-cancel" onclick="closeReportModal()">Cancel</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid <?= $isAdmin ? '' : 'sales-summary' ?>" id="summaryStats">
            <div class="stat-card">
                <div class="label"><i class="fas fa-shopping-bag"></i> Shop Sales</div>
                <div class="value" id="val-shop-sales">0</div>
            </div>
            <div class="stat-card">
                <div class="label"><i class="fas fa-money-bill-wave"></i> Gross Revenue</div>
                <div class="value" id="val-gross-revenue">₱0.00</div>
            </div>
            <?php if (!$isAdmin): ?>
            <div class="stat-card">
                <div class="label"><i class="fas fa-chart-pie"></i> Net Revenue</div>
                <div class="value" id="val-net">₱0.00</div>
            </div>
            <?php endif; ?>
            <?php if ($isAdmin): ?>
            <div class="stat-card">
                <div class="label"><i class="fas fa-receipt"></i> Total Expenses</div>
                <div class="value" id="val-expenses">₱0.00</div>
            </div>
            <div class="stat-card">
                <div class="label"><i class="fas fa-chart-pie"></i> Net Revenue</div>
                <div class="value" id="val-net">₱0.00</div>
            </div>
            <div class="stat-card">
                <div class="label"><i class="fas fa-calculator"></i> Average Sale</div>
                <div class="value" id="val-average">₱0.00</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sections -->
        <?php if ($isAdmin): ?>
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
        <?php endif; ?>

        <div class="report-section">
            <h3><?= $isAdmin ? 'Shop Sales Details' : 'Completed Sales for Selected Day' ?></h3>
            <div id="sales-list-container"><p class="empty-msg">No completed sales found for this period.</p></div>
        </div>

        <?php if ($isAdmin): ?>
        <div class="report-section">
            <h3>Shop Expenses</h3>
            <div id="expenses-list-container"><p class="empty-msg">No expenses recorded for this period.</p></div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        const isAdmin = <?= json_encode($isAdmin) ?>;

        function openReportModal() {
            const selectedDate = isAdmin
                ? document.getElementById('toDate').value
                : document.getElementById('salesDate').value;
            document.getElementById('reportDate').value = selectedDate || '<?= $today ?>';
            document.getElementById('reportModal').classList.add('show');
        }

        function closeReportModal() {
            document.getElementById('reportModal').classList.remove('show');
        }

        function openReportOutput(format) {
            const params = new URLSearchParams({
                period: document.getElementById('reportPeriod').value,
                date: document.getElementById('reportDate').value
            });
            if (format === 'pdf') {
                window.location.href = `api/revenue_reports.php?export=pdf&${params.toString()}`;
            } else {
                window.open(`sales_report_print.php?${params.toString()}`, '_blank', 'noopener');
            }
            closeReportModal();
        }

        document.getElementById('reportModal')?.addEventListener('click', function (event) {
            if (event.target === this) {
                closeReportModal();
            }
        });

        async function loadReports() {
            const query = isAdmin
                ? new URLSearchParams({
                    from: document.getElementById('fromDate').value,
                    to: document.getElementById('toDate').value
                })
                : new URLSearchParams({date: document.getElementById('salesDate').value});
            document.getElementById('last-updated').innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Updating data...';

            try {
                const res = await fetch(`api/revenue_reports.php?${query.toString()}`);
                if (!res.ok) {
                    throw new Error(`Report request failed (${res.status})`);
                }
                const data = await res.json();

                document.getElementById('val-shop-sales').textContent = data.summary.shop_sales;
                document.getElementById('val-gross-revenue').textContent = '₱' + parseFloat(data.summary.gross_revenue).toLocaleString('en-US', {minimumFractionDigits: 2});
                if (!isAdmin) {
                    document.getElementById('val-net').textContent = '₱' + parseFloat(data.summary.revenue_after_expenses).toLocaleString('en-US', {minimumFractionDigits: 2});
                }

                if (isAdmin) {
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
                        pm.forEach(p => { html += `<tr><td><i class="fas fa-${p.payment_method === 'Cash' ? 'money-bill' : 'mobile-alt'}" style="color:#1a472a; margin-right:8px;"></i> ${p.payment_method}</td><td>₱${parseFloat(p.total).toLocaleString('en-US', {minimumFractionDigits: 2})}</td></tr>`; });
                        document.getElementById('payment-method-container').innerHTML = html + '</tbody></table>';
                    } else { document.getElementById('payment-method-container').innerHTML = '<p class="empty-msg">No payment data found for this period.</p>'; }

                    const tp = data.top_products;
                    if (tp.length > 0) {
                        let html = '<table class="data-table"><thead><tr><th>Product</th><th>Qty Sold</th><th>Revenue</th></tr></thead><tbody>';
                        tp.forEach(p => { html += `<tr><td><strong>${p.product_name}</strong></td><td><span style="background:#e8f0e8; color:#1a472a; padding:3px 10px; border-radius:12px; font-weight:700;">${p.qty_sold}</span></td><td>₱${parseFloat(p.total_revenue).toLocaleString('en-US', {minimumFractionDigits: 2})}</td></tr>`; });
                        document.getElementById('top-products-container').innerHTML = html + '</tbody></table>';
                    } else { document.getElementById('top-products-container').innerHTML = '<p class="empty-msg">No product sales found for this period.</p>'; }

                    const el = data.expenses_list;
                    if (el.length > 0) {
                        let html = '<table class="data-table"><thead><tr><th>Expense Description</th><th>Date</th><th>Amount</th></tr></thead><tbody>';
                        el.forEach(e => { html += `<tr><td>${e.expense_name}</td><td>${new Date(e.expense_date).toLocaleDateString()}</td><td style="color:#dc3545;">- ₱${parseFloat(e.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td></tr>`; });
                        document.getElementById('expenses-list-container').innerHTML = html + '</tbody></table>';
                    } else { document.getElementById('expenses-list-container').innerHTML = '<p class="empty-msg">No expenses recorded for this period.</p>'; }
                }

                const sl = data.sales_list;
                if (sl.length > 0) {
                    let html = '<table class="data-table"><thead><tr><th>Receipt #</th><th>Date & Time</th><th>Total Amount</th></tr></thead><tbody>';
                    sl.forEach(s => { html += `<tr><td><span style="font-family:monospace; font-size:13px; color:#666;">${s.receipt_number || 'N/A'}</span></td><td>${new Date(s.sale_date).toLocaleString()}</td><td>₱${parseFloat(s.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td></tr>`; });
                    document.getElementById('sales-list-container').innerHTML = html + '</tbody></table>';
                } else { document.getElementById('sales-list-container').innerHTML = '<p class="empty-msg">No completed sales found for this period.</p>'; }

                document.getElementById('last-updated').innerHTML = `<i class="fas fa-check-circle" style="color:#1a472a;"></i> Last updated: ${new Date().toLocaleTimeString()}${isAdmin ? ' (Auto-refreshes every 20s)' : ''}`;
            } catch (e) {
                console.error('Error loading reports:', e);
                document.getElementById('last-updated').innerHTML = '<span style="color:#dc3545;"><i class="fas fa-exclamation-circle"></i> Error updating data. ' + e.message + '</span>';
            }
        }

        // Initial Load
        loadReports();
        
        // Auto refresh every 20 seconds
        if (isAdmin) {
            setInterval(loadReports, 20000);
        }
    </script>
<?php require 'includes/shop_about.php'; ?>
</body>
</html>