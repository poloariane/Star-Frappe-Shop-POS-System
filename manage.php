<?php
require_once 'config/database.php';
requireAdmin();

$message = '';
$messageType = '';

// ============================================
// ADD STAFF
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'] ?? 'Staff';
    
    if (empty($username) || empty($password) || empty($name)) {
        $message = 'Please fill in all required fields';
        $messageType = 'error';
    } elseif (!in_array($role, ['Admin', 'Staff'])) {
        $message = 'Invalid role selected';
        $messageType = 'error';
    } else {
        $stmt = $pdo->prepare("SELECT staff_id FROM staff WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            $message = 'Username already exists';
            $messageType = 'error';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO staff (name, username, password, email, phone, role, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'Active')
            ");
            
            if ($stmt->execute([$name, $username, $hashedPassword, $email, $phone, $role])) {
                $message = 'Staff member added successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to add staff member';
                $messageType = 'error';
            }
        }
    }
}

// ============================================
// ADD PRODUCT
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['product_name']);
    $categoryId = intval($_POST['category_id']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $emoji = trim($_POST['emoji']);
    
    if (empty($name) || $price <= 0 || $categoryId <= 0) {
        $message = 'Please provide valid product details';
        $messageType = 'error';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO product (product_name, category_id, price, description, emoji, status) 
            VALUES (?, ?, ?, ?, ?, 'Available')
        ");
        
        if ($stmt->execute([$name, $categoryId, $price, $description, $emoji])) {
            $message = 'Product added successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to add product';
            $messageType = 'error';
        }
    }
}

// ============================================
// ADD CATEGORY
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $catName = trim($_POST['category_name']);
    
    if (empty($catName)) {
        $message = 'Category name is required';
        $messageType = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO category (category_name) VALUES (?)");
            $stmt->execute([$catName]);
            $message = 'Category added successfully!';
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Category already exists';
            $messageType = 'error';
        }
    }
}

// ============================================
// DELETE STAFF
// ============================================
if (isset($_GET['delete_staff'])) {
    $id = intval($_GET['delete_staff']);
    // Prevent self-deletion
    if ($id === $_SESSION['staff_id']) {
        $message = 'You cannot delete your own account';
        $messageType = 'error';
    } else {
        $stmt = $pdo->prepare("DELETE FROM staff WHERE staff_id = ?");
        $stmt->execute([$id]);
        header('Location: manage.php?deleted=staff');
        exit;
    }
}

// ============================================
// DELETE PRODUCT
// ============================================
if (isset($_GET['delete_product'])) {
    $stmt = $pdo->prepare("DELETE FROM product WHERE product_id = ?");
    $stmt->execute([intval($_GET['delete_product'])]);
    header('Location: manage.php?deleted=product');
    exit;
}

