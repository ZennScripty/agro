<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

if (session_status() === PHP_SESSION_NONE) {
    initSecureSession();
}

echo '<pre>';
echo 'Session ID: ' . session_id() . "\n";
echo 'Is Logged In: ' . (isLoggedIn() ? 'Yes' : 'No') . "\n";
echo 'User ID: ' . (getCurrentUserId() ?? 'null') . "\n";
echo 'User Role: ' . (getCurrentUserRole() ?? 'null') . "\n";
echo 'Session Data: ';
print_r($_SESSION);
echo '</pre>';