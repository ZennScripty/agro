<?php

/**
 * SAMRIDHI AGRO - Staff Header Include
 * 
 * This file contains the common header structure for all staff pages.
 * 
 * @package SamridhiAgro
 * @subpackage Includes
 * @version 1.1.1
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
$pageTitle = $pageTitle ?? 'Staff Panel';
$currentPage = basename($_SERVER['PHP_SELF']);

// Get current user data
$currentUser = getCurrentUser();

// Get staff data with avatar
$db = getDB();
$sql = "SELECT u.*, sp.department, sp.designation 
        FROM users u 
        LEFT JOIN staff_profiles sp ON u.id = sp.user_id 
        WHERE u.id = ?";
$staff = $db->fetchOne($sql, [$_SESSION['user_id'] ?? 0]);

// ============================================
// PERMISSION-BASED MENU ITEMS
// ============================================

// Staff specific permissions
$canViewAttendance = hasPermission('staff.attendance.view');
$canViewVisits = hasPermission('staff.visits.view');

// Admin pages access (if permissions granted)
$canViewAgents = isAdmin() || hasPermission('agent.view');
$canViewShops = isAdmin() || hasPermission('shop.view');
$canViewProducts = isAdmin() || hasPermission('product.view');
$canViewOrders = isAdmin() || hasPermission('order.view');
$canViewPayments = isAdmin() || hasPermission('payment.view');
$canViewReports = isAdmin() || hasPermission('report.view');

// Get notification counts
$notificationCount = 0;



if ($canViewVisits) {
    $sql = "SELECT COUNT(*) as count FROM staff_visits WHERE staff_id = ? AND status = 'planned' AND visit_date >= CURDATE()";
    $result = $db->fetchOne($sql, [$_SESSION['user_id'] ?? 0]);
    $notificationCount += $result['count'] ?? 0;
}

// Pending counts for badges
$pendingAgents = 0;
$pendingShops = 0;
$pendingOrders = 0;
$pendingPayments = 0;

if ($canViewAgents) {
    $sql = "SELECT COUNT(*) as count FROM agents WHERE status = 'pending'";
    $result = $db->fetchOne($sql);
    $pendingAgents = $result['count'] ?? 0;
}

if ($canViewShops) {
    $sql = "SELECT COUNT(*) as count FROM shops WHERE status = 'pending'";
    $result = $db->fetchOne($sql);
    $pendingShops = $result['count'] ?? 0;
}

if ($canViewOrders) {
    $sql = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
    $result = $db->fetchOne($sql);
    $pendingOrders = $result['count'] ?? 0;
}

if ($canViewPayments) {
    $sql = "SELECT COUNT(*) as count FROM shop_payments WHERE status = 'submitted'";
    $result = $db->fetchOne($sql);
    $pendingPayments = $result['count'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escapeHtml($pageTitle); ?> - Staff Portal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
            background: #F7FCF7;
        }

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

        .sidebar-menu .menu-item .badge.badge-warning {
            background: #F59E0B;
        }

        .sidebar-menu .menu-item .badge.badge-success {
            background: #16A34A;
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
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(34, 197, 94, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #22C55E;
            overflow: hidden;
            flex-shrink: 0;
            border: 2px solid rgba(34, 197, 94, 0.3);
        }

        .sidebar-footer .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .sidebar-footer .user-avatar .avatar-placeholder {
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
            min-width: fit-content;
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
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #DCFCE7;
            color: #065F46;
            border: 1px solid #BBF7D0;
        }

        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }

        .alert-warning {
            background: #FEF3C7;
            color: #92400E;
            border: 1px solid #FDE68A;
        }

        .alert-info {
            background: #DBEAFE;
            color: #1E40AF;
            border: 1px solid #BFDBFE;
        }

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

        .sidebar-overlay.active {
            display: block;
        }

        /* Sub-menu styles */
        .sidebar-menu .sub-menu {
            padding-left: 20px;
            margin-left: 20px;
            border-left: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-menu .sub-menu .menu-item {
            padding: 8px 16px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.5);
        }

        .sidebar-menu .sub-menu .menu-item:hover {
            color: rgba(255, 255, 255, 0.8);
        }

        .sidebar-menu .sub-menu .menu-item.active {
            color: #22C55E;
        }

        .sidebar-menu .sub-menu .menu-item i {
            font-size: 13px;
            width: 16px;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 16px;
            }

            .topbar-left .menu-toggle {
                display: block;
            }

            .topbar-right {
                width: 100%;
                justify-content: flex-end;
            }
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

                <div class="menu-label" style="margin-top: 20px;">My Work</div>

                <?php if ($canViewAttendance): ?>
                    <a href="attendence.php" class="menu-item <?php echo $currentPage === 'attendence.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i> Attendance
                    </a>
                <?php endif; ?>

                <?php if ($canViewVisits): ?>
                    <a href="../admin/visits.php" class="menu-item <?php echo $currentPage === 'visits.php' ? 'active' : ''; ?>">
                        <i class="fas fa-route"></i> Visits
                        <?php if ($notificationCount > 0): ?>
                            <span class="badge"><?php echo $notificationCount; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>



                <div class="menu-label" style="margin-top: 20px;">Management</div>

                <?php if ($canViewAgents): ?>
                    <a href="../admin/agents.php" class="menu-item <?php echo $currentPage === 'agents.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-tie"></i> Agents
                        <?php if ($pendingAgents > 0): ?>
                            <span class="badge badge-warning"><?php echo $pendingAgents; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <?php if ($canViewShops): ?>
                    <a href="../admin/shops.php" class="menu-item <?php echo $currentPage === 'shops.php' ? 'active' : ''; ?>">
                        <i class="fas fa-store"></i> Shops
                        <?php if ($pendingShops > 0): ?>
                            <span class="badge badge-warning"><?php echo $pendingShops; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <?php if ($canViewProducts): ?>
                    <a href="../admin/products.php" class="menu-item <?php echo $currentPage === 'products.php' ? 'active' : ''; ?>">
                        <i class="fas fa-box"></i> Products
                    </a>
                <?php endif; ?>

                <?php if ($canViewOrders): ?>
                    <a href="../admin/orders.php" class="menu-item <?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-cart"></i> Orders
                        <?php if ($pendingOrders > 0): ?>
                            <span class="badge badge-warning"><?php echo $pendingOrders; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <?php if ($canViewPayments): ?>
                    <a href="../admin/payments.php" class="menu-item <?php echo $currentPage === 'payments.php' ? 'active' : ''; ?>">
                        <i class="fas fa-credit-card"></i> Payments
                        <?php if ($pendingPayments > 0): ?>
                            <span class="badge badge-warning"><?php echo $pendingPayments; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <?php if ($canViewReports): ?>
                    <a href="../admin/reports.php" class="menu-item <?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                <?php endif; ?>

                <div class="menu-label" style="margin-top: 20px;">Account</div>
                <a href="profile.php" class="menu-item <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i> My Profile
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php if (!empty($staff['avatar']) && file_exists('../uploads/avatars/' . $staff['avatar'])): ?>
                            <img src="../uploads/avatars/<?php echo escapeHtml($staff['avatar']); ?>" alt="<?php echo escapeHtml($staff['full_name'] ?? 'Staff'); ?>">
                        <?php else: ?>
                            <i class="fas fa-user avatar-placeholder"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="user-name"><?php echo escapeHtml($staff['full_name'] ?? 'Staff'); ?></div>
                        <div class="user-role"><?php echo escapeHtml($staff['designation'] ?? 'Staff Member'); ?></div>
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
                <script>
                    window.__flashMessages = <?php echo json_encode($flashMessages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
                </script>
            <?php endif; ?>