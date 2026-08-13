<?php
/**
 * SAMRIDHI AGRO - Dashboard Data API
 * 
 * This file provides JSON data for dashboard charts and statistics.
 * It is called via AJAX from the dashboard page.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 1.1.0
 */

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response

// Include configuration and security
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    initSecureSession();
}

// ============================================
// PERMISSION CHECK - Allow Admin OR Staff with permission
// ============================================
requireLogin();

// Admin has all access, Staff needs specific permission
if (!isAdmin() && !hasPermission('agent.view')) {
    logActivity('unauthorized_access', $_SESSION['user_id'], 'security', 
                'Attempted to access agents.php without permission');
    setFlashMessage('error', 'You do not have permission to access this page.');
    redirect('dashboard.php');
    exit;
}

// Check if user has edit permissions for actions
$canEdit = isAdmin() || hasPermission('agent.edit');
$canDelete = isAdmin() || hasPermission('agent.delete');
$canApprove = isAdmin() || hasPermission('agent.approve');
$canCreate = isAdmin() || hasPermission('agent.create');

// Set JSON content type
header('Content-Type: application/json');

// Get database instance
$db = getDB();

// Determine which data to fetch
$type = $_GET['type'] ?? 'all';

$response = [];

try {
    switch ($type) {
        case 'sales-trend':
            $response = getSalesTrendData($db);
            break;
            
        case 'order-status':
            $response = getOrderStatusData($db);
            break;
            
        case 'category-distribution':
            $response = getCategoryDistributionData($db);
            break;
            
        case 'monthly-revenue':
            $response = getMonthlyRevenueData($db);
            break;
            
        case 'recent-activity':
            $response = getRecentActivityData($db);
            break;
            
        case 'all':
        default:
            $response = [
                'salesTrend' => getSalesTrendData($db),
                'orderStatus' => getOrderStatusData($db),
                'categoryDistribution' => getCategoryDistributionData($db),
                'monthlyRevenue' => getMonthlyRevenueData($db),
                'recentActivity' => getRecentActivityData($db)
            ];
            break;
    }
    
    $response['success'] = true;
    
} catch (Exception $e) {
    // Log the error
    error_log('Dashboard Data API Error: ' . $e->getMessage());
    
    $response = [
        'success' => false,
        'error' => 'An error occurred while fetching dashboard data.'
    ];
}

// Output JSON response
echo json_encode($response);
exit;

// ============================================
// DATA FETCHING FUNCTIONS
// ============================================

/**
 * Get sales trend data for the last 12 months
 * 
 * @param Database $db Database instance
 * @return array Sales trend data
 */
function getSalesTrendData($db) {
    $months = [];
    $sales = [];
    
    // Get last 12 months
    for ($i = 11; $i >= 0; $i--) {
        $date = date('Y-m-01', strtotime("-$i months"));
        $months[] = date('M', strtotime($date));
        
        // Get sales for this month (delivered orders only)
        $start = date('Y-m-01', strtotime($date));
        $end = date('Y-m-t', strtotime($date));
        
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as total 
                FROM orders 
                WHERE status = 'delivered' 
                AND order_date BETWEEN ? AND ?";
        $result = $db->fetchOne($sql, [$start . ' 00:00:00', $end . ' 23:59:59']);
        $sales[] = round($result['total'] ?? 0, 2);
    }
    
    // If no sales data, provide sample data for demo
    if (array_sum($sales) == 0) {
        // Generate some sample data for demonstration
        $sales = [];
        for ($i = 0; $i < 12; $i++) {
            $sales[] = rand(5000, 50000);
        }
    }
    
    return [
        'labels' => $months,
        'datasets' => [
            [
                'label' => 'Sales',
                'data' => $sales,
                'backgroundColor' => 'rgba(22, 163, 74, 0.2)',
                'borderColor' => '#16A34A',
                'borderWidth' => 2,
                'fill' => true,
                'tension' => 0.4
            ]
        ]
    ];
}

/**
 * Get order status distribution data
 * 
 * @param Database $db Database instance
 * @return array Order status data
 */
function getOrderStatusData($db) {
    $sql = "SELECT status, COUNT(*) as count 
            FROM orders 
            GROUP BY status";
    $results = $db->fetchAll($sql);
    
    $labels = [];
    $data = [];
    $colors = [];
    
    $colorMap = [
        'pending' => '#F59E0B',
        'confirmed' => '#3B82F6',
        'processing' => '#8B5CF6',
        'shipped' => '#06B6D4',
        'delivered' => '#22C55E',
        'cancelled' => '#EF4444',
        'returned' => '#F59E0B'
    ];
    
    if (empty($results)) {
        // Provide sample data if no orders exist
        return [
            'labels' => ['No Data'],
            'datasets' => [
                [
                    'data' => [1],
                    'backgroundColor' => ['#E5EDE7'],
                    'borderWidth' => 1
                ]
            ]
        ];
    }
    
    foreach ($results as $row) {
        $labels[] = ucfirst($row['status']);
        $data[] = (int)$row['count'];
        $colors[] = $colorMap[$row['status']] ?? '#6B7280';
    }
    
    return [
        'labels' => $labels,
        'datasets' => [
            [
                'data' => $data,
                'backgroundColor' => $colors,
                'borderWidth' => 1
            ]
        ]
    ];
}

/**
 * Get product category distribution data
 * 
 * @param Database $db Database instance
 * @return array Category distribution data
 */
function getCategoryDistributionData($db) {
    $sql = "SELECT c.category_name, COUNT(p.id) as count 
            FROM categories c 
            LEFT JOIN products p ON c.id = p.category_id 
            WHERE c.status = 'active' 
            GROUP BY c.id 
            ORDER BY count DESC 
            LIMIT 8";
    $results = $db->fetchAll($sql);
    
    $labels = [];
    $data = [];
    $colors = [
        '#14532D', '#16A34A', '#22C55E', '#65A30D',
        '#EAB308', '#B45309', '#DC2626', '#2563EB'
    ];
    
    if (empty($results)) {
        // Provide sample data if no categories exist
        return [
            'labels' => ['No Categories'],
            'datasets' => [
                [
                    'data' => [1],
                    'backgroundColor' => ['#E5EDE7'],
                    'borderWidth' => 1
                ]
            ]
        ];
    }
    
    foreach ($results as $index => $row) {
        $labels[] = $row['category_name'];
        $data[] = (int)$row['count'];
    }
    
    return [
        'labels' => $labels,
        'datasets' => [
            [
                'data' => $data,
                'backgroundColor' => array_slice($colors, 0, count($data)),
                'borderWidth' => 1
            ]
        ]
    ];
}

/**
 * Get monthly revenue data for the last 6 months
 * 
 * @param Database $db Database instance
 * @return array Monthly revenue data
 */
function getMonthlyRevenueData($db) {
    $months = [];
    $revenue = [];
    $orders = [];
    
    // Get last 6 months
    for ($i = 5; $i >= 0; $i--) {
        $date = date('Y-m-01', strtotime("-$i months"));
        $months[] = date('M', strtotime($date));
        
        $start = date('Y-m-01', strtotime($date));
        $end = date('Y-m-t', strtotime($date));
        
        // Revenue
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as total 
                FROM orders 
                WHERE status = 'delivered' 
                AND order_date BETWEEN ? AND ?";
        $result = $db->fetchOne($sql, [$start . ' 00:00:00', $end . ' 23:59:59']);
        $revenue[] = round($result['total'] ?? 0, 2);
        
        // Order count
        $sql = "SELECT COUNT(*) as count 
                FROM orders 
                WHERE order_date BETWEEN ? AND ?";
        $result = $db->fetchOne($sql, [$start . ' 00:00:00', $end . ' 23:59:59']);
        $orders[] = (int)($result['count'] ?? 0);
    }
    
    // If no revenue data, provide sample data for demo
    if (array_sum($revenue) == 0) {
        $revenue = [];
        $orders = [];
        for ($i = 0; $i < 6; $i++) {
            $revenue[] = rand(10000, 80000);
            $orders[] = rand(5, 30);
        }
    }
    
    return [
        'labels' => $months,
        'datasets' => [
            [
                'label' => 'Revenue (₹)',
                'data' => $revenue,
                'backgroundColor' => 'rgba(20, 83, 45, 0.2)',
                'borderColor' => '#14532D',
                'borderWidth' => 2,
                'yAxisID' => 'y',
                'tension' => 0.4,
                'fill' => true
            ],
            [
                'label' => 'Orders',
                'data' => $orders,
                'backgroundColor' => 'rgba(234, 179, 8, 0.2)',
                'borderColor' => '#EAB308',
                'borderWidth' => 2,
                'yAxisID' => 'y1',
                'tension' => 0.4,
                'fill' => true
            ]
        ]
    ];
}

/**
 * Get recent activity data
 * 
 * @param Database $db Database instance
 * @return array Recent activity data
 */
function getRecentActivityData($db) {
    $sql = "SELECT al.*, u.full_name 
            FROM activity_logs al 
            LEFT JOIN users u ON al.user_id = u.id 
            ORDER BY al.created_at DESC 
            LIMIT 10";
    $results = $db->fetchAll($sql);
    
    $activities = [];
    
    if (empty($results)) {
        // Provide sample activity if no logs exist
        return [
            [
                'id' => 1,
                'user' => 'System',
                'action' => 'info',
                'description' => 'Welcome to Samridhi Agro dashboard',
                'time' => 'Just now',
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
    }
    
    foreach ($results as $row) {
        // Get the action for icon mapping
        $action = $row['action'] ?? 'Unknown';
        
        // Map action to user-friendly description
        $description = $row['description'] ?? $action;
        
        $activities[] = [
            'id' => $row['id'],
            'user' => $row['full_name'] ?? 'System',
            'action' => $action,
            'description' => $description,
            'time' => timeAgo($row['created_at']),
            'timestamp' => $row['created_at']
        ];
    }
    
    return $activities;
}