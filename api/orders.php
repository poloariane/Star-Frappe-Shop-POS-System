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
    
    if (empty($input['items'])) {
        echo json_encode(['success' => false, 'error' => 'No items in order']);
        exit;
    }
    
    if (!isset($input['payment_method']) || !isset($input['amount_tendered'])) {
        echo json_encode(['success' => false, 'error' => 'Missing payment information']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        $staffId = $_SESSION['staff_id'];
        $total = floatval($input['total']);
        $paymentMethod = $input['payment_method'];
        $amountTendered = floatval($input['amount_tendered']);
        $changeAmount = floatval($input['change_amount'] ?? 0);
        
        $stmt = $pdo->prepare("
            INSERT INTO sale (staff_id, total_amount, status) 
            VALUES (?, ?, 'Completed')
        ");
        $stmt->execute([$staffId, $total]);
        $saleId = $pdo->lastInsertId();
        
        $stmtItem = $pdo->prepare("
            INSERT INTO sale_item (sale_id, product_id, quantity, unit_price, subtotal) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($input['items'] as $item) {
            $subtotal = floatval($item['price']) * intval($item['qty']);
            $stmtItem->execute([
                $saleId,
                intval($item['id']),
                intval($item['qty']),
                floatval($item['price']),
                $subtotal
            ]);
            
            deductIngredientsFromInventory($pdo, intval($item['id']), intval($item['qty']), $staffId);
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO payment (sale_id, payment_method, amount_received, change_amount) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$saleId, $paymentMethod, $amountTendered, $changeAmount]);
        
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