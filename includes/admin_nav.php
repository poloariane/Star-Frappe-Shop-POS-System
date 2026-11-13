<?php
// Kunin ang kasalukuyang filename para ma-highlight kung saan page tayo
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* Shared Sidebar Styles */
    .sidebar { position: fixed; left: 0; top: 0; width: 260px; height: 100vh; background: linear-gradient(180deg, #1a472a 0%, #0d2b18 100%); padding: 20px 0; z-index: 1000; overflow-y: auto; }
    .sidebar-brand { display: flex; align-items: center; padding: 0 25px 25px; color: white; font-size: 22px; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.1); }
    .sidebar-brand img { width: 48px; height: 48px; object-fit: contain; margin-right: 12px; flex-shrink: 0; }
    .sidebar-nav { padding: 15px 0; }
    .sidebar-nav a { display: flex; align-items: center; padding: 14px 25px; color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent; font-size: 15px; }
    .sidebar-nav a i { width: 25px; margin-right: 12px; font-size: 18px; }
    .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.1); color: white; border-left-color: #ffd700; }
    .sidebar-nav .logout-link { margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; }
    .main-wrapper { margin-left: 260px; padding: 20px 30px; }
    @media (max-width: 992px) {
        .sidebar { width: 70px; }
        .sidebar-brand span, .sidebar-nav a span { display: none; }
        .sidebar-nav a i { margin-right: 0; font-size: 22px; }
        .main-wrapper { margin-left: 70px; }
    }
</style>

<div class="sidebar">
    <div class="sidebar-brand">
        <img src="assets/logo.png" alt="Logo">
        <span>Star Frappe</span>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="<?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-pie"></i> <span>Dashboard</span>
        </a>
        
        <?php if (isStaff()): ?>
        <a href="pos.php" class="<?= $currentPage == 'pos.php' ? 'active' : '' ?>">
            <i class="fas fa-cash-register"></i> <span>POS System</span>
        </a>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
        <a href="manage.php" class="<?= $currentPage == 'manage.php' ? 'active' : '' ?>">
            <i class="fas fa-users-cog"></i> <span>Manage Staff/Products</span>
        </a>
        <a href="inventory.php" class="<?= $currentPage == 'inventory.php' ? 'active' : '' ?>">
            <i class="fas fa-boxes"></i> <span>Inventory</span>
        </a>
        <a href="expenses.php" class="<?= $currentPage == 'expenses.php' ? 'active' : '' ?>">
            <i class="fas fa-money-bill-transfer"></i> <span>Expenses</span>
        </a>
        <a href="receipt.php" class="<?= $currentPage == 'receipt.php' ? 'active' : '' ?>">
            <i class="fas fa-receipt"></i> <span>Receipts</span>
        </a>
        <a href="revenue_reports.php" class="<?= $currentPage == 'revenue_reports.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i> <span>Revenue Reports</span>
        </a>
        <?php endif; ?>

        <?php if (isStaff()): ?>
        <a href="inventory.php" class="<?= $currentPage == 'inventory.php' ? 'active' : '' ?>">
            <i class="fas fa-boxes"></i> <span>Inventory</span>
        </a>
        <a href="expenses.php" class="<?= $currentPage == 'expenses.php' ? 'active' : '' ?>">
            <i class="fas fa-money-bill-transfer"></i> <span>Expenses</span>
        </a>
        <a href="receipt.php" class="<?= $currentPage == 'receipt.php' ? 'active' : '' ?>">
            <i class="fas fa-receipt"></i> <span>Receipts</span>
        </a>
        <a href="revenue_reports.php" class="<?= $currentPage == 'revenue_reports.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i> <span>Daily Sales Report</span>
        </a>
        <?php endif; ?>

        <a href="logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </nav>
</div>