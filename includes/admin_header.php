<?php
// ============================================
// INCLUDE ALL REQUIRED FILES
// ============================================

// NO WHITESPACE OR OUTPUT BEFORE THIS POINT

// Include configuration
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    initSecureSession();
}

// ============================================
// PAGE VARIABLES
// ============================================

// Ensure required variables are set
$pageTitle = $pageTitle ?? 'Admin Panel';
$currentPage = basename($_SERVER['PHP_SELF']);

// ============================================
// GET CURRENT USER DATA
// ============================================

$currentUser = getCurrentUser();

// Get current user with avatar
$db = getDB();

$sql = "SELECT u.*
        FROM users u
        WHERE u.id = ?";

$currentUserWithAvatar = $db->fetchOne(
    $sql,
    [$_SESSION['user_id'] ?? 0]
);
// ============================================
// CHECK USER ROLE FOR DYNAMIC HEADER
// ============================================

$isAdmin = isAdmin();
$isStaff = isStaff();

// ============================================
// GET PENDING COUNTS FOR BADGES
// ============================================

$db = getDB();

// Pending Shops
$sql = "SELECT COUNT(*) as count FROM shops WHERE status = 'pending'";
$result = $db->fetchOne($sql);
$pendingShops = $result['count'] ?? 0;

// Pending Agents
$sql = "SELECT COUNT(*) as count FROM agents WHERE status = 'pending'";
$result = $db->fetchOne($sql);
$pendingAgents = $result['count'] ?? 0;

// Pending Orders
$sql = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
$result = $db->fetchOne($sql);
$pendingOrders = $result['count'] ?? 0;

// Pending Payments (submitted to admin)
$sql = "SELECT COUNT(*) as count FROM shop_payments WHERE status = 'submitted'";
$result = $db->fetchOne($sql);
$pendingPayments = $result['count'] ?? 0;

$totalPending = $pendingShops + $pendingAgents + $pendingOrders + $pendingPayments;

// Notification count
$notificationCount = 3;

// Staff specific notifications
$staffNotificationCount = 0;
if ($isStaff) {
    // New leads for staff
    $sql = "SELECT COUNT(*) as count FROM staff_leads WHERE staff_id = ? AND status = 'new'";
    $result = $db->fetchOne($sql, [$_SESSION['user_id'] ?? 0]);
    $staffNotificationCount += $result['count'] ?? 0;

    // Planned visits for staff
    $sql = "SELECT COUNT(*) as count FROM staff_visits WHERE staff_id = ? AND status = 'planned' AND visit_date >= CURDATE()";
    $result = $db->fetchOne($sql, [$_SESSION['user_id'] ?? 0]);
    $staffNotificationCount += $result['count'] ?? 0;
}

// ============================================
// PERMISSION CHECKS FOR STAFF (when viewing admin pages)
// ============================================

