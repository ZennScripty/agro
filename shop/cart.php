<?php
/**
 * SAMRIDHI AGRO - Shop Cart
 * 
 * This page displays the shop's cart and allows placing orders.
 * Payment record created automatically with order.
 * Payment management is handled in payments.php
 * 
 * @package SamridhiAgro
 * @subpackage Shop
 * @author Samridhi Agro Team
 * @version 2.0.5
 */

// Set page title
$pageTitle = 'My Cart';

// Include shop header
require_once __DIR__ . '/../includes/shop_header.php';

// Require shop login
requireLogin();
requireRole('shop');

// Get database instance
$db = getDB();

// Get shop data
$sql = "SELECT s.* FROM shops s WHERE s.user_id = ?";
$shop = $db->fetchOne($sql, [$_SESSION['user_id']]);

// Initialize cart if not exists
if (!isset($_SESSION['shop_cart'])) {
    $_SESSION['shop_cart'] = [];
}

// ============================================
// DECLARE VARIABLES AT TOP
// ============================================
$discount = 0; // Default discount

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token.');
        redirect('shop/cart.php');
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    
    // Remove item
    if ($action === 'remove') {
        $productId = (int)($_POST['product_id'] ?? 0);
        if (isset($_SESSION['shop_cart'][$productId])) {
            unset($_SESSION['shop_cart'][$productId]);
            setFlashMessage('success', 'Item removed from cart.');
        }
        redirect('shop/cart.php');
        exit;
    }
    
    // Update quantity
    if ($action === 'update') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        
        if ($quantity <= 0) {
            unset($_SESSION['shop_cart'][$productId]);
        } elseif (isset($_SESSION['shop_cart'][$productId])) {
            // Check stock
            $sql = "SELECT quantity FROM products WHERE id = ? AND status = 'active'";
            $product = $db->fetchOne($sql, [$productId]);
            if ($product && $quantity <= $product['quantity']) {
                $_SESSION['shop_cart'][$productId]['quantity'] = $quantity;
            } else {
                setFlashMessage('error', 'Not enough stock available.');
            }
        }
        redirect('shop/cart.php');
        exit;
    }
    
    // Place order (No Tax)
    if ($action === 'place_order') {
        if (empty($_SESSION['shop_cart'])) {
            setFlashMessage('error', 'Your cart is empty.');
            redirect('shop/cart.php');
            exit;
        }
        
        $shippingAddress = sanitizeInput($_POST['shipping_address'] ?? '');
        $shippingCity = sanitizeInput($_POST['shipping_city'] ?? '');
        $shippingState = sanitizeInput($_POST['shipping_state'] ?? '');
        $shippingPincode = sanitizeInput($_POST['shipping_pincode'] ?? '');
        $deliveryNotes = sanitizeInput($_POST['delivery_notes'] ?? '');
        
        if (empty($shippingAddress)) {
            setFlashMessage('error', 'Please enter shipping address.');
            redirect('shop/cart.php');
            exit;
        }
        
        try {
            // Calculate totals (NO TAX)
            $subtotal = 0;
            $items = [];
            foreach ($_SESSION['shop_cart'] as $productId => $item) {
                $subtotal += $item['price'] * $item['quantity'];
                $items[] = [
                    'product_id' => $productId,
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ];
            }
            
            // No tax - Grand Total = Subtotal
            $tax = 0;
            $discount = 0;
            $totalAmount = $subtotal - $discount;
            
            // Generate order number
            $orderNumber = 'ORD-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            
            // Start transaction
            $db->beginTransaction();
            
            // Insert order (NO TAX)
            $sql = "INSERT INTO orders (
                        order_number, shop_id, agent_id,
                        order_date, subtotal, tax, discount, total_amount,
                        status, payment_status, payment_method,
                        shipping_address, shipping_city, shipping_state, shipping_pincode,
                        delivery_notes, created_by, created_at
                    ) VALUES (?, ?, ?, NOW(), ?, 0, ?, ?, 'pending', 'pending', 'pending', ?, ?, ?, ?, ?, ?, NOW())";
            
            $db->query($sql, [
                $orderNumber,
                $shop['id'],
                $shop['agent_id'],
                $subtotal,
                $discount,
                $totalAmount,
                $shippingAddress,
                $shippingCity,
                $shippingState,
                $shippingPincode,
                $deliveryNotes,
                $_SESSION['user_id']
            ]);
            
            $orderId = $db->lastInsertId();
            
            // Insert order items
            foreach ($items as $item) {
                $sql = "INSERT INTO order_items (order_id, product_id, quantity, price, total, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
                $db->query($sql, [
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price'],
                    $item['price'] * $item['quantity']
                ]);
            }
            
            // Create payment record for this order
            // Payment will be managed in payments.php
            $sql = "INSERT INTO shop_payments (
                        shop_id, agent_id, order_id, payment_type, 
                        amount, paid_amount, remaining_amount,
                        installment_number, total_installments,
                        payment_date, payment_method, status, 
                        payment_received_by, created_at
                    ) VALUES (?, ?, ?, 'order_payment', ?, 0, ?, 1, 1, NOW(), 'pending', 'pending', 'shop', NOW())";
            
            $db->query($sql, [
                $shop['id'],
                $shop['agent_id'],
                $orderId,
                $totalAmount,
                $totalAmount, // remaining_amount = full amount initially
            ]);
            
            // Commit transaction
            $db->commit();
            
            // Clear cart
            $_SESSION['shop_cart'] = [];
            
            logActivity('create', $_SESSION['user_id'], 'order', 'Placed order #' . $orderNumber);
            
            setFlashMessage('success', 'Order placed successfully! Order #: ' . $orderNumber);
            redirect('shop/order-view.php?id=' . $orderId);
            exit;
            
        } catch (Exception $e) {
            $db->rollback();
            error_log('Order creation error: ' . $e->getMessage());
            setFlashMessage('error', 'Failed to place order. Please try again.');
            redirect('shop/cart.php');
            exit;
        }
    }
}

