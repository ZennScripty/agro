<?php
/**
 * SAMRIDHI AGRO - Shop Header Include
 * 
 * This file contains the common header structure for all shop pages.
 * 
 * @package SamridhiAgro
 * @subpackage Includes
 * @version 2.0.0
 */

// Include all required files
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    initSecureSession();
}

// Ensure required variables are set
$pageTitle = $pageTitle ?? 'Shop Panel';
$currentPage = basename($_SERVER['PHP_SELF']);

// Get current user data
$currentUser = getCurrentUser();

// Get shop data
$db = getDB();
$sql = "SELECT s.*, u.full_name, u.username, u.email 
        FROM shops s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.user_id = ?";
$shop = $db->fetchOne($sql, [$_SESSION['user_id'] ?? 0]);

// Get cart item count
$cartCount = 0;
if (isset($_SESSION['shop_cart']) && !empty($_SESSION['shop_cart'])) {
    foreach ($_SESSION['shop_cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}

// Get pending payments count
$pendingPayments = 0;
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $sql = "SELECT COUNT(*) as count FROM shop_payments sp 
            JOIN shops s ON sp.shop_id = s.id 
            WHERE s.user_id = ? AND sp.status IN ('pending', 'collected', 'submitted')";
    $result = $db->fetchOne($sql, [$_SESSION['user_id']]);
    $pendingPayments = $result['count'] ?? 0;
}

$notificationCount = $pendingPayments;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escapeHtml($pageTitle); ?> - Shop Portal</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        .dashboard-wrapper { display: flex; min-height: 100vh; background: #F7FCF7; }
        
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #052E16 0%, #14532D 100%);
            color: white;
            padding: 24px 0;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }
        
        .sidebar-brand {
            padding: 0 20px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar-brand .brand-icon {
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #22C55E;
        }
        
        .sidebar-brand .brand-text {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: white;
        }
        
        .sidebar-brand .brand-text span {
            color: #22C55E;
        }
        
        .sidebar-menu {
            flex: 1;
            padding: 20px 12px;
        }
        
        .sidebar-menu .menu-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.4);
            padding: 0 12px;
            margin-bottom: 12px;
            font-weight: 600;
        }
        
        .sidebar-menu .menu-item {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            margin-bottom: 2px;
            gap: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 500;
        }
        
        .sidebar-menu .menu-item:hover {
            background: rgba(255, 255, 255, 0.08);
            color: white;
        }
        
        .sidebar-menu .menu-item.active {
            background: rgba(34, 197, 94, 0.15);
            color: #22C55E;
        }
        
        .sidebar-menu .menu-item i {
            width: 20px;
            font-size: 16px;
        }
        
        .sidebar-menu .menu-item .badge {
            margin-left: auto;
            background: #DC2626;
            color: white;
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 600;
            min-width: 18px;
            text-align: center;
        }
        
        .sidebar-menu .menu-item .badge.badge-success {
            background: #16A34A;
        }
        
        .sidebar-menu .menu-item .badge.badge-warning {
            background: #F59E0B;
        }
        
        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-footer .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar-footer .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(34, 197, 94, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #22C55E;
        }
        
        .sidebar-footer .user-name {
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            color: white;
        }
        
        .sidebar-footer .user-role {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 20px;
            min-height: 100vh;
        }
        
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            background: white;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: fit-content;
        }
        
        .topbar-left .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 22px;
            color: #14532D;
            cursor: pointer;
        }
        
        .topbar-left .page-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 20px;
            font-weight: 600;
            color: #052E16;
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .btn-logout {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            padding: 6px 14px;
            border-radius: 8px;
            border: 1px solid #FECACA;
            color: #DC2626;
            background: transparent;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background: #FEE2E2;
            border-color: #DC2626;
        }
        
        .content-card {
            background: white;
            border-radius: 12px;
            padding: 20px 24px;
            border: 1px solid #E5EDE7;
            margin-bottom: 20px;
        }
        
        .content-card .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .content-card .card-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: #052E16;
        }
        
        .content-card .card-action {
            font-size: 13px;
            color: #16A34A;
            text-decoration: none;
            font-weight: 500;
        }
        
        .content-card .card-action:hover {
            color: #14532D;
        }
        
        .flash-messages {
            margin-bottom: 20px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-success { background: #DCFCE7; color: #065F46; border: 1px solid #BBF7D0; }
        .alert-error { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }
        .alert-warning { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
        .alert-info { background: #DBEAFE; color: #1E40AF; border: 1px solid #BFDBFE; }
        
        .alert .close-btn {
            margin-left: auto;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: inherit;
            opacity: 0.6;
        }
        
        .alert .close-btn:hover {
            opacity: 1;
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 999;
        }
        
        .sidebar-overlay.active { display: block; }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 16px; }
            .topbar-left .menu-toggle { display: block; }
            .topbar { padding: 12px 16px;  gap: 12px; position: sticky; top: 0; z-index: 12;}
            .topbar-right { width: 100%; justify-content: flex-end; }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="dashboard-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon"><i class="fas fa-seedling"></i></div>
                <div class="brand-text">Samridhi<span>Agro</span></div>
            </div>
            
            <nav class="sidebar-menu">
                <div class="menu-label">Main</div>
                <a href="dashboard.php" class="menu-item <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
                
                <div class="menu-label" style="margin-top: 20px;">Shop</div>
                <a href="orders.php" class="menu-item <?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> My Orders
                    <?php 
                    // Count pending orders
                    $pendingOrders = 0;
                    if (isset($_SESSION['user_id'])) {
                        $sql = "SELECT COUNT(*) as count FROM orders o 
                                JOIN shops s ON o.shop_id = s.id 
                                WHERE s.user_id = ? AND o.status = 'pending'";
                        $result = $db->fetchOne($sql, [$_SESSION['user_id']]);
                        $pendingOrders = $result['count'] ?? 0;
                    }
                    if ($pendingOrders > 0): ?>
                        <span class="badge"><?php echo $pendingOrders; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="products.php" class="menu-item <?php echo $currentPage === 'products.php' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i> Products
                </a>
                
                <a href="cart.php" class="menu-item <?php echo $currentPage === 'cart.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-bag"></i> Cart
                    <?php if ($cartCount > 0): ?>
                        <span class="badge"><?php echo $cartCount; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="payments.php" class="menu-item <?php echo $currentPage === 'payments.php' ? 'active' : ''; ?>">
                    <i class="fas fa-credit-card"></i> Payments
                    <?php if ($pendingPayments > 0): ?>
                        <span class="badge"><?php echo $pendingPayments; ?></span>
                    <?php endif; ?>
                </a>
                
                <div class="menu-label" style="margin-top: 20px;">Account</div>
                <a href="profile.php" class="menu-item <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i> My Profile
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar"><i class="fas fa-user"></i></div>
                    <div>
                        <div class="user-name"><?php echo escapeHtml($shop['full_name'] ?? 'Shop'); ?></div>
                        <div class="user-role">Shop Owner</div>
                    </div>
                </div>
            </div>
        </aside>
        
        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
                    <h1 class="page-title"><?php echo escapeHtml($pageTitle); ?></h1>
                </div>
                <div class="topbar-right">
                    <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            
            <?php
            $flashMessages = getFlashMessages();
            if (!empty($flashMessages)):
            ?>
            <div class="flash-messages">
                <?php foreach ($flashMessages as $type => $messages): ?>
                    <?php foreach ($messages as $message): ?>
                    <div class="alert alert-<?php echo $type; ?>">
                        <i class="fas fa-<?php 
                            echo match($type) {
                                'success' => 'check-circle',
                                'error' => 'exclamation-circle',
                                'warning' => 'exclamation-triangle',
                                'info' => 'info-circle',
                                default => 'circle'
                            };
                        ?>"></i>
                        <span><?php echo escapeHtml($message); ?></span>
                        <button class="close-btn" onclick="this.parentElement.remove()">&times;</button>
                    </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>