$canViewAgents = $isAdmin || hasPermission('agent.view');
$canViewShops = $isAdmin || hasPermission('shop.view');
$canViewProducts = $isAdmin || hasPermission('product.view');
$canViewCategories = $isAdmin || hasPermission('category.view');
$canViewOrders = $isAdmin || hasPermission('order.view');
$canViewPayments = $isAdmin || hasPermission('payment.view');
$canViewReports = $isAdmin || hasPermission('report.view');
$canViewInventory = $isAdmin || hasPermission('inventory.view');
$canManageStaff = $isAdmin || hasPermission('staff.view');
$canViewAttendanceSettings = $isAdmin || hasPermission('attendance.settings.view');
$canViewAttendance = $isAdmin || hasPermission('attendance.list');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escapeHtml($pageTitle); ?> - Samridhi Agro</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">

    <style>
        /* Admin Dashboard Common Styles */
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
            background: #F7FCF7;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
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
            padding: 0 24px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-brand .brand-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #22C55E;
        }

        .sidebar-brand .brand-text {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 20px;
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
            padding: 12px 16px;
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
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 600;
        }

        .sidebar-menu .menu-item .badge.badge-warning {
            background: #F59E0B;
        }

        .sidebar-menu .menu-item .badge.badge-success {
            background: #16A34A;
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

        .sidebar-footer {
            padding: 20px 24px;
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
            font-size: 18px;
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

        /* Main Content */
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 24px;
            min-height: 100vh;
        }

        /* Top Bar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            background: white;
            border-radius: 16px;
            margin-bottom: 24px;
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
            font-size: 24px;
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
            gap: 16px;
        }

        .topbar-right .notification-btn {
            position: relative;
            background: none;
            border: none;
            font-size: 20px;
            color: #4A5B5D;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .topbar-right .notification-btn:hover {
            background: #F0FDF4;
            color: #14532D;
        }

        .topbar-right .notification-btn .notif-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            background: #DC2626;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 50%;
            font-weight: 600;
            min-width: 18px;
            text-align: center;
        }

        .btn-logout {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            padding: 8px 16px;
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

        /* Content Card */
        .content-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #E5EDE7;
            margin-bottom: 24px;
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
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: #16A34A;
            text-decoration: none;
            font-weight: 500;
        }

        .content-card .card-action:hover {
            color: #14532D;
        }

        /* Table Styles */
        .table-wrapper {
            overflow-x: auto;
        }

        .table-custom {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
        }

        .table-custom th {
            text-align: left;
            padding: 12px 8px;
            font-weight: 600;
            color: #6B7A7B;
            border-bottom: 2px solid #E5EDE7;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-custom td {
            padding: 12px 8px;
            border-bottom: 1px solid #F0FDF4;
            color: #4A5B5D;
        }

        .table-custom tr:hover td {
            background: #F7FCF7;
        }

        .badge-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-status.badge-success {
            background: #DCFCE7;
            color: #065F46;
        }

        .badge-status.badge-warning {
            background: #FEF3C7;
            color: #92400E;
        }

        .badge-status.badge-danger {
            background: #FEE2E2;
            color: #991B1B;
        }

        .badge-status.badge-info {
            background: #DBEAFE;
            color: #1E40AF;
        }

        .badge-status.badge-primary {
            background: #EDE9FE;
            color: #5B21B6;
        }

        /* Mobile Responsive */
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

            .topbar {
                padding: 12px 16px;
                flex-wrap: wrap;
                gap: 12px;
            }

            .topbar-right {
                /* width: 100%; */
                justify-content: flex-end;
            }

            .topbar-left .page-title {
                font-size: 18px;
            }
        }

        /* Sidebar overlay for mobile */
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

        /* Flash Messages */
        .flash-messages {
            margin-bottom: 20px;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }

        .sidebar-footer .user-avatar {
            overflow: hidden;
            flex-shrink: 0;
            border: 2px solid rgba(34, 197, 94, 0.3);
        }

        .sidebar-footer .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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

        .alert-icon {
            font-size: 18px;
        }

        .alert .close-btn {
            margin-left: auto;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: inherit;
            opacity: 0.6;
            padding: 0 4px;
        }

        .alert .close-btn:hover {
            opacity: 1;
        }
    </style>
</head>