// Calculate cart totals
$cartItems = $_SESSION['shop_cart'] ?? [];
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$csrfToken = generateCsrfToken();
?>

<style>
    .cart-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .cart-table th {
        text-align: left;
        padding: 12px 10px;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6B7A7B;
        border-bottom: 2px solid #E5EDE7;
    }
    
    .cart-table td {
        padding: 12px 10px;
        border-bottom: 1px solid #F0FDF4;
        vertical-align: middle;
    }
    
    .cart-table .product-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .cart-table .product-info .product-thumb {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        background: #F3F4F6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: #6B7A7B;
        border: 1px solid #E5EDE7;
        flex-shrink: 0;
    }
    
    .cart-table .product-info .product-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .cart-table .qty-input-cart {
        width: 60px;
        padding: 6px 8px;
        border: 2px solid #E5EDE7;
        border-radius: 6px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        text-align: center;
    }
    
    .cart-table .qty-input-cart:focus {
        outline: none;
        border-color: #16A34A;
    }
    
    .btn-remove {
        padding: 4px 10px;
        background: #FEE2E2;
        color: #DC2626;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-remove:hover {
        background: #FECACA;
    }
    
    .cart-summary {
        background: #F7FCF7;
        border-radius: 12px;
        padding: 20px 24px;
        margin-top: 20px;
    }
    
    .cart-summary .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        font-size: 15px;
    }
    
    .cart-summary .summary-row.grand-total {
        font-size: 18px;
        font-weight: 700;
        padding-top: 12px;
        border-top: 2px solid #E5EDE7;
        margin-top: 6px;
        color: #052E16;
    }
    
    .cart-summary .summary-row.grand-total .value {
        color: #14532D;
    }
    
    .btn-place-order {
        padding: 14px 40px;
        background: linear-gradient(135deg, #14532D, #16A34A);
        color: white;
        border: none;
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-place-order:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(22, 163, 74, 0.3);
    }
    
    .btn-place-order:disabled {
        background: #E5EDE7;
        color: #6B7A7B;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .empty-cart {
        text-align: center;
        padding: 40px;
        color: #6B7A7B;
    }
    
    .empty-cart i {
        font-size: 48px;
        display: block;
        margin-bottom: 12px;
        color: #D1D5DB;
    }
    
    @media (max-width: 768px) {
        .cart-table {
            font-size: 13px;
        }
        .cart-table .product-info .product-thumb {
            width: 40px;
            height: 40px;
            font-size: 16px;
        }
        .cart-table .qty-input-cart {
            width: 50px;
        }
        .shipping-grid {
            grid-template-columns: 1fr !important;
        }
        .shipping-grid .full-width {
            grid-column: 1 !important;
        }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-shopping-bag" style="color: #16A34A;"></i>
            My Cart
            <?php if (!empty($cartItems)): ?>
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo array_sum(array_column($cartItems, 'quantity')); ?> items)
            </span>
            <?php endif; ?>
        </h3>
        <a href="products.php" class="card-action">
            <i class="fas fa-arrow-left"></i> Continue Shopping
        </a>
    </div>
    
    <?php if (empty($cartItems)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-bag"></i>
            <p>Your cart is empty</p>
            <a href="products.php" style="color: #16A34A; text-decoration: none; font-weight: 500;">
                <i class="fas fa-arrow-right"></i> Start Shopping
            </a>
        </div>
    <?php else: ?>
        <form method="POST" action="" id="cartForm">
            <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
            
            <div style="overflow-x: auto;">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $productId => $item): ?>
                        <tr>
                            <td>
                                <div class="product-info">
                                    <div class="product-thumb">
                                        <i class="fas fa-box"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 500; color: #052E16;">
                                            <?php echo escapeHtml($item['name']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>₹ <?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <form method="POST" action="" style="display: inline-flex; gap: 4px; align-items: center;">
                                    <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                                    <input type="number" name="quantity" class="qty-input-cart" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['max_quantity'] ?? 999; ?>" onchange="this.form.submit()">
                                </form>
                            </td>
                            <td>₹ <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            <td style="text-align: center;">
                                <form method="POST" action="" class="remove-item-form" style="display: inline;">
                                    <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                                    <button type="submit" class="btn-remove">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Order Summary (NO TAX) -->
            <div class="cart-summary">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>₹ <?php echo number_format($subtotal, 2); ?></span>
                </div>
                <?php if ($discount > 0): ?>
                <div class="summary-row">
                    <span>Discount</span>
                    <span>-₹ <?php echo number_format($discount, 2); ?></span>
                </div>
                <?php endif; ?>
                <div class="summary-row grand-total">
                    <span>Grand Total</span>
                    <span class="value">₹ <?php echo number_format($subtotal, 2); ?></span>
                </div>
            </div>
            
            <!-- Shipping Details -->
            <div style="margin-top: 20px; background: white; border: 1px solid #E5EDE7; border-radius: 12px; padding: 20px 24px;">
                <h4 style="font-family: 'Space Grotesk', sans-serif; font-size: 16px; color: #052E16; margin-bottom: 16px;">
                    <i class="fas fa-truck" style="color: #16A34A;"></i> Shipping Details
                </h4>
                
                <div class="shipping-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="full-width" style="grid-column: 1 / -1;">
                        <label style="display: block; font-weight: 600; font-size: 14px; color: #14532D; margin-bottom: 4px;">
                            Address <span style="color: #DC2626;">*</span>
                        </label>
                        <textarea name="shipping_address" class="form-input" rows="2" required style="width: 100%; padding: 10px 14px; border: 2px solid #E5EDE7; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px;"><?php echo escapeHtml($shop['address'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 14px; color: #14532D; margin-bottom: 4px;">City</label>
                        <input type="text" name="shipping_city" class="form-input" value="<?php echo escapeHtml($shop['city'] ?? ''); ?>" style="width: 100%; padding: 10px 14px; border: 2px solid #E5EDE7; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 14px; color: #14532D; margin-bottom: 4px;">State</label>
                        <input type="text" name="shipping_state" class="form-input" value="<?php echo escapeHtml($shop['state'] ?? ''); ?>" style="width: 100%; padding: 10px 14px; border: 2px solid #E5EDE7; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; font-size: 14px; color: #14532D; margin-bottom: 4px;">Pincode</label>
                        <input type="text" name="shipping_pincode" class="form-input" value="<?php echo escapeHtml($shop['pincode'] ?? ''); ?>" style="width: 100%; padding: 10px 14px; border: 2px solid #E5EDE7; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px;">
                    </div>
                    <div class="full-width" style="grid-column: 1 / -1;">
                        <label style="display: block; font-weight: 600; font-size: 14px; color: #14532D; margin-bottom: 4px;">Delivery Notes</label>
                        <textarea name="delivery_notes" class="form-input" rows="2" placeholder="Any special delivery instructions" style="width: 100%; padding: 10px 14px; border: 2px solid #E5EDE7; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 14px;"></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Place Order Button -->
            <div style="margin-top: 20px; text-align: right;">
                <input type="hidden" name="action" value="place_order">
                <button type="submit" class="btn-place-order">
                    <i class="fas fa-check"></i> Place Order
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/shop_footer.php'; ?>