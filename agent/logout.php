<?php
/**
 * SAMRIDHI AGRO - Agent Logout
 * 
 * This page handles secure agent logout by destroying the session
 * and redirecting to the login page.
 * 
 * @package SamridhiAgro
 * @subpackage Agent
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

// Include configuration and functions
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/functions.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    initSecureSession();
}

// Log the logout activity if user is logged in
if (isLoggedIn()) {
    logActivity('logout', $_SESSION['user_id'], 'auth', 'Agent logged out');
}

// Destroy the session
destroySession();

// Set a flash message for the login page
setFlashMessage('info', 'You have been successfully logged out.');

// Redirect to login page
redirect('login.php');
exit;