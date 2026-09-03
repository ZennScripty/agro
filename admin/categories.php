<?php

/**
 * SAMRIDHI AGRO - Category Management
 * 
 * This page displays all product categories with search,
 * and management capabilities.
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
$pageTitle = 'Category Management';

// Include configuration files
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
requirePermissionOrAdmin('category.view', 'categories.php');

// Get database instance
$db = getDB();

// ============================================
// PROCESS ACTIONS
// ============================================

// Handle toggle status
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    requirePermission('category.edit');

    $categoryId = (int)$_GET['id'];
    $csrfToken = $_GET['csrf'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        setFlashMessage('error', 'Invalid security token.');
    } else {
        // Get current status
        $sql = "SELECT status, category_name FROM categories WHERE id = ?";
        $category = $db->fetchOne($sql, [$categoryId]);

        if ($category) {
            $newStatus = $category['status'] === 'active' ? 'inactive' : 'active';
            $sql = "UPDATE categories SET status = ? WHERE id = ?";
            $db->query($sql, [$newStatus, $categoryId]);

            logActivity(
                'update',
                $_SESSION['user_id'],
                'category',
                'Toggled category status to ' . $newStatus . ' for: ' . $category['category_name']
            );

            setFlashMessage('success', 'Category status updated successfully.');
        } else {
            setFlashMessage('error', 'Category not found.');
        }
    }

    redirect('admin/categories.php');
    exit;
}

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    requirePermission('category.delete');

    $categoryId = (int)$_GET['id'];
    $csrfToken = $_GET['csrf'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        setFlashMessage('error', 'Invalid security token.');
    } else {
        // Check if category has products
        $sql = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
        $result = $db->fetchOne($sql, [$categoryId]);

        if ($result && $result['count'] > 0) {
            setFlashMessage('error', 'Cannot delete category. It has ' . $result['count'] . ' products associated with it.');
        } else {
            // Get category name for log
            $sql = "SELECT category_name FROM categories WHERE id = ?";
            $category = $db->fetchOne($sql, [$categoryId]);

            if ($category) {
                // Check if category has sub-categories
                $sql = "SELECT COUNT(*) as count FROM categories WHERE parent_id = ?";
                $result = $db->fetchOne($sql, [$categoryId]);

                if ($result && $result['count'] > 0) {
                    setFlashMessage('error', 'Cannot delete category. It has ' . $result['count'] . ' sub-categories.');
                } else {
                    $sql = "DELETE FROM categories WHERE id = ?";
                    $db->query($sql, [$categoryId]);

                    logActivity(
                        'delete',
                        $_SESSION['user_id'],
                        'category',
                        'Deleted category: ' . $category['category_name']
                    );

                    setFlashMessage('success', 'Category deleted successfully.');
                }
            } else {
                setFlashMessage('error', 'Category not found.');
            }
        }
    }

    redirect('admin/categories.php');
    exit;
}

// ============================================
// GET CATEGORY LIST
// ============================================

// Search parameter
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = PAGINATION_DEFAULT_LIMIT;
$offset = getPaginationOffset($page, $perPage);

// Build query
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(category_name LIKE ? OR category_slug LIKE ? OR description LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Count total records
$sql = "SELECT COUNT(*) as total FROM categories $whereClause";
$result = $db->fetchOne($sql, $params);
$totalCategories = $result['total'] ?? 0;

// Get category records with parent name
$sql = "SELECT c.*, 
        p.category_name as parent_name,
        (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count,
        (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as sub_category_count
        FROM categories c
        LEFT JOIN categories p ON c.parent_id = p.id
        $whereClause
        ORDER BY c.parent_id IS NULL DESC, c.sort_order ASC, c.category_name ASC
        LIMIT ? OFFSET ?";

$queryParams = array_merge($params, [$perPage, $offset]);
$categoryList = $db->fetchAll($sql, $queryParams);

// Pagination
$totalPages = ceil($totalCategories / $perPage);
$pagination = getPagination($totalCategories, $page, $perPage, 'categories.php?page={page}&search=' . urlencode($search));

// CSRF token for actions
$csrfToken = generateCsrfToken();

// ============================================
// STEP 2: NOW include admin header (HTML starts here)
// ============================================
require_once '../includes/admin_header.php';
?>

<style>
    .category-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: white;
        flex-shrink: 0;
    }

    .category-icon.active {
        background: #16A34A;
    }

    .category-icon.inactive {
        background: #6B7A7B;
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

    .badge-status.badge-secondary {
        background: #F3F4F6;
        color: #6B7A7B;
    }

    .badge-status.badge-info {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .btn-action {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
        font-size: 13px;
    }

    .btn-action:hover {
        transform: translateY(-2px);
    }

    .btn-edit {
        background: #EDE9FE;
        color: #7C3AED;
    }

    .btn-edit:hover {
        background: #DDD6FE;
    }

    .btn-toggle {
        background: #FEF3C7;
        color: #D97706;
    }

    .btn-toggle:hover {
        background: #FDE68A;
    }

    .btn-delete {
        background: #FEE2E2;
        color: #DC2626;
    }

    .btn-delete:hover {
        background: #FECACA;
    }

    .btn-view {
        background: #DBEAFE;
        color: #2563EB;
    }

    .btn-view:hover {
        background: #BFDBFE;
    }

    .tree-indent {
        display: inline-block;
        width: 20px;
        margin-left: 4px;
    }

    .tree-line {
        color: #6B7A7B;
        font-size: 12px;
    }
</style>

<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-tags" style="color: #16A34A;"></i>
            All Categories
            <span style="font-size: 14px; font-weight: 400; color: #6B7A7B; margin-left: 8px;">
                (<?php echo number_format($totalCategories); ?>)
            </span>
        </h3>
        <div>
            <?php if (hasPermission('category.create')): ?>

                <a href="category-add.php" class="btn-primary" style="
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 20px;
                background: linear-gradient(135deg, #14532D, #16A34A);
                color: white;
                border: none;
                border-radius: 10px;
                font-family: 'Inter', sans-serif;
                font-size: 14px;
                font-weight: 600;
                text-decoration: none;
                transition: all 0.3s ease;
                cursor: pointer;
            ">
                    <i class="fas fa-plus"></i>
                    Add Category
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Search -->
    <div style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
        <form method="GET" action="" style="flex: 1; min-width: 200px; display: flex; gap: 12px;">
            <div style="flex: 1; min-width: 180px; position: relative;">
                <input
                    type="text"
                    name="search"
                    placeholder="Search categories..."
                    value="<?php echo escapeHtml($search); ?>"
                    style="
                        width: 100%;
                        padding: 10px 16px 10px 40px;
                        border: 2px solid #E5EDE7;
                        border-radius: 10px;
                        font-family: 'Inter', sans-serif;
                        font-size: 14px;
                        transition: all 0.3s ease;
                        background: white;
                    ">
                <i class="fas fa-search" style="
                    position: absolute;
                    left: 14px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: #6B7A7B;
                "></i>
            </div>
            <button type="submit" style="
                padding: 10px 24px;
                background: #14532D;
                color: white;
                border: none;
                border-radius: 10px;
                font-family: 'Inter', sans-serif;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
            ">
                <i class="fas fa-search"></i> Search
            </button>
            <?php if (!empty($search)): ?>
                <a href="categories.php" style="
                padding: 10px 16px;
                background: #F3F4F6;
                color: #4A5B5D;
                border: none;
                border-radius: 10px;
                font-family: 'Inter', sans-serif;
                font-size: 14px;
                text-decoration: none;
                transition: all 0.3s ease;
            ">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Category Table -->
    <div class="table-wrapper">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Slug</th>
                    <th>Parent</th>
                    <th>Products</th>
                    <th>Sub-Categories</th>
                    <th>Status</th>
                    <th>Sort Order</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categoryList)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #6B7A7B;">
                            <i class="fas fa-tags" style="font-size: 32px; display: block; margin-bottom: 12px; color: #D1D5DB;"></i>
                            No categories found
                            <?php if (!empty($search)): ?>
                                <br><span style="font-size: 13px;">Try adjusting your search</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categoryList as $category): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="category-icon <?php echo $category['status']; ?>">
                                        <i class="fas fa-<?php echo !empty($category['icon']) ? escapeHtml($category['icon']) : 'tag'; ?>"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: #052E16;">
                                            <?php if ($category['parent_id']): ?>
                                                <span class="tree-line">├── </span>
                                            <?php endif; ?>
                                            <?php echo escapeHtml($category['category_name']); ?>
                                        </div>
                                        <?php if (!empty($category['description'])): ?>
                                            <div style="font-size: 12px; color: #6B7A7B;">
                                                <?php echo escapeHtml(truncateText($category['description'], 50)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="font-family: monospace; font-size: 13px; color: #6B7A7B;">
                                    <?php echo escapeHtml($category['category_slug']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($category['parent_name']): ?>
                                    <span style="font-size: 13px;"><?php echo escapeHtml($category['parent_name']); ?></span>
                                <?php else: ?>
                                    <span style="color: #6B7A7B; font-size: 13px;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: #14532D;">
                                    <?php echo number_format($category['product_count']); ?>
                                </span>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: #7C3AED;">
                                    <?php echo number_format($category['sub_category_count']); ?>
                                </span>
                            </td>
                            <td>
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
                            </td>
                            <td>
                                <span style="font-size: 13px; color: #6B7A7B;">
                                    <?php echo $category['sort_order']; ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 4px; justify-content: center;">

                                    <?php if (hasPermission('product.view')): ?>
                                        <!-- View Products -->
                                        <a href="products.php?category=<?php echo $category['id']; ?>"
                                            class="btn-action btn-view"
                                            title="View Products">
                                            <i class="fas fa-box"></i>
                                        </a>
                                    <?php endif; ?>

                                    <!-- Edit -->
                                    <?php if (hasPermission('category.edit')): ?>
                                        <a href="category-edit.php?id=<?php echo $category['id']; ?>"
                                            class="btn-action btn-edit"
                                            title="Edit Category">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if (hasPermission('category.edit')): ?>
                                        <!-- Toggle Status -->
                                        <a href="categories.php?action=toggle&id=<?php echo $category['id']; ?>&csrf=<?php echo $csrfToken; ?>"
                                            class="btn-action btn-toggle"
                                            title="<?php echo $category['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>"
                                            onclick="return confirm('Are you sure you want to <?php echo $category['status'] === 'active' ? 'deactivate' : 'activate'; ?> this category?')">
                                            <i class="fas fa-<?php echo $category['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                        </a>
                                    <?php endif; ?>

                                    <!-- Delete -->
                                    <?php if (hasPermission('category.delete')): ?>
                                        <a href="categories.php?action=delete&id=<?php echo $category['id']; ?>&csrf=<?php echo $csrfToken; ?>"
                                            class="btn-action btn-delete"
                                            title="Delete Category"
                                            onclick="return confirm('Are you sure you want to delete this category? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div style="margin-top: 20px;">
            <?php echo $pagination; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/admin_footer.php'; ?>