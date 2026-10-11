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
            background: linear-gradient(135deg, #0d2b18 0%, #1a472a 100%);
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
            width: 1000px;
            max-width: 100%;
            overflow: hidden;
            display: flex;
        }
        
        .order-summary {
            width: 45%;
            background: #f8faf8;
            padding: 40px;
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
        
        .payment-section {
            width: 55%;
            padding: 40px;
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
            grid-template-columns: repeat(3, 1fr);
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
            background: #f8d7da;
            color: #dc3545;
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
            background: #dc3545;
            color: white;
        }
        
        .btn-confirm {
            flex: 2;
            padding: 18px;
            background: linear-gradient(135deg, #1a472a 0%, #2d5a27 100%);
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
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(26, 71, 42, 0.3);
        }
        
        .btn-confirm:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
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
        
        .btn-no { background: #f8d7da; color: #dc3545; }
        .btn-no:hover { background: #dc3545; color: white; }
        
        .btn-yes { background: #1a472a; color: white; }
        .btn-yes:hover { background: #2d5a27; }
        
        @media (max-width: 768px) {
            .payment-container { flex-direction: column; }
            .order-summary, .payment-section { width: 100%; }
            .payment-methods { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="order-summary">
            <h2><i class="fas fa-receipt"></i> Order Summary</h2>
            <div class="order-items" id="order-items"></div>
            <div class="order-total">
                <span>Total:</span>
                <span class="amount" id="order-total">₱0.00</span>
            </div>
        </div>
        
        <div class="payment-section">
            <h2><i class="fas fa-credit-card"></i> Payment Method</h2>
            
            <div class="payment-methods">
                <div class="payment-method active" onclick="selectPayment('Cash', this)">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Cash</span>
                </div>
                <div class="payment-method" onclick="selectPayment('GCash', this)">
                    <i class="fas fa-mobile-alt"></i>
                    <span>GCash</span>
                </div>
                <div class="payment-method" onclick="selectPayment('Card', this)">
                    <i class="fas fa-credit-card"></i>
                    <span>Card</span>
                </div>
            </div>
            
            <div class="input-group" id="tendered-group">
                <label>Amount Tendered:</label>
                <input type="number" id="amount-tendered" placeholder="Enter amount" step="0.01" min="0" oninput="calculateChange()">
            </div>
            
            <div class="change-display">
                <span>Change:</span>
                <span class="amount" id="change-amount">₱0.00</span>
            </div>
            
            <div class="action-buttons">
                <button class="btn-cancel" onclick="goBack()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button class="btn-confirm" id="btn-confirm" onclick="showConfirmation()">
                    <i class="fas fa-check"></i> Complete Payment
                </button>
            </div>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div class="modal-overlay" id="confirm-modal">
        <div class="confirm-modal">
            <i class="fas fa-question-circle"></i>
            <h3>Confirm Payment</h3>
            <p>Is the amount received correct?</p>
            <div class="amount-received" id="confirm-amount">₱0.00</div>
            <div class="modal-buttons">
                <button class="btn-no" onclick="closeConfirmation()">
                    <i class="fas fa-times"></i> No, Go Back
                </button>
                <button class="btn-yes" onclick="confirmPayment()">
                    <i class="fas fa-check"></i> Yes, Proceed
                </button>
            </div>
        </div>
    </div>
    
    <script>
        let cart = [];
        let total = 0;
        let paymentMethod = 'Cash';
        let amountTendered = 0;
        let isProcessing = false;
        
        function loadOrder() {
            cart = JSON.parse(sessionStorage.getItem('cart') || '[]');
            
            if (cart.length === 0) {
                alert('No items in cart!');
                window.location.href = 'pos.php';
                return;
            }
            
            total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
            
            document.getElementById('order-items').innerHTML = cart.map(item => `
                <div class="order-item">
                    <div>
                        <div class="name">${item.name}</div>
                        <div class="qty">x${item.qty} @ ₱${item.price.toFixed(2)}</div>
                    </div>
                    <div class="subtotal">₱${(item.price * item.qty).toFixed(2)}</div>
                </div>
            `).join('');
            
            document.getElementById('order-total').textContent = '₱' + total.toFixed(2);
        }
        
        function selectPayment(method, el) {
            paymentMethod = method;
            document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('active'));
            el.classList.add('active');
            
            document.getElementById('tendered-group').style.display = 
                method === 'Cash' ? 'block' : 'none';
            
            if (method !== 'Cash') {
                document.getElementById('amount-tendered').value = total.toFixed(2);
                calculateChange();
            } else {
                document.getElementById('amount-tendered').value = '';
                document.getElementById('change-amount').textContent = '₱0.00';
            }
        }
        
        function calculateChange() {
            amountTendered = parseFloat(document.getElementById('amount-tendered').value) || 0;
            const change = amountTendered - total;
            
            document.getElementById('change-amount').textContent = 
                '₱' + (change >= 0 ? change.toFixed(2) : '0.00');
        }
        
        function showConfirmation() {
            amountTendered = parseFloat(document.getElementById('amount-tendered').value) || 0;
            
            if (amountTendered < total) {
                alert('Amount tendered is less than the total!');
                return;
            }
            
            // For GCash / Card, show "Did customer pay?" question first
            if (paymentMethod === 'GCash' || paymentMethod === 'Card') {
                const paid = confirm(
                    'PAYMENT CONFIRMATION\n\n' +
                    'Method: ' + paymentMethod + '\n' +
                    'Amount: ₱' + amountTendered.toFixed(2) + '\n\n' +
                    'Has the customer paid? Click OK to confirm, Cancel to go back.'
                );
                if (!paid) return;
            }
            
            document.getElementById('confirm-amount').textContent = '₱' + amountTendered.toFixed(2);
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
                payment_method: paymentMethod,
                amount_tendered: amountTendered,
                change_amount: change
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
                    alert('Error: ' + (data.error || 'Failed to process order'));
                    isProcessing = false;
                }
            } catch (e) {
                console.error('Error:', e);
                alert('Failed to process order. Please try again.');
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