<?php
require_once 'config/database.php';
requireLogin();
$currentPage = basename($_SERVER['PHP_SELF']);
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
            overflow: hidden; /* Para hindi mag-scroll ang buong page, dun lang sa menu at cart */
        }

        /* =========================================
           SIDEBAR NAVIGATION
        ========================================= */
        .sidebar { position: fixed; left: 0; top: 0; width: 260px; height: 100vh; background: linear-gradient(180deg, #1a472a 0%, #0d2b18 100%); padding: 20px 0; z-index: 1000; overflow-y: auto; }
        .sidebar-brand { display: flex; align-items: center; padding: 0 25px 25px; color: white; font-size: 22px; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-brand-link { display: flex; align-items: center; color: inherit; text-decoration: none; }
        .sidebar-brand img { width: 48px; height: 48px; object-fit: contain; margin-right: 12px; flex-shrink: 0; }
        .sidebar-nav { padding: 15px 0; }
        .sidebar-nav a { display: flex; align-items: center; padding: 14px 25px; color: rgba(255,255,255,0.7); text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent; font-size: 15px; }
        .sidebar-nav a i { width: 25px; margin-right: 12px; font-size: 18px; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.1); color: white; border-left-color: #ffd700; }
        .sidebar-nav .logout-link { margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; }

        /* =========================================
           POS LAYOUT
        ========================================= */
        .pos-container {
            display: flex;
            height: 100vh; /* Full height */
            margin-left: 260px; /* Ibaba para hindi matakpan ng sidebar */
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
            min-height: 75px; 
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
        .pos-title-link { display: flex; align-items: center; gap: 10px; color: inherit; text-decoration: none; }
        
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
            font: inherit;
            color: inherit;
            width: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            border-color: #1a472a;
            box-shadow: 0 10px 30px rgba(26, 71, 42, 0.15);
        }
        
        .product-placeholder { display: grid; place-items: center; width: 80px; height: 80px; margin: 0 auto 10px; border-radius: 12px; background: #edf3ed; color: #1a472a; font-size: 34px; }
        
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
        .product-card .variant-label { margin: 0 0 5px; color: #687268; font-size: 13px; }
        
        /* Right - Cart */
        .cart-section {
            width: 420px;
            background: white;
            display: flex;
            flex-direction: column;
            box-shadow: -5px 0 20px rgba(0,0,0,0.05);
            height: 100vh; /* Full height */
        }
        
        .cart-header {
            padding: 20px 25px;
            background: linear-gradient(135deg, #1a472a 0%, #2d5a27 100%);
            color: white;
        }

        .cart-header-row { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .btn-clear {
            padding: 8px 10px;
            border: 1px solid rgba(255,255,255,.55);
            border-radius: 8px;
            color: white;
            background: rgba(255,255,255,.12);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-clear:hover { background: rgba(255,255,255,.22); }
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
            gap: 8px;
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
        .cart-item-main { flex: 1; min-width: 0; }
        .cart-item-info { min-width: 0; }
        .cart-item-info h4 { overflow-wrap: anywhere; }
        .remove-item {
            width: 30px;
            height: 30px;
            flex: 0 0 30px;
            border: 0;
            border-radius: 8px;
            color: #a32830;
            background: #f8d7da;
            cursor: pointer;
        }
        .remove-item:hover { color: white; background: #dc3545; }
        
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
            background: white;
        }

        .discount-field { margin-bottom: 14px; }
        .discount-field label { display: block; margin-bottom: 6px; color: #4b554b; font-size: 12px; font-weight: 700; }
        .discount-field input { width: 100%; padding: 10px 12px; border: 1px solid #dfe7df; border-radius: 8px; font: inherit; }
        .discount-field input:focus { outline: 2px solid rgba(26,71,42,.22); border-color: #1a472a; }
        .discount-status { color: #a32830; font-size: 11px; }
        .cart-subtotal, .cart-discount { display: flex; justify-content: space-between; margin-bottom: 8px; color: #626c62; font-size: 13px; }
        .cart-discount { color: #1a472a; }
        
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
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar { width: 70px; }
            .sidebar-brand-link span, .sidebar-nav a span { display: none; }
            .sidebar-nav a i { margin-right: 0; font-size: 22px; }
            .pos-container { margin-left: 70px; }
        }

        @media (max-width: 900px) {
            body { overflow: auto; }
            .pos-container { flex-direction: column; height: auto; }
            .cart-section { width: 100%; height: auto; }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR NAVIGATION -->
    <div class="sidebar">
    <div class="sidebar-brand">
        <a href="#shop-about" class="shop-logo-trigger sidebar-brand-link" aria-label="About Star Frappe"><img src="assets/logo.png" alt="Star Frappe logo"><span>Star Frappe</span></a>
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
            <?php endif; ?>
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
                <i class="fas fa-chart-line"></i> <span><?= isAdmin() ? 'Revenue Reports' : 'Daily Sales Report' ?></span>
            </a>
            <a href="logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a>
        </nav>
        <?php require 'includes/shop_sidebar_account.php'; ?>
    </div>
    <?php require 'includes/mobile_navigation.php'; ?>

    <!-- POS LAYOUT (Naka-move sa kanan dahil sa margin-left) -->
    <div class="pos-container">
        <!-- Menu Section -->
        <div class="menu-section">
            <div class="pos-header">
                <h1><a href="#shop-about" class="shop-logo-trigger pos-title-link" aria-label="About Star Frappe"><img src="assets/logo.png" alt="Star Frappe logo" class="brand-logo"><span>Star Frappe Shop POS</span></a></h1>
                
                <div style="text-align: right;">
                    <div style="color: #1a472a; font-weight: 700; font-size: 15px; margin-bottom: 4px;">
                        <i class="fas fa-user-circle"></i> Cashier: <?= htmlspecialchars($_SESSION['full_name']) ?>
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px; justify-content: flex-end;">
                        <span style="color: #666; font-size: 13px;"><?= date('M d, Y h:i A') ?></span>
                    </div>
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
                <div class="cart-header-row">
                    <h2><i class="fas fa-basket-shopping"></i> Order Summary</h2>
                    <button class="btn-clear" type="button" onclick="clearCart()"><i class="fas fa-trash"></i> Remove all</button>
                </div>
                <p style="margin-top: 6px; color: rgba(255,255,255,.8); font-size: 13px;">Review items and quantities before payment.</p>
            </div>
            
            <div class="cart-items" id="cart-items">
                <div class="empty-cart">
                    <i class="fas fa-basket-shopping"></i>
                    <p>Your order is empty</p>
                    <p style="font-size: 13px; margin-top: 5px;">Select products to begin.</p>
                </div>
            </div>
            
            <div class="cart-footer">
                <div class="discount-field">
                    <label for="discount-amount">Discount amount (₱)</label>
                    <input type="number" id="discount-amount" min="0" step="0.01" value="0" placeholder="0.00">
                    <div class="discount-status" id="discount-status" role="status" aria-live="polite"></div>
                </div>
                <div class="cart-subtotal"><span>Subtotal</span><span id="cart-subtotal">₱0.00</span></div>
                <div class="cart-discount"><span>Discount</span><span id="cart-discount">−₱0.00</span></div>
                <div class="cart-total">
                    <span>Amount due</span>
                    <span class="amount" id="cart-total">₱0.00</span>
                </div>
                <button class="btn-done" id="btn-done" onclick="proceedToPayment()" disabled>
                    Continue to Payment
                </button>
            </div>
        </div>
    </div>
    
    <?php require 'includes/shop_about.php'; ?>
    <script>
        let products = [];
        let categories = new Set();
        let cart = [];
        let currentCategory = 'all';
        let discountAmount = 0;
        
        async function loadProducts() {
            try {
                const res = await fetch('api/products.php?action=list');
                products = await res.json();
                products = products.map(product => ({
                    ...product,
                    id: Number(product.id),
                    variant_id: product.variant_id === null ? null : Number(product.variant_id)
                }));
                
                // Build unique categories for tabs
                const tabs = document.getElementById('category-tabs');
                tabs.innerHTML = '<button class="category-tab active" onclick="filterCategory(\'all\', this)">All</button>';
                
                const cats = [...new Set(products.map(p => p.category))];
                cats.forEach(cat => {
                    if (cat) {
                        const btn = document.createElement('button');
                        btn.className = 'category-tab';
                        btn.textContent = cat;
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
            
            grid.innerHTML = items.map((p, index) => {
                const displayIcon = p.image_path
                    ? `<img src="${escapeHtml(p.image_path)}" alt="${escapeHtml(p.name)}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 10px; margin-bottom: 10px;">`
                    : '<div class="product-placeholder"><i class="fas fa-mug-hot" aria-hidden="true"></i></div>';
                const variantLabel = p.variant_name
                    ? `<p class="variant-label">${escapeHtml(p.variant_name)}</p>`
                    : '';
                return `
                    <button type="button" class="product-card" data-product-index="${index}">
                        ${displayIcon}
                        <h4>${escapeHtml(p.name)}</h4>
                        ${variantLabel}
                        <div class="price">₱${parseFloat(p.price).toFixed(2)}</div>
                    </button>
                `;
            }).join('');
            grid.querySelectorAll('[data-product-index]').forEach(button => {
                button.addEventListener('click', function () {
                    const product = items[Number(this.dataset.productIndex)];
                    addToCart(product.id, product.name, product.price, product.variant_id, product.variant_name);
                });
            });
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, character => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            })[character]);
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
        
        function addToCart(id, name, price, variantId = null, variantName = null) {
            const existing = cart.find(item =>
                Number(item.id) === Number(id) &&
                Number(item.variant_id || 0) === Number(variantId || 0)
            );
            if (existing) {
                existing.qty++;
            } else {
                cart.push({
                    id: Number(id),
                    variant_id: variantId ? Number(variantId) : null,
                    name,
                    variant_name: variantName || null,
                    price: parseFloat(price),
                    qty: 1
                });
            }
            renderCart();
        }

        function renderCart() {
            const container = document.getElementById('cart-items');
            
            if (cart.length === 0) {
                container.innerHTML = `
                    <div class="empty-cart">
                        <i class="fas fa-basket-shopping"></i>
                        <p>Your order is empty</p>
                        <p style="font-size: 13px; margin-top: 5px;">Select products to begin.</p>
                    </div>
                `;
                document.getElementById('cart-total').textContent = '₱0.00';
                document.getElementById('cart-subtotal').textContent = '₱0.00';
                document.getElementById('cart-discount').textContent = '−₱0.00';
                document.getElementById('btn-done').disabled = true;
                sessionStorage.removeItem('cart');
                discountAmount = 0;
                document.getElementById('discount-amount').value = '0';
                sessionStorage.removeItem('discount_amount');
                return;
            }
            
            container.innerHTML = cart.map((item, index) => `
                <div class="cart-item">
                    <div class="cart-item-main">
                        <div class="cart-item-info">
                            <h4>${escapeHtml(item.name)}</h4>
                            ${item.variant_name ? `<span class="item-price">${escapeHtml(item.variant_name)}</span><br>` : ''}
                            <span class="item-price">₱${item.price.toFixed(2)} each</span>
                        </div>
                        <div class="cart-item-controls">
                            <button class="qty-btn minus" aria-label="Decrease quantity" data-qty-index="${index}" data-delta="-1">-</button>
                            <span class="qty-display">${item.qty}</span>
                            <button class="qty-btn plus" aria-label="Increase quantity" data-qty-index="${index}" data-delta="1">+</button>
                        </div>
                    </div>
                    <button class="remove-item" aria-label="Remove item" title="Remove item" data-remove-index="${index}"><i class="fas fa-trash"></i></button>
                </div>
            `).join('');
            container.querySelectorAll('[data-qty-index]').forEach(button => {
                button.addEventListener('click', function () {
                    updateQty(Number(this.dataset.qtyIndex), Number(this.dataset.delta));
                });
            });
            container.querySelectorAll('[data-remove-index]').forEach(button => {
                button.addEventListener('click', function () {
                    removeItem(Number(this.dataset.removeIndex));
                });
            });
            
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
            const validDiscount = Number.isFinite(discountAmount) && discountAmount >= 0 && discountAmount <= subtotal;
            const displayedDiscount = Math.max(0, Number.isFinite(discountAmount) ? discountAmount : 0);
            const total = Math.max(0, subtotal - displayedDiscount);
            document.getElementById('cart-subtotal').textContent = '₱' + subtotal.toFixed(2);
            document.getElementById('cart-discount').textContent = '−₱' + displayedDiscount.toFixed(2);
            document.getElementById('cart-total').textContent = '₱' + total.toFixed(2);
            document.getElementById('btn-done').disabled = !validDiscount;
            document.getElementById('discount-status').textContent = validDiscount ? '' : 'Discount cannot exceed the order subtotal.';
            
            sessionStorage.setItem('cart', JSON.stringify(cart));
            sessionStorage.setItem('discount_amount', discountAmount.toFixed(2));
            document.getElementById('discount-amount').value = discountAmount.toFixed(2);
        }

        function removeItem(index) {
            cart.splice(index, 1);
            renderCart();
        }
        
        function updateQty(index, delta) {
            const item = cart[index];
            if (!item) return;
            
            item.qty += delta;
            if (item.qty <= 0) {
                cart.splice(index, 1);
            }
            renderCart();
        }
        
        function clearCart() {
            if (cart.length > 0 && confirm('Remove all items from this order?')) {
                cart = [];
                renderCart();
            }
        }
        
        function proceedToPayment() {
            if (cart.length === 0) return;
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
            if (!Number.isFinite(discountAmount) || discountAmount < 0 || discountAmount > subtotal) {
                document.getElementById('discount-status').textContent = 'Discount cannot exceed the order subtotal.';
                return;
            }
            document.getElementById('discount-status').textContent = '';
            sessionStorage.setItem('cart', JSON.stringify(cart));
            sessionStorage.setItem('discount_amount', discountAmount.toFixed(2));
            window.location.href = 'payment.php';
        }

        document.getElementById('discount-amount').addEventListener('input', function () {
            discountAmount = Number(this.value);
            if (!Number.isFinite(discountAmount) || discountAmount < 0) discountAmount = 0;
            document.getElementById('discount-status').textContent = '';
            renderCart();
        });
        discountAmount = Number(sessionStorage.getItem('discount_amount') || 0);
        cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
        renderCart();
        
        loadProducts();
    </script>
</body>
</html>