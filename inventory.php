<?php
require_once 'config/database.php';
requireLogin();

$message = '';
$messageType = '';

// ADD INVENTORY ITEM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    if (!isAdmin()) { $message = 'Only admins can add items'; $messageType = 'error'; }
    else {
        $itemName = trim($_POST['item_name']);
        $unit = trim($_POST['unit']);
        $currentStock = floatval($_POST['current_stock']);
        $reorderLevel = floatval($_POST['reorder_level']);
        
        if (empty($itemName) || empty($unit)) {
            $message = 'Item name and unit are required';
            $messageType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO inventory_item (item_name, unit, current_stock, reorder_level, status) 
                    VALUES (?, ?, ?, ?, 
                        CASE 
                            WHEN ? <= 0 THEN 'Out of Stock'
                            WHEN ? <= ? THEN 'Low Stock'
                            ELSE 'In Stock'
                        END
                    )
                ");
                $stmt->execute([$itemName, $unit, $currentStock, $reorderLevel, $currentStock, $currentStock, $reorderLevel]);
                $message = 'Inventory item added successfully!';
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'Item already exists or invalid data';
                $messageType = 'error';
            }
        }
    }
}

// ADD / DEDUCT STOCK
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_stock'])) {
    $itemId = intval($_POST['item_id']);
    $type = $_POST['transaction_type'];
    $qty = floatval($_POST['quantity']);
    $remarks = trim($_POST['remarks']);
    
    if ($qty <= 0) {
        $message = 'Quantity must be greater than 0';
        $messageType = 'error';
    } elseif (!in_array($type, ['Add', 'Deduct'])) {
        $message = 'Invalid transaction type';
        $messageType = 'error';
    } else {
        try {
            $pdo->beginTransaction();
            
            $operator = $type === 'Add' ? '+' : '-';
            $stmt = $pdo->prepare("
                UPDATE inventory_item 
                SET current_stock = current_stock $operator ? 
                WHERE inventory_item_id = ?
            ");
            $stmt->execute([$qty, $itemId]);
            
            $stmt = $pdo->prepare("
                INSERT INTO inventory_transaction 
                (inventory_item_id, staff_id, transaction_type, quantity, remarks) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$itemId, $_SESSION['staff_id'], $type, $qty, $remarks]);
            
            $pdo->commit();
            
            updateInventoryStatus($pdo, $itemId);
            
            $message = "Stock $type successful!";
            $messageType = 'success';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Failed: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// ADD PRODUCT INGREDIENT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_ingredient'])) {
    if (!isAdmin()) { $message = 'Only admins can edit recipes'; $messageType = 'error'; }
    else {
        $productId = intval($_POST['product_id']);
        $itemId = intval($_POST['inventory_item_id']);
        $qtyUsed = floatval($_POST['quantity_used']);
        $unit = trim($_POST['unit']);
        
        if ($productId <= 0 || $itemId <= 0 || $qtyUsed <= 0) {
            $message = 'Please fill in all fields correctly';
            $messageType = 'error';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO product_ingredient (product_id, inventory_item_id, quantity_used, unit) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$productId, $itemId, $qtyUsed, $unit]);
            $message = 'Recipe ingredient added!';
            $messageType = 'success';
        }
    }
}

// DELETE INVENTORY ITEM
if (isset($_GET['delete_item']) && isAdmin()) {
    $stmt = $pdo->prepare("DELETE FROM inventory_item WHERE inventory_item_id = ?");
    $stmt->execute([intval($_GET['delete_item'])]);
    header('Location: inventory.php?deleted=item');
    exit;
}

// DELETE INGREDIENT
if (isset($_GET['delete_ingredient']) && isAdmin()) {
    $stmt = $pdo->prepare("DELETE FROM product_ingredient WHERE product_ingredient_id = ?");
    $stmt->execute([intval($_GET['delete_ingredient'])]);
    header('Location: inventory.php?deleted=ingredient');
    exit;
}

// LOAD DATA
$inventoryItems = $pdo->query("SELECT * FROM inventory_item ORDER BY item_name")->fetchAll();

