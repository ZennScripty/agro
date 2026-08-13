<?php
/**
 * SAMRIDHI AGRO - Unauthorized Access
 * 
 * This page shows when a user tries to access a page without permission.
 * 
 * @package SamridhiAgro
 * @subpackage Staff
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Unauthorized Access';

// Include staff header
require_once '../includes/staff_header.php';
?>

<style>
    .unauthorized-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 60vh;
        text-align: center;
        padding: 40px 20px;
    }
    
    .unauthorized-container .lock-icon {
        font-size: 80px;
        color: #DC2626;
        margin-bottom: 20px;
    }
    
    .unauthorized-container h2 {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 28px;
        font-weight: 700;
        color: #052E16;
        margin-bottom: 12px;
    }
    
    .unauthorized-container p {
        font-family: 'Inter', sans-serif;
        font-size: 16px;
        color: #6B7A7B;
        max-width: 500px;
        margin-bottom: 24px;
    }
    
    .btn-go-back {
        padding: 12px 32px;
        background: #14532D;
        color: white;
        border: none;
        border-radius: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .btn-go-back:hover {
        background: #052E16;
        transform: translateY(-2px);
    }
</style>

<div class="content-card" style="padding: 0; border: none; box-shadow: none; background: transparent;">
    <div class="unauthorized-container">
        <div class="lock-icon">
            <i class="fas fa-lock"></i>
        </div>
        <h2>Access Denied</h2>
        <p>
            You do not have permission to access this page. 
            Please contact your administrator if you believe this is an error.
        </p>
        <a href="<?php echo STAFF_URL; ?>dashboard.php" class="btn-go-back">
            <i class="fas fa-arrow-left"></i> Go to Dashboard
        </a>
    </div>
</div>

<?php require_once '../includes/staff_footer.php'; ?>