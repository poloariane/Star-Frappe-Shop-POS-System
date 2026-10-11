<?php
require_once 'config/database.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Star Frappe Shop - POS System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7f5;
            min-height: 100vh;
        }
        
        .pos-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Left - Menu */
        .menu-section {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .pos-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 15px 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .pos-header h1 {
            color: #1a472a;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 22px;
        }

        .pos-header .brand-logo {
            width: 42px;
            height: 42px;
            object-fit: contain;
        }
        
        .back-btn {
            padding: 10px 20px;
            background: #f0f5f0;
            color: #1a472a;
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
        
        .back-btn:hover {
            background: #1a472a;
            color: white;
        }
        
        .category-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .category-tab {
            padding: 12px 30px;
            background: white;
            border: 2px solid #e8f0e8;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            color: #666;
        }
        
        .category-tab.active, .category-tab:hover {
            background: #1a472a;
            color: white;
            border-color: #1a472a;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            border-color: #1a472a;
            box-shadow: 0 10px 30px rgba(26, 71, 42, 0.15);
        }
        
        .product-card .emoji {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .product-card h4 {
            color: #1a1a1a;
            font-size: 15px;
            margin-bottom: 5px;
        }
        
        .product-card .price {
            color: #1a472a;
            font-weight: 700;
            font-size: 18px;
        }
        
        /* Right - Cart */
        .cart-section {
            width: 420px;
            background: white;
            display: flex;
            flex-direction: column;
            box-shadow: -5px 0 20px rgba(0,0,0,0.05);
        }
        
        .cart-header {
            padding: 20px 25px;
            background: linear-gradient(135deg, #1a472a 0%, #2d5a27 100%);
            color: white;
        }
        
        .cart-header h2 {
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8faf8;
            border-radius: 12px;
            margin-bottom: 10px;
        }
        
        .cart-item-info h4 {
            color: #1a1a1a;
            font-size: 14px;
            margin-bottom: 3px;
        }
        
        .cart-item-info .item-price {
            color: #1a472a;
            font-weight: 600;
            font-size: 13px;
        }
        
        .cart-item-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .qty-btn {
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.3s;
        }
        
        .qty-btn.minus { background: #f8d7da; color: #dc3545; }
        .qty-btn.plus { background: #d4edda; color: #1a472a; }
        
        .qty-btn:hover { transform: scale(1.1); }
        
        .qty-display {
            font-weight: 700;
            min-width: 25px;
            text-align: center;
        }
        
        .empty-cart {
            text-align: center;
            color: #999;
            padding: 50px 20px;
        }
        
        .empty-cart i {
            font-size: 60px;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        .cart-footer {
            padding: 20px 25px;
            border-top: 1px solid #f0f0f0;
        }
        
        .cart-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 700;
        }
        
        .cart-total .amount {
            color: #1a472a;
            font-size: 28px;
        }
        
        .btn-done {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #1a472a 0%, #2d5a27 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-done:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(26, 71, 42, 0.3);
        }
        
        .btn-done:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .btn-clear {
            width: 100%;
            padding: 12px;
            background: #f8d7da;
            color: #dc3545;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-clear:hover {
            background: #dc3545;
            color: white;
        }
        
        @media (max-width: 900px) {
            .pos-container { flex-direction: column; }
            .cart-section { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="pos-container">
        <!-- Menu Section -->
        <div class="menu-section">
            <div class="pos-header">
                <h1><img src="assets/logo.png" alt="Star Frappe logo" class="brand-logo"> Star Frappe Shop POS</h1>
                <div>
                    <span style="color: #666; margin-right: 15px;"><?= date('M d, Y h:i A') ?></span>
                    <a href="dashboard.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            
            <div class="category-tabs" id="category-tabs">
                <button class="category-tab active" onclick="filterCategory('all', this)">All</button>
            </div>
            
            <div class="products-grid" id="products-grid">
                <p style="color:#999; grid-column: 1/-1; text-align:center; padding:40px;">Loading products...</p>
            </div>
        </div>
        
        <!-- Cart Section -->
        <div class="cart-section">
            <div class="cart-header">
                <h2><i class="fas fa-shopping-cart"></i> Current Order</h2>
            </div>
            
            <div class="cart-items" id="cart-items">
                <div class="empty-cart">
                    <i class="fas fa-cart-plus"></i>
                    <p>No items added yet</p>
                    <p style="font-size: 13px; margin-top: 5px;">Click products to add</p>
                </div>
            </div>
            
            <div class="cart-footer">
                <div class="cart-total">
                    <span>Total:</span>
                    <span class="amount" id="cart-total">₱0.00</span>
                </div>
                <button class="btn-done" id="btn-done" onclick="proceedToPayment()" disabled>
                    <i class="fas fa-check"></i>
                    Done - Proceed to Payment
                </button>
                <button class="btn-clear" onclick="clearCart()">
                    <i class="fas fa-trash"></i> Clear Cart
                </button>
            </div>
        </div>
    </div>
    
    <script>
        let products = [];
        let categories = new Set();
        let cart = [];
        let currentCategory = 'all';
        
        async function loadProducts() {
            try {
                const res = await fetch('api/products.php?action=list');
                products = await res.json();
                
                // Build unique categories for tabs
                const tabs = document.getElementById('category-tabs');
                tabs.innerHTML = '<button class="category-tab active" onclick="filterCategory(\'all\', this)">All</button>';
                
                const cats = [...new Set(products.map(p => p.category))];
                cats.forEach(cat => {
                    if (cat) {
                        const btn = document.createElement('button');
                        btn.className = 'category-tab';
                        btn.textContent = cat + 's';
                        btn.onclick = function() { filterCategory(cat, this); };
                        tabs.appendChild(btn);
                    }
                });
                
                renderProducts(products);
            } catch (e) {
                console.error('Error loading products:', e);
                document.getElementById('products-grid').innerHTML = 
                    '<p style="color:#dc3545; grid-column:1/-1; text-align:center; padding:40px;">Failed to load products</p>';
            }
        }
        
        function renderProducts(items) {
            const grid = document.getElementById('products-grid');
            if (items.length === 0) {
                grid.innerHTML = '<p style="color:#999; grid-column: 1/-1; text-align:center; padding:40px;">No products available</p>';
                return;
            }
            
            grid.innerHTML = items.map(p => `
                <div class="product-card" onclick="addToCart(${p.id}, '${p.name.replace(/'/g, "\\'")}', ${p.price})">
                    <div class="emoji">${p.emoji || '🍽️'}</div>
                    <h4>${p.name}</h4>
                    <div class="price">₱${parseFloat(p.price).toFixed(2)}</div>
                </div>
            `).join('');
        }
        
        function filterCategory(cat, btn) {
            currentCategory = cat;
            document.querySelectorAll('.category-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            if (cat === 'all') {
                renderProducts(products);
            } else {
                renderProducts(products.filter(p => p.category === cat));
            }
        }
        
        function addToCart(id, name, price) {
            const existing = cart.find(item => item.id === id);
            if (existing) {
                existing.qty++;
            } else {
                cart.push({ id, name, price: parseFloat(price), qty: 1 });
            }
            renderCart();
        }
        
        function renderCart() {
            const container = document.getElementById('cart-items');
            
            if (cart.length === 0) {
                container.innerHTML = `
                    <div class="empty-cart">
                        <i class="fas fa-cart-plus"></i>
                        <p>No items added yet</p>
                        <p style="font-size: 13px; margin-top: 5px;">Click products to add</p>
                    </div>
                `;
                document.getElementById('cart-total').textContent = '₱0.00';
                document.getElementById('btn-done').disabled = true;
                sessionStorage.removeItem('cart');
                return;
            }
            
            container.innerHTML = cart.map(item => `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <h4>${item.name}</h4>
                        <span class="item-price">₱${item.price.toFixed(2)} each</span>
                    </div>
                    <div class="cart-item-controls">
                        <button class="qty-btn minus" onclick="updateQty(${item.id}, -1)">-</button>
                        <span class="qty-display">${item.qty}</span>
                        <button class="qty-btn plus" onclick="updateQty(${item.id}, 1)">+</button>
                    </div>
                </div>
            `).join('');
            
            const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
            document.getElementById('cart-total').textContent = '₱' + total.toFixed(2);
            document.getElementById('btn-done').disabled = false;
            
            sessionStorage.setItem('cart', JSON.stringify(cart));
        }
        
        function updateQty(id, delta) {
            const item = cart.find(i => i.id === id);
            if (!item) return;
            
            item.qty += delta;
            if (item.qty <= 0) {
                cart = cart.filter(i => i.id !== id);
            }
            renderCart();
        }
        
        function clearCart() {
            if (cart.length > 0 && confirm('Clear all items from cart?')) {
                cart = [];
                renderCart();
            }
        }
        
        function proceedToPayment() {
            if (cart.length === 0) return;
            sessionStorage.setItem('cart', JSON.stringify(cart));
            window.location.href = 'payment.php';
        }
        
        loadProducts();
    </script>
</body>
</html>