<?php
require_once 'config/database.php';
requireLogin();

$saleId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$showSingle = $saleId > 0;

$sale = null;
$items = [];
$payment = null;
$receipt = null;

if ($showSingle) {
    $stmt = $pdo->prepare("
        SELECT 
            s.sale_id,
            s.sale_date,
            s.total_amount,
            s.status,
            st.name as staff_name
        FROM sale s 
        LEFT JOIN staff st ON s.staff_id = st.staff_id 
        WHERE s.sale_id = ?
    ");
    $stmt->execute([$saleId]);
    $sale = $stmt->fetch();
    
    if ($sale) {
        $stmt = $pdo->prepare("
            SELECT 
                si.quantity,
                si.unit_price,
                si.subtotal,
                p.product_name,
                p.emoji
            FROM sale_item si 
            JOIN product p ON si.product_id = p.product_id 
            WHERE si.sale_id = ?
        ");
        $stmt->execute([$saleId]);
        $items = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("SELECT * FROM payment WHERE sale_id = ?");
        $stmt->execute([$saleId]);
        $payment = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT * FROM receipt WHERE sale_id = ?");
        $stmt->execute([$saleId]);
        $receipt = $stmt->fetch();
    }
}

// Load all sales for listing
$stmt = $pdo->query("
    SELECT 
        s.sale_id,
        s.sale_date,
        s.total_amount,
        s.status,
        st.name as staff_name,
        r.receipt_number,
        p.payment_method
    FROM sale s 
    LEFT JOIN staff st ON s.staff_id = st.staff_id 
    LEFT JOIN receipt r ON s.sale_id = r.sale_id 
    LEFT JOIN payment p ON s.sale_id = p.sale_id 
    ORDER BY s.sale_date DESC 
    LIMIT 100
");
$allSales = $stmt->fetchAll();

// Count completed and voided
$completedCount = 0;
$voidCount = 0;
foreach ($allSales as $s) {
    if ($s['status'] === 'Void') $voidCount++;
    else $completedCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Star Frappe Shop - Receipts</title>
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
        
        /* Stats Cards */
        .stats-row {
            max-width: 900px;
            margin: 0 auto 25px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .stat-box {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .stat-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .stat-box.completed {
            border-left-color: #1a472a;
        }
        
        .stat-box.void {
            border-left-color: #dc3545;
        }
        
        .stat-box .icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: white;
        }
        
        .stat-box.completed .icon { background: #1a472a; }
        .stat-box.void .icon { background: #dc3545; }
        
        .stat-box h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .stat-box p {
            font-size: 32px;
            font-weight: 700;
            color: #1a1a1a;
        }
        
        /* Receipt View */
        .receipt-container {
            max-width: 420px;
            margin: 0 auto 30px;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .receipt-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px dashed #ddd;
            margin-bottom: 20px;
        }
        
        .receipt-header h2 {
            color: #1a472a;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .receipt-header .brand-logo {
            display: block;
            width: 160px;
            max-width: 100%;
            height: auto;
            margin: 0 auto 10px;
        }
        
        .receipt-header p {
            color: #666;
            font-size: 13px;
        }
        
        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 13px;
            color: #666;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .receipt-items { margin-bottom: 20px; }
        
        .receipt-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }
        
        .receipt-item .name { color: #1a1a1a; }
        .receipt-item .price { color: #1a472a; font-weight: 600; }
        
        .receipt-divider {
            border-top: 2px dashed #ddd;
            margin: 15px 0;
        }
        
        .receipt-total {
            display: flex;
            justify-content: space-between;
            font-size: 20px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 15px;
        }
        
        .receipt-total .amount { color: #1a472a; }
        
        .receipt-payment {
            font-size: 13px;
            color: #666;
            padding: 15px 0;
            border-top: 1px dashed #ddd;
        }
        
        .receipt-payment div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .receipt-footer {
            text-align: center;
            padding-top: 20px;
            border-top: 2px dashed #ddd;
            margin-top: 20px;
            color: #999;
            font-size: 12px;
        }
        
        .receipt-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .receipt-actions button,
        .receipt-actions a {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn-print { background: #1a472a; color: white; }
        .btn-print:hover { background: #2d5a27; }
        
        .btn-new-order { background: #ffd700; color: #1a1a1a; }
        .btn-new-order:hover { background: #e6c200; }
        
        .btn-void {
            background: #dc3545;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.3s;
            width: 100%;
            margin-top: 15px;
        }
        
        .btn-void:hover { background: #b02a37; }
        
        /* Void banner */
        .void-banner {
            background: #f8d7da;
            border: 2px dashed #dc3545;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
            text-align: center;
        }
        
        .void-banner .void-text {
            font-size: 28px;
            font-weight: 900;
            color: #dc3545;
            letter-spacing: 3px;
            margin-bottom: 8px;
        }
        
        /* Void modal */
        .void-modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 3000;
            align-items: center;
            justify-content: center;
        }
        
        .void-modal-overlay.show { display: flex; }
        
        .void-modal {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 450px;
            width: 90%;
            text-align: center;
        }
        
        .void-modal i.void-icon {
            font-size: 50px;
            color: #dc3545;
            margin-bottom: 15px;
        }
        
        .void-modal h3 {
            color: #1a1a1a;
            margin-bottom: 10px;
        }
        
        .void-modal p {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .void-modal textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e8f0e8;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            margin-bottom: 20px;
            resize: vertical;
        }
        
        .void-modal textarea:focus {
            outline: none;
            border-color: #dc3545;
        }
        
        .void-modal label {
            display: block;
            text-align: left;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 6px;
            font-size: 13px;
        }
        
        .void-modal-buttons {
            display: flex;
            gap: 10px;
        }
        
        .void-modal-buttons button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            font-size: 14px;
        }
        
        .void-modal-cancel {
            background: #f0f0f0;
            color: #666;
        }
        
        .void-modal-cancel:hover { background: #e0e0e0; }
        
        .void-modal-confirm {
            background: #dc3545;
            color: white;
        }
        
        .void-modal-confirm:hover { background: #b02a37; }
        
        /* Orders List */
        .orders-list {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .orders-list h2 {
            color: #1a1a1a;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .orders-list h2 i { color: #1a472a; }
        
        .order-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        
        .order-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-left: 4px solid #1a472a;
        }
        
        .order-card.void-card {
            opacity: 0.7;
            border-left: 4px solid #dc3545;
        }
        
        .order-info h4 {
            color: #1a472a;
            margin-bottom: 5px;
        }
        
        .order-info p {
            color: #666;
            font-size: 13px;
        }
        
        .void-badge {
            background: #dc3545;
            color: white;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            margin-left: 8px;
        }
        
        .order-right { text-align: right; }
        
        .order-right .amount {
            font-size: 20px;
            font-weight: 700;
            color: #1a472a;
        }
        
        .order-right .amount.void-amount {
            text-decoration: line-through;
            color: #999;
        }
        
        .order-right .method {
            font-size: 12px;
            color: #999;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
            background: white;
            border-radius: 15px;
        }
        
        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            color: #ddd;
        }
        
        @media print {
            body { background: white; padding: 0; }
            .header, .orders-list, .receipt-actions, .btn-void, .stats-row { display: none !important; }
            .receipt-container { 
                box-shadow: none; 
                margin: 0; 
                max-width: 100%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-receipt"></i> Receipts</h1>
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    
    <?php if ($showSingle && $sale): ?>
    <!-- Single Receipt View -->
    <div class="receipt-container" id="receipt">
        <div class="receipt-header">
            <img src="assets/logo.png" alt="Star Frappe logo" class="brand-logo">
            <p>Point of Sale Receipt</p>
        </div>
        
        <div class="receipt-info">
            <span><strong><?= htmlspecialchars($receipt['receipt_number'] ?? 'N/A') ?></strong></span>
            <span><?= date('M d, Y h:i A', strtotime($sale['sale_date'])) ?></span>
        </div>
        
        <div class="receipt-info">
            <span>Cashier: <?= htmlspecialchars($sale['staff_name'] ?? 'N/A') ?></span>
            <span><?= htmlspecialchars($payment['payment_method'] ?? 'N/A') ?></span>
        </div>
        
        <div class="receipt-divider"></div>
        
        <div class="receipt-items">
            <?php foreach ($items as $item): ?>
            <div class="receipt-item">
                <div>
                    <div class="name"><?= htmlspecialchars($item['product_name']) ?></div>
                    <div style="font-size: 12px; color: #999;">
                        x<?= $item['quantity'] ?> @ ₱<?= number_format($item['unit_price'], 2) ?>
                    </div>
                </div>
                <span class="price">₱<?= number_format($item['subtotal'], 2) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="receipt-divider"></div>
        
        <div class="receipt-total">
            <span>TOTAL:</span>
            <span class="amount">₱<?= number_format($sale['total_amount'], 2) ?></span>
        </div>
        
        <div class="receipt-payment">
            <div>
                <span>Amount Tendered:</span>
                <span>₱<?= number_format($payment['amount_received'] ?? 0, 2) ?></span>
            </div>
            <div>
                <span>Change:</span>
                <span>₱<?= number_format($payment['change_amount'] ?? 0, 2) ?></span>
            </div>
        </div>
        
        <?php if (isset($receipt['is_void']) && $receipt['is_void']): ?>
            <!-- VOID BANNER -->
            <div class="void-banner">
                <div class="void-text">⚠ VOIDED ⚠</div>
                <?php if ($receipt['void_reason']): ?>
                    <p style="color: #721c24; font-size: 13px; margin-top: 5px;">
                        <strong>Reason:</strong> <?= htmlspecialchars($receipt['void_reason']) ?>
                    </p>
                <?php endif; ?>
                <?php if ($receipt['void_date']): ?>
                    <p style="color: #721c24; font-size: 12px; margin-top: 5px;">
                        Voided on <?= date('M d, Y h:i A', strtotime($receipt['void_date'])) ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="receipt-footer">
                <p>Thank you for your purchase! 🌟</p>
                <p style="margin-top: 10px;">Please come again!</p>
            </div>
        <?php endif; ?>
        
        <div class="receipt-actions">
            <button class="btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="pos.php" class="btn-new-order">
                <i class="fas fa-plus"></i> New Order
            </a>
        </div>
        
        <?php if (!isset($receipt['is_void']) || !$receipt['is_void']): ?>
        <button class="btn-void" onclick="openVoidModal()">
            <i class="fas fa-ban"></i> Void This Order
        </button>
        
        <!-- Void Modal -->
        <div class="void-modal-overlay" id="void-modal">
            <div class="void-modal">
                <i class="fas fa-exclamation-triangle void-icon"></i>
                <h3>Void This Order?</h3>
                <p>This will mark the order as <strong>Void</strong> and restore all deducted inventory.</p>
                
                <label>Reason for voiding *</label>
                <textarea id="void-reason" rows="3" placeholder="e.g., Customer changed order, wrong item, etc."></textarea>
                
                <div class="void-modal-buttons">
                    <button class="void-modal-cancel" onclick="closeVoidModal()">Cancel</button>
                    <button class="void-modal-confirm" onclick="confirmVoid(<?= $sale['sale_id'] ?>)">
                        <i class="fas fa-ban"></i> Void Order
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        function openVoidModal() {
            document.getElementById('void-modal').classList.add('show');
            document.getElementById('void-reason').focus();
        }
        
        function closeVoidModal() {
            document.getElementById('void-modal').classList.remove('show');
            document.getElementById('void-reason').value = '';
        }
        
        async function confirmVoid(saleId) {
            const reason = document.getElementById('void-reason').value.trim();
            
            if (!reason) {
                alert('Please enter a reason for voiding.');
                return;
            }
            
            try {
                const res = await fetch('api/orders.php?action=void', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ sale_id: saleId, reason: reason })
                });
                
                const data = await res.json();
                
                if (data.success) {
                    alert('Order voided successfully.');
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to void'));
                }
            } catch (e) {
                alert('Failed to void order. Please try again.');
            }
        }
        </script>
        <?php endif; ?>
    </div>
    
    <?php elseif ($showSingle && !$sale): ?>
    <div class="empty-state">
        <i class="fas fa-exclamation-triangle"></i>
        <h2>Receipt Not Found</h2>
        <p>No sale found with ID #<?= $saleId ?></p>
        <br>
        <a href="receipt.php" class="back-btn" style="display: inline-flex;">
            <i class="fas fa-list"></i> View All Receipts
        </a>
    </div>
    
    <?php else: ?>
    <!-- Stats Cards (only on list view) -->
    <div class="stats-row">
        <div class="stat-box completed">
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <h3>Completed</h3>
                <p><?= $completedCount ?></p>
            </div>
        </div>
        <div class="stat-box void">
            <div class="icon">
                <i class="fas fa-ban"></i>
            </div>
            <div>
                <h3>Void</h3>
                <p><?= $voidCount ?></p>
            </div>
        </div>
    </div>
    
    <div class="orders-list">
        <h2><i class="fas fa-history"></i> Recent Receipts</h2>
        
        <?php if (empty($allSales)): ?>
            <div class="empty-state">
                <i class="fas fa-receipt"></i>
                <p>No sales yet</p>
                <p style="margin-top: 10px; font-size: 13px;">Complete an order in the POS to see receipts here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($allSales as $s): ?>
            <a href="receipt.php?id=<?= $s['sale_id'] ?>" class="order-card <?= $s['status'] === 'Void' ? 'void-card' : '' ?>">
                <div class="order-info">
                    <h4>
                        <?= htmlspecialchars($s['receipt_number'] ?? 'RCP #' . $s['sale_id']) ?>
                        <?php if ($s['status'] === 'Void'): ?>
                            <span class="void-badge">VOID</span>
                        <?php endif; ?>
                    </h4>
                    <p>
                        <?= date('M d, Y h:i A', strtotime($s['sale_date'])) ?> • 
                        Staff: <?= htmlspecialchars($s['staff_name'] ?? 'N/A') ?>
                    </p>
                </div>
                <div class="order-right">
                    <div class="amount <?= $s['status'] === 'Void' ? 'void-amount' : '' ?>">
                        ₱<?= number_format($s['total_amount'], 2) ?>
                    </div>
                    <div class="method"><?= htmlspecialchars($s['payment_method'] ?? '-') ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</body>
</html>