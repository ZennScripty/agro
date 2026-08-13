<?php
/**
 * SAMRIDHI AGRO - Admin Logout
 * 
 * This page handles secure admin logout by destroying the session
 * and redirecting to the login page.
 * 
 * @package SamridhiAgro
 * @subpackage Admin
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Include configuration and security
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

// Log the logout activity if user is logged in
if (isLoggedIn()) {
    logActivity(
        $_SESSION['user_id'],
        'logout',
        'auth',
        'User logged out from admin panel'
    );
}

// Destroy the session
destroySession();

// Set a flash message for the login page
setFlashMessage('info', 'You have been successfully logged out.');

// Redirect to login page
redirect('login.php');