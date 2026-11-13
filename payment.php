<?php
require_once 'config/database.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Star Frappe Shop - Payment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #edf2ed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .payment-container {
            background: white;
            border-radius: 25px;
            box-shadow: 0 25px 80px rgba(0,0,0,0.3);
            width: 1080px;
            max-width: 100%;
            overflow: hidden;
            display: grid;
            grid-template-columns: .9fr 1.1fr;
        }

        .checkout-header {
            grid-column: 1 / -1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 20px 34px;
            border-bottom: 1px solid #e8eee8;
        }

        .checkout-brand { display: flex; align-items: center; gap: 12px; color: #1a472a; }
        .checkout-brand img { width: 52px; height: 52px; object-fit: contain; }
        .checkout-brand strong { display: block; font-size: 17px; }
        .checkout-brand .brand-caption { display: block; margin-top: 4px; color: #758075; font-size: 12px; }
        .checkout-step { color: #657065; font-size: 13px; font-weight: 600; }
        
        .order-summary {
            width: auto;
            background: #f8faf8;
            padding: 36px;
        }
        
        .order-summary h2 {
            color: #1a1a1a;
            font-size: 22px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .order-summary h2 i { color: #1a472a; }
        
        .order-items {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 25px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e8f0e8;
        }
        
        .order-item .name {
            color: #1a1a1a;
            font-weight: 500;
        }
        
        .order-item .qty {
            color: #999;
            font-size: 13px;
        }
        
        .order-item .subtotal {
            color: #1a472a;
            font-weight: 700;
        }
        
        .order-total {
            display: flex;
            justify-content: space-between;
            padding: 20px 0;
            border-top: 2px solid #1a472a;
            font-size: 22px;
            font-weight: 700;
        }
        
        .order-total .amount { color: #1a472a; }
        .order-breakdown { display: flex; justify-content: space-between; margin: 8px 0; color: #687268; font-size: 14px; }
        .order-breakdown.discount { color: #1a472a; }
        
        .payment-section {
            width: auto;
            padding: 36px;
        }
        
        .payment-section h2 {
            color: #1a1a1a;
            font-size: 22px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .payment-section h2 i { color: #1a472a; }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .payment-method {
            padding: 20px;
            border: 2px solid #e8f0e8;
            border-radius: 15px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
            background: white;
            font: inherit;
        }
        
        .payment-method:hover { border-color: #1a472a; }
        
        .payment-method.active {
            border-color: #1a472a;
            background: #f0f5f0;
        }
        
        .payment-method i {
            font-size: 28px;
            color: #1a472a;
            margin-bottom: 8px;
            display: block;
        }
        
        .payment-method span {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 14px;
        }
        .payment-method { color: inherit; }
        .wallet-provider { display: none; margin: -12px 0 24px; }
        .wallet-provider.is-visible { display: block; }
        .wallet-provider label { display: block; margin-bottom: 8px; color: #1a1a1a; font-weight: 600; }
        .wallet-provider select { width: 100%; padding: 13px 15px; border: 2px solid #e8f0e8; border-radius: 10px; background: white; font: inherit; }

        .payment-method:focus-visible { outline: 3px solid #ffd700; outline-offset: 2px; }
        
        .input-group { margin-bottom: 25px; }
        
        .input-group label {
            display: block;
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .input-group input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e8f0e8;
            border-radius: 12px;
            font-size: 18px;
            transition: all 0.3s;
            background: #f8faf8;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #1a472a;
            background: white;
            box-shadow: 0 0 0 4px rgba(26, 71, 42, 0.1);
        }

        .input-hint { display: block; margin-top: 7px; color: #778177; font-size: 12px; }
        .checkout-status { display: none; margin-bottom: 16px; padding: 12px 14px; border-radius: 9px; font-size: 13px; }
        .checkout-status.is-visible { display: block; }
        .checkout-status.is-error { color: #8d242c; background: #fce8e9; }
        
        .change-display {
            display: flex;
            justify-content: space-between;
            padding: 20px;
            background: #f0f5f0;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 18px;
            font-weight: 700;
        }
        
        .change-display .amount { color: #1a472a; }
        
        .action-buttons {
            display: flex;
            gap: 15px;
        }
        
        .btn-cancel {
            flex: 1;
            padding: 18px;
            background: #edf1ed;
            color: #495449;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-cancel:hover {
            background: #dfe6df;
        }
        
        .btn-confirm {
            flex: 2;
            padding: 18px;
            background: #1a472a;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-confirm:hover {
            background: #2d5a27;
        }
        
        .btn-confirm:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .btn-confirm:disabled:hover { background: #ccc; }
        
        /* Confirmation Modal */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }
        
        .modal-overlay.show { display: flex; }
        
        .confirm-modal {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 450px;
            width: 90%;
            animation: slideUp 0.3s;
        }
        
        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .confirm-modal i {
            font-size: 60px;
            color: #1a472a;
            margin-bottom: 20px;
        }
        
        .confirm-modal h3 {
            color: #1a1a1a;
            font-size: 22px;
            margin-bottom: 10px;
        }
        
        .confirm-modal p {
            color: #666;
            margin-bottom: 25px;
        }
        
        .confirm-modal .amount-received {
            font-size: 28px;
            font-weight: 700;
            color: #1a472a;
            margin-bottom: 25px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 15px;
        }
        
        .modal-buttons button {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-no { background: #edf1ed; color: #495449; }
        .btn-no:hover { background: #dfe6df; }
        
        .btn-yes { background: #1a472a; color: white; }
        .btn-yes:hover { background: #2d5a27; }
        
        @media (max-width: 768px) {
            .payment-container { grid-template-columns: 1fr; }
            .checkout-header { padding: 20px; }
            .order-summary, .payment-section { padding: 26px; }
        }

        @media (max-width: 480px) {
            .payment-methods { grid-template-columns: 1fr; }
            .action-buttons { flex-direction: column-reverse; }
            .checkout-step { display: none; }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <header class="checkout-header">
            <a href="#shop-about" class="checkout-brand shop-logo-trigger" aria-label="About Star Frappe">
                <img src="assets/logo.png" alt="Star Frappe logo">
                <span><strong>Star Frappe</strong><span class="brand-caption">Secure checkout</span></span>
            </a>
            <span class="checkout-step">Step 2 of 2 · Confirm payment</span>
        </header>
        <div class="order-summary">
            <h2><i class="fas fa-receipt"></i> Review Your Order</h2>
            <p style="margin: -12px 0 20px; color: #707a70; font-size: 13px;"><span id="order-item-count">0</span> items</p>
            <div class="order-items" id="order-items"></div>
            <div class="order-breakdown"><span>Subtotal</span><span id="order-subtotal">₱0.00</span></div>
            <div class="order-breakdown discount"><span>Discount</span><span id="order-discount">−₱0.00</span></div>
            <div class="order-total">
                <span>Amount due</span>
                <span class="amount" id="order-total">₱0.00</span>
            </div>
        </div>
        
        <div class="payment-section">
            <h2><i class="fas fa-credit-card"></i> Choose Payment Method</h2>
            
            <div class="payment-methods">
                <button type="button" class="payment-method active" aria-pressed="true" onclick="selectPayment('Cash', this)">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Cash</span>
                </button>
                <button type="button" class="payment-method" aria-pressed="false" onclick="selectPayment('E-Wallet', this)">
                    <i class="fas fa-wallet"></i>
                    <span>E-Wallet</span>
                </button>
                <button type="button" class="payment-method" aria-pressed="false" onclick="selectPayment('Card', this)">
                    <i class="fas fa-credit-card"></i>
                    <span>Card</span>
                </button>
            </div>

            <div class="wallet-provider" id="wallet-provider">
                <label for="wallet-name">E-wallet provider</label>
                <select id="wallet-name">
                    <option value="GCash">GCash</option>
                    <option value="Maya">Maya</option>
                    <option value="MariBank">MariBank</option>
                    <option value="Other E-Wallet">Other</option>
                </select>
                <span class="input-hint">Verify the payment in the provider app before confirming.</span>
            </div>
            
            <div class="input-group" id="tendered-group">
                <label for="amount-tendered">Cash received</label>
                <input type="number" id="amount-tendered" placeholder="Enter amount received" step="0.01" min="0" oninput="calculateChange()">
                <span class="input-hint">Enter the amount given by the customer.</span>
            </div>
            
            <div class="change-display">
                <span>Change due</span>
                <span class="amount" id="change-amount">₱0.00</span>
            </div>

            <div class="checkout-status" id="checkout-status" role="status" aria-live="polite"></div>
            
            <div class="action-buttons">
                <button class="btn-cancel" onclick="goBack()">
                    <i class="fas fa-arrow-left"></i> Back to Order
                </button>
                <button class="btn-confirm" id="btn-confirm" onclick="showConfirmation()">
                    Complete Sale
                </button>
            </div>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div class="modal-overlay" id="confirm-modal">
        <div class="confirm-modal">
            <i class="fas fa-circle-check"></i>
            <h3>Review Payment</h3>
            <p id="confirm-message">Confirm the amount received before completing this sale.</p>
            <div class="amount-received" id="confirm-amount">₱0.00</div>
            <div class="modal-buttons">
                <button class="btn-no" onclick="closeConfirmation()">
                    Go Back
                </button>
                <button class="btn-yes" onclick="confirmPayment()">
                    Confirm Payment
                </button>
            </div>
        </div>
    </div>
    
    <?php require 'includes/shop_about.php'; ?>
    <script>
        let cart = [];
        let total = 0;
        let paymentMethod = 'Cash';
        let walletProvider = 'GCash';
        let amountTendered = 0;
        let discountAmount = 0;
        let isProcessing = false;
        
        function loadOrder() {
            cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
            discountAmount = Number(sessionStorage.getItem('discount_amount') || 0);
            
            if (cart.length === 0) {
                window.location.href = 'pos.php';
                return;
            }
            
            const subtotal = cart.reduce((sum, item) => sum + (Number(item.price) * Number(item.qty)), 0);
            if (!Number.isFinite(discountAmount) || discountAmount < 0 || discountAmount > subtotal) {
                discountAmount = 0;
            }
            total = subtotal - discountAmount;
            document.getElementById('order-item-count').textContent = cart.reduce((sum, item) => sum + item.qty, 0);
            
            document.getElementById('order-items').innerHTML = cart.map(item => `
                <div class="order-item">
                    <div>
                        <div class="name">${escapeHtml(item.name)}${item.variant_name ? ' - ' + escapeHtml(item.variant_name) : ''}</div>
                        <div class="qty">x${item.qty} @ ₱${item.price.toFixed(2)}</div>
                    </div>
                    <div class="subtotal">₱${(item.price * item.qty).toFixed(2)}</div>
                </div>
            `).join('');
            
            document.getElementById('order-subtotal').textContent = '₱' + subtotal.toFixed(2);
            document.getElementById('order-discount').textContent = '−₱' + discountAmount.toFixed(2);
            document.getElementById('order-total').textContent = '₱' + total.toFixed(2);
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
        
        function selectPayment(method, el) {
            paymentMethod = method;
            document.querySelectorAll('.payment-method').forEach(m => {
                m.classList.remove('active');
                m.setAttribute('aria-pressed', 'false');
            });
            el.classList.add('active');
            el.setAttribute('aria-pressed', 'true');
            
            document.getElementById('tendered-group').style.display =
                method === 'Cash' ? 'block' : 'none';
            document.getElementById('wallet-provider').classList.toggle('is-visible', method === 'E-Wallet');
            
            if (method !== 'Cash') {
                document.getElementById('amount-tendered').value = total.toFixed(2);
                calculateChange();
            } else {
                document.getElementById('amount-tendered').value = '';
                document.getElementById('change-amount').textContent = '₱0.00';
            }
        }
        
        function calculateChange() {
            amountTendered = paymentMethod === 'Cash'
                ? (parseFloat(document.getElementById('amount-tendered').value) || 0)
                : total;
            const change = amountTendered - total;
            
            document.getElementById('change-amount').textContent = 
                '₱' + (change >= 0 ? change.toFixed(2) : '0.00');
        }
        
        function showConfirmation() {
            amountTendered = paymentMethod === 'Cash'
                ? (parseFloat(document.getElementById('amount-tendered').value) || 0)
                : total;
            const status = document.getElementById('checkout-status');
            status.className = 'checkout-status';
            status.textContent = '';
            
            if (amountTendered < total) {
                status.textContent = 'The amount received must be at least the amount due.';
                status.classList.add('is-visible', 'is-error');
                return;
            }
            
            document.getElementById('confirm-amount').textContent = '₱' + amountTendered.toFixed(2);
            walletProvider = document.getElementById('wallet-name').value;
            const paymentLabel = paymentMethod === 'E-Wallet' ? paymentMethod + ' (' + walletProvider + ')' : paymentMethod;
            document.getElementById('confirm-message').textContent = paymentMethod === 'Cash'
                ? 'Confirm the cash received before completing this sale.'
                : 'Confirm that the customer paid by ' + paymentLabel + ' before completing this sale.';
            document.getElementById('confirm-modal').classList.add('show');
        }
        
        function closeConfirmation() {
            document.getElementById('confirm-modal').classList.remove('show');
        }
        
        async function confirmPayment() {
            if (isProcessing) return;
            isProcessing = true;
            
            const change = amountTendered - total;
            
            const orderData = {
                items: cart,
                total: total,
                payment_method: paymentMethod === 'E-Wallet' ? 'E-Wallet (' + walletProvider + ')' : paymentMethod,
                amount_tendered: amountTendered,
                change_amount: change,
                discount_amount: discountAmount
            };
            
            try {
                const res = await fetch('api/orders.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(orderData)
                });
                
                const data = await res.json();
                
                if (data.success) {
                    sessionStorage.removeItem('cart');
                    sessionStorage.setItem('last_receipt_number', data.receipt_number);
                    window.location.href = 'receipt.php?id=' + data.sale_id;
                } else {
                    closeConfirmation();
                    const status = document.getElementById('checkout-status');
                    status.textContent = data.error || 'The sale could not be completed. Please try again.';
                    status.classList.add('is-visible', 'is-error');
                    isProcessing = false;
                }
            } catch (e) {
                console.error('Error:', e);
                closeConfirmation();
                const status = document.getElementById('checkout-status');
                status.textContent = 'Unable to reach the server. Check your connection and try again.';
                status.classList.add('is-visible', 'is-error');
                isProcessing = false;
            }
        }
        
        function goBack() {
            window.location.href = 'pos.php';
        }
        
        loadOrder();
    </script>
</body>
</html>