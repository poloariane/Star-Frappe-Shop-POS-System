<?php
require_once '../config/database.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// ============================================
// LIST ALL PRODUCTS (with category name & stock status)
// ============================================
if ($action === 'list') {
    $stmt = $pdo->query("
        SELECT 
            p.product_id AS id,
            p.product_name AS name,
            p.price,
            p.default_variant_name,
            NULL AS variant_id,
            COALESCE(NULLIF(p.default_variant_name, ''), 'Default') AS variant_name,
            p.description,
            p.emoji,
            p.image_path,
            p.status,
            c.category_name AS category,
            c.category_id
        FROM product p
        LEFT JOIN category c ON p.category_id = c.category_id
        WHERE p.status = 'Available'

        UNION ALL

        SELECT
            p.product_id AS id,
            p.product_name AS name,
            pv.price,
            p.default_variant_name,
            pv.variant_id,
            pv.variant_name,
            p.description,
            p.emoji,
            p.image_path,
            p.status,
            c.category_name AS category,
            c.category_id
        FROM product_variant pv
        JOIN product p ON p.product_id = pv.product_id
        LEFT JOIN category c ON c.category_id = p.category_id
        WHERE p.status = 'Available'
        ORDER BY category, name, variant_name
    ");
    echo json_encode($stmt->fetchAll());
    exit;
}

// ============================================
// LIST CATEGORIES
// ============================================
if ($action === 'categories') {
    $stmt = $pdo->query("SELECT * FROM category ORDER BY category_name");
    echo json_encode($stmt->fetchAll());
    exit;
}

// ============================================
// GET SINGLE PRODUCT
// ============================================
if ($action === 'get' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("
        SELECT 
            p.product_id AS id,
            p.product_name AS name,
            p.price,
            p.description,
            p.emoji,
            p.image_path,
            p.status,
            c.category_name AS category,
            c.category_id
        FROM product p 
        LEFT JOIN category c ON p.category_id = c.category_id 
        WHERE p.product_id = ?
    ");
    $stmt->execute([$_GET['id']]);
    echo json_encode($stmt->fetch());
    exit;
}

// ============================================
// GET INGREDIENTS FOR A PRODUCT (recipe)
// ============================================
if ($action === 'ingredients' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("
        SELECT 
            pi.product_ingredient_id,
            pi.inventory_item_id,
            ii.item_name,
            pi.quantity_used,
            pi.unit,
            ii.current_stock
        FROM product_ingredient pi 
        JOIN inventory_item ii ON pi.inventory_item_id = ii.inventory_item_id 
        WHERE pi.product_id = ?
    ");
    $stmt->execute([$_GET['id']]);
    echo json_encode($stmt->fetchAll());
    exit;
}

echo json_encode(['error' => 'Invalid action']);
?>