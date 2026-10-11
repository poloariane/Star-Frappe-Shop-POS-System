<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        // Query the new 'staff' table instead of 'users'
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE username = ? AND status = 'Active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['staff_id'] = $user['staff_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Star Frappe Shop - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0d2b18 0%, #1a472a 50%, #0d2b18 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 900px;
            max-width: 95%;
            display: flex;
            min-height: 550px;
        }
        
        .login-left {
            background: linear-gradient(180deg, #1a472a 0%, #0d2b18 100%);
            padding: 50px;
            width: 45%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .login-left::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 50%;
            top: -100px;
            right: -100px;
        }
        
        .login-left::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255, 215, 0, 0.05);
            border-radius: 50%;
            bottom: -50px;
            left: -50px;
        }
        
<<<<<<< HEAD
        .brand-logo {
            width: 270px;
            max-width: 100%;
            height: auto;
            margin-bottom: 20px;
            border-radius: 12px;
=======
        .brand-icon {
            font-size: 80px;
            color: #ffd700;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .login-left h1 {
            font-size: 32px;
            text-align: center;
            margin-bottom: 10px;
>>>>>>> origin/main
        }
        
        .login-left p {
            color: rgba(255,255,255,0.7);
            text-align: center;
        }
        
        .login-right {
            padding: 60px 50px;
            width: 55%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-right h2 {
            color: #1a1a1a;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .login-right .subtitle {
            color: #666;
            margin-bottom: 40px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #1a472a;
            font-size: 18px;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 2px solid #e8f0e8;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
            background: #f8faf8;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #1a472a;
            background: white;
            box-shadow: 0 0 0 4px rgba(26, 71, 42, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #1a472a 0%, #2d5a27 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(26, 71, 42, 0.3);
        }
        
        .error-message {
            background: #f8d7da;
            color: #dc3545;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .login-container { flex-direction: column; }
            .login-left, .login-right { width: 100%; padding: 40px 30px; }
<<<<<<< HEAD
=======
            .brand-icon { font-size: 50px; }
            .login-left h1 { font-size: 24px; }
>>>>>>> origin/main
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
<<<<<<< HEAD
            <img src="assets/logo.png" alt="Star Frappe logo" class="brand-logo">
=======
            <i class="fas fa-star brand-icon"></i>
            <h1>Star Frappe Shop</h1>
>>>>>>> origin/main
            <p>Point of Sale System</p>
        </div>
        <div class="login-right">
            <h2>Welcome Back!</h2>
            <p class="subtitle">Please login to your account</p>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Enter your username" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </button>
            </form>
        </div>
    </div>
</body>
</html>