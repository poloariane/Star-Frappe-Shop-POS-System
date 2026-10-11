<?php
require_once 'config/database.php';
requireLogin();

$role = $_SESSION['role'];
$fullName = $_SESSION['full_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Star Frappe Shop - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7f5;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0; top: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, #1a472a 0%, #0d2b18 100%);
            padding: 20px 0;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            padding: 0 25px 25px;
            color: white;
            font-size: 22px;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-brand img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .sidebar-nav { padding: 15px 0; }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 14px 25px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
            font-size: 15px;
        }
        
        .sidebar-nav a i {
            width: 25px;
            margin-right: 12px;
            font-size: 18px;
        }
        
        .sidebar-nav a:hover, .sidebar-nav a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #ffd700;
        }
        
        .sidebar-nav .logout-link {
            margin-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
        }
        
        /* Main Content */
        .main-wrapper {
            margin-left: 260px;
            padding: 20px 30px;
        }
        
        /* Header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .header-left h1 {
            font-size: 24px;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-left h1 i { color: #1a472a; }
        
        .date-display {
            display: block;
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .profile-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background: #f0f5f0;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #1a472a;
            font-weight: 600;
        }
        
        .profile-btn:hover {
            background: #1a472a;
            color: white;
        }
        
        .profile-btn .avatar {
            width: 35px; height: 35px;
            border-radius: 50%;
            background: #1a472a;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        
        .profile-btn:hover .avatar {
            background: #ffd700;
            color: #1a1a1a;
        }
        
        .role-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .role-badge.Admin { background: #ffd700; color: #1a1a1a; }
        .role-badge.Staff { background: #1a472a; color: white; }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 65px; height: 65px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
        }
        
        .stat-info h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
        }
        
        /* Charts */
        .chart-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .chart-btn {
            padding: 10px 25px;
            border: 2px solid #e8f0e8;
            background: white;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            color: #666;
        }
        
        .chart-btn:hover, .chart-btn.active {
            background: #1a472a;
            color: white;
            border-color: #1a472a;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .chart-card h3 {
            font-size: 18px;
            color: #1a1a1a;
            margin-bottom: 20px;
        }
        
        .chart-card canvas { max-height: 300px; }
        
        /* Bottom Grid */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .panel {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .panel h3 {
            font-size: 18px;
            color: #1a1a1a;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .panel h3 i { color: #1a472a; }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .order-item:last-child { border-bottom: none; }
        
        .order-number { font-weight: 600; color: #1a472a; }
        .order-time { font-size: 12px; color: #999; }
        .order-amount { font-weight: 700; color: #1a1a1a; }
        
        /* Staff POS Button */
        .pos-start-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 60vh;
            text-align: center;
        }
        
        .pos-start-icon {
            font-size: 120px;
            color: #1a472a;
            margin-bottom: 30px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .pos-start-container h1 {
            font-size: 36px;
            color: #1a1a1a;
            margin-bottom: 15px;
        }
        
        .pos-start-container p {
            color: #666;
            font-size: 18px;
            margin-bottom: 40px;
        }
        
        .btn-start-pos {
            padding: 20px 60px;
            background: linear-gradient(135deg, #1a472a 0%, #2d5a27 100%);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 20px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
        }
        
        .btn-start-pos:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(26, 71, 42, 0.4);
        }
        
        @media (max-width: 1200px) {
            .charts-grid { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 992px) {
            .sidebar { width: 70px; }
            .sidebar-brand span, .sidebar-nav a span { display: none; }
            .sidebar-nav a i { margin-right: 0; font-size: 22px; }
            .main-wrapper { margin-left: 70px; }
        }
        
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .bottom-grid { grid-template-columns: 1fr; }
            .dashboard-header { flex-direction: column; gap: 15px; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <img src="assets/logo.png" alt="Star Frappe logo">
            <span>Star Frappe</span>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="active">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
            <?php if (isStaff()): ?>
            <a href="pos.php">
                <i class="fas fa-cash-register"></i>
                <span>POS System</span>
            </a>
            <?php endif; ?>
            <?php if (isAdmin()): ?>
<a href="manage.php">
    <i class="fas fa-users-cog"></i>
    <span>Manage Staff/Products</span>
</a>
<?php endif; ?>
<a href="inventory.php">
    <i class="fas fa-boxes"></i>
    <span>Inventory</span>
</a>
<a href="expenses.php">
    <i class="fas fa-money-bill-transfer"></i>
    <span>Expenses</span>
</a>
            <a href="receipt.php">
                <i class="fas fa-receipt"></i>
                <span>Receipts</span>
            </a>
            <a href="logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-wrapper">
        <header class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-chart-pie"></i> Dashboard</h1>
                <span class="date-display" id="current-date"></span>
            </div>
            <div class="header-right">
                <span class="role-badge <?= $role ?>"><?= htmlspecialchars($role) ?></span>
                <a href="profile.php" class="profile-btn">
                    <div class="avatar"><?= strtoupper(substr($fullName, 0, 1)) ?></div>
                    <span><?= htmlspecialchars($fullName) ?></span>
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </header>

        <?php if (isAdmin()): ?>
        <!-- Admin Dashboard -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #1a472a;">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Sales</h3>
                    <p id="total-orders">0</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #ffd700; color: #1a1a1a;">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Revenue</h3>
                    <p id="total-revenue">₱0.00</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #2d5a27;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>Sales Today</h3>
                    <p id="customers-today">0</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #4a7c4a;">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-info">
                    <h3>Best Seller</h3>
                    <p id="best-seller" style="font-size: 18px;">-</p>
                </div>
            </div>
        </div>

        <div class="chart-controls">
            <button class="chart-btn active" onclick="loadChart('day', event)">Today</button>
            <button class="chart-btn" onclick="loadChart('week', event)">This Week</button>
            <button class="chart-btn" onclick="loadChart('month', event)">This Month</button>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h3><i class="fas fa-chart-line"></i> Sales Overview</h3>
                <canvas id="salesChart"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-chart-bar"></i> Top Selling Items</h3>
                <canvas id="topItemsChart"></canvas>
            </div>
        </div>

        <div class="bottom-grid">
            <div class="panel">
                <h3><i class="fas fa-clock"></i> Recent Sales</h3>
                <div id="recent-orders">
                    <p style="color: #999; text-align: center; padding: 20px;">Loading...</p>
                </div>
            </div>
            <div class="panel">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <a href="manage.php" style="padding: 20px; background: #1a472a; color: white; text-decoration: none; border-radius: 12px; text-align: center;">
                        <i class="fas fa-user-plus" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                        Add Staff
                    </a>
                    <a href="manage.php#products" style="padding: 20px; background: #2d5a27; color: white; text-decoration: none; border-radius: 12px; text-align: center;">
                        <i class="fas fa-box" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                        Add Product
                    </a>
                    <a href="inventory.php" style="padding: 20px; background: #4a7c4a; color: white; text-decoration: none; border-radius: 12px; text-align: center;">
                        <i class="fas fa-boxes" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                        Inventory
                    </a>
                    <a href="expenses.php" style="padding: 20px; background: #ffd700; color: #1a1a1a; text-decoration: none; border-radius: 12px; text-align: center;">
                        <i class="fas fa-money-bill-transfer" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                        Expenses
                    </a>
                </div>
            </div>
        </div>

        <script>
        document.getElementById('current-date').textContent = new Date().toLocaleDateString('en-US', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });

        let salesChart, topItemsChart;

        async function loadDashboard() {
            try {
                const res = await fetch('api/dashboard.php?action=stats');
                const data = await res.json();
                
                document.getElementById('total-orders').textContent = data.total_sales;
                document.getElementById('total-revenue').textContent = '₱' + parseFloat(data.total_revenue).toFixed(2);
                document.getElementById('customers-today').textContent = data.sales_today;
                document.getElementById('best-seller').textContent = data.best_seller || '-';
                
                const ordersHtml = data.recent_sales.length > 0 
                    ? data.recent_sales.map(s => `
                        <div class="order-item">
                            <div>
                                <span class="order-number">RCP #${s.receipt_number || s.sale_id}</span>
                                <div class="order-time">${new Date(s.sale_date).toLocaleString()}</div>
                            </div>
                            <span class="order-amount">₱${parseFloat(s.total_amount).toFixed(2)}</span>
                        </div>
                    `).join('')
                    : '<p style="color: #999; text-align: center; padding: 20px;">No sales yet</p>';
                
                document.getElementById('recent-orders').innerHTML = ordersHtml;
                loadTopItemsChart(data.top_items);
            } catch (e) {
                console.error('Error loading dashboard:', e);
            }
        }

        async function loadChart(period, event) {
            document.querySelectorAll('.chart-btn').forEach(b => b.classList.remove('active'));
            event.target.classList.add('active');
            
            try {
                const res = await fetch('api/dashboard.php?action=chart&period=' + period);
                const data = await res.json();
                
                const ctx = document.getElementById('salesChart').getContext('2d');
                if (salesChart) salesChart.destroy();
                
                salesChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Sales (₱)',
                            data: data.values,
                            borderColor: '#1a472a',
                            backgroundColor: 'rgba(26, 71, 42, 0.1)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointBackgroundColor: '#ffd700',
                            pointBorderColor: '#1a472a',
                            pointRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { callback: value => '₱' + value }
                            }
                        }
                    }
                });
            } catch (e) {
                console.error('Error loading chart:', e);
            }
        }

        function loadTopItemsChart(items) {
            const ctx = document.getElementById('topItemsChart').getContext('2d');
            if (topItemsChart) topItemsChart.destroy();
            
            topItemsChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: items.map(i => i.product_name),
                    datasets: [{
                        data: items.map(i => i.total_qty),
                        backgroundColor: ['#1a472a', '#2d5a27', '#4a7c4a', '#ffd700', '#8fbc8f']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        loadDashboard();
        loadChart('day', {target: document.querySelector('.chart-btn')});
        </script>
        
        <?php else: ?>
        <!-- Staff Dashboard - POS Only -->
        <div class="pos-start-container">
            <i class="fas fa-cash-register pos-start-icon"></i>
            <h1>Welcome, <?= htmlspecialchars($fullName) ?>!</h1>
            <p>Ready to take orders? Click below to start the POS System.</p>
            <a href="pos.php" class="btn-start-pos">
                <i class="fas fa-play"></i>
                Start POS System
            </a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>