// ============================================
// TOGGLE STAFF STATUS (Active/Inactive)
// ============================================
if (isset($_GET['toggle_staff'])) {
    $id = intval($_GET['toggle_staff']);
    if ($id === $_SESSION['staff_id']) {
        $message = 'You cannot deactivate your own account';
        $messageType = 'error';
    } else {
        $stmt = $pdo->prepare("
            UPDATE staff 
            SET status = IF(status = 'Active', 'Inactive', 'Active') 
            WHERE staff_id = ?
        ");
        $stmt->execute([$id]);
        header('Location: manage.php');
        exit;
    }
}

// ============================================
// TOGGLE PRODUCT STATUS (Available/Unavailable)
// ============================================
if (isset($_GET['toggle_product'])) {
    $stmt = $pdo->prepare("
        UPDATE product 
        SET status = IF(status = 'Available', 'Unavailable', 'Available') 
        WHERE product_id = ?
    ");
    $stmt->execute([intval($_GET['toggle_product'])]);
    header('Location: manage.php#products');
    exit;
}

// ============================================
// LOAD DATA
// ============================================
$staffList = $pdo->query("SELECT * FROM staff ORDER BY created_at DESC")->fetchAll();
$productList = $pdo->query("
    SELECT p.*, c.category_name 
    FROM product p 
    LEFT JOIN category c ON p.category_id = c.category_id 
    ORDER BY c.category_name, p.product_name
")->fetchAll();
$categories = $pdo->query("SELECT * FROM category ORDER BY category_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Star Frappe Shop - Manage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7f5;
            min-height: 100vh;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .header h1 {
            color: #1a472a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .back-btn {
            padding: 10px 20px;
            background: #1a472a;
            color: white;
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
        
        .back-btn:hover { background: #2d5a27; }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .tab-btn {
            padding: 15px 30px;
            background: white;
            border: 2px solid #e8f0e8;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s;
            color: #666;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .tab-btn.active, .tab-btn:hover {
            background: #1a472a;
            color: white;
            border-color: #1a472a;
        }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 25px;
        }
        
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .form-card h2 {
            color: #1a1a1a;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
        }
        
        .form-card h2 i { color: #1a472a; }
        
        .form-group { margin-bottom: 18px; }
        
        .form-group label {
            display: block;
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e8f0e8;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            background: #f8faf8;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1a472a;
            background: white;
            box-shadow: 0 0 0 4px rgba(26, 71, 42, 0.1);
        }
        
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1a472a 0%, #2d5a27 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(26, 71, 42, 0.3);
        }
        
        .list-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        
        .list-card h2 {
            color: #1a1a1a;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 20px;
        }
        
        .list-card h2 i { color: #1a472a; }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            text-align: left;
            padding: 12px;
            background: #f8faf8;
            color: #666;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .data-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #f0f0f0;
            color: #1a1a1a;
            font-size: 14px;
        }
        
        .data-table tr:hover { background: #f8faf8; }
        
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge.Drink { background: #e8f0e8; color: #1a472a; }
        .badge.Waffle { background: #fff3e0; color: #e65100; }
        .badge.Admin { background: #ffd700; color: #1a1a1a; }
        .badge.Staff { background: #e8f0e8; color: #1a472a; }
        .badge.Active { background: #d4edda; color: #1a472a; }
        .badge.Inactive { background: #f8d7da; color: #dc3545; }
        .badge.Available { background: #d4edda; color: #1a472a; }
        .badge.Unavailable { background: #f8d7da; color: #dc3545; }
        
        .btn-delete {
            padding: 6px 12px;
            background: #f8d7da;
            color: #dc3545;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-delete:hover { background: #dc3545; color: white; }
        
        .btn-toggle {
            padding: 6px 12px;
            background: #fff3cd;
            color: #856404;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-right: 4px;
        }
        
        .btn-toggle:hover { background: #856404; color: white; }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert.success {
            background: #d4edda;
            color: #1a472a;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #dc3545;
            border: 1px solid #f5c6cb;
        }
        
        .section-divider {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #f0f0f0;
        }
        
        @media (max-width: 992px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-users-cog"></i> Manage Staff & Products</h1>
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
            <?= ucfirst(htmlspecialchars($_GET['deleted'])) ?> deleted successfully!
        </div>
    <?php endif; ?>
    
    <div class="tabs">
        <button class="tab-btn active" onclick="showTab('staff', this)">
            <i class="fas fa-users"></i> Staff Management
        </button>
        <button class="tab-btn" onclick="showTab('products', this)">
            <i class="fas fa-box"></i> Product Management
        </button>
    </div>
    
    <!-- ============================================
         STAFF TAB
    ============================================ -->
    <div class="tab-content active" id="staff-tab">
        <div class="grid-2">
            <div class="form-card">
                <h2><i class="fas fa-user-plus"></i> Add New Staff</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" placeholder="Enter full name" required>
                    </div>
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" placeholder="Enter username" required>
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" placeholder="Enter password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" required>
                            <option value="Staff">Staff</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="Enter email">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" placeholder="Enter phone number">
                    </div>
                    <button type="submit" name="add_staff" class="btn-submit">
                        <i class="fas fa-plus"></i> Add Staff Member
                    </button>
                </form>
            </div>
            
            <div class="list-card">
                <h2><i class="fas fa-users"></i> Staff Members</h2>
                <?php if (empty($staffList)): ?>
                    <p style="color: #999; text-align: center; padding: 40px;">No staff members yet</p>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staffList as $s): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($s['name']) ?></strong>
                                <?php if ($s['email']): ?>
                                    <div style="font-size: 12px; color: #999;"><?= htmlspecialchars($s['email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($s['username']) ?></td>
                            <td><span class="badge <?= $s['role'] ?>"><?= $s['role'] ?></span></td>
                            <td><span class="badge <?= $s['status'] ?>"><?= $s['status'] ?></span></td>
                            <td>
                                <a href="?toggle_staff=<?= $s['staff_id'] ?>" class="btn-toggle" 
                                   onclick="return confirm('Toggle status for <?= htmlspecialchars($s['name']) ?>?')"
                                   title="Toggle Active/Inactive">
                                    <i class="fas fa-power-off"></i>
                                </a>
                                <a href="?delete_staff=<?= $s['staff_id'] ?>" class="btn-delete" 
                                   onclick="return confirm('Delete <?= htmlspecialchars($s['name']) ?>?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- ============================================
         PRODUCTS TAB
    ============================================ -->
    <div class="tab-content" id="products-tab">
        <div class="grid-2">
            <div class="form-card">
                <h2><i class="fas fa-plus-circle"></i> Add New Product</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Product Name *</label>
                        <input type="text" name="product_name" placeholder="Enter product name" required>
                    </div>
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category_id" required>
                            <option value="">-- Select category --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['category_id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Price (₱) *</label>
                        <input type="number" name="price" placeholder="0.00" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Emoji/Icon</label>
                        <input type="text" name="emoji" placeholder="e.g., 🥤" maxlength="5">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3" placeholder="Product description"></textarea>
                    </div>
                    <button type="submit" name="add_product" class="btn-submit">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                </form>
                
                <div class="section-divider">
                    <h2><i class="fas fa-tag"></i> Add New Category</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Category Name *</label>
                            <input type="text" name="category_name" placeholder="e.g., Cake, Rice Meal" required>
                        </div>
                        <button type="submit" name="add_category" class="btn-submit">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="list-card">
                <h2><i class="fas fa-box"></i> Product List (<?= count($productList) ?>)</h2>
                <?php if (empty($productList)): ?>
                    <p style="color: #999; text-align: center; padding: 40px;">No products yet</p>
                <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productList as $p): ?>
                        <tr>
                            <td>
                                <span style="font-size: 20px; margin-right: 8px;"><?= $p['emoji'] ?? '🍽️' ?></span>
                                <?= htmlspecialchars($p['product_name']) ?>
                            </td>
                            <td><span class="badge <?= $p['category_name'] ?>"><?= htmlspecialchars($p['category_name']) ?></span></td>
                            <td>₱<?= number_format($p['price'], 2) ?></td>
                            <td><span class="badge <?= $p['status'] ?>"><?= $p['status'] ?></span></td>
                            <td>
                                <a href="?toggle_product=<?= $p['product_id'] ?>" class="btn-toggle"
                                   onclick="return confirm('Toggle availability for <?= htmlspecialchars($p['product_name']) ?>?')"
                                   title="Toggle Available/Unavailable">
                                    <i class="fas fa-power-off"></i>
                                </a>
                                <a href="?delete_product=<?= $p['product_id'] ?>" class="btn-delete"
                                   onclick="return confirm('Delete <?= htmlspecialchars($p['product_name']) ?>?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tab, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            btn.classList.add('active');
            document.getElementById(tab + '-tab').classList.add('active');
        }
        
        // Auto-open products tab if URL has #products
        if (window.location.hash === '#products') {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.tab-btn')[1].classList.add('active');
            document.getElementById('products-tab').classList.add('active');
        }
    </script>
</body>
</html>