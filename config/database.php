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

function getShopOwnerName($pdo) {
    $stmt = $pdo->query("SELECT name FROM staff WHERE role = 'Admin' ORDER BY staff_id ASC LIMIT 1");
    $ownerName = $stmt->fetchColumn();
    return $ownerName !== false ? $ownerName : 'Owner not set';
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

function getInventoryUnitDefinitions() {
    $massUnits = [
        'kg' => 1000,
        'kilogram' => 1000,
        'kilograms' => 1000,
        'g' => 1,
        'gram' => 1,
        'grams' => 1,
        'mg' => 0.001,
        'milligram' => 0.001,
        'milligrams' => 0.001,
        'lb' => 453.59237,
        'lbs' => 453.59237,
        'pound' => 453.59237,
        'pounds' => 453.59237,
        'oz' => 28.349523125,
        'ounce' => 28.349523125,
        'ounces' => 28.349523125
    ];
    $volumeUnits = [
        'l' => 1000,
        'liter' => 1000,
        'liters' => 1000,
        'litre' => 1000,
        'litres' => 1000,
        'dl' => 100,
        'deciliter' => 100,
        'deciliters' => 100,
        'decilitre' => 100,
        'decilitres' => 100,
        'cl' => 10,
        'centiliter' => 10,
        'centiliters' => 10,
        'centilitre' => 10,
        'centilitres' => 10,
        'ml' => 1,
        'milliliter' => 1,
        'milliliters' => 1,
        'millilitre' => 1,
        'millilitres' => 1
    ];
    $countUnits = [
        'pc' => 1,
        'pcs' => 1,
        'piece' => 1,
        'pieces' => 1,
        'ea' => 1,
        'each' => 1,
        'unit' => 1,
        'units' => 1
    ];
    $definitions = [];
    foreach ($massUnits as $unit => $factor) {
        $definitions[$unit] = ['dimension' => 'mass', 'factor' => $factor];
    }
    foreach ($volumeUnits as $unit => $factor) {
        $definitions[$unit] = ['dimension' => 'volume', 'factor' => $factor];
    }
    foreach ($countUnits as $unit => $factor) {
        $definitions[$unit] = ['dimension' => 'count', 'factor' => $factor];
    }
    return $definitions;
}

function convertInventoryQuantity($quantity, $fromUnit, $toUnit) {
    $quantity = (float)$quantity;
    $fromUnit = strtolower(trim((string)$fromUnit));
    $toUnit = strtolower(trim((string)$toUnit));
    if (!is_finite($quantity) || $quantity < 0 || $fromUnit === '' || $toUnit === '') {
        throw new InvalidArgumentException('Enter a valid quantity and measurement unit.');
    }

    $definitions = getInventoryUnitDefinitions();
    $fromDefinition = $definitions[$fromUnit] ?? null;
    $toDefinition = $definitions[$toUnit] ?? null;
    if ($fromDefinition === null && $toDefinition === null && $fromUnit === $toUnit) {
        return $quantity;
    }
    if ($fromDefinition === null || $toDefinition === null || $fromDefinition['dimension'] !== $toDefinition['dimension']) {
        throw new InvalidArgumentException("Cannot convert $fromUnit to $toUnit. Use compatible measurement units.");
    }

    $convertedQuantity = round($quantity * $fromDefinition['factor'] / $toDefinition['factor'], 8);
    if ($quantity > 0 && $convertedQuantity <= 0) {
        throw new InvalidArgumentException('The quantity is too small to represent in the inventory unit.');
    }

    return $convertedQuantity;
}

function formatInventoryQuantity($quantity) {
    return rtrim(rtrim(number_format((float)$quantity, 8, '.', ','), '0'), '.');
}

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
        SELECT pi.inventory_item_id, pi.quantity_used, pi.unit, ii.unit AS inventory_unit
        FROM product_ingredient pi
        JOIN inventory_item ii ON ii.inventory_item_id = pi.inventory_item_id
        WHERE pi.product_id = ?
    ");
    $stmt->execute([$productId]);
    $ingredients = $stmt->fetchAll();
    
    if (empty($ingredients)) {
        return;
    }
    
    foreach ($ingredients as $ing) {
        $quantityPerProduct = convertInventoryQuantity($ing['quantity_used'], $ing['unit'], $ing['inventory_unit']);
        $totalUsed = round($quantityPerProduct * $quantitySold, 8);
        
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
                SELECT pi.inventory_item_id, pi.quantity_used, pi.unit, ii.unit AS inventory_unit
                FROM product_ingredient pi
                JOIN inventory_item ii ON ii.inventory_item_id = pi.inventory_item_id
                WHERE pi.product_id = ?
            ");
            $stmt2->execute([$item['product_id']]);
            $ingredients = $stmt2->fetchAll();
            
            foreach ($ingredients as $ing) {
                $quantityPerProduct = convertInventoryQuantity($ing['quantity_used'], $ing['unit'], $ing['inventory_unit']);
                $restoreQty = round($quantityPerProduct * $item['quantity'], 8);
                
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