<?php
require_once 'config/database.php';
requireLogin();

$message = '';
$messageType = '';

// Load current user from `staff` table
$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$_SESSION['staff_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    if (empty($name)) {
        $message = 'Name is required';
        $messageType = 'error';
    } else {
        $stmt = $pdo->prepare("UPDATE staff SET name = ?, email = ?, phone = ? WHERE staff_id = ?");
        if ($stmt->execute([$name, $email, $phone, $_SESSION['staff_id']])) {
            $_SESSION['full_name'] = $name;
            $message = 'Profile updated successfully!';
            $messageType = 'success';
            
            // Refresh
            $stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
            $stmt->execute([$_SESSION['staff_id']]);
            $user = $stmt->fetch();
        } else {
            $message = 'Failed to update profile';
            $messageType = 'error';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (!password_verify($currentPassword, $user['password'])) {
        $message = 'Current password is incorrect';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'New passwords do not match';
        $messageType = 'error';
    } elseif (strlen($newPassword) < 6) {
        $message = 'Password must be at least 6 characters';
        $messageType = 'error';
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE staff SET password = ? WHERE staff_id = ?");
        if ($stmt->execute([$hashedPassword, $_SESSION['staff_id']])) {
            $message = 'Password changed successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to change password';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Star Frappe Shop - Profile</title>
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
        
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 25px;
            max-width: 1100px;
            margin: 0 auto;
        }
        
        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #1a472a 0%, #2d5a27 100%);
            padding: 40px;
            text-align: center;
            color: white;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #ffd700;
            color: #1a472a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: 700;
            margin: 0 auto 20px;
            border: 4px solid rgba(255,255,255,0.3);
        }
        
        .profile-header h2 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .profile-header p {
            color: rgba(255,255,255,0.8);
            font-size: 14px;
        }
        
        .role-tag {
            display: inline-block;
            margin-top: 15px;
            padding: 6px 20px;
            background: #ffd700;
            color: #1a1a1a;
            border-radius: 20px;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
        }
        
        .profile-body { padding: 30px; }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-row:last-child { border-bottom: none; }
        
        .info-label { color: #666; font-size: 14px; }
        
        .info-value {
            color: #1a1a1a;
            font-weight: 600;
            font-size: 14px;
            text-align: right;
        }
        
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
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
        
        .form-group { margin-bottom: 20px; }
        
        .form-group label {
            display: block;
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e8f0e8;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            background: #f8faf8;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1a472a;
            background: white;
            box-shadow: 0 0 0 4px rgba(26, 71, 42, 0.1);
        }
        
        .form-group input:disabled {
            background: #f0f0f0;
            cursor: not-allowed;
        }
        
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #1a472a 0%, #2d5a27 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
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
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert.success { background: #d4edda; color: #1a472a; }
        .alert.error { background: #f8d7da; color: #dc3545; }
        
        .password-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }
        
        @media (max-width: 992px) {
            .profile-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-user-circle"></i> My Profile</h1>
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
    
    <div class="profile-grid">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <h2><?= htmlspecialchars($user['name']) ?></h2>
                <p>@<?= htmlspecialchars($user['username']) ?></p>
                <span class="role-tag"><?= htmlspecialchars($user['role']) ?></span>
            </div>
            <div class="profile-body">
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-envelope"></i> Email</span>
                    <span class="info-value"><?= htmlspecialchars($user['email'] ?? 'Not set') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-phone"></i> Phone</span>
                    <span class="info-value"><?= htmlspecialchars($user['phone'] ?? 'Not set') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-toggle-on"></i> Status</span>
                    <span class="info-value"><?= htmlspecialchars($user['status']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-calendar"></i> Member Since</span>
                    <span class="info-value"><?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                </div>
            </div>
        </div>
        
        <div class="form-card">
            <h2><i class="fas fa-edit"></i> Edit Profile</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
                <button type="submit" name="update_profile" class="btn-submit">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
            
            <div class="password-section">
                <h2><i class="fas fa-lock"></i> Change Password</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn-submit">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>