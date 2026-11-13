<?php
require_once '../config/database.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// ============================================
// CREATE SALE
// ============================================
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!is_array($input) || empty($input['items']) || !is_array($input['items'])) {
        echo json_encode(['success' => false, 'error' => 'No items in order']);
        exit;
    }
    
    if (!is_array($input) || !isset($input['payment_method']) || !isset($input['amount_tendered'])) {
        echo json_encode(['success' => false, 'error' => 'Missing payment information']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        $staffId = $_SESSION['staff_id'];
        $paymentMethod = $input['payment_method'];
        $amountTendered = floatval($input['amount_tendered']);
        $discountAmount = floatval($input['discount_amount'] ?? 0);
        $validPaymentMethods = ['Cash', 'Card', 'E-Wallet (GCash)', 'E-Wallet (Maya)', 'E-Wallet (MariBank)', 'E-Wallet (Other E-Wallet)'];
        if (!in_array($paymentMethod, $validPaymentMethods, true)) {
            throw new InvalidArgumentException('Choose a valid payment method');
        }
        if (!is_finite($amountTendered) || !is_finite($discountAmount) || $amountTendered < 0 || $discountAmount < 0) {
            throw new InvalidArgumentException('Payment and discount amounts must be valid non-negative amounts');
        }

        $productStmt = $pdo->prepare("SELECT price, status FROM product WHERE product_id = ? FOR UPDATE");
        $variantStmt = $pdo->prepare("SELECT variant_name, price FROM product_variant WHERE variant_id = ? AND product_id = ?");
        $normalizedItems = [];
        $grossTotalCents = 0;
        foreach ($input['items'] as $item) {
            if (!is_array($item)) {
                throw new InvalidArgumentException('The order contains an invalid item');
            }
            $productId = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($item['qty'] ?? null, FILTER_VALIDATE_INT);
            if (!$productId || !$quantity || $quantity < 1) {
                throw new InvalidArgumentException('The order contains an invalid product or quantity');
            }
            $productStmt->execute([$productId]);
            $product = $productStmt->fetch();
            if (!$product || $product['status'] !== 'Available') {
                throw new InvalidArgumentException('A product in this order is unavailable');
            }
            $variantId = null;
            $variantName = null;
            $unitPrice = (float)$product['price'];
            if (isset($item['variant_id']) && $item['variant_id'] !== '' && $item['variant_id'] !== null) {
                $variantId = filter_var($item['variant_id'], FILTER_VALIDATE_INT);
                if (!$variantId || $variantId < 1) {
                    throw new InvalidArgumentException('The order contains an invalid product variant');
                }
                $variantStmt->execute([$variantId, $productId]);
                $variant = $variantStmt->fetch();
                if (!$variant) {
                    throw new InvalidArgumentException('A product variant in this order is no longer available');
                }
                $variantName = $variant['variant_name'];
                $unitPrice = (float)$variant['price'];
            }
            $unitPriceCents = (int)round($unitPrice * 100);
            $lineGrossCents = $unitPriceCents * $quantity;
            $grossTotalCents += $lineGrossCents;
            $normalizedItems[] = [
                'id' => $productId,
                'variant_name' => $variantName,
                'qty' => $quantity,
                'unit_price_cents' => $unitPriceCents,
                'gross_cents' => $lineGrossCents
            ];
        }
        if ($grossTotalCents <= 0) {
            throw new InvalidArgumentException('The order total must be greater than zero');
        }

        $discountCents = (int)round($discountAmount * 100);
        if ($discountCents > $grossTotalCents) {
            throw new InvalidArgumentException('Discount cannot exceed the order subtotal');
        }
        $totalCents = $grossTotalCents - $discountCents;
        $amountTenderedCents = (int)round($amountTendered * 100);
        if ($amountTenderedCents < $totalCents) {
            throw new InvalidArgumentException('Amount received is less than the amount due');
        }
        if ($paymentMethod !== 'Cash' && $amountTenderedCents !== $totalCents) {
            throw new InvalidArgumentException('Non-cash payment amount must match the amount due');
        }
        $changeAmount = $paymentMethod === 'Cash' ? ($amountTenderedCents - $totalCents) / 100 : 0;
        
        $stmt = $pdo->prepare("
            INSERT INTO sale (staff_id, total_amount, status) 
            VALUES (?, ?, 'Completed')
        ");
        $stmt->execute([$staffId, $totalCents / 100]);
        $saleId = $pdo->lastInsertId();
        
        $stmtItem = $pdo->prepare("
            INSERT INTO sale_item (sale_id, product_id, variant_name, quantity, unit_price, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $allocatedDiscountCents = 0;
        $cumulativeGrossCents = 0;
        $lastItemIndex = count($normalizedItems) - 1;
        foreach ($normalizedItems as $index => $item) {
            $cumulativeGrossCents += $item['gross_cents'];
            $targetAllocatedCents = $index === $lastItemIndex
                ? $discountCents
                : (int)round($discountCents * $cumulativeGrossCents / $grossTotalCents);
            $lineDiscountCents = $targetAllocatedCents - $allocatedDiscountCents;
            $allocatedDiscountCents = $targetAllocatedCents;
            $lineNetCents = $item['gross_cents'] - $lineDiscountCents;
            $stmtItem->execute([
                $saleId,
                $item['id'],
                $item['variant_name'],
                $item['qty'],
                $item['unit_price_cents'] / 100,
                $lineNetCents / 100
            ]);
            
            deductIngredientsFromInventory($pdo, $item['id'], $item['qty'], $staffId);
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO payment (sale_id, payment_method, amount_received, change_amount) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$saleId, $paymentMethod, $amountTenderedCents / 100, $changeAmount]);
        
        $receiptNumber = generateReceiptNumber($pdo);
        $stmt = $pdo->prepare("
            INSERT INTO receipt (sale_id, receipt_number) 
            VALUES (?, ?)
        ");
        $stmt->execute([$saleId, $receiptNumber]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'sale_id' => $saleId, 
            'receipt_number' => $receiptNumber
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ============================================
// VOID SALE
// ============================================
if ($action === 'void' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $saleId = intval($input['sale_id'] ?? 0);
    $reason = trim($input['reason'] ?? 'No reason provided');
    
    if ($saleId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid sale ID']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT status FROM sale WHERE sale_id = ?");
    $stmt->execute([$saleId]);
    $sale = $stmt->fetch();
    
    if (!$sale) {
        echo json_encode(['success' => false, 'error' => 'Sale not found']);
        exit;
    }
    
    if ($sale['status'] === 'Void') {
        echo json_encode(['success' => false, 'error' => 'Sale is already voided']);
        exit;
    }
    
    $success = voidSale($pdo, $saleId, $reason, $_SESSION['staff_id']);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Sale voided successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to void sale']);
    }
    exit;
}

// ============================================
// LIST ALL SALES
// ============================================
if ($action === 'list') {
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
    echo json_encode($stmt->fetchAll());
    exit;
}

// ============================================
// GET SINGLE SALE
// ============================================
if ($action === 'get' && isset($_GET['id'])) {
    $saleId = intval($_GET['id']);
    
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
    
    if (!$sale) {
        echo json_encode(['error' => 'Sale not found']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            si.sale_item_id,
            si.product_id,
            p.product_name,
            p.emoji,
            si.variant_name,
            si.quantity,
            si.unit_price,
            si.subtotal
        FROM sale_item si 
        JOIN product p ON si.product_id = p.product_id 
        WHERE si.sale_id = ?
    ");
    $stmt->execute([$saleId]);
    $sale['items'] = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT * FROM payment WHERE sale_id = ?");
    $stmt->execute([$saleId]);
    $sale['payment'] = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM receipt WHERE sale_id = ?");
    $stmt->execute([$saleId]);
    $sale['receipt'] = $stmt->fetch();
    
    echo json_encode($sale);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
?>