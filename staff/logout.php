<?php
/**
 * SAMRIDHI AGRO - Staff Logout
 * 
 * This page handles secure staff logout.
 * 
 * @package SamridhiAgro
 * @subpackage Staff
 * @author Samridhi Agro Team
 * @version 1.0.0
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/functions.php';

if (isLoggedIn()) {
    logActivity('logout', $_SESSION['user_id'], 'auth', 'Staff logged out');
}

destroySession();
setFlashMessage('info', 'You have been successfully logged out.');
redirect('');
exit;