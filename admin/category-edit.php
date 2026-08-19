<?php
/**
 * SAMRIDHI AGRO - Edit Category
 * 
 * This page allows administrators to update existing category details.
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
$pageTitle = 'Edit Category';

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
requirePermission('category.edit');

// Get database instance
$db = getDB();

// Get category ID from URL
$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no ID or invalid ID, redirect to categories list
if ($categoryId <= 0) {
    setFlashMessage('error', 'Invalid category ID.');
    redirect('admin/categories.php');
    exit;
}

// Get category data
$sql = "SELECT * FROM categories WHERE id = ?";
$category = $db->fetchOne($sql, [$categoryId]);

// If category not found, redirect
if (!$category) {
    setFlashMessage('error', 'Category not found.');
    redirect('admin/categories.php');
    exit;
}

// Get parent categories for dropdown (excluding current category and its descendants)
$sql = "SELECT id, category_name, parent_id 
        FROM categories 
        WHERE status = 'active' AND id != ? 
        ORDER BY category_name";
$parentCategories = $db->fetchAll($sql, [$categoryId]);

// Common icons for dropdown
$icons = [
    'tag' => 'Tag',
    'seedling' => 'Seedling',
    'leaf' => 'Leaf',
    'tree' => 'Tree',
    'apple-alt' => 'Apple',
    'carrot' => 'Carrot',
    'wheat' => 'Wheat',
    'flower' => 'Flower',
    'water' => 'Water',
    'sun' => 'Sun',
    'cloud' => 'Cloud',
    'rain' => 'Rain',
    'tractor' => 'Tractor',
    'shopping-bag' => 'Shopping Bag',
    'box' => 'Box',
    'package' => 'Package',
    'cubes' => 'Cubes',
    'layer-group' => 'Layer Group',
    'tags' => 'Tags',
    'star' => 'Star'
];

// Initialize form data
$formData = [
    'category_name' => $category['category_name'],
    'category_slug' => $category['category_slug'],
    'description' => $category['description'] ?? '',
    'icon' => $category['icon'] ?? 'tag',
    'parent_id' => $category['parent_id'] ?? 0,
    'sort_order' => $category['sort_order'] ?? 0,
    'status' => $category['status']
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        setFlashMessage('error', 'Invalid security token. Please try again.');
        redirect('admin/category-edit.php?id=' . $categoryId);
        exit;
    }
    
    // Get and sanitize form data
    $formData = [
        'category_name' => sanitizeInput($_POST['category_name'] ?? ''),
        'category_slug' => sanitizeInput($_POST['category_slug'] ?? ''),
        'description' => sanitizeInput($_POST['description'] ?? ''),
        'icon' => sanitizeInput($_POST['icon'] ?? 'tag'),
        'parent_id' => (int)($_POST['parent_id'] ?? 0),
        'sort_order' => (int)($_POST['sort_order'] ?? 0),
        'status' => sanitizeInput($_POST['status'] ?? 'active')
    ];
    
    // Generate slug if empty
    if (empty($formData['category_slug'])) {
        $formData['category_slug'] = createSlug($formData['category_name']);
    }
    
    // Validation
    $hasErrors = false;
    
    // Category Name - required
    if (empty($formData['category_name'])) {
        $errors['category_name'] = 'Category name is required';
        $hasErrors = true;
    } elseif (strlen($formData['category_name']) < 2) {
        $errors['category_name'] = 'Category name must be at least 2 characters';
        $hasErrors = true;
    }
    
    // Category Slug - required, unique (except current)
    if (empty($formData['category_slug'])) {
        $errors['category_slug'] = 'Category slug is required';
        $hasErrors = true;
    } elseif (!preg_match('/^[a-z0-9-]+$/', $formData['category_slug'])) {
        $errors['category_slug'] = 'Slug can only contain lowercase letters, numbers and hyphens';
        $hasErrors = true;
    } else {
        $sql = "SELECT id FROM categories WHERE category_slug = ? AND id != ?";
        $existing = $db->fetchOne($sql, [$formData['category_slug'], $categoryId]);
        if ($existing) {
            $errors['category_slug'] = 'Slug already exists. Please use another.';
            $hasErrors = true;
        }
    }
    
    // Parent ID - check if exists and not self
    if ($formData['parent_id'] > 0) {
        // Check if parent exists
        $sql = "SELECT id FROM categories WHERE id = ? AND status = 'active'";
        $parent = $db->fetchOne($sql, [$formData['parent_id']]);
        if (!$parent) {
            $errors['parent_id'] = 'Selected parent category is not valid.';
            $hasErrors = true;
        }
        
        // Check if parent is not itself (prevent self-parenting)
        if ($formData['parent_id'] == $categoryId) {
            $errors['parent_id'] = 'A category cannot be its own parent.';
            $hasErrors = true;
        }
    }
    
    // Sort Order - must be positive integer
    if ($formData['sort_order'] < 0) {
        $errors['sort_order'] = 'Sort order must be a positive number';
        $hasErrors = true;
    }
    
    // Status - must be valid
    if (!in_array($formData['status'], ['active', 'inactive'])) {
        $errors['status'] = 'Invalid status value';
        $hasErrors = true;
    }
    
    // If no errors, update category
    if (!$hasErrors) {
        try {
            // Update category
            $sql = "UPDATE categories SET 
                        category_name = ?,
                        category_slug = ?,
                        description = ?,
                        icon = ?,
                        parent_id = ?,
                        sort_order = ?,
                        status = ?,
                        updated_at = NOW()
                    WHERE id = ?";
            
            $db->query($sql, [
                $formData['category_name'],
                $formData['category_slug'],
                $formData['description'],
                $formData['icon'],
                $formData['parent_id'] > 0 ? $formData['parent_id'] : null,
                $formData['sort_order'],
                $formData['status'],
                $categoryId
            ]);
            
            // Log activity
            logActivity(
                'update',
                $_SESSION['user_id'],
                'category',
                'Updated category: ' . $formData['category_name']
            );
            
            setFlashMessage('success', 'Category updated successfully!');
            
            // Redirect to categories list
            redirect('admin/categories.php');
            exit;
            
        } catch (Exception $e) {
            error_log('Category update error: ' . $e->getMessage());
            setFlashMessage('error', 'Failed to update category. Please try again.');
            redirect('admin/category-edit.php?id=' . $categoryId);
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

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
    
    .icon-preview {
        display: inline-block;
        font-size: 24px;
        padding: 8px 16px;
        background: #F7FCF7;
        border-radius: 8px;
        border: 2px solid #E5EDE7;
        margin-top: 4px;
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
    
    .category-info {
        background: #F7FCF7;
        border-radius: 10px;
        padding: 16px 20px;
        margin-bottom: 24px;
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: center;
        border: 1px solid #E5EDE7;
    }
    
    .category-info .info-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;
        color: #4A5B5D;
    }
    
    .category-info .info-item strong {
        color: #052E16;
    }
    
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr !important;
        }
        .category-info {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-edit" style="color: #16A34A;"></i>
            Edit Category
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                #<?php echo $category['id']; ?> - <?php echo escapeHtml($category['category_name']); ?>
            </span>
        </h3>
        <div style="display: flex; gap: 8px;">
            <a href="categories.php" class="card-action">
                <i class="fas fa-arrow-left"></i> Back to Categories
            </a>
        </div>
    </div>
    
    <!-- Category Info -->
    <div class="category-info">
        <div class="info-item">
            <i class="fas fa-hashtag" style="color: #6B7A7B;"></i>
            <strong>ID:</strong> #<?php echo $category['id']; ?>
        </div>
        <div class="info-item">
            <i class="fas fa-calendar-plus" style="color: #6B7A7B;"></i>
            <strong>Created:</strong> <?php echo formatDate($category['created_at']); ?>
        </div>
        <div class="info-item">
            <i class="fas fa-clock" style="color: #6B7A7B;"></i>
            <strong>Last Updated:</strong> <?php echo formatDate($category['updated_at']); ?>
        </div>
        <div class="info-item">
            <i class="fas fa-<?php echo $category['icon'] ?? 'tag'; ?>" style="color: #16A34A;"></i>
            <strong>Icon:</strong> <?php echo ucfirst($category['icon'] ?? 'tag'); ?>
        </div>
        <div class="info-item" style="margin-left: auto;">
            <?php 
            $statusColors = [
                'active' => 'badge-success',
                'inactive' => 'badge-secondary'
            ];
            $color = $statusColors[$category['status']] ?? 'badge-secondary';
            ?>
            <span class="badge-status <?php echo $color; ?>">
                <?php echo ucfirst($category['status']); ?>
            </span>
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
    
    <form method="POST" action="" id="categoryForm" novalidate>
        <!-- CSRF Token -->
        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">
        
        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Left Column -->
            <div>
                <!-- Category Name -->
                <div class="form-group">
                    <label class="form-label" for="category_name">
                        <i class="fas fa-tag" style="color: #16A34A;"></i>
                        Category Name <span style="color: #DC2626;">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="category_name" 
                        name="category_name" 
                        class="form-input <?php echo isset($errors['category_name']) ? 'error' : ''; ?>"
                        value="<?php echo escapeHtml($formData['category_name']); ?>"
                        placeholder="Enter category name"
                        required
                    >
                    <?php if (isset($errors['category_name'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['category_name']); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Category Slug -->
                <div class="form-group">
                    <label class="form-label" for="category_slug">
                        <i class="fas fa-link" style="color: #16A34A;"></i>
                        Slug
                        <span style="font-weight: 400; color: #6B7A7B; font-size: 12px;">(auto-generated if left empty)</span>
                    </label>
                    <input 
                        type="text" 
                        id="category_slug" 
                        name="category_slug" 
                        class="form-input <?php echo isset($errors['category_slug']) ? 'error' : ''; ?>"
                        value="<?php echo escapeHtml($formData['category_slug']); ?>"
                        placeholder="e.g., fresh-vegetables"
                    >
                    <?php if (isset($errors['category_slug'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['category_slug']); ?></div>
                    <?php endif; ?>
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i> Only lowercase letters, numbers and hyphens
                    </div>
                </div>
                
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
                        placeholder="Enter category description (optional)"
                    ><?php echo escapeHtml($formData['description']); ?></textarea>
                </div>
            </div>
            
            <!-- Right Column -->
            <div>
                <!-- Parent Category -->
                <div class="form-group">
                    <label class="form-label" for="parent_id">
                        <i class="fas fa-sitemap" style="color: #16A34A;"></i>
                        Parent Category
                    </label>
                    <select id="parent_id" name="parent_id" class="form-input <?php echo isset($errors['parent_id']) ? 'error' : ''; ?>">
                        <option value="0">None (Top Level)</option>
                        <?php foreach ($parentCategories as $parent): ?>
                            <option value="<?php echo $parent['id']; ?>" 
                                <?php echo $formData['parent_id'] == $parent['id'] ? 'selected' : ''; ?>>
                                <?php echo escapeHtml($parent['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['parent_id'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['parent_id']); ?></div>
                    <?php endif; ?>
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i> Select a parent category to create a sub-category
                    </div>
                </div>
                
                <!-- Icon -->
                <div class="form-group">
                    <label class="form-label" for="icon">
                        <i class="fas fa-icons" style="color: #16A34A;"></i>
                        Icon
                    </label>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <select id="icon" name="icon" class="form-input" style="flex: 1;">
                            <?php foreach ($icons as $value => $label): ?>
                                <option value="<?php echo $value; ?>" 
                                    <?php echo $formData['icon'] === $value ? 'selected' : ''; ?>>
                                    <i class="fas fa-<?php echo $value; ?>"></i> <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="icon-preview" id="iconPreview">
                            <i class="fas fa-<?php echo $formData['icon']; ?>"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Sort Order -->
                <div class="form-group">
                    <label class="form-label" for="sort_order">
                        <i class="fas fa-sort" style="color: #16A34A;"></i>
                        Sort Order
                    </label>
                    <input 
                        type="number" 
                        id="sort_order" 
                        name="sort_order" 
                        class="form-input <?php echo isset($errors['sort_order']) ? 'error' : ''; ?>"
                        value="<?php echo escapeHtml($formData['sort_order']); ?>"
                        placeholder="0"
                        min="0"
                    >
                    <?php if (isset($errors['sort_order'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['sort_order']); ?></div>
                    <?php endif; ?>
                    <div class="form-hint">
                        <i class="fas fa-info-circle"></i> Lower numbers appear first
                    </div>
                </div>
                
                <!-- Status -->
                <div class="form-group">
                    <label class="form-label" for="status">
                        <i class="fas fa-toggle-on" style="color: #16A34A;"></i>
                        Status
                    </label>
                    <select id="status" name="status" class="form-input <?php echo isset($errors['status']) ? 'error' : ''; ?>">
                        <option value="active" <?php echo $formData['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $formData['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                    <?php if (isset($errors['status'])): ?>
                        <div class="form-error"><?php echo escapeHtml($errors['status']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #E5EDE7; display: flex; gap: 12px;">
            <button type="submit" class="btn-primary" id="submitBtn">
                <i class="fas fa-save"></i> <span id="btnText">Update Category</span>
                <span id="btnSpinner" style="display:none;">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
            </button>
            
            <a href="categories.php" class="btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate slug from name
    const nameInput = document.getElementById('category_name');
    const slugInput = document.getElementById('category_slug');
    
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
    
    // Live icon preview
    const iconSelect = document.getElementById('icon');
    const iconPreview = document.getElementById('iconPreview');
    
    iconSelect.addEventListener('change', function() {
        iconPreview.innerHTML = '<i class="fas fa-' + this.value + '"></i>';
    });
    
    // SweetAlert confirmation for unsaved changes
    let formChanged = false;
    const form = document.getElementById('categoryForm');
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            formChanged = true;
        });
    });
    
    // Confirm on page leave if changes unsaved
    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
    
    // Reset form changed flag on submit
    form.addEventListener('submit', function() {
        formChanged = false;
    });
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>