$products = $pdo->query("
    SELECT p.product_id, p.product_name, p.emoji, c.category_name 
    FROM product p 
    LEFT JOIN category c ON p.category_id = c.category_id 
    ORDER BY c.category_name, p.product_name
")->fetchAll();

$recipeStmt = $pdo->query("
    SELECT 
        pi.product_ingredient_id,
        pi.product_id,
        pi.inventory_item_id,
        pi.quantity_used,
        pi.unit,
        p.product_name,
        ii.item_name,
        ii.current_stock
    FROM product_ingredient pi 
    JOIN product p ON pi.product_id = p.product_id 
    JOIN inventory_item ii ON pi.inventory_item_id = ii.inventory_item_id 
    ORDER BY p.product_name, ii.item_name
");
$recipes = $recipeStmt->fetchAll();

$recipesByProduct = [];
foreach ($recipes as $r) {
    $recipesByProduct[$r['product_id']]['name'] = $r['product_name'];
    $recipesByProduct[$r['product_id']]['ingredients'][] = $r;
}

$transactions = $pdo->query("
    SELECT 
        t.*,
        ii.item_name,
        ii.unit,
        s.name as staff_name
    FROM inventory_transaction t 
    JOIN inventory_item ii ON t.inventory_item_id = ii.inventory_item_id 
    LEFT JOIN staff s ON t.staff_id = s.staff_id 
    ORDER BY t.transaction_date DESC 
    LIMIT 20
")->fetchAll();

$totalItems = count($inventoryItems);
$lowStockCount = 0;
$outOfStockCount = 0;
foreach ($inventoryItems as $item) {
    if ($item['status'] === 'Out of Stock') $outOfStockCount++;
    elseif ($item['status'] === 'Low Stock') $lowStockCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Star Frappe Shop - Inventory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7f5; min-height: 100vh; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; background: white; padding: 20px 30px; border-radius: 15px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .header h1 { color: #1a472a; display: flex; align-items: center; gap: 10px; }
        .back-btn { padding: 10px 20px; background: #1a472a; color: white; border: none; border-radius: 10px; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 8px; font-weight: 600; transition: all 0.3s; }
        .back-btn:hover { background: #2d5a27; }
        .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }
        .stat-box { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 20px; }
        .stat-box .icon { width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
        .stat-box h3 { font-size: 14px; color: #666; margin-bottom: 5px; }
        .stat-box p { font-size: 28px; font-weight: 700; color: #1a1a1a; }
        .tabs { display: flex; gap: 10px; margin-bottom: 25px; flex-wrap: wrap; }
        .tab-btn { padding: 15px 30px; background: white; border: 2px solid #e8f0e8; border-radius: 12px; cursor: pointer; font-weight: 600; font-size: 15px; transition: all 0.3s; color: #666; display: flex; align-items: center; gap: 10px; }
        .tab-btn.active, .tab-btn:hover { background: #1a472a; color: white; border-color: #1a472a; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 25px; margin-bottom: 20px; }
        .card h2 { color: #1a1a1a; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 18px; }
        .card h2 i { color: #1a472a; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1.5fr; gap: 25px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; color: #1a1a1a; font-weight: 600; margin-bottom: 6px; font-size: 13px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 14px; border: 2px solid #e8f0e8; border-radius: 8px; font-size: 14px; transition: all 0.3s; background: #f8faf8; font-family: inherit; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #1a472a; background: white; box-shadow: 0 0 0 3px rgba(26, 71, 42, 0.1); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .btn-submit { width: 100%; padding: 12px; background: linear-gradient(135deg, #1a472a 0%, #2d5a27 100%); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(26, 71, 42, 0.3); }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { text-align: left; padding: 12px; background: #f8faf8; color: #666; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .data-table td { padding: 14px 12px; border-bottom: 1px solid #f0f0f0; color: #1a1a1a; font-size: 14px; }
        .data-table tr:hover { background: #f8faf8; }
        .stock-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; }
        .stock-badge.In-Stock { background: #d4edda; color: #1a472a; }
        .stock-badge.Low-Stock { background: #fff3cd; color: #856404; }
        .stock-badge.Out-of-Stock { background: #f8d7da; color: #dc3545; }
        .stock-badge.Add { background: #d4edda; color: #1a472a; }
        .stock-badge.Deduct { background: #f8d7da; color: #dc3545; }
        .inline-form { display: flex; gap: 6px; align-items: center; }
        .inline-form select, .inline-form input { padding: 6px 10px; border: 1px solid #e8f0e8; border-radius: 6px; font-size: 13px; background: white; }
        .inline-form select { width: 90px; }
        .inline-form input[type="number"] { width: 80px; }
        .btn-save { padding: 6px 12px; background: #1a472a; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; transition: all 0.3s; }
        .btn-save:hover { background: #2d5a27; }
        .btn-delete { padding: 5px 10px; background: #f8d7da; color: #dc3545; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; transition: all 0.3s; text-decoration: none; display: inline-block; }
        .btn-delete:hover { background: #dc3545; color: white; }
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert.success { background: #d4edda; color: #1a472a; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #dc3545; border: 1px solid #f5c6cb; }
        .recipe-card { background: #f8faf8; border-radius: 12px; padding: 15px; margin-bottom: 15px; border-left: 4px solid #1a472a; }
        .recipe-card h3 { color: #1a472a; margin-bottom: 10px; font-size: 15px; }
        .recipe-card .ingredients-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .ingredient-chip { background: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; color: #1a1a1a; display: inline-flex; align-items: center; gap: 6px; border: 1px solid #e8f0e8; }
        .ingredient-chip .remove { color: #dc3545; text-decoration: none; font-weight: 700; cursor: pointer; }
        .empty-state { text-align: center; padding: 40px; color: #999; }
        @media (max-width: 992px) {
            .grid-2 { grid-template-columns: 1fr; }
            .stats-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-boxes"></i> Inventory Management</h1>
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
            <?= ucfirst(htmlspecialchars($_GET['deleted'])) ?> deleted!
        </div>
    <?php endif; ?>
    
    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-box">
            <div class="icon" style="background: #1a472a;">
                <i class="fas fa-box"></i>
            </div>
            <div>
                <h3>Total Items</h3>
                <p><?= $totalItems ?></p>
            </div>
        </div>
        <div class="stat-box">
            <div class="icon" style="background: #ffc107; color: #1a1a1a;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <h3>Low Stock</h3>
                <p><?= $lowStockCount ?></p>
            </div>
        </div>
        <div class="stat-box">
            <div class="icon" style="background: #dc3545;">
                <i class="fas fa-times-circle"></i>
            </div>
            <div>
                <h3>Out of Stock</h3>
                <p><?= $outOfStockCount ?></p>
            </div>
        </div>
    </div>
    
    <!-- Tabs -->
    <div class="tabs">
        <button class="tab-btn active" onclick="showTab('items', this)">
            <i class="fas fa-cubes"></i> Inventory Items
        </button>
        <button class="tab-btn" onclick="showTab('recipes', this)">
            <i class="fas fa-utensils"></i> Product Recipes
        </button>
        <button class="tab-btn" onclick="showTab('history', this)">
            <i class="fas fa-history"></i> Transaction History
        </button>
    </div>
    
    <!-- TAB 1: ITEMS -->
    <div class="tab-content active" id="items-tab">
        <div class="grid-2">
            <?php if (isAdmin()): ?>
            <div class="card">
                <h2><i class="fas fa-plus-circle"></i> Add New Item</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Item Name *</label>
                        <input type="text" name="item_name" placeholder="e.g., Milk, Coffee Powder" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Unit *</label>
                            <input type="text" name="unit" placeholder="kg, liter, pcs" required>
                        </div>
                        <div class="form-group">
                            <label>Initial Stock</label>
                            <input type="number" name="current_stock" step="0.01" min="0" value="0">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Reorder Level (Low Stock Alert)</label>
                        <input type="number" name="reorder_level" step="0.01" min="0" value="10">
                    </div>
                    <button type="submit" name="add_item" class="btn-submit">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="card" style="<?= !isAdmin() ? 'grid-column: 1 / -1;' : '' ?>">
                <h2><i class="fas fa-list"></i> Stock Levels</h2>
                <?php if (empty($inventoryItems)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open" style="font-size: 40px; margin-bottom: 15px;"></i>
                        <p>No inventory items yet.</p>
                    </div>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Adjust</th>
                            <?php if (isAdmin()): ?><th></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventoryItems as $item): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($item['item_name']) ?></strong></td>
                            <td>
                                <strong><?= number_format($item['current_stock'], 2) ?></strong>
                                <span style="color: #999; font-size: 12px;"><?= htmlspecialchars($item['unit']) ?></span>
                                <div style="font-size: 11px; color: #999;">Min: <?= number_format($item['reorder_level'], 2) ?></div>
                            </td>
                            <td>
                                <span class="stock-badge <?= str_replace(' ', '-', $item['status']) ?>">
                                    <?= $item['status'] ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="item_id" value="<?= $item['inventory_item_id'] ?>">
                                    <select name="transaction_type">
                                        <option value="Add">Add</option>
                                        <option value="Deduct">Deduct</option>
                                    </select>
                                    <input type="number" name="quantity" step="0.01" min="0.01" placeholder="Qty" required>
                                    <input type="hidden" name="remarks" value="">
                                    <button type="submit" name="adjust_stock" class="btn-save">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            </td>
                            <?php if (isAdmin()): ?>
                            <td>
                                <a href="?delete_item=<?= $item['inventory_item_id'] ?>" class="btn-delete"
                                   onclick="return confirm('Delete <?= htmlspecialchars($item['item_name']) ?>?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- TAB 2: RECIPES -->
    <div class="tab-content" id="recipes-tab">
        <div class="grid-2">
            <?php if (isAdmin()): ?>
            <div class="card">
                <h2><i class="fas fa-plus"></i> Add Ingredient to Product</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Product *</label>
                        <select name="product_id" required>
                            <option value="">-- Select product --</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['product_id'] ?>">
                                    <?= $p['emoji'] ?> <?= htmlspecialchars($p['product_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ingredient *</label>
                        <select name="inventory_item_id" required>
                            <option value="">-- Select ingredient --</option>
                            <?php foreach ($inventoryItems as $item): ?>
                                <option value="<?= $item['inventory_item_id'] ?>">
                                    <?= htmlspecialchars($item['item_name']) ?> 
                                    (<?= number_format($item['current_stock'], 2) ?> <?= $item['unit'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Quantity Used *</label>
                            <input type="number" name="quantity_used" step="0.001" min="0.001" placeholder="0.5" required>
                        </div>
                        <div class="form-group">
                            <label>Unit *</label>
                            <input type="text" name="unit" placeholder="kg, liter, pcs" required>
                        </div>
                    </div>
                    <button type="submit" name="add_ingredient" class="btn-submit">
                        <i class="fas fa-plus"></i> Add to Recipe
                    </button>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="card" style="<?= !isAdmin() ? 'grid-column: 1 / -1;' : '' ?>">
                <h2><i class="fas fa-book"></i> Product Recipes</h2>
                <?php if (empty($recipesByProduct)): ?>
                    <div class="empty-state">
                        <i class="fas fa-utensils" style="font-size: 40px; margin-bottom: 15px;"></i>
                        <p>No recipes defined yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recipesByProduct as $productId => $data): ?>
                    <div class="recipe-card">
                        <h3>
                            <i class="fas fa-coffee" style="margin-right: 8px;"></i>
                            <?= htmlspecialchars($data['name']) ?>
                        </h3>
                        <div class="ingredients-list">
                            <?php foreach ($data['ingredients'] as $ing): ?>
                            <div class="ingredient-chip">
                                <span><?= htmlspecialchars($ing['item_name']) ?>: <?= number_format($ing['quantity_used'], 3) ?> <?= htmlspecialchars($ing['unit']) ?></span>
                                <?php if (isAdmin()): ?>
                                <a href="?delete_ingredient=<?= $ing['product_ingredient_id'] ?>" 
                                   class="remove" 
                                   onclick="return confirm('Remove this ingredient?')">×</a>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- TAB 3: HISTORY -->
    <div class="tab-content" id="history-tab">
        <div class="card">
            <h2><i class="fas fa-history"></i> Recent Stock Transactions (Last 20)</h2>
            <?php if (empty($transactions)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list" style="font-size: 40px; margin-bottom: 15px;"></i>
                    <p>No transactions yet.</p>
                </div>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Staff</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td><?= date('M d, Y h:i A', strtotime($t['transaction_date'])) ?></td>
                        <td><strong><?= htmlspecialchars($t['item_name']) ?></strong></td>
                        <td>
                            <span class="stock-badge <?= $t['transaction_type'] ?>">
                                <?= $t['transaction_type'] ?>
                            </span>
                        </td>
                        <td>
                            <?= number_format($t['quantity'], 2) ?>
                            <span style="color: #999; font-size: 12px;"><?= htmlspecialchars($t['unit']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($t['staff_name'] ?? 'N/A') ?></td>
                        <td style="font-size: 12px; color: #666;"><?= htmlspecialchars($t['remarks'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function showTab(tab, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            btn.classList.add('active');
            document.getElementById(tab + '-tab').classList.add('active');
        }
    </script>
</body>
</html>