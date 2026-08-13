<?php
/**
 * SAMRIDHI AGRO - Edit Product
 * 
 * This page allows administrators to update existing product details.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// ============================================
// STEP 1: All PHP logic FIRST (no HTML output)
// ============================================

// Set page title
$pageTitle = 'Edit Product';

// Include configuration files
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    initSecureSession();
}

// Require admin login and permission
requireLogin();
requireRole('admin');
requirePermission('product.edit');

// Get database instance
$db = getDB();

// Get product ID from URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no ID or invalid ID, redirect to product list
if ($productId <= 0) {
    setFlashMessage('error', 'Invalid product ID.');
    redirect('admin/products.php');
    exit;
}

// Get product data
$sql = "SELECT p.*, c.category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ?";
$product = $db->fetchOne($sql, [$productId]);

// If product not found, redirect
if (!$product) {
    setFlashMessage('error', 'Product not found.');
    redirect('admin/products.php');
    exit;
}

// Get categories for dropdown
$sql = "SELECT id, category_name FROM categories WHERE status = 'active' ORDER BY category_name";
$categories = $db->fetchAll($sql);

// Units of measurement
$units = [
    'piece' => 'Piece',
    'kg' => 'Kilogram (kg)',
    'g' => 'Gram (g)',
    'l' => 'Liter (L)',
    'ml' => 'Milliliter (mL)',
    'ton' => 'Ton',
    'quintal' => 'Quintal',
    'packet' => 'Packet',
    'box' => 'Box',
    'bundle' => 'Bundle',
    'dozen' => 'Dozen'
];

// Initialize variables
$errors = [];
$formData = [
    'product_name' => $product['product_name'],
    'product_slug' => $product['product_slug'],
    'category_id' => $product['category_id'],
    'sku' => $product['sku'],
    'description' => $product['description'] ?? '',
    'unit' => $product['unit'],
    'price' => $product['price'],
    'cost_price' => $product['cost_price'] ?? 0,
    'quantity' => $product['quantity'],
    'min_quantity' => $product['min_quantity'] ?? 0,
    'status' => $product['status'],
    'is_featured' => $product['is_featured'] ?? 0,
    'image' => $product['image'] ?? ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token. Please try again.');
        redirect('admin/product-edit.php?id=' . $productId);
        exit;
    }
    
    // Get and sanitize form data
    $formData = [
        'product_name' => sanitizeInput($_POST['product_name'] ?? ''),
        'product_slug' => sanitizeInput($_POST['product_slug'] ?? ''),
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'sku' => sanitizeInput($_POST['sku'] ?? ''),
        'description' => sanitizeInput($_POST['description'] ?? ''),
        'unit' => sanitizeInput($_POST['unit'] ?? 'piece'),
        'price' => (float)($_POST['price'] ?? 0),
        'cost_price' => (float)($_POST['cost_price'] ?? 0),
        'quantity' => (int)($_POST['quantity'] ?? 0),
        'min_quantity' => (int)($_POST['min_quantity'] ?? 0),
        'status' => sanitizeInput($_POST['status'] ?? 'active'),
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0
    ];
    
    // Generate slug if empty
    if (empty($formData['product_slug'])) {
        $formData['product_slug'] = createSlug($formData['product_name']);
    }
    
    // Validation
    $hasErrors = false;
    
    // Product Name - required
    if (empty($formData['product_name'])) {
        $errors['product_name'] = 'Product name is required';
        $hasErrors = true;
    } elseif (strlen($formData['product_name']) < 3) {
        $errors['product_name'] = 'Product name must be at least 3 characters';
        $hasErrors = true;
    }
    
    // Product Slug - required, unique (except current)
    if (empty($formData['product_slug'])) {
        $errors['product_slug'] = 'Product slug is required';
        $hasErrors = true;
    } elseif (!preg_match('/^[a-z0-9-]+$/', $formData['product_slug'])) {
        $errors['product_slug'] = 'Slug can only contain lowercase letters, numbers and hyphens';
        $hasErrors = true;
    } else {
        $sql = "SELECT id FROM products WHERE product_slug = ? AND id != ?";
        $existing = $db->fetchOne($sql, [$formData['product_slug'], $productId]);
        if ($existing) {
            $errors['product_slug'] = 'Slug already exists. Please use another.';
            $hasErrors = true;
        }
    }
    
    // Category - required
    if ($formData['category_id'] <= 0) {
        $errors['category_id'] = 'Please select a category';
        $hasErrors = true;
    } else {
        $sql = "SELECT id FROM categories WHERE id = ? AND status = 'active'";
        $category = $db->fetchOne($sql, [$formData['category_id']]);
        if (!$category) {
            $errors['category_id'] = 'Selected category is not valid.';
            $hasErrors = true;
        }
    }
    
    // SKU - required, unique (except current)
    if (empty($formData['sku'])) {
        $errors['sku'] = 'SKU is required';
        $hasErrors = true;
    } else {
        $sql = "SELECT id FROM products WHERE sku = ? AND id != ?";
        $existing = $db->fetchOne($sql, [$formData['sku'], $productId]);
        if ($existing) {
            $errors['sku'] = 'SKU already exists. Please use another.';
            $hasErrors = true;
        }
    }
    
    // Price - must be positive
    if ($formData['price'] < 0) {
        $errors['price'] = 'Price must be a positive number';
        $hasErrors = true;
    }
    
    // Cost Price - must be positive
    if ($formData['cost_price'] < 0) {
        $errors['cost_price'] = 'Cost price must be a positive number';
        $hasErrors = true;
    }
    
    // Quantity - must be positive
    if ($formData['quantity'] < 0) {
        $errors['quantity'] = 'Quantity must be a positive number';
        $hasErrors = true;
    }
    
    // Min Quantity - must be positive
    if ($formData['min_quantity'] < 0) {
        $errors['min_quantity'] = 'Minimum quantity must be a positive number';
        $hasErrors = true;
    }
    
    // Unit - must be valid
    if (!array_key_exists($formData['unit'], $units)) {
        $errors['unit'] = 'Invalid unit of measurement';
        $hasErrors = true;
    }
    
    // Status - must be valid
    if (!in_array($formData['status'], ['active', 'inactive', 'out_of_stock'])) {
        $errors['status'] = 'Invalid status value';
        $hasErrors = true;
    }
    
    // If no errors, update product
    if (!$hasErrors) {
        try {
            // Handle image upload
            $imageName = $formData['image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadFile($_FILES['image'], '../uploads/products/', ALLOWED_IMAGE_TYPES, MAX_IMAGE_SIZE);
                if ($uploadResult['success']) {
                    // Delete old image if exists
                    if (!empty($formData['image']) && file_exists('../uploads/products/' . $formData['image'])) {
                        unlink('../uploads/products/' . $formData['image']);
                        if (file_exists('../uploads/products/thumbs/' . $formData['image'])) {
                            unlink('../uploads/products/thumbs/' . $formData['image']);
                        }
                    }
                    $imageName = $uploadResult['filename'];
                    
                    // Create thumbnail
                    $thumbPath = '../uploads/products/thumbs/' . $imageName;
                    createThumbnail(
                        $uploadResult['path'],
                        $thumbPath,
                        IMAGE_THUMB_WIDTH,
                        IMAGE_THUMB_HEIGHT,
                        true
                    );
                } else {
                    $errors['image'] = $uploadResult['error'];
                    $hasErrors = true;
                }
            }
            
            if (!$hasErrors) {
                // Update product
                $sql = "UPDATE products SET 
                            product_name = ?,
                            product_slug = ?,
                            category_id = ?,
                            sku = ?,
                            description = ?,
                            unit = ?,
                            price = ?,
                            cost_price = ?,
                            quantity = ?,
                            min_quantity = ?,
                            image = ?,
                            status = ?,
                            is_featured = ?,
                            updated_at = NOW()
                        WHERE id = ?";
                
                $db->query($sql, [
                    $formData['product_name'],
                    $formData['product_slug'],
                    $formData['category_id'],
                    $formData['sku'],
                    $formData['description'],
                    $formData['unit'],
                    $formData['price'],
                    $formData['cost_price'],
                    $formData['quantity'],
                    $formData['min_quantity'],
                    $imageName ?: null,
                    $formData['status'],
                    $formData['is_featured'],
                    $productId
                ]);
                
                // Log activity
                logActivity(
                    'update',
                    $_SESSION['user_id'],
                    'product',
                    'Updated product: ' . $formData['product_name'] . ' (SKU: ' . $formData['sku'] . ')'
                );
                
                setFlashMessage('success', 'Product updated successfully!');
                
                // Redirect to product list
                redirect('admin/products.php');
                exit;
            }
            
        } catch (Exception $e) {
            error_log('Product update error: ' . $e->getMessage());
            setFlashMessage('error', 'Failed to update product. Please try again.');
            redirect('admin/product-edit.php?id=' . $productId);
            exit;
        }
    }
}

// Generate CSRF token
$csrfToken = generateCsrfToken();

// ============================================
// STEP 2: NOW include admin header (HTML starts here)
// ============================================
require_once '../includes/admin_header.php';
?>

<style>
    .form-group {
        margin-bottom: 16px;
    }
    
    .form-label {
        display: block;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 600;
        color: #14532D;
        margin-bottom: 6px;
    }
    
    .form-input {
        width: 100%;
        padding: 10px 14px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        border: 2px solid #E5EDE7;
        border-radius: 8px;
        background: white;
        transition: all 0.3s ease;
        color: #052E16;
        box-sizing: border-box;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #16A34A;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
    }
    
    .form-input.error {
        border-color: #DC2626;
        background: rgba(220, 38, 38, 0.05);
    }
    
    .form-input:disabled {
        background: #F3F4F6;
        cursor: not-allowed;
    }
    
    .form-error {
        color: #DC2626;
        font-size: 13px;
        font-family: 'Inter', sans-serif;
        margin-top: 4px;
    }
    
    .form-hint {
        font-size: 12px;
        color: #6B7A7B;
        margin-top: 4px;
    }
    
    .image-preview {
        width: 150px;
        height: 150px;
        border: 2px dashed #E5EDE7;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        color: #6B7A7B;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #F7FCF7;
        position: relative;
        overflow: hidden;
    }
    
    .image-preview:hover {
        border-color: #16A34A;
        background: #F0FDF4;
    }
    
    .image-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        position: absolute;
        top: 0;
        left: 0;
    }
    
    .image-preview .placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }
    
    .image-preview .placeholder i {
        font-size: 32px;
        color: #6B7A7B;
    }
    
    .btn-primary {
        padding: 12px 32px;
        background: linear-gradient(135deg, #14532D, #16A34A);
        color: white;
        border: none;
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(22, 163, 74, 0.3);
    }
    
    .btn-secondary {
        padding: 12px 24px;
        background: #F3F4F6;
        color: #4A5B5D;
        border: none;
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 15px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .btn-secondary:hover {
        background: #E5E7EB;
    }
    
    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #4A5B5D;
    }
    
    .checkbox-group input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #16A34A;
        cursor: pointer;
    }
    
    .btn-remove-image {
        position: absolute;
        top: 8px;
        right: 8px;
        background: #DC2626;
        color: white;
        border: none;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        z-index: 10;
    }
    
    .btn-remove-image:hover {
        transform: scale(1.1);
        background: #991B1B;
    }
    
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-edit" style="color: #16A34A;"></i>
            Edit Product
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                #<?php echo $product['id']; ?> - <?php echo escapeHtml($product['product_name']); ?>
            </span>
        </h3>
        <div style="display: flex; gap: 8px;">
            <a href="admin/product-view.php?id=<?php echo $product['id']; ?>" class="card-action">
                <i class="fas fa-eye"></i> View
            </a>
            <a href="admin/products.php" class="card-action">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div style="background: #FEE2E2; border: 1px solid #FECACA; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
        <p style="color: #991B1B; font-weight: 600; margin-bottom: 8px;">
            <i class="fas fa-exclamation-circle"></i> Please fix the following errors:
        </p>
        <ul style="margin: 0; padding-left: 20px; color: #991B1B;">
            <?php foreach ($errors as $field => $error): ?>
                <li><?php echo escapeHtml($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <form method="POST" action="" id="productForm" enctype="multipart/form-data" novalidate>
        <!-- CSRF Token -->
        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
        
        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Left Column -->
            <div>
                <!-- Product Name -->
                <div class="form-group">
                    <label class="form-label" for="product_name">
                        <i class="fas fa-tag" style="color: #16A34A;"></i>
                        Product Name <span style="color: #DC2626;">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="product_name" 
                        name="product_name" 
                        class="form-input <?php echo isset($errors['product_name']) ? 'error' : ''; ?>"
                        value="<?php echo escapeHtml($formData['product_name']); ?>"
                        placeholder="Enter product name"
                        required
                    >
                    <?php if (isset($errors['product_name'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['product_name']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Product Slug -->
                <div class="form-group">
                    <label class="form-label" for="product_slug">
                        <i class="fas fa-link" style="color: #16A34A;"></i>
                        Slug
                        <span style="font-weight: 400; color: #6B7A7B; font-size: 12px;">(auto-generated if left empty)</span>
                    </label>
                    <input 
                        type="text" 
                        id="product_slug" 
                        name="product_slug" 
                        class="form-input <?php echo isset($errors['product_slug']) ? 'error' : ''; ?>"
                        value="<?php echo escapeHtml($formData['product_slug']); ?>"
                        placeholder="e.g., organic-rice-1kg"
                    >
                    <?php if (isset($errors['product_slug'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['product_slug']); ?></div>
                    <?php endif; ?>
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i> Only lowercase letters, numbers and hyphens
                    </div>
                </div>
                
                <!-- Category -->
                <div class="form-group">
                    <label class="form-label" for="category_id">
                        <i class="fas fa-folder" style="color: #16A34A;"></i>
                        Category <span style="color: #DC2626;">*</span>
                    </label>
                    <select id="category_id" name="category_id" class="form-input <?php echo isset($errors['category_id']) ? 'error' : ''; ?>" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                <?php echo $formData['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo escapeHtml($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['category_id'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['category_id']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- SKU -->
                <div class="form-group">
                    <label class="form-label" for="sku">
                        <i class="fas fa-barcode" style="color: #16A34A;"></i>
                        SKU <span style="color: #DC2626;">*</span>
                    </label>
                    <div style="display: flex; gap: 8px;">
                        <input 
                            type="text" 
                            id="sku" 
                            name="sku" 
                            class="form-input <?php echo isset($errors['sku']) ? 'error' : ''; ?>"
                            value="<?php echo escapeHtml($formData['sku']); ?>"
                            placeholder="Enter SKU"
                            required
                            style="flex: 1;"
                        >
                        <button type="button" onclick="generateSku()" style="
                            padding: 10px 16px;
                            background: #F3F4F6;
                            border: 2px solid #E5EDE7;
                            border-radius: 8px;
                            cursor: pointer;
                            font-family: 'Inter', sans-serif;
                            font-size: 14px;
                            transition: all 0.3s ease;
                            white-space: nowrap;
                        ">
                            <i class="fas fa-sync"></i> Generate
                        </button>
                    </div>
                    <?php if (isset($errors['sku'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['sku']); ?></div>
                    <?php endif; ?>
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i> Unique product identifier (Stock Keeping Unit)
                    </div>
                </div>
                
                <!-- Unit -->
                <div class="form-group">
                    <label class="form-label" for="unit">
                        <i class="fas fa-weight" style="color: #16A34A;"></i>
                        Unit of Measurement <span style="color: #DC2626;">*</span>
                    </label>
                    <select id="unit" name="unit" class="form-input <?php echo isset($errors['unit']) ? 'error' : ''; ?>" required>
                        <?php foreach ($units as $key => $label): ?>
                            <option value="<?php echo $key; ?>" 
                                <?php echo $formData['unit'] === $key ? 'selected' : ''; ?>>
                                <?php echo escapeHtml($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['unit'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['unit']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Column -->
            <div>
                <!-- Description -->
                <div class="form-group">
                    <label class="form-label" for="description">
                        <i class="fas fa-align-left" style="color: #16A34A;"></i>
                        Description
                    </label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="form-input"
                        rows="4"
                        placeholder="Enter product description (optional)"
                    ><?php echo escapeHtml($formData['description']); ?></textarea>
                </div>
                
                <!-- Pricing -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label" for="price">
                            <i class="fas fa-rupee-sign" style="color: #16A34A;"></i>
                            Price (₹) <span style="color: #DC2626;">*</span>
                        </label>
                        <input 
                            type="number" 
                            id="price" 
                            name="price" 
                            class="form-input <?php echo isset($errors['price']) ? 'error' : ''; ?>"
                            value="<?php echo escapeHtml($formData['price']); ?>"
                            placeholder="0.00"
                            step="0.01"
                            min="0"
                            required
                        >
                        <?php if (isset($errors['price'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['price']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="cost_price">
                            <i class="fas fa-coins" style="color: #16A34A;"></i>
                            Cost Price (₹)
                        </label>
                        <input 
                            type="number" 
                            id="cost_price" 
                            name="cost_price" 
                            class="form-input <?php echo isset($errors['cost_price']) ? 'error' : ''; ?>"
                            value="<?php echo escapeHtml($formData['cost_price']); ?>"
                            placeholder="0.00"
                            step="0.01"
                            min="0"
                        >
                        <?php if (isset($errors['cost_price'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['cost_price']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Stock -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label" for="quantity">
                            <i class="fas fa-boxes" style="color: #16A34A;"></i>
                            Quantity <span style="color: #DC2626;">*</span>
                        </label>
                        <input 
                            type="number" 
                            id="quantity" 
                            name="quantity" 
                            class="form-input <?php echo isset($errors['quantity']) ? 'error' : ''; ?>"
                            value="<?php echo escapeHtml($formData['quantity']); ?>"
                            placeholder="0"
                            min="0"
                            required
                        >
                        <?php if (isset($errors['quantity'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['quantity']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="min_quantity">
                            <i class="fas fa-exclamation-triangle" style="color: #16A34A;"></i>
                            Min Quantity
                        </label>
                        <input 
                            type="number" 
                            id="min_quantity" 
                            name="min_quantity" 
                            class="form-input <?php echo isset($errors['min_quantity']) ? 'error' : ''; ?>"
                            value="<?php echo escapeHtml($formData['min_quantity']); ?>"
                            placeholder="0"
                            min="0"
                        >
                        <?php if (isset($errors['min_quantity'])): ?>
                            <div class="form-error"><?php echo escapeHtml($errors['min_quantity']); ?></div>
                        <?php endif; ?>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i> Low stock alert threshold
                        </div>
                    </div>
                </div>
                
                <!-- Image Upload -->
                <div class="form-group">
                    <label class="form-label" for="image">
                        <i class="fas fa-image" style="color: #16A34A;"></i>
                        Product Image
                    </label>
                    <div style="display: flex; gap: 16px; align-items: flex-start; flex-wrap: wrap;">
                        <div class="image-preview" id="imagePreview" onclick="document.getElementById('image').click()">
                            <?php if (!empty($formData['image']) && file_exists('../uploads/products/' . $formData['image'])): ?>
                                <img src="../uploads/products/<?php echo escapeHtml($formData['image']); ?>" alt="Product Image">
                                <button type="button" class="btn-remove-image" onclick="event.stopPropagation(); removeImage()" title="Remove Image">
                                    <i class="fas fa-times"></i>
                                </button>
                            <?php else: ?>
                                <div class="placeholder">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Click to upload</span>
                                    <span style="font-size: 11px;">Max 5MB</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <input 
                            type="file" 
                            id="image" 
                            name="image" 
                            accept="image/*"
                            style="display: none;"
                            onchange="previewImage(this)"
                        >
                        <input type="hidden" name="remove_image" id="remove_image" value="0">
                        <?php if (isset($errors['image'])): ?>
                            <div style="width: 100%;">
                                <div class="form-error"><?php echo escapeHtml($errors['image']); ?></div>
                            </div>
                        <?php endif; ?>
                        <div style="font-size: 12px; color: #6B7A7B;">
                            <i class="fas fa-info-circle"></i> Allowed: JPG, PNG, GIF, WebP
                        </div>
                    </div>
                </div>
                
                <!-- Status & Featured -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label class="form-label" for="status">
                            <i class="fas fa-toggle-on" style="color: #16A34A;"></i>
                            Status
                        </label>
                        <select id="status" name="status" class="form-input">
                            <option value="active" <?php echo $formData['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $formData['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="out_of_stock" <?php echo $formData['status'] === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <label class="checkbox-group" style="margin: 0;">
                            <input type="checkbox" name="is_featured" value="1" <?php echo $formData['is_featured'] ? 'checked' : ''; ?>>
                            <span><i class="fas fa-star" style="color: #EAB308;"></i> Featured Product</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #E5EDE7; display: flex; gap: 12px;">
            <button type="submit" class="btn-primary" id="submitBtn">
                <i class="fas fa-save"></i> <span id="btnText">Update Product</span>
                <span id="btnSpinner" style="display:none;">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
            </button>
            
            <a href="admin/products.php" class="btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate slug from name
    const nameInput = document.getElementById('product_name');
    const slugInput = document.getElementById('product_slug');
    
    nameInput.addEventListener('blur', function() {
        if (slugInput.value === '') {
            const slug = this.value.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
            slugInput.value = slug;
        }
    });
});

// Generate SKU
function generateSku() {
    const year = new Date().getFullYear();
    const random = String(Math.floor(Math.random() * 99999)).padStart(5, '0');
    const sku = 'PRD-' + year + '-' + random;
    document.getElementById('sku').value = sku;
}

// Preview Image
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Product Image">' +
                '<button type="button" class="btn-remove-image" onclick="event.stopPropagation(); removeImage()" title="Remove Image">' +
                '<i class="fas fa-times"></i></button>';
        }
        reader.readAsDataURL(input.files[0]);
        document.getElementById('remove_image').value = '0';
    }
}

// Remove Image
function removeImage() {
    if (confirm('Are you sure you want to remove this image?')) {
        document.getElementById('imagePreview').innerHTML = 
            '<div class="placeholder">' +
            '<i class="fas fa-cloud-upload-alt"></i>' +
            '<span>Click to upload</span>' +
            '<span style="font-size: 11px;">Max 5MB</span>' +
            '</div>';
        document.getElementById('image').value = '';
        document.getElementById('remove_image').value = '1';
    }
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>