<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">
                    <i class="fas fa-seedling"></i>
                </div>
                <div class="brand-text">Samridhi<span>Agro</span></div>
            </div>

            <nav class="sidebar-menu">
                <!-- Main Menu - Common for all -->
                <div class="menu-label">Main</div>
                <a href="<?php echo ADMIN_URL; ?>dashboard.php" class="menu-item <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-th-large"></i>
                    Dashboard
                </a>

                <!-- ============================================ -->
                <!-- STAFF WORK SECTION (Only for Staff)          -->
                <!-- ============================================ -->
                <?php if ($isStaff): ?>
                    <div class="menu-label" style="margin-top: 20px;">My Work</div>

                    <a href="attendance.php" class="menu-item <?php echo $currentPage === 'attendance.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i> Attendance
                    </a>
                <?php endif; ?>


                <!-- ============================================ -->
                <!-- MANAGEMENT SECTION (Admin or Staff with permissions) -->
                <!-- ============================================ -->
                <div class="menu-label" style="margin-top: 20px;">Management</div>


                <!-- Staff Management -->
                <?php if (hasPermission('staff.view')): ?>
                    <a href="<?php echo ADMIN_URL; ?>staff.php" class="menu-item <?php echo in_array($currentPage, ['staff.php', 'staff-attendance.php', 'staff-visits.php', 'staff-leads.php']) ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        Staff
                    </a>
                <?php endif; ?>
                <!-- Agents - Admin or Staff with permission -->
                <?php if ($canViewAgents): ?>
                    <a href="<?php echo ADMIN_URL; ?>agents.php" class="menu-item <?php echo $currentPage === 'agents.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-tie"></i>
                        Agents
                        <?php if ($pendingAgents > 0): ?>
                            <span class="badge badge-warning"><?php echo $pendingAgents; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>


                <a href="<?php echo ADMIN_URL; ?>visits.php" class="menu-item <?php echo $currentPage === 'visits.php' ? 'active' : ''; ?>">
                    <i class="fas fa-route"></i>
                    Visits

                </a>

                <!-- Shops - Admin or Staff with permission -->
                <?php if ($canViewShops): ?>
                    <a href="<?php echo ADMIN_URL; ?>shops.php" class="menu-item <?php echo $currentPage === 'shops.php' ? 'active' : ''; ?>">
                        <i class="fas fa-store"></i>
                        Shops
                        <?php if ($pendingShops > 0): ?>
                            <span class="badge badge-warning"><?php echo $pendingShops; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <!-- Products - Admin or Staff with permission -->
                <?php if ($canViewProducts): ?>
                    <a href="<?php echo ADMIN_URL; ?>products.php" class="menu-item <?php echo in_array($currentPage, ['products.php']) ? 'active' : ''; ?>">
                        <i class="fas fa-box"></i>
                        Products
                    </a>
                    <!-- <div class="sub-menu">
                        <a href="<?php echo ADMIN_URL; ?>products.php" class="menu-item <?php echo $currentPage === 'products.php' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> All Products
                        </a>
                        
                    </div> -->
                <?php endif; ?>

                <!-- category - Admin or Staff with permission -->
                <?php if ($canViewCategories): ?>
                    <a href="<?php echo ADMIN_URL; ?>categories.php" class="menu-item <?php echo $currentPage === 'categories.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i>
                        Categories
                    </a>
                <?php endif; ?>

                <!-- Orders - Admin or Staff with permission -->
                <?php if ($canViewOrders): ?>
                    <a href="<?php echo ADMIN_URL; ?>orders.php" class="menu-item <?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-cart"></i>
                        Orders
                        <?php if ($pendingOrders > 0): ?>
                            <span class="badge badge-warning"><?php echo $pendingOrders; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <!-- Payments - Admin or Staff with permission -->
                <?php if ($canViewPayments): ?>
                    <a href="<?php echo ADMIN_URL; ?>payments.php" class="menu-item <?php echo $currentPage === 'payments.php' ? 'active' : ''; ?>">
                        <i class="fas fa-credit-card"></i>
                        Payments
                        <?php if ($pendingPayments > 0): ?>
                            <span class="badge badge-warning"><?php echo $pendingPayments; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <!-- Inventory - Admin or Staff with permission -->
                <?php if ($canViewInventory): ?>
                    <a href="<?php echo ADMIN_URL; ?>inventory.php" class="menu-item <?php echo $currentPage === 'inventory.php' ? 'active' : ''; ?>">
                        <i class="fas fa-warehouse"></i>
                        Inventory
                    </a>
                <?php endif; ?>

                <?php if ($canViewAttendance): ?>
                    <a href="<?php echo ADMIN_URL; ?>attendance-list.php" class="menu-item <?php echo $currentPage === 'attendance-list.php' ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i> Attendance List
                    </a>
                <?php endif; ?>
                <?php if ($canViewAttendanceSettings): ?>
                    <a href="<?php echo ADMIN_URL; ?>attendance-settings.php" class="menu-item <?php echo $currentPage === 'attendance-settings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i> Attendance Settings
                    </a>
                <?php endif; ?>
                <!-- ============================================ -->
                <!-- SYSTEM SECTION (Admin only)                   -->
                <!-- ============================================ -->
                <?php if ($isAdmin): ?>
                    <div class="menu-label" style="margin-top: 20px;">System</div>

                    <a href="<?php echo ADMIN_URL; ?>reports.php" class="menu-item <?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                    <a href="<?php echo ADMIN_URL; ?>settings.php" class="menu-item <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <a href="<?php echo ADMIN_URL; ?>profile.php" class="menu-item <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-circle"></i> My Profile
                    </a>
                    <a href="<?php echo ADMIN_URL; ?>activity-logs.php" class="menu-item <?php echo $currentPage === 'activity-logs.php' ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i> Activity Logs
                    </a>
                <?php else: ?>
                    <!-- Staff Profile -->
                    <div class="menu-label" style="margin-top: 20px;">Account</div>
                    <a href="staff-profile.php" class="menu-item <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-circle"></i> My Profile
                    </a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php
                        $avatar = $currentUserWithAvatar['avatar'] ?? '';

                        if (!empty($avatar) && file_exists('../uploads/avatars/' . $avatar)):
                        ?>
                            <img src="../uploads/avatars/<?php echo escapeHtml($avatar); ?>"
                                alt="<?php echo escapeHtml($currentUserWithAvatar['full_name'] ?? 'User'); ?>">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="user-name"><?php echo escapeHtml($currentUser['full_name'] ?? 'User'); ?></div>
                        <div class="user-role">
                            <?php if ($isAdmin): ?>
                                Administrator
                            <?php elseif ($isStaff): ?>
                                Staff Member
                            <?php else: ?>
                                User
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title"><?php echo escapeHtml($pageTitle); ?></h1>
                </div>
                <div class="topbar-right">
                    <!-- <button class="notification-btn" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <?php if ($notificationCount > 0): ?>
                            <span class="notif-badge"><?php echo $notificationCount; ?></span>
                        <?php endif; ?>
                    </button> -->
                    <a href="<?php echo ADMIN_URL; ?>logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php
            $flashMessages = getFlashMessages();
            if (!empty($flashMessages)):
            ?>
                <script>
                    window.__flashMessages = <?php echo json_encode($flashMessages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
                </script>
            <?php endif; ?>