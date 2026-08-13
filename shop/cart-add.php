<?php
/**
 * SAMRIDHI AGRO - Shop Cart Add
 * 
 * AJAX handler for adding products to cart.
 * 
 * @package SamridhiAgro
 * @subpackage Shop
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/functions.php';

// Require shop login
if (!isLoggedIn() || !hasRole('shop')) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit;
}

// Validate CSRF token
if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

// Get product ID and quantity
$productId = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if ($productId <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
    exit;
}

// Get database instance
$db = getDB();

// Check if product exists and is available
$sql = "SELECT id, product_name, price, quantity FROM products WHERE id = ? AND status = 'active'";
$product = $db->fetchOne($sql, [$productId]);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

if ($product['quantity'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
    exit;
}

// Initialize cart if not exists
if (!isset($_SESSION['shop_cart'])) {
    $_SESSION['shop_cart'] = [];
}

// Add to cart or update quantity
if (isset($_SESSION['shop_cart'][$productId])) {
    // Check if adding more exceeds stock
    $newQuantity = $_SESSION['shop_cart'][$productId]['quantity'] + $quantity;
    if ($newQuantity > $product['quantity']) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
        exit;
    }
    $_SESSION['shop_cart'][$productId]['quantity'] = $newQuantity;
} else {
    $_SESSION['shop_cart'][$productId] = [
        'id' => $product['id'],
        'name' => $product['product_name'],
        'price' => $product['price'],
        'quantity' => $quantity,
        'max_quantity' => $product['quantity']
    ];
}

// Calculate cart count
$cartCount = 0;
foreach ($_SESSION['shop_cart'] as $item) {
    $cartCount += $item['quantity'];
}

echo json_encode([
    'success' => true,
    'message' => 'Product added to cart',
    'cart_count' => $cartCount
]);
exit;