<?php
session_start();

$host = 'localhost';
$dbname = 'star_frappe_shop';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// ============================================
// SESSION HELPERS
// ============================================
function isLoggedIn() {
    return isset($_SESSION['staff_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
}

function isStaff() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Staff';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit;
    }
}

// ============================================
// INVENTORY HELPERS
// ============================================

/**
 * Update inventory item status based on current stock vs reorder level
 */
function updateInventoryStatus($pdo, $inventoryItemId) {
    $stmt = $pdo->prepare("
        UPDATE inventory_item 
        SET status = CASE 
            WHEN current_stock <= 0 THEN 'Out of Stock'
            WHEN current_stock <= reorder_level THEN 'Low Stock'
            ELSE 'In Stock'
        END
        WHERE inventory_item_id = ?
    ");
    $stmt->execute([$inventoryItemId]);
}

/**
 * Deduct ingredients from inventory based on product recipe
 */
function deductIngredientsFromInventory($pdo, $productId, $quantitySold, $staffId) {
    $stmt = $pdo->prepare("
        SELECT inventory_item_id, quantity_used, unit 
        FROM product_ingredient 
        WHERE product_id = ?
    ");
    $stmt->execute([$productId]);
    $ingredients = $stmt->fetchAll();
    
    if (empty($ingredients)) {
        return;
    }
    
    foreach ($ingredients as $ing) {
        $totalUsed = $ing['quantity_used'] * $quantitySold;
        
        $stmt = $pdo->prepare("
            UPDATE inventory_item 
            SET current_stock = current_stock - ? 
            WHERE inventory_item_id = ?
        ");
        $stmt->execute([$totalUsed, $ing['inventory_item_id']]);
        
        $stmt = $pdo->prepare("
            INSERT INTO inventory_transaction 
            (inventory_item_id, staff_id, transaction_type, quantity, remarks) 
            VALUES (?, ?, 'Deduct', ?, ?)
        ");
        $stmt->execute([
            $ing['inventory_item_id'],
            $staffId,
            $totalUsed,
            "Auto-deducted from sale (Product ID: $productId, Qty: $quantitySold)"
        ]);
        
        updateInventoryStatus($pdo, $ing['inventory_item_id']);
    }
}

/**
 * Generate unique receipt number
 */
function generateReceiptNumber($pdo) {
    $date = date('Ymd');
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM receipt 
        WHERE DATE(receipt_date) = CURDATE()
    ");
    $stmt->execute();
    $count = $stmt->fetch()['count'] + 1;
    return 'RCP-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

/**
 * Void a sale — sets status to 'Void' and marks the receipt
 */
function voidSale($pdo, $saleId, $reason, $staffId) {
    try {
        $pdo->beginTransaction();
        
        // Update sale status
        $stmt = $pdo->prepare("UPDATE sale SET status = 'Void' WHERE sale_id = ?");
        $stmt->execute([$saleId]);
        
        // Update receipt
        $stmt = $pdo->prepare("
            UPDATE receipt 
            SET is_void = 1, void_reason = ?, void_date = NOW(), void_by = ? 
            WHERE sale_id = ?
        ");
        $stmt->execute([$reason, $staffId, $saleId]);
        
        // Restore inventory (reverse deductions)
        $stmt = $pdo->prepare("
            SELECT product_id, quantity FROM sale_item WHERE sale_id = ?
        ");
        $stmt->execute([$saleId]);
        $items = $stmt->fetchAll();
        
        foreach ($items as $item) {
            $stmt2 = $pdo->prepare("
                SELECT inventory_item_id, quantity_used 
                FROM product_ingredient 
                WHERE product_id = ?
            ");
            $stmt2->execute([$item['product_id']]);
            $ingredients = $stmt2->fetchAll();
            
            foreach ($ingredients as $ing) {
                $restoreQty = $ing['quantity_used'] * $item['quantity'];
                
                $stmt3 = $pdo->prepare("
                    UPDATE inventory_item 
                    SET current_stock = current_stock + ? 
                    WHERE inventory_item_id = ?
                ");
                $stmt3->execute([$restoreQty, $ing['inventory_item_id']]);
                
                $stmt4 = $pdo->prepare("
                    INSERT INTO inventory_transaction 
                    (inventory_item_id, staff_id, transaction_type, quantity, remarks) 
                    VALUES (?, ?, 'Add', ?, ?)
                ");
                $stmt4->execute([
                    $ing['inventory_item_id'],
                    $staffId,
                    $restoreQty,
                    "Restored from VOID sale #$saleId"
                ]);
                
                updateInventoryStatus($pdo, $ing['inventory_item_id']);
            }
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}
?>