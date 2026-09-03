<?php

/**
 * SAMRIDHI AGRO - Complete Functions & Security
 * 
 * This file contains ALL reusable utility functions, security functions,
 * authentication, authorization, session management, and login protection.
 * 
 * @package SamridhiAgro
 * @subpackage Config
 * @author Samridhi Agro Team
 * @version 2.0.0
 */

// ============================================
// SECURITY & SANITIZATION FUNCTIONS
// ============================================

/**
 * Sanitize input data to prevent XSS attacks
 * 
 * @param string|array $data The data to sanitize
 * @param bool $stripTags Whether to strip HTML tags
 * @return string|array Sanitized data
 */
function sanitizeInput($data, $stripTags = true)
{
    if (is_array($data)) {
        return array_map(function ($item) use ($stripTags) {
            return sanitizeInput($item, $stripTags);
        }, $data);
    }

    // Remove leading/trailing whitespace
    $data = trim($data);

    // Strip HTML tags if requested
    if ($stripTags) {
        $data = strip_tags($data);
    }

    // Convert special characters to HTML entities
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Escape output for HTML display
 * 
 * @param string|null $string The string to escape
 * @return string Escaped string
 */
function escapeHtml($string)
{
    if ($string === null) {
        return '';
    }
    return htmlspecialchars((string)$string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Escape output for JavaScript
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function escapeJs($string)
{
    return json_encode($string, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/**
 * Escape output for URL
 * 
 * @param string $string String to escape
 * @return string Escaped string
 */
function escapeUrl($string)
{
    return urlencode((string)$string);
}

// ============================================
// CSRF PROTECTION
// ============================================

/**
 * Generate CSRF token and store in session
 * 
 * @return string CSRF token
 */
function generateCsrfToken()
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Get CSRF token from session
 * 
 * @return string|null CSRF token or null if not set
 */
function getCsrfToken()
{
    return $_SESSION[CSRF_TOKEN_NAME] ?? null;
}

/**
 * Verify CSRF token
 * 
 * @param string $token Token to verify
 * @return bool True if valid
 */
function verifyCsrfToken($token)
{
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Generate HTML input for CSRF token
 * 
 * @return string HTML input field
 */
function csrfField()
{
    $token = generateCsrfToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $token . '">';
}

// ============================================
// VALIDATION FUNCTIONS
// ============================================

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool True if valid
 */
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Indian format)
 * 
 * @param string $phone Phone number to validate
 * @return bool True if valid
 */
function isValidPhone($phone)
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^[6-9]\d{9}$/', $phone) === 1;
}

/**
 * Validate URL
 * 
 * @param string $url URL to validate
 * @return bool True if valid
 */
function isValidUrl($url)
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Validate pincode (Indian format)
 * 
 * @param string $pincode Pincode to validate
 * @return bool True if valid
 */
function isValidPincode($pincode)
{
    return preg_match('/^[1-9][0-9]{5}$/', $pincode) === 1;
}

/**
 * Validate GST number (Indian format)
 * 
 * @param string $gst GST number to validate
 * @return bool True if valid
 */
function isValidGST($gst)
{
    return preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gst) === 1;
}

/**
 * Validate PAN number (Indian format)
 * 
 * @param string $pan PAN number to validate
 * @return bool True if valid
 */
function isValidPAN($pan)
{
    return preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan) === 1;
}

/**
 * Validate password strength
 * 
 * @param string $password Password to validate
 * @return array ['valid' => bool, 'message' => string]
 */
function validatePassword($password)
{
    $errors = [];

    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long";
    }

    if (PASSWORD_REQUIRE_UPPER && !preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }

    if (PASSWORD_REQUIRE_NUMBER && !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }

    if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validate and sanitize form input
 * 
 * @param array $data Form data to validate
 * @param array $rules Validation rules
 * @return array ['valid' => bool, 'errors' => array, 'data' => array]
 */
function validateInput($data, $rules)
{
    $errors = [];
    $sanitized = [];

    foreach ($rules as $field => $ruleSet) {
        $value = $data[$field] ?? null;
        $fieldRules = explode('|', $ruleSet);

        foreach ($fieldRules as $rule) {
            if (strpos($rule, ':') !== false) {
                list($ruleName, $ruleParam) = explode(':', $rule, 2);
            } else {
                $ruleName = $rule;
                $ruleParam = null;
            }

            switch ($ruleName) {
                case 'required':
                    if (empty($value) && $value !== '0') {
                        $errors[$field][] = ucfirst($field) . ' is required';
                    }
                    break;

                case 'email':
                    if (!empty($value) && !isValidEmail($value)) {
                        $errors[$field][] = 'Invalid email address';
                    }
                    break;

                case 'phone':
                    if (!empty($value) && !isValidPhone($value)) {
                        $errors[$field][] = 'Invalid phone number';
                    }
                    break;

                case 'min':
                    if (!empty($value) && strlen($value) < (int)$ruleParam) {
                        $errors[$field][] = ucfirst($field) . ' must be at least ' . $ruleParam . ' characters';
                    }
                    break;

                case 'max':
                    if (!empty($value) && strlen($value) > (int)$ruleParam) {
                        $errors[$field][] = ucfirst($field) . ' must not exceed ' . $ruleParam . ' characters';
                    }
                    break;

                case 'numeric':
                    if (!empty($value) && !is_numeric($value)) {
                        $errors[$field][] = ucfirst($field) . ' must be a number';
                    }
                    break;

                case 'integer':
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                        $errors[$field][] = ucfirst($field) . ' must be an integer';
                    }
                    break;

                case 'url':
                    if (!empty($value) && !isValidUrl($value)) {
                        $errors[$field][] = 'Invalid URL';
                    }
                    break;

                case 'pincode':
                    if (!empty($value) && !isValidPincode($value)) {
                        $errors[$field][] = 'Invalid pincode';
                    }
                    break;

                case 'gst':
                    if (!empty($value) && !isValidGST($value)) {
                        $errors[$field][] = 'Invalid GST number';
                    }
                    break;

                case 'sanitize':
                    if (!empty($value)) {
                        $sanitized[$field] = sanitizeInput($value);
                    }
                    break;

                default:
                    if (!empty($value)) {
                        $sanitized[$field] = sanitizeInput($value);
                    }
                    break;
            }
        }
    }

    // Merge sanitized values with original data
    $sanitized = array_merge($data, $sanitized);

    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'data' => $sanitized
    ];
}

// ============================================
// STRING MANIPULATION FUNCTIONS
// ============================================

/**
 * Generate a slug from a string
 * 
 * @param string $string The string to convert to slug
 * @param string $separator The separator to use
 * @return string Slug
 */
function createSlug($string, $separator = '-')
{
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', $separator, $string);
    return trim($string, $separator);
}

/**
 * Truncate text to a specified length
 * 
 * @param string $text The text to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix to add if truncated
 * @return string Truncated text
 */
function truncateText($text, $length = 100, $suffix = '...')
{
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Generate a random string
 * 
 * @param int $length Length of the string
 * @param string $type Type of characters (alnum, alpha, numeric, hex)
 * @return string Random string
 */
function generateRandomString($length = 10, $type = 'alnum')
{
    $characters = '';
    switch ($type) {
        case 'alpha':
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            break;
        case 'numeric':
            $characters = '0123456789';
            break;
        case 'hex':
            $characters = '0123456789abcdef';
            break;
        case 'alnum':
        default:
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            break;
    }

    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $randomString;
}

/**
 * Format currency amount
 * 
 * @param float $amount The amount to format
 * @param string $currency Currency symbol
 * @return string Formatted currency
 */
function formatCurrency($amount, $currency = '₹')
{
    $amount = floatval($amount);
    return $currency . ' ' . number_format($amount, 2);
}

/**
 * Format date for display
 * 
 * @param string|DateTime $date Date to format
 * @param string $format Format to use
 * @return string Formatted date
 */
function formatDate($date, $format = DATE_FORMAT)
{
    if ($date instanceof DateTime) {
        return $date->format($format);
    }
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Format date for database
 * 
 * @param string|DateTime $date Date to format
 * @return string Formatted date for database
 */
function formatDbDate($date)
{
    if ($date instanceof DateTime) {
        return $date->format(DB_DATE_FORMAT);
    }
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date(DB_DATE_FORMAT, $timestamp);
}

/**
 * Format datetime for database
 * 
 * @param string|DateTime $datetime Datetime to format
 * @return string Formatted datetime for database
 */
function formatDbDatetime($datetime)
{
    if ($datetime instanceof DateTime) {
        return $datetime->format(DB_DATETIME_FORMAT);
    }
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    return date(DB_DATETIME_FORMAT, $timestamp);
}

/**
 * Get time ago string
 * 
 * @param string|DateTime $datetime The datetime
 * @return string Time ago string
 */
function timeAgo($datetime)
{
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return $diff . ' seconds ago';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } elseif ($diff < 2592000) {
        return floor($diff / 604800) . ' weeks ago';
    } elseif ($diff < 31536000) {
        return floor($diff / 2592000) . ' months ago';
    } else {
        return floor($diff / 31536000) . ' years ago';
    }
}

// ============================================
// FILE UPLOAD FUNCTIONS
// ============================================

/**
 * Upload a file with validation
 * 
 * @param array $file The $_FILES array element
 * @param string $targetDir Target directory path
 * @param array $allowedTypes Allowed MIME types
 * @param int $maxSize Maximum file size in bytes
 * @return array ['success' => bool, 'filename' => string, 'error' => string]
 */
function uploadFile($file, $targetDir, $allowedTypes = null, $maxSize = null)
{
    $allowedTypes = $allowedTypes ?? ALLOWED_IMAGE_TYPES;
    $maxSize = $maxSize ?? MAX_FILE_SIZE;

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed with error code: ' . $file['error']];
    }

    // Check file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size exceeds maximum allowed size'];
    }

    // Get file info
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    // Check file type
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }

    // Generate safe filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFilename = generateRandomString(20) . '.' . $extension;

    // Create target directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Move uploaded file
    $targetPath = rtrim($targetDir, '/') . '/' . $newFilename;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }

    return ['success' => true, 'filename' => $newFilename, 'path' => $targetPath];
}

/**
 * Create image thumbnail
 * 
 * @param string $sourcePath Source image path
 * @param string $targetPath Target thumbnail path
 * @param int $width Thumbnail width
 * @param int $height Thumbnail height
 * @param bool $crop Whether to crop or resize
 * @return bool True on success
 */
function createThumbnail($sourcePath, $targetPath, $width = 300, $height = 300, $crop = true)
{
    // Check source file
    if (!file_exists($sourcePath)) {
        return false;
    }

    // Get image info
    $imageInfo = getimagesize($sourcePath);

    if (!$imageInfo) {
        return false;
    }

    $sourceWidth  = (int)$imageInfo[0];
    $sourceHeight = (int)$imageInfo[1];
    $type         = $imageInfo[2];

    // Target dimensions must be integers
    $width  = max(1, (int)$width);
    $height = max(1, (int)$height);

    // Create source image
    switch ($type) {

        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;

        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($sourcePath);
            break;

        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($sourcePath);
            break;

        case IMAGETYPE_WEBP:
            if (!function_exists('imagecreatefromwebp')) {
                return false;
            }

            $sourceImage = imagecreatefromwebp($sourcePath);
            break;

        default:
            return false;
    }

    if (!$sourceImage) {
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Create Thumbnail
    |--------------------------------------------------------------------------
    */

    if ($crop) {

        // Scale image so that target area is completely covered
        $ratio = max(
            $width / $sourceWidth,
            $height / $sourceHeight
        );

        // IMPORTANT:
        // Convert calculated dimensions to integers
        $newWidth = (int)round($sourceWidth * $ratio);
        $newHeight = (int)round($sourceHeight * $ratio);

        // Calculate crop position
        $x = (int)round(($newWidth - $width) / 2);
        $y = (int)round(($newHeight - $height) / 2);

        // Create thumbnail canvas
        $thumb = imagecreatetruecolor($width, $height);

        // Preserve transparency
        if (
            $type === IMAGETYPE_PNG ||
            $type === IMAGETYPE_GIF ||
            $type === IMAGETYPE_WEBP
        ) {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);

            $transparent = imagecolorallocatealpha(
                $thumb,
                0,
                0,
                0,
                127
            );

            imagefill($thumb, 0, 0, $transparent);
        }

        // Resize + crop
        imagecopyresampled(
            $thumb,
            $sourceImage,
            0,
            0,
            $x,
            $y,
            $width,
            $height,
            $newWidth,
            $newHeight
        );
    } else {

        /*
        |--------------------------------------------------------------------------
        | Keep aspect ratio when crop = false
        |--------------------------------------------------------------------------
        */

        $ratio = min(
            $width / $sourceWidth,
            $height / $sourceHeight
        );

        $newWidth  = max(1, (int)round($sourceWidth * $ratio));
        $newHeight = max(1, (int)round($sourceHeight * $ratio));

        $thumb = imagecreatetruecolor(
            $newWidth,
            $newHeight
        );

        // Preserve transparency
        if (
            $type === IMAGETYPE_PNG ||
            $type === IMAGETYPE_GIF ||
            $type === IMAGETYPE_WEBP
        ) {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);

            $transparent = imagecolorallocatealpha(
                $thumb,
                0,
                0,
                0,
                127
            );

            imagefill($thumb, 0, 0, $transparent);
        }

        imagecopyresampled(
            $thumb,
            $sourceImage,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $sourceWidth,
            $sourceHeight
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Target Directory
    |--------------------------------------------------------------------------
    */

    $targetDir = dirname($targetPath);

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    /*
    |--------------------------------------------------------------------------
    | Save Thumbnail
    |--------------------------------------------------------------------------
    */

    $success = false;

    switch ($type) {

        case IMAGETYPE_JPEG:

            $success = imagejpeg(
                $thumb,
                $targetPath,
                defined('IMAGE_QUALITY') ? IMAGE_QUALITY : 80
            );

            break;

        case IMAGETYPE_PNG:

            $success = imagepng(
                $thumb,
                $targetPath,
                9
            );

            break;

        case IMAGETYPE_GIF:

            $success = imagegif(
                $thumb,
                $targetPath
            );

            break;

        case IMAGETYPE_WEBP:

            $success = imagewebp(
                $thumb,
                $targetPath,
                defined('IMAGE_QUALITY') ? IMAGE_QUALITY : 80
            );

            break;
    }

    // Free memory
    imagedestroy($sourceImage);
    imagedestroy($thumb);

    return $success;
}

// ============================================
// DATABASE HELPER FUNCTIONS
// ============================================

/**
 * Check if a record exists in the database
 * 
 * @param string $table Table name
 * @param string $where Where clause (e.g., "id = ?")
 * @param array $params Parameters for prepared statement
 * @return bool True if exists
 */
function recordExists($table, $where, $params = [])
{
    $db = getDB();
    $sql = "SELECT COUNT(*) as count FROM `$table` WHERE $where";
    $result = $db->fetchOne($sql, $params);
    return $result && $result['count'] > 0;
}

/**
 * Get a single record by ID
 * 
 * @param string $table Table name
 * @param int $id Record ID
 * @param string $idColumn ID column name
 * @return array|null Record or null if not found
 */
function getRecordById($table, $id, $idColumn = 'id')
{
    $db = getDB();
    $sql = "SELECT * FROM `$table` WHERE `$idColumn` = ?";
    return $db->fetchOne($sql, [$id]);
}

/**
 * Get total count of records in a table
 * 
 * @param string $table Table name
 * @param string $where Optional WHERE clause
 * @param array $params Parameters for prepared statement
 * @return int Total count
 */
function getRecordCount($table, $where = null, $params = [])
{
    $db = getDB();
    $sql = "SELECT COUNT(*) as count FROM `$table`";
    if ($where) {
        $sql .= " WHERE $where";
    }
    $result = $db->fetchOne($sql, $params);
    return $result ? (int)$result['count'] : 0;
}

// ============================================
// USER AUTHENTICATION FUNCTIONS
// ============================================

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in
 */
function isLoggedIn()
{
    if (session_status() === PHP_SESSION_NONE) {
        return false;
    }
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * Get current user ID
 * 
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId()
{
    if (!isLoggedIn()) {
        return null;
    }
    return (int)$_SESSION['user_id'];
}

/**
 * Get current user role
 * 
 * @return string|null User role or null if not logged in
 */
function getCurrentUserRole()
{
    if (!isLoggedIn()) {
        return null;
    }
    return $_SESSION['user_role'] ?? null;
}

/**
 * Check if user has a specific role
 * 
 * @param string|array $roles Role(s) to check
 * @param int|null $userId User ID (null for current user)
 * @return bool True if user has role
 */
function hasRole($roles, $userId = null)
{
    // If userId is provided, check that user specifically
    if ($userId !== null) {
        $db = getDB();
        $sql = "SELECT role FROM users WHERE id = ?";
        $user = $db->fetchOne($sql, [$userId]);
        if (!$user) {
            return false;
        }
        $userRole = $user['role'];
    } else {
        // Check current logged in user
        if (!isLoggedIn()) {
            return false;
        }
        $userRole = getCurrentUserRole();
    }

    if (empty($userRole)) {
        return false;
    }

    if (is_array($roles)) {
        return in_array($userRole, $roles);
    }

    return $userRole === $roles;
}

// ============================================
// NOTIFICATION FUNCTIONS
// ============================================

/**
 * Add a notification for a user
 * 
 * @param int $userId User ID
 * @param string $type Notification type
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string|null $link Optional link
 * @return bool True on success
 */
function addNotification($userId, $type, $title, $message, $link = null)
{
    $db = getDB();
    $sql = "INSERT INTO notifications (user_id, type, title, message, link, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    return $db->query($sql, [$userId, $type, $title, $message, $link]) !== false;
}

/**
 * Get notifications for a user
 * 
 * @param int $userId User ID
 * @param int $limit Maximum number of notifications
 * @param bool $unreadOnly Only get unread notifications
 * @return array Notifications
 */
function getNotifications($userId, $limit = 10, $unreadOnly = false)
{
    $db = getDB();
    $sql = "SELECT * FROM notifications WHERE user_id = ?";
    if ($unreadOnly) {
        $sql .= " AND is_read = 0";
    }
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    return $db->fetchAll($sql, [$userId, $limit]);
}

/**
 * Mark notification as read
 * 
 * @param int $notificationId Notification ID
 * @return bool True on success
 */
function markNotificationRead($notificationId)
{
    $db = getDB();
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ?";
    return $db->query($sql, [$notificationId]) !== false;
}

/**
 * Mark all notifications as read for a user
 * 
 * @param int $userId User ID
 * @return bool True on success
 */
function markAllNotificationsRead($userId)
{
    $db = getDB();
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
    return $db->query($sql, [$userId]) !== false;
}

// ============================================
// ACTIVITY LOGGING FUNCTIONS
// ============================================

/**
 * Log an activity
 * 
 * @param string $action Action performed (REQUIRED)
 * @param int|null $userId User ID
 * @param string|null $module Module name
 * @param string|null $description Description
 * @param array|null $oldData Old data
 * @param array|null $newData New data
 * @return bool True on success
 */
function logActivity($action, $userId = null, $module = null, $description = null, $oldData = null, $newData = null)
{
    if (!LOG_ENABLED) {
        return true;
    }

    $db = getDB();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $sql = "INSERT INTO activity_logs (user_id, action, module, description, ip_address, user_agent, old_data, new_data, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $oldDataJson = $oldData ? json_encode($oldData) : null;
    $newDataJson = $newData ? json_encode($newData) : null;

    return $db->query($sql, [$userId, $action, $module, $description, $ipAddress, $userAgent, $oldDataJson, $newDataJson]) !== false;
}

// ============================================
// SESSION MANAGEMENT
// ============================================

/**
 * Initialize secure session
 * NOTE: Session settings must be set BEFORE session_start()
 */
function initSecureSession()
{
    // Only set session settings if session is not already active
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session parameters BEFORE starting session
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');

        if (defined('APP_ENV') && APP_ENV === 'production') {
            ini_set('session.cookie_secure', 1);
        }

        // Start session
        session_start();
    }

    // Regenerate session ID periodically for security
    if (!isset($_SESSION['session_regenerated'])) {
        session_regenerate_id(true);
        $_SESSION['session_regenerated'] = time();
    }

    // Check session timeout
    if (
        isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)
    ) {
        destroySession();
        if (isset($_SERVER['REQUEST_URI'])) {
            header('Location: login.php?timeout=1');
            exit;
        }
    }

    $_SESSION['last_activity'] = time();
}

/**
 * Destroy session and clear all session data
 */
function destroySession()
{
    // Clear all session variables
    $_SESSION = array();

    // Delete session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    // Destroy session
    session_destroy();
}

// ============================================
// AUTHENTICATION FUNCTIONS
// ============================================

/**
 * Authenticate user with username/email and password
 * 
 * @param string $username Username or email
 * @param string $password Plain text password
 * @return array ['success' => bool, 'user' => array|null, 'error' => string|null]
 */
function authenticateUser($username, $password)
{
    $db = getDB();

    // Check for login attempts
    $blockCheck = isLoginBlocked($username);
    if ($blockCheck['blocked']) {
        return [
            'success' => false,
            'error' => 'Too many failed login attempts. Please try again after ' .
                ceil($blockCheck['remaining'] / 60) . ' minutes.'
        ];
    }

    // Get user by username or email
    $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
    $user = $db->fetchOne($sql, [$username, $username]);

    if (!$user) {
        recordFailedAttempt($username);
        return [
            'success' => false,
            'error' => 'Invalid username or password'
        ];
    }

    // Check if user is suspended
    if ($user['status'] === 'suspended') {
        return [
            'success' => false,
            'error' => 'Your account has been suspended. Please contact support.'
        ];
    }

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        recordFailedAttempt($username);
        return [
            'success' => false,
            'error' => 'Invalid username or password'
        ];
    }

    // Check if password needs rehash (for security upgrades)
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $db->query("UPDATE users SET password_hash = ? WHERE id = ?", [$newHash, $user['id']]);
    }

    // Clear failed login attempts on successful login
    clearFailedAttempts($username);

    // Update last login information
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $db->query(
        "UPDATE users SET last_login = NOW(), last_ip = ? WHERE id = ?",
        [$ipAddress, $user['id']]
    );

    // Set session data
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_username'] = $user['username'];
    $_SESSION['user_avatar'] = $user['avatar'];
    $_SESSION['login_time'] = time();
    $_SESSION['ip_address'] = $ipAddress;
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Regenerate session ID after login for security
    session_regenerate_id(true);

    // Log the login activity
    logActivity('login', $user['id'], 'auth', 'User logged in successfully');

    return [
        'success' => true,
        'user' => $user,
        'error' => null
    ];
}

/**
 * Get current user data
 * 
 * @return array|null User data or null if not logged in
 */
function getCurrentUser()
{
    if (!isLoggedIn()) {
        return null;
    }

    $db = getDB();
    $sql = "SELECT * FROM users WHERE id = ?";
    return $db->fetchOne($sql, [$_SESSION['user_id']]);
}

/**
 * Logout user and destroy session
 */
function logoutUser()
{
    if (isLoggedIn()) {
        logActivity('logout', $_SESSION['user_id'], 'auth', 'User logged out');
    }
    destroySession();
}

// ============================================
// LOGIN ATTEMPT PROTECTION
// ============================================

/**
 * Check if login is blocked for a user/IP
 * 
 * @param string $username Username
 * @return array ['blocked' => bool, 'remaining' => int, 'attempts' => int]
 */
function isLoginBlocked($username)
{
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Check attempts from this IP or username
    $sql = "SELECT COUNT(*) as count, MAX(attempt_time) as last_attempt 
            FROM failed_login_attempts 
            WHERE (username = ? OR ip_address = ?) 
            AND attempt_time >= DATE_SUB(NOW(), INTERVAL ? SECOND)";

    $result = $db->fetchOne($sql, [$username, $ip, LOGIN_ATTEMPT_WINDOW]);

    if (!$result) {
        return ['blocked' => false, 'remaining' => 0, 'attempts' => 0];
    }

    $attempts = (int)$result['count'];

    // If attempts exceed max, check if still within lockout window
    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        $lastAttempt = strtotime($result['last_attempt']);
        $lockoutEnd = $lastAttempt + LOGIN_LOCKOUT_TIME;
        $remaining = $lockoutEnd - time();

        if ($remaining > 0) {
            return [
                'blocked' => true,
                'remaining' => $remaining,
                'attempts' => $attempts
            ];
        }

        // Clear attempts after lockout period
        clearFailedAttempts($username);
        return ['blocked' => false, 'remaining' => 0, 'attempts' => 0];
    }

    return ['blocked' => false, 'remaining' => 0, 'attempts' => $attempts];
}

/**
 * Record a failed login attempt
 * 
 * @param string $username Username
 */
function recordFailedAttempt($username)
{
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    $sql = "INSERT INTO failed_login_attempts (username, ip_address, attempt_time) 
            VALUES (?, ?, NOW())";
    $db->query($sql, [$username, $ip]);
}

/**
 * Clear failed login attempts for a user
 * 
 * @param string $username Username
 */
function clearFailedAttempts($username)
{
    $db = getDB();
    $sql = "DELETE FROM failed_login_attempts WHERE username = ?";
    $db->query($sql, [$username]);
}

// ============================================
// AUTHORIZATION FUNCTIONS
// ============================================

/**
 * Check if user has permission to perform an action
 * 
 * @param string $permissionSlug Permission slug to check
 * @param int|null $userId User ID (null for current user)
 * @return bool True if authorized
 */
function hasPermission($permissionSlug, $userId = null)
{
    if ($userId === null) {
        if (!isLoggedIn()) {
            return false;
        }
        $userId = $_SESSION['user_id'];
    }

    $db = getDB();

    // Get user role
    $sql = "SELECT role FROM users WHERE id = ?";
    $user = $db->fetchOne($sql, [$userId]);

    if (!$user) {
        return false;
    }

    // Admin has all permissions
    if ($user['role'] === 'admin') {
        return true;
    }

    // Check if user has this permission via role
    $sql = "SELECT COUNT(*) as count 
            FROM role_permissions rp
            JOIN roles r ON rp.role_id = r.id
            JOIN permissions p ON rp.permission_id = p.id
            JOIN users u ON u.role = r.role_slug
            WHERE u.id = ? AND p.permission_slug = ?";

    $result = $db->fetchOne($sql, [$userId, $permissionSlug]);

    if ($result && $result['count'] > 0) {
        return true;
    }

    // Check if user has this permission directly (user-level override)
    $sql = "SELECT COUNT(*) as count 
            FROM user_permissions up
            JOIN permissions p ON up.permission_id = p.id
            WHERE up.user_id = ? AND p.permission_slug = ?";

    $result = $db->fetchOne($sql, [$userId, $permissionSlug]);

    return $result && $result['count'] > 0;
}

/**
 * Check if user has one of multiple permissions
 * 
 * @param array $permissionSlugs Array of permission slugs
 * @param int|null $userId User ID (null for current user)
 * @return bool True if has any of the permissions
 */
function hasAnyPermission($permissionSlugs, $userId = null)
{
    foreach ($permissionSlugs as $permission) {
        if (hasPermission($permission, $userId)) {
            return true;
        }
    }
    return false;
}

/**
 * Check if user has all permissions
 * 
 * @param array $permissionSlugs Array of permission slugs
 * @param int|null $userId User ID (null for current user)
 * @return bool True if has all permissions
 */
function hasAllPermissions($permissionSlugs, $userId = null)
{
    foreach ($permissionSlugs as $permission) {
        if (!hasPermission($permission, $userId)) {
            return false;
        }
    }
    return true;
}

/**
 * Get all permissions for a user
 * 
 * @param int|null $userId User ID (null for current user)
 * @return array Array of permission slugs
 */
function getUserPermissions($userId = null)
{
    if ($userId === null) {
        if (!isLoggedIn()) {
            return [];
        }
        $userId = $_SESSION['user_id'];
    }

    $db = getDB();

    // Get user role
    $sql = "SELECT role FROM users WHERE id = ?";
    $user = $db->fetchOne($sql, [$userId]);

    if (!$user) {
        return [];
    }

    // Admin has all permissions
    if ($user['role'] === 'admin') {
        $sql = "SELECT permission_slug FROM permissions";
        $results = $db->fetchAll($sql);
        return array_column($results, 'permission_slug');
    }

    // Get permissions from role
    $sql = "SELECT p.permission_slug 
            FROM role_permissions rp
            JOIN roles r ON rp.role_id = r.id
            JOIN permissions p ON rp.permission_id = p.id
            JOIN users u ON u.role = r.role_slug
            WHERE u.id = ?
            UNION
            SELECT p.permission_slug 
            FROM user_permissions up
            JOIN permissions p ON up.permission_id = p.id
            WHERE up.user_id = ?";

    $results = $db->fetchAll($sql, [$userId, $userId]);
    return array_column($results, 'permission_slug');
}

// ============================================
// AUTHENTICATION MIDDLEWARE
// ============================================

/**
 * Require user to be logged in
 * Redirects to login page if not logged in
 * 
 * @param string $redirectUrl URL to redirect to after login
 */
function requireLogin($redirectUrl = 'login.php')
{
    // Initialize session if not already done
    if (session_status() === PHP_SESSION_NONE) {
        initSecureSession();
    }

    if (!isLoggedIn()) {
        // Store the current URL for redirect after login
        if (isset($_SERVER['REQUEST_URI'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        }

        // Redirect to login page
        if (!headers_sent()) {
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            echo '<script>window.location.href="' . $redirectUrl . '";</script>';
            exit;
        }
    }
}

/**
 * Require user to have a specific role
 * 
 * @param string|array $roles Required role(s)
 * @param string $redirectUrl URL to redirect to if unauthorized
 */
function requireRole($roles, $redirectUrl = 'unauthorized.php')
{
    requireLogin();

    if (!hasRole($roles)) {
        if (!headers_sent()) {
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            echo '<script>window.location.href="' . $redirectUrl . '";</script>';
            exit;
        }
    }
}

/**
 * Require user to have a specific permission
 * 
 * @param string $permissionSlug Required permission
 * @param string $redirectUrl URL to redirect to if unauthorized
 */
function requirePermission($permissionSlug, $redirectUrl = 'unauthorized.php')
{
    // First check if user is logged in
    requireLogin();

    // Then check permission
    if (!hasPermission($permissionSlug)) {
        logActivity(
            'unauthorized_access',
            $_SESSION['user_id'] ?? null,
            'security',
            'User attempted to access ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . ' without permission: ' . $permissionSlug
        );

        if (!headers_sent()) {
            header('Location: ' . $redirectUrl);
            exit;
        } else {
            echo '<script>window.location.href="' . $redirectUrl . '";</script>';
            exit;
        }
    }
}

/**
 * Check if user is admin
 * 
 * @return bool True if user is admin
 */
function isAdmin()
{
    return hasRole('admin');
}

/**
 * Check if user is staff
 * 
 * @return bool True if user is staff
 */
function isStaff()
{
    return hasRole('staff');
}

/**
 * Check if user is agent
 * 
 * @return bool True if user is agent
 */
function isAgent()
{
    return hasRole('agent');
}

/**
 * Check if user is shop
 * 
 * @return bool True if user is shop
 */
function isShop()
{
    return hasRole('shop');
}

// ============================================
// PASSWORD MANAGEMENT
// ============================================

/**
 * Hash a password
 * 
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify a password
 * 
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches
 */
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

/**
 * Generate a secure random password
 * 
 * @param int $length Length of password
 * @return string Generated password
 */
function generateSecurePassword($length = 12)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';

    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }

    // Ensure password meets requirements
    $validation = validatePassword($password);
    if (!$validation['valid']) {
        return generateSecurePassword($length + 1);
    }

    return $password;
}

/**
 * Change user password
 * 
 * @param int $userId User ID
 * @param string $newPassword New password
 * @return bool True on success
 */
function changeUserPassword($userId, $newPassword)
{
    $db = getDB();
    $hashedPassword = hashPassword($newPassword);

    $sql = "UPDATE users SET password_hash = ? WHERE id = ?";
    return $db->query($sql, [$hashedPassword, $userId]) !== false;
}

// ============================================
// USER-FRIENDLY FUNCTIONS
// ============================================

/**
 * Get human-readable status badge HTML
 * 
 * @param string $status Status value
 * @param string $type Status type (order, user, product, approval)
 * @return string HTML badge
 */
function getStatusBadge($status, $type = 'default')
{
    $colors = [
        'order' => [
            'pending' => 'warning',
            'confirmed' => 'info',
            'processing' => 'primary',
            'shipped' => 'info',
            'delivered' => 'success',
            'cancelled' => 'danger',
            'returned' => 'warning'
        ],
        'user' => [
            'active' => 'success',
            'inactive' => 'warning',
            'suspended' => 'danger'
        ],
        'product' => [
            'active' => 'success',
            'inactive' => 'warning',
            'out_of_stock' => 'danger'
        ],
        'approval' => [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'suspended' => 'danger'
        ]
    ];

    $color = $colors[$type][$status] ?? 'secondary';
    $statusDisplay = ucwords(str_replace('_', ' ', $status));

    return '<span class="badge badge-' . $color . '">' . escapeHtml($statusDisplay) . '</span>';
}

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 * @param int $statusCode HTTP status code
 */
/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 * @param int $statusCode HTTP status code
 */
function redirect($url, $statusCode = 302)
{
    // If URL doesn't start with http:// or https://, make it relative to site root
    if (!preg_match('/^https?:\/\//', $url)) {
        $url = SITE_URL . ltrim($url, '/');
    }

    // Check if headers already sent
    if (headers_sent()) {
        // Use JavaScript fallback
        echo '<script>window.location.href="' . addslashes($url) . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . addslashes($url) . '"></noscript>';
        exit;
    }

    header('Location: ' . $url, true, $statusCode);
    exit;
}

/**
 * Set a flash message
 * 
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message content
 */
function setFlashMessage($type, $message)
{
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    if (!isset($_SESSION['flash_messages'][$type])) {
        $_SESSION['flash_messages'][$type] = [];
    }
    $_SESSION['flash_messages'][$type][] = $message;
}

/**
 * Get and clear flash messages
 * 
 * @param string|null $type Message type to get
 * @return array|null Flash messages
 */
function getFlashMessages($type = null)
{
    if (!isset($_SESSION['flash_messages'])) {
        return [];
    }

    if ($type !== null) {
        $messages = $_SESSION['flash_messages'][$type] ?? [];
        unset($_SESSION['flash_messages'][$type]);
        return $messages;
    }

    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Display flash messages as HTML
 * 
 * @param string|null $type Message type to display
 * @return string HTML output
 */
function displayFlashMessages($type = null)
{
    $messages = getFlashMessages($type);
    if (empty($messages)) {
        return '';
    }

    $html = '';
    foreach ($messages as $messageType => $messageList) {
        foreach ($messageList as $message) {
            $html .= '<div class="alert alert-' . $messageType . ' alert-dismissible fade show" role="alert">';
            $html .= escapeHtml($message);
            $html .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
            $html .= '<span aria-hidden="true">&times;</span>';
            $html .= '</button>';
            $html .= '</div>';
        }
    }

    return $html;
}

// ============================================
// ENVIRONMENT & DEBUG FUNCTIONS
// ============================================

/**
 * Dump variable for debugging (development only)
 * 
 * @param mixed $var Variable to dump
 * @param bool $exit Whether to exit after dumping
 */
function debugDump($var, $exit = false)
{
    if (APP_ENV === 'development') {
        echo '<pre style="background: #f4f4f4; border: 1px solid #ccc; padding: 15px; margin: 10px; border-radius: 5px;">';
        var_dump($var);
        echo '</pre>';
        if ($exit) {
            exit;
        }
    }
}

/**
 * Write to log file
 * 
 * @param string $message Message to log
 * @param string $level Log level (debug, info, warning, error, critical)
 */
function writeLog($message, $level = 'info')
{
    if (!LOG_ENABLED) {
        return;
    }

    $logFile = LOG_PATH . date('Y-m-d') . LOG_FILE_EXTENSION;
    $timestamp = date('Y-m-d H:i:s');
    $level = strtoupper($level);
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;

    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// ============================================
// PAGINATION HELPERS
// ============================================

/**
 * Generate pagination HTML
 * 
 * @param int $totalItems Total number of items
 * @param int $currentPage Current page number
 * @param int $perPage Items per page
 * @param string $url Base URL with placeholder {page}
 * @param int $range Number of pages to show in range
 * @return string Pagination HTML
 */
/**
 * Generate pagination HTML with Bootstrap-like structure
 * 
 * @param int $totalItems Total number of items
 * @param int $currentPage Current page number
 * @param int $perPage Items per page
 * @param string $url Base URL with placeholder {page}
 * @param int $range Number of pages to show in range
 * @param string $size Pagination size (sm, default, lg)
 * @param string $style Pagination style (default, bordered, rounded, shadow)
 * @param string $alignment Alignment (center, left, right, between)
 * @return string Pagination HTML
 */
function getPagination($totalItems, $currentPage, $perPage = PAGINATION_DEFAULT_LIMIT, $url = '?page={page}', $range = PAGINATION_PAGE_RANGE, $size = '', $style = '', $alignment = 'center')
{
    $totalPages = ceil($totalItems / $perPage);

    if ($totalPages <= 1) {
        return '';
    }

    $sizeClass = $size ? 'pagination-' . $size : '';
    $styleClass = $style ? 'pagination-' . $style : '';
    $alignClass = 'pagination-' . $alignment;

    // Build the URL with page parameter
    $pageUrl = str_replace('{page}', '{page}', $url);

    $html = '<div class="pagination-container ' . $alignClass . '">';
    $html .= '<nav aria-label="Page navigation">';
    $html .= '<ul class="pagination ' . $sizeClass . ' ' . $styleClass . '">';

    // First page
    if ($currentPage > 1) {
        $html .= '<li class="page-item first">';
        $html .= '<a class="page-link" href="' . str_replace('{page}', 1, $pageUrl) . '" aria-label="First">';
        $html .= '<i class="fas fa-angle-double-left"></i>';
        $html .= '</a></li>';
    } else {
        $html .= '<li class="page-item first disabled"><span class="page-link"><i class="fas fa-angle-double-left"></i></span></li>';
    }

    // Previous button
    $prevPage = $currentPage - 1;
    if ($currentPage > 1) {
        $html .= '<li class="page-item prev">';
        $html .= '<a class="page-link" href="' . str_replace('{page}', $prevPage, $pageUrl) . '" aria-label="Previous">';
        $html .= '<i class="fas fa-chevron-left"></i> <span class="page-text">Previous</span>';
        $html .= '</a></li>';
    } else {
        $html .= '<li class="page-item prev disabled"><span class="page-link"><i class="fas fa-chevron-left"></i> <span class="page-text">Previous</span></span></li>';
    }

    // Page numbers
    $start = max(1, $currentPage - $range);
    $end = min($totalPages, $currentPage + $range);

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . str_replace('{page}', 1, $pageUrl) . '">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        if ($i == $currentPage) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . str_replace('{page}', $i, $pageUrl) . '">' . $i . '</a></li>';
        }
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . str_replace('{page}', $totalPages, $pageUrl) . '">' . $totalPages . '</a></li>';
    }

    // Next button
    $nextPage = $currentPage + 1;
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item next">';
        $html .= '<a class="page-link" href="' . str_replace('{page}', $nextPage, $pageUrl) . '" aria-label="Next">';
        $html .= '<span class="page-text">Next</span> <i class="fas fa-chevron-right"></i>';
        $html .= '</a></li>';
    } else {
        $html .= '<li class="page-item next disabled"><span class="page-link"><span class="page-text">Next</span> <i class="fas fa-chevron-right"></i></span></li>';
    }

    // Last page
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item last">';
        $html .= '<a class="page-link" href="' . str_replace('{page}', $totalPages, $pageUrl) . '" aria-label="Last">';
        $html .= '<i class="fas fa-angle-double-right"></i>';
        $html .= '</a></li>';
    } else {
        $html .= '<li class="page-item last disabled"><span class="page-link"><i class="fas fa-angle-double-right"></i></span></li>';
    }

    $html .= '</ul>';

    // Info text
    $startItem = (($currentPage - 1) * $perPage) + 1;
    $endItem = min($currentPage * $perPage, $totalItems);
    $html .= '<div class="pagination-info">';
    $html .= 'Showing <strong>' . $startItem . '</strong> to <strong>' . $endItem . '</strong> of <strong>' . number_format($totalItems) . '</strong> results';
    $html .= '</div>';

    $html .= '</nav>';
    $html .= '</div>';

    return $html;
}

/**
 * Get offset for pagination
 * 
 * @param int $page Current page
 * @param int $perPage Items per page
 * @return int Offset for SQL LIMIT
 */
function getPaginationOffset($page, $perPage = PAGINATION_DEFAULT_LIMIT)
{
    return max(0, ($page - 1) * $perPage);
}

// ============================================
// ATTENDANCE FUNCTIONS
// ============================================

/**
 * Check if user can check-in from current location
 * 
 * @param int $userId User ID
 * @param float $lat Latitude
 * @param float $lng Longitude
 * @return array ['allowed' => bool, 'message' => string]
 */
function canCheckInFromLocation($userId, $lat, $lng)
{
    $db = getDB();

    // Get user role
    $sql = "SELECT role FROM users WHERE id = ?";
    $user = $db->fetchOne($sql, [$userId]);

    if (!$user) {
        return ['allowed' => false, 'message' => 'User not found'];
    }

    // Agents can check-in from anywhere
    if ($user['role'] === 'agent') {
        // Check agent settings
        $sql = "SELECT setting_value FROM attendance_settings WHERE setting_key = 'agent_allow_anywhere'";
        $setting = $db->fetchOne($sql);
        if ($setting && $setting['setting_value'] == '1') {
            return ['allowed' => true, 'message' => 'Agent allowed from anywhere'];
        }
    }

    // Staff must be within geofence
    if ($user['role'] === 'staff') {
        // Get geofence settings
        $sql = "SELECT setting_key, setting_value FROM attendance_settings 
                WHERE setting_key IN ('office_lat', 'office_lng', 'geolocation_radius')";
        $settings = $db->fetchAll($sql);

        $officeLat = 0;
        $officeLng = 0;
        $radius = 500; // Default 500 meters

        foreach ($settings as $s) {
            if ($s['setting_key'] === 'office_lat') $officeLat = (float)$s['setting_value'];
            if ($s['setting_key'] === 'office_lng') $officeLng = (float)$s['setting_value'];
            if ($s['setting_key'] === 'geolocation_radius') $radius = (float)$s['setting_value'];
        }

        if ($officeLat == 0 || $officeLng == 0) {
            return ['allowed' => false, 'message' => 'Office location not configured'];
        }

        // Calculate distance
        $distance = calculateDistance($lat, $lng, $officeLat, $officeLng);

        if ($distance <= $radius) {
            return ['allowed' => true, 'message' => 'Within geofence radius'];
        } else {
            return [
                'allowed' => false,
                'message' => 'You are ' . number_format($distance, 0) . ' meters away from office. Maximum allowed: ' . $radius . ' meters'
            ];
        }
    }

    return ['allowed' => false, 'message' => 'Invalid user role'];
}

/**
 * Calculate distance between two coordinates in meters
 * 
 * @param float $lat1 Latitude 1
 * @param float $lng1 Longitude 1
 * @param float $lat2 Latitude 2
 * @param float $lng2 Longitude 2
 * @return float Distance in meters
 */
function calculateDistance($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6371000; // meters

    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLng / 2) * sin($dLng / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

/**
 * Record attendance check-in
 * 
 * @param int $userId User ID
 * @param string|null $location Location name
 * @param float|null $lat Latitude
 * @param float|null $lng Longitude
 * @param string|null $ip IP address
 * @return array ['success' => bool, 'message' => string, 'attendance_id' => int|null]
 */
function recordAttendanceCheckIn($userId, $location = null, $lat = null, $lng = null, $ip = null)
{
    $db = getDB();
    $today = date('Y-m-d');
    $ip = $ip ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Check if already checked in today
    $sql = "SELECT id, check_in_time FROM attendance WHERE user_id = ? AND date = ?";
    $existing = $db->fetchOne($sql, [$userId, $today]);

    if ($existing) {
        return ['success' => false, 'message' => 'Already checked in today at ' . date('h:i A', strtotime($existing['check_in_time']))];
    }

    // Get user role
    $sql = "SELECT role FROM users WHERE id = ?";
    $user = $db->fetchOne($sql, [$userId]);
    $userType = $user['role'] ?? 'staff';

    // Check location for staff (agents can check-in from anywhere)
    if ($userType === 'staff' && $lat !== null && $lng !== null) {
        $locationCheck = canCheckInFromLocation($userId, $lat, $lng);
        if (!$locationCheck['allowed']) {
            return ['success' => false, 'message' => $locationCheck['message']];
        }
    }

    // Prepare location data - use null if not provided
    $checkInLocation = $location !== null ? $location : null;
    $checkInLat = $lat !== null ? $lat : null;
    $checkInLng = $lng !== null ? $lng : null;

    // Insert attendance
    $sql = "INSERT INTO attendance (user_id, user_type, date, check_in_time, check_in_location, check_in_lat, check_in_lng, check_in_ip, status, created_at) 
            VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, 'present', NOW())";

    $db->query($sql, [
        $userId,
        $userType,
        $today,
        $checkInLocation,
        $checkInLat,
        $checkInLng,
        $ip
    ]);

    $attendanceId = $db->lastInsertId();

    logActivity('check_in', $userId, 'attendance', 'Checked in at ' . ($location ?? 'Unknown location'));

    return ['success' => true, 'message' => 'Check-in successful!', 'attendance_id' => $attendanceId];
}

/**
 * Record attendance check-out
 * 
 * @param int $userId User ID
 * @param string|null $location Location name
 * @param float|null $lat Latitude
 * @param float|null $lng Longitude
 * @param string|null $ip IP address
 * @return array ['success' => bool, 'message' => string]
 */
function recordAttendanceCheckOut($userId, $location = null, $lat = null, $lng = null, $ip = null)
{
    $db = getDB();
    $today = date('Y-m-d');
    $ip = $ip ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Check if checked in today and not checked out
    $sql = "SELECT id, check_in_time FROM attendance WHERE user_id = ? AND date = ? AND check_out_time IS NULL";
    $existing = $db->fetchOne($sql, [$userId, $today]);

    if (!$existing) {
        return ['success' => false, 'message' => 'You have not checked in today or already checked out'];
    }

    // Prepare location data - use null if not provided
    $checkOutLocation = $location !== null ? $location : null;
    $checkOutLat = $lat !== null ? $lat : null;
    $checkOutLng = $lng !== null ? $lng : null;

    // Update attendance
    $sql = "UPDATE attendance SET 
            check_out_time = NOW(),
            check_out_location = ?,
            check_out_lat = ?,
            check_out_lng = ?,
            check_out_ip = ?
            WHERE id = ?";

    $db->query($sql, [$checkOutLocation, $checkOutLat, $checkOutLng, $ip, $existing['id']]);

    logActivity('check_out', $userId, 'attendance', 'Checked out at ' . ($location ?? 'Unknown location'));

    return ['success' => true, 'message' => 'Check-out successful!'];
}


function getAttendanceSummary($userId, $days = 30)
{
    $db = getDB();

    $sql = "SELECT 
            COUNT(*) as total_days,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
            SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) as half_days,
            SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days,
            SUM(overtime_hours) as total_overtime
            FROM attendance 
            WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";

    return $db->fetchOne($sql, [$userId, $days]);
}

/**
 * Get attendance dashboard widgets for user
 * 
 * @param int $userId User ID
 * @return array Dashboard widgets data
 */
function getAttendanceWidgets($userId)
{
    $db = getDB();

    // Today's status
    $today = date('Y-m-d');
    $sql = "SELECT status, check_in_time, check_out_time FROM attendance WHERE user_id = ? AND date = ?";
    $todayAttendance = $db->fetchOne($sql, [$userId, $today]);

    // Week summary
    $sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
            FROM attendance 
            WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    $weekSummary = $db->fetchOne($sql, [$userId]);

    // Month summary
    $sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
            FROM attendance 
            WHERE user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $monthSummary = $db->fetchOne($sql, [$userId]);

    return [
        'today' => [
            'status' => $todayAttendance['status'] ?? 'absent',
            'check_in' => $todayAttendance['check_in_time'] ?? null,
            'check_out' => $todayAttendance['check_out_time'] ?? null,
            'is_checked_in' => $todayAttendance && !$todayAttendance['check_out_time']
        ],
        'week' => [
            'total' => $weekSummary['total'] ?? 0,
            'present' => $weekSummary['present'] ?? 0,
            'absent' => $weekSummary['absent'] ?? 0,
            'percentage' => ($weekSummary['total'] ?? 0) > 0 ? round(($weekSummary['present'] ?? 0) / ($weekSummary['total'] ?? 0) * 100) : 0
        ],
        'month' => [
            'total' => $monthSummary['total'] ?? 0,
            'present' => $monthSummary['present'] ?? 0,
            'absent' => $monthSummary['absent'] ?? 0,
            'percentage' => ($monthSummary['total'] ?? 0) > 0 ? round(($monthSummary['present'] ?? 0) / ($monthSummary['total'] ?? 0) * 100) : 0
        ]
    ];
}



/**
 * Get Font Awesome icon class for a module
 * 
 * @param string $module Module name
 * @return string Font Awesome icon class
 */
function getModuleIcon($module)
{
    $icons = [
        // Main Modules
        'dashboard' => 'th-large',
        'staff' => 'users',
        'agent' => 'user-tie',
        'shop' => 'store',
        'product' => 'box',
        'category' => 'tags',
        'order' => 'shopping-cart',
        'inventory' => 'warehouse',
        'report' => 'chart-bar',
        'settings' => 'cog',
        'security' => 'shield-alt',
        'payment' => 'credit-card',
        'attendance' => 'calendar-check',
        'visits' => 'route',
        'leads' => 'bullhorn',
        'auth' => 'user-lock',
        'profile' => 'user-circle',
        'general' => 'circle'
    ];
    return $icons[$module] ?? 'circle';
}

/**
 * Get Font Awesome icon class based on permission action
 * 
 * @param string $slug Permission slug
 * @return string Font Awesome icon class
 */
function getPermissionIcon($slug)
{
    // View permissions
    if (strpos($slug, 'view') !== false) {
        return 'eye';
    }
    // Create permissions
    if (strpos($slug, 'create') !== false) {
        return 'plus-circle';
    }
    // Edit permissions
    if (strpos($slug, 'edit') !== false) {
        return 'edit';
    }
    // Delete permissions
    if (strpos($slug, 'delete') !== false) {
        return 'trash';
    }
    // Approve permissions
    if (strpos($slug, 'approve') !== false) {
        return 'check-double';
    }
    // Update permissions
    if (strpos($slug, 'update') !== false) {
        return 'sync';
    }
    // Cancel permissions
    if (strpos($slug, 'cancel') !== false) {
        return 'times-circle';
    }
    // Manage permissions
    if (strpos($slug, 'manage') !== false) {
        return 'cogs';
    }
    // Confirm permissions
    if (strpos($slug, 'confirm') !== false) {
        return 'check-circle';
    }
    // Toggle permissions
    if (strpos($slug, 'toggle') !== false) {
        return 'toggle-on';
    }
    // Default
    return 'circle';
}



// Create a function in functions.php
/**
 * Check if user has any of the given permissions or is admin
 * 
 * @param array $permissions Array of permission slugs
 * @return bool True if user has any permission or is admin
 */
function hasAnyPermissionOrAdmin($permissions)
{
    if (!isLoggedIn()) {
        return false;
    }

    if (isAdmin()) {
        return true;
    }

    foreach ($permissions as $permission) {
        if (hasPermission($permission)) {
            return true;
        }
    }

    return false;
}

/**
 * Require user to have any of the given permissions or be admin
 * Redirects to unauthorized page if not
 * 
 * @param array $permissions Array of permission slugs
 * @param string $redirectUrl URL to redirect to
 */

/**
 * Check if user has permission to access a page
 * Admin has all access, Staff needs specific permission
 * 
 * @param string $permissionSlug The permission slug to check (e.g., 'shop.view', 'agent.edit')
 * @param string $pageName The page name for logging (e.g., 'agents.php')
 * @param string $redirectUrl URL to redirect if unauthorized (default: 'dashboard.php')
 * @return bool Returns true if authorized, otherwise redirects
 */
function requirePermissionOrAdmin($permissionSlug, $pageName = '', $redirectUrl = 'dashboard.php')
{
    // First check if user is logged in
    requireLogin();

    // Admin has all access - allow
    if (isAdmin()) {
        return true;
    }

    // Check if user has the specific permission
    if (hasPermission($permissionSlug)) {
        return true;
    }

    // If no permission, log and redirect
    $pageName = $pageName ?: ($_SERVER['REQUEST_URI'] ?? 'unknown');
    logActivity(
        'unauthorized_access',
        $_SESSION['user_id'] ?? null,
        'security',
        'Attempted to access ' . $pageName . ' without ' . $permissionSlug . ' permission'
    );

    setFlashMessage('error', 'You do not have permission to access this page.');
    redirect($redirectUrl);
    exit;
}

/**
 * Check if user has any of the given permissions or is admin
 * 
 * @param array $permissionSlugs Array of permission slugs
 * @param string $pageName The page name for logging
 * @param string $redirectUrl URL to redirect if unauthorized
 * @return bool Returns true if authorized, otherwise redirects
 */


// ============================================
// PERMISSION MANAGEMENT FUNCTIONS
// ============================================



/**
 * Require user to be logged in and have ANY of the given permissions OR be admin
 * Redirects to unauthorized page if not
 * 
 * @param array $permissionSlugs Array of permission slugs
 * @param string $pageName The page name for logging
 * @param string $redirectUrl URL to redirect if unauthorized (default: 'dashboard.php')
 * @return bool Returns true if authorized, otherwise redirects
 */
function requireAnyPermissionOrAdmin($permissionSlugs, $pageName = '', $redirectUrl = 'dashboard.php')
{
    // First check if user is logged in
    requireLogin();

    // Admin has all access - allow
    if (isAdmin()) {
        return true;
    }

    // Check if user has any of the permissions
    foreach ($permissionSlugs as $permission) {
        if (hasPermission($permission)) {
            return true;
        }
    }

    // If no permission, log and redirect
    $pageName = $pageName ?: ($_SERVER['REQUEST_URI'] ?? 'unknown');
    $permsList = implode(', ', $permissionSlugs);
    logActivity(
        'unauthorized_access',
        $_SESSION['user_id'] ?? null,
        'security',
        'Attempted to access ' . $pageName . ' without any of: ' . $permsList
    );

    setFlashMessage('error', 'You do not have permission to access this page.');
    redirect($redirectUrl);
    exit;
}

/**
 * Get permission slug for a page
 * Helper function to map page names to permission slugs
 * 
 * @param string $page The page name
 * @return string The permission slug
 */
function getPagePermission($page)
{
    $permissions = [
        // Dashboard
        'dashboard' => 'dashboard.view',

        // Staff Management
        'staff' => 'staff.view',
        'staff-add' => 'staff.create',
        'staff-edit' => 'staff.edit',
        'staff-permissions' => 'staff.permissions',

        // Agent Management
        'agents' => 'agent.view',
        'agent-add' => 'agent.create',
        'agent-edit' => 'agent.edit',
        'agent-view' => 'agent.view',

        // Shop Management
        'shops' => 'shop.view',
        'shop-add' => 'shop.create',
        'shop-edit' => 'shop.edit',
        'shop-view' => 'shop.view',

        // Product Management
        'products' => 'product.view',
        'product-add' => 'product.create',
        'product-edit' => 'product.edit',
        'product-view' => 'product.view',
        'categories' => 'category.view',
        'category-add' => 'category.create',

        // Order Management
        'orders' => 'order.view',
        'order-view' => 'order.view',

        // Payment Management
        'payments' => 'payment.view',
        'payment-view' => 'payment.view',

        // Inventory Management
        'inventory' => 'inventory.view',
        'inventory-log' => 'inventory.view',

        // Reports
        'reports' => 'report.view',

        // Settings
        'settings' => 'settings.view',
        'profile' => 'settings.view',

        // Activity Logs
        'activity-logs' => 'report.view',
    ];

    return $permissions[$page] ?? 'dashboard.view';
}

// ----------------speech============
    // =========================================================
    // TEXT TO SPEECH
    // =========================================================

/**
 * Compress and convert image to WebP format
 * 
 * @param string $sourcePath Source image path
 * @param string $destPath Destination path (optional, default same with .webp)
 * @param int $quality Compression quality (1-100, default 70)
 * @param int $maxWidth Maximum width (optional)
 * @param int $maxHeight Maximum height (optional)
 * @param bool $keepOriginal Keep original file? (default false)
 * @return array ['success' => bool, 'path' => string, 'size' => int, 'message' => string]
 */
function compressAndConvertToWebP(
    $sourcePath,
    $destPath = null,
    $quality = 70,
    $maxWidth = 0,
    $maxHeight = 0,
    $keepOriginal = false
) {
    // ============================================
    // CHECK SOURCE
    // ============================================

    if (!file_exists($sourcePath)) {
        return [
            'success' => false,
            'path' => '',
            'size' => 0,
            'message' => 'Source file not found'
        ];
    }

    // ============================================
    // GET IMAGE INFO
    // ============================================

    $info = @getimagesize($sourcePath);

    if (!$info) {
        return [
            'success' => false,
            'path' => '',
            'size' => 0,
            'message' => 'Invalid image file'
        ];
    }

    $mime = $info['mime'];
    $width = $info[0];
    $height = $info[1];

    // ============================================
    // CHECK WEBP SUPPORT
    // ============================================

    if (!function_exists('imagewebp')) {

        if ($destPath === null) {
            $ext = pathinfo(
                $sourcePath,
                PATHINFO_EXTENSION
            );

            $destPath =
                pathinfo(
                    $sourcePath,
                    PATHINFO_DIRNAME
                ) . '/' .
                pathinfo(
                    $sourcePath,
                    PATHINFO_FILENAME
                ) . '.' . $ext;
        }

        if (!copy($sourcePath, $destPath)) {
            return [
                'success' => false,
                'path' => '',
                'size' => 0,
                'message' => 'Failed to copy original image'
            ];
        }

        return [
            'success' => true,
            'path' => $destPath,
            'size' => filesize($destPath),
            'message' => 'WebP not supported, copied original'
        ];
    }

    // ============================================
    // CREATE SOURCE IMAGE
    // ============================================

    $src = null;

    switch ($mime) {

        case 'image/jpeg':
        case 'image/jpg':

            $src = @imagecreatefromjpeg($sourcePath);

            break;

        case 'image/png':

            $src = @imagecreatefrompng($sourcePath);

            break;

        case 'image/gif':

            $src = @imagecreatefromgif($sourcePath);

            break;

        case 'image/webp':

            $src = @imagecreatefromwebp($sourcePath);

            break;

        case 'image/bmp':
        case 'image/x-ms-bmp':

            $src = @imagecreatefrombmp($sourcePath);

            break;

        default:

            return [
                'success' => false,
                'path' => '',
                'size' => 0,
                'message' => 'Unsupported image format: ' . $mime
            ];
    }

    if (!$src) {

        return [
            'success' => false,
            'path' => '',
            'size' => 0,
            'message' => 'Failed to create image resource'
        ];
    }

    // ============================================
    // FIX EXIF ORIENTATION
    // ============================================
    //
    // Mobile cameras commonly store the actual
    // orientation inside EXIF metadata instead
    // of physically rotating the pixels.
    //
    // We physically rotate the image here before
    // resizing and converting to WebP.
    // ============================================

    if (
        ($mime === 'image/jpeg' || $mime === 'image/jpg') &&
        function_exists('exif_read_data')
    ) {

        $exif = @exif_read_data($sourcePath);

        if (
            $exif !== false &&
            isset($exif['Orientation'])
        ) {

            $orientation = (int)$exif['Orientation'];

            switch ($orientation) {

                // --------------------------------
                // Normal
                // --------------------------------
                case 1:
                    break;

                // --------------------------------
                // Flip horizontal
                // --------------------------------
                case 2:

                    if (function_exists('imageflip')) {
                        imageflip(
                            $src,
                            IMG_FLIP_HORIZONTAL
                        );
                    }

                    break;

                // --------------------------------
                // Rotate 180
                // --------------------------------
                case 3:

                    $rotated = imagerotate(
                        $src,
                        180,
                        0
                    );

                    if ($rotated !== false) {
                        imagedestroy($src);
                        $src = $rotated;
                    }

                    break;

                // --------------------------------
                // Flip vertical
                // --------------------------------
                case 4:

                    if (function_exists('imageflip')) {
                        imageflip(
                            $src,
                            IMG_FLIP_VERTICAL
                        );
                    }

                    break;

                // --------------------------------
                // Flip horizontal + rotate 90 CCW
                // --------------------------------
                case 5:

                    $rotated = imagerotate(
                        $src,
                        -90,
                        0
                    );

                    if ($rotated !== false) {

                        imagedestroy($src);
                        $src = $rotated;

                        if (function_exists('imageflip')) {
                            imageflip(
                                $src,
                                IMG_FLIP_HORIZONTAL
                            );
                        }
                    }

                    break;

                // --------------------------------
                // Rotate 90 clockwise
                // --------------------------------
                case 6:

                    $rotated = imagerotate(
                        $src,
                        -90,
                        0
                    );

                    if ($rotated !== false) {
                        imagedestroy($src);
                        $src = $rotated;
                    }

                    break;

                // --------------------------------
                // Flip horizontal + rotate 90 CW
                // --------------------------------
                case 7:

                    $rotated = imagerotate(
                        $src,
                        90,
                        0
                    );

                    if ($rotated !== false) {

                        imagedestroy($src);
                        $src = $rotated;

                        if (function_exists('imageflip')) {
                            imageflip(
                                $src,
                                IMG_FLIP_HORIZONTAL
                            );
                        }
                    }

                    break;

                // --------------------------------
                // Rotate 90 counter-clockwise
                // --------------------------------
                case 8:

                    $rotated = imagerotate(
                        $src,
                        90,
                        0
                    );

                    if ($rotated !== false) {
                        imagedestroy($src);
                        $src = $rotated;
                    }

                    break;
            }

            // IMPORTANT:
            // After physical rotation, get the
            // corrected dimensions again.
            $width = imagesx($src);
            $height = imagesy($src);
        }
    } else {

        // For non-JPEG images use actual dimensions
        $width = imagesx($src);
        $height = imagesy($src);
    }

    // ============================================
    // CALCULATE NEW DIMENSIONS
    // ============================================

    $newWidth = $width;
    $newHeight = $height;

    if (
        $maxWidth > 0 &&
        $maxHeight > 0
    ) {

        $ratio = min(
            $maxWidth / $width,
            $maxHeight / $height
        );

        if ($ratio < 1) {

            $newWidth = (int)round(
                $width * $ratio
            );

            $newHeight = (int)round(
                $height * $ratio
            );
        }
    } elseif (
        $maxWidth > 0 &&
        $width > $maxWidth
    ) {

        $ratio = $maxWidth / $width;

        $newWidth = $maxWidth;

        $newHeight = (int)round(
            $height * $ratio
        );
    } elseif (
        $maxHeight > 0 &&
        $height > $maxHeight
    ) {

        $ratio = $maxHeight / $height;

        $newHeight = $maxHeight;

        $newWidth = (int)round(
            $width * $ratio
        );
    }

    // ============================================
    // CREATE DESTINATION IMAGE
    // ============================================

    $dst = imagecreatetruecolor(
        $newWidth,
        $newHeight
    );

    if (!$dst) {

        imagedestroy($src);

        return [
            'success' => false,
            'path' => '',
            'size' => 0,
            'message' => 'Failed to create destination image'
        ];
    }

    // ============================================
    // TRANSPARENCY
    // ============================================

    imagealphablending(
        $dst,
        false
    );

    imagesavealpha(
        $dst,
        true
    );

    $transparent = imagecolorallocatealpha(
        $dst,
        0,
        0,
        0,
        127
    );

    imagefill(
        $dst,
        0,
        0,
        $transparent
    );

    // ============================================
    // RESIZE
    // ============================================

    imagecopyresampled(
        $dst,
        $src,
        0,
        0,
        0,
        0,
        $newWidth,
        $newHeight,
        $width,
        $height
    );

    // ============================================
    // DESTINATION PATH
    // ============================================

    if ($destPath === null) {

        $dir = pathinfo(
            $sourcePath,
            PATHINFO_DIRNAME
        );

        $filename = pathinfo(
            $sourcePath,
            PATHINFO_FILENAME
        );

        $destPath =
            $dir . '/' .
            $filename .
            '.webp';
    }

    // ============================================
    // FORCE WEBP EXTENSION
    // ============================================

    if (
        strtolower(
            pathinfo(
                $destPath,
                PATHINFO_EXTENSION
            )
        ) !== 'webp'
    ) {

        $destPath =
            pathinfo(
                $destPath,
                PATHINFO_DIRNAME
            ) . '/' .
            pathinfo(
                $destPath,
                PATHINFO_FILENAME
            ) . '.webp';
    }

    // ============================================
    // SAVE WEBP
    // ============================================

    $success = imagewebp(
        $dst,
        $destPath,
        $quality
    );

    // ============================================
    // FREE MEMORY
    // ============================================

    imagedestroy($src);
    imagedestroy($dst);

    if (!$success) {

        return [
            'success' => false,
            'path' => '',
            'size' => 0,
            'message' => 'Failed to save WebP image'
        ];
    }

    // ============================================
    // FILE SIZE
    // ============================================

    $newSize = @filesize($destPath);
    $oldSize = @filesize($sourcePath);

    // ============================================
    // REMOVE ORIGINAL TEMP FILE
    // ============================================

    if (
        !$keepOriginal &&
        $sourcePath !== $destPath
    ) {
        @unlink($sourcePath);
    }

    // ============================================
    // RETURN
    // ============================================

    return [
        'success' => true,
        'path' => $destPath,
        'size' => $newSize ?: 0,
        'old_size' => $oldSize ?: 0,
        'saved_percentage' => ($oldSize && $oldSize > 0)
            ? round(
                (1 - ($newSize / $oldSize)) * 100
            )
            : 0,
        'message' =>
        'Image converted to WebP, compressed, and orientation corrected'
    ];
}

/**
 * Upload and compress image (Combined function for easy use)
 * 
 * @param array $file $_FILES array
 * @param string $uploadDir Upload directory
 * @param int $quality Compression quality (default 70)
 * @param int $maxWidth Max width (default 1920)
 * @param int $maxHeight Max height (default 1920)
 * @param bool $createThumb Create thumbnail? (default false)
 * @param int $thumbWidth Thumbnail width (default 400)
 * @param int $thumbHeight Thumbnail height (default 400)
 * @return array ['success' => bool, 'filename' => string, 'path' => string, 'thumb_path' => string, 'message' => string]
 */
function uploadAndCompressImage($file, $uploadDir, $quality = 70, $maxWidth = 1920, $maxHeight = 1920, $createThumb = false, $thumbWidth = 400, $thumbHeight = 400)
{
    // Check file upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize limit',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE limit',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        return ['success' => false, 'filename' => '', 'path' => '', 'thumb_path' => '', 'message' => $errors[$file['error']] ?? 'Unknown upload error'];
    }

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'filename' => '', 'path' => '', 'thumb_path' => '', 'message' => 'Invalid file type. Allowed: JPG, PNG, GIF, WebP, BMP'];
    }

    // Validate file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'filename' => '', 'path' => '', 'thumb_path' => '', 'message' => 'File size exceeds 5MB limit'];
    }

    // Create upload directory if not exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $extension = 'webp'; // Always convert to WebP
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $tempPath = $uploadDir . 'temp_' . $filename;
    $finalPath = $uploadDir . $filename;

    // Move uploaded file to temp location
    if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
        return ['success' => false, 'filename' => '', 'path' => '', 'thumb_path' => '', 'message' => 'Failed to move uploaded file'];
    }

    // Compress and convert to WebP
    $result = compressAndConvertToWebP($tempPath, $finalPath, $quality, $maxWidth, $maxHeight, false);

    if (!$result['success']) {
        @unlink($tempPath);
        return ['success' => false, 'filename' => '', 'path' => '', 'thumb_path' => '', 'message' => $result['message']];
    }

    // Create thumbnail if requested
    $thumbPath = '';
    if ($createThumb) {
        $thumbDir = $uploadDir . 'thumbs/';
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        $thumbPath = $thumbDir . $filename;
        $thumbResult = compressAndConvertToWebP($finalPath, $thumbPath, $quality, $thumbWidth, $thumbHeight, false);

        if (!$thumbResult['success']) {
            // Thumbnail failed, but main image is fine
            $thumbPath = '';
        }
    }

    return [
        'success' => true,
        'filename' => $filename,
        'path' => $finalPath,
        'thumb_path' => $thumbPath,
        'size' => $result['size'] ?? 0,
        'saved_percentage' => $result['saved_percentage'] ?? 0,
        'message' => 'Image uploaded, compressed, and converted to WebP'
    ];
}
/**
 * Upload and compress image for visit photos
 * Uses existing image compression functions
 * 
 * @param array $file $_FILES array element
 * @param string $targetDir Target directory
 * @param int $quality Compression quality (70-90)
 * @param int $maxWidth Max width for compression
 * @param int $maxHeight Max height for compression
 * @return array ['success' => bool, 'filename' => string, 'error' => string]
 */
function uploadVisitPhoto($file, $targetDir, $quality = 75, $maxWidth = 1920, $maxHeight = 1920)
{
    // Check if uploadAndCompressImage exists
    if (function_exists('uploadAndCompressImage')) {
        return uploadAndCompressImage($file, $targetDir, $quality, $maxWidth, $maxHeight);
    }

    // Fallback: use uploadFile if compress function not available
    return uploadFile($file, $targetDir, ALLOWED_IMAGE_TYPES, MAX_IMAGE_SIZE);
}

/**
 * Get visits for an agent
 *
 * @param int $agentId Agent ID
 * @param string|null $status Optional status filter
 * @param int $limit Limit results
 * @return array Visits list
 */
function getAgentVisits($agentId, $status = null, $limit = 50)
{
    $db = getDB();

    // IMPORTANT:
    // $agentId = agents.id
    $params = [$agentId];

    $sql = "SELECT v.*, 
            s.shop_name AS existing_shop_name,

            a.agent_code AS agent_code,

            u.full_name AS agent_name,
            u.username AS agent_username,
            u.email AS agent_email,
            u.phone AS agent_phone,

            u2.full_name AS assigned_by_name

            FROM visits v

            LEFT JOIN shops s ON v.shop_id = s.id

            LEFT JOIN agents a ON v.agent_id = a.id
            LEFT JOIN users u ON a.user_id = u.id

            LEFT JOIN users u2 ON v.assigned_by = u2.id

            WHERE v.agent_id = ?";

    if ($status && in_array($status, ['assigned', 'completed', 'cancelled'])) {
        $sql .= " AND v.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY v.visit_date DESC, v.visit_time DESC LIMIT ?";
    $params[] = $limit;

    return $db->fetchAll($sql, $params);
}

/**
 * Get visits for admin view with filters
 * 
 * @param array $filters Filter options
 * @param int $limit Limit results
 * @return array Visits list with agent details
 */
function getFilteredVisits($filters = [], $limit = 100)
{
    $db = getDB();
    $params = [];

    $sql = "SELECT v.*, 
            s.shop_name AS existing_shop_name,
            s.shop_code AS shop_code,
            a.agent_code AS agent_code,
            u.full_name AS agent_name,
            u.username AS agent_username,
            u2.full_name AS assigned_by_name
            FROM visits v
            LEFT JOIN shops s ON v.shop_id = s.id
            LEFT JOIN agents a ON v.agent_id = a.id
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN users u2 ON v.assigned_by = u2.id
            WHERE 1=1";

    if (!empty($filters['agent_id']) && $filters['agent_id'] > 0) {
        $sql .= " AND v.agent_id = ?";
        $params[] = $filters['agent_id'];
    }

    if (!empty($filters['status']) && in_array($filters['status'], ['assigned', 'completed', 'cancelled'])) {
        $sql .= " AND v.status = ?";
        $params[] = $filters['status'];
    }

    if (!empty($filters['visit_type']) && in_array($filters['visit_type'], ['assigned', 'self', 'new_shop'])) {
        $sql .= " AND v.visit_type = ?";
        $params[] = $filters['visit_type'];
    }

    if (!empty($filters['date_from'])) {
        $sql .= " AND v.visit_date >= ?";
        $params[] = $filters['date_from'];
    }

    if (!empty($filters['date_to'])) {
        $sql .= " AND v.visit_date <= ?";
        $params[] = $filters['date_to'];
    }

    if (!empty($filters['search'])) {
        $sql .= " AND (
            v.shop_name LIKE ?
            OR v.owner_name LIKE ?
            OR v.contact_number LIKE ?
            OR s.shop_name LIKE ?
        )";

        $search = '%' . $filters['search'] . '%';
        $params = array_merge($params, [
            $search,
            $search,
            $search,
            $search
        ]);
    }

    $sql .= " ORDER BY v.created_at DESC LIMIT ?";
    $params[] = $limit;

    return $db->fetchAll($sql, $params);
}

/**
 * Get visit details by ID
 * 
 * @param int $visitId Visit ID
 * @param int|null $agentId Optional agent ID for permission check
 * @return array|null Visit details
 */
function getVisitById($visitId, $agentId = null)
{
    $db = getDB();

    $params = [$visitId];

    $sql = "SELECT v.*, 
            s.shop_name AS existing_shop_name,
            s.shop_code AS shop_code,
            a.agent_code AS agent_code,
            u.full_name AS agent_name,
            u.username AS agent_username,
            u2.full_name AS assigned_by_name
            FROM visits v
            LEFT JOIN shops s ON v.shop_id = s.id
            LEFT JOIN agents a ON v.agent_id = a.id
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN users u2 ON v.assigned_by = u2.id
            WHERE v.id = ?";

    if ($agentId !== null && $agentId > 0) {
        $sql .= " AND v.agent_id = ?";
        $params[] = $agentId;
    }

    return $db->fetchOne($sql, $params);
}

/**
 * Create a new visit (self or new shop)
 * 
 * @param int $agentId Agent  ID
 * @param array $data Visit data
 * @return array ['success' => bool, 'message' => string, 'visit_id' => int|null]
 */
function createVisit($agentId, $data)
{
    $db = getDB();

    // Extract data
    $shopId = $data['shop_id'] ?? null;
    $visitType = $data['visit_type'] ?? 'self';
    $shopName = $data['shop_name'] ?? null;
    $ownerName = $data['owner_name'] ?? null;
    $contactNumber = $data['contact_number'] ?? null;
    $address = $data['address'] ?? null;
    $purpose = $data['purpose'] ?? null;
    $remark = $data['remark'] ?? null;
    $latitude = $data['latitude'] ?? null;
    $longitude = $data['longitude'] ?? null;
    $accuracy = $data['accuracy'] ?? null;
    $photo = $data['photo'] ?? null;
    $photoThumbnail = $data['photo_thumbnail'] ?? null;

    // Validate
    if ($visitType === 'assigned' && !$shopId) {
        return ['success' => false, 'message' => 'Shop is required for assigned visit'];
    }

    if (($visitType === 'self' || $visitType === 'new_shop') && empty($shopName)) {
        return ['success' => false, 'message' => 'Shop name is required'];
    }

    if (empty($latitude) || empty($longitude)) {
        return ['success' => false, 'message' => 'Location is required for visit'];
    }

    if (empty($photo)) {
        return ['success' => false, 'message' => 'Visit photo is required'];
    }

    // Set status based on visit type
    $status = $visitType === 'assigned' ? 'assigned' : 'completed';

    $sql = "INSERT INTO visits (
                agent_id, shop_id, visit_type, shop_name, owner_name,
                contact_number, address, purpose, remark,
                visit_date, visit_time, latitude, longitude, accuracy,
                photo, photo_thumbnail, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), CURTIME(), ?, ?, ?, ?, ?, ?, NOW())";

    $db->query($sql, [
        $agentId,
        $shopId,
        $visitType,
        $shopName,
        $ownerName,
        $contactNumber,
        $address,
        $purpose,
        $remark,
        $latitude,
        $longitude,
        $accuracy,
        $photo,
        $photoThumbnail,
        $status
    ]);

    $visitId = $db->lastInsertId();

    logActivity('create', $agentId, 'visit', 'Created visit for shop: ' . ($shopName ?? 'Unknown'));

    return ['success' => true, 'message' => 'Visit created successfully', 'visit_id' => $visitId];
}

/**
 * Update visit status
 * 
 * @param int $visitId Visit ID
 * @param string $status New status
 * @param int|null $agentId Agent ID for permission check
 * @return array ['success' => bool, 'message' => string]
 */
function updateVisitStatus($visitId, $status, $agentId = null)
{
    $db = getDB();

    if (!in_array($status, ['assigned', 'completed', 'cancelled'])) {
        return ['success' => false, 'message' => 'Invalid status'];
    }

    $params = [$status, $visitId];
    $sql = "UPDATE visits SET status = ?, updated_at = NOW() WHERE id = ?";

    if ($agentId) {
        $sql .= " AND agent_id = ?";
        $params[] = $agentId;
    }

    $stmt = $db->query($sql, $params);

    // Check if any rows were affected using rowCount on the statement
    if ($stmt->rowCount() === 0) {
        return ['success' => false, 'message' => 'Visit not found or not authorized'];
    }

    logActivity('update', $agentId ?? $_SESSION['user_id'] ?? null, 'visit', 'Updated visit status to ' . $status);

    return ['success' => true, 'message' => 'Visit status updated'];
}

/**
 * Assign visit to agent
 * 
 * @param int $agentId Agent  ID
 * @param int $shopId Shop ID
 * @param string $purpose Visit purpose
 * @param int $assignedBy Admin user ID
 * @param string|null $remark Additional remark
 * @return array ['success' => bool, 'message' => string, 'visit_id' => int|null]
 */
function assignVisit($agentId, $shopId, $purpose, $assignedBy, $remark = null)
{
    $db = getDB();

    // Get shop details
    $sql = "SELECT shop_name, owner_name, phone, address FROM shops WHERE id = ? AND status = 'approved'";
    $shop = $db->fetchOne($sql, [$shopId]);

    if (!$shop) {
        return ['success' => false, 'message' => 'Shop not found'];
    }

    // Check if agent exists and is approved
    $sql = "SELECT a.id
        FROM agents a
        WHERE a.id = ?
        AND a.status = 'approved'";

    $agent = $db->fetchOne($sql, [$agentId]);

    if (!$agent) {
        return ['success' => false, 'message' => 'Agent not found or not approved'];
    }

    $sql = "INSERT INTO visits (
                agent_id, shop_id, visit_type, shop_name, owner_name,
                contact_number, address, purpose, remark,
                visit_date, visit_time, status, assigned_by, assigned_date, created_at
            ) VALUES (?, ?, 'assigned', ?, ?, ?, ?, ?, ?, CURDATE(), CURTIME(), 'assigned', ?, NOW(), NOW())";

    $db->query($sql, [
        $agentId,
        $shopId,
        $shop['shop_name'],
        $shop['owner_name'],
        $shop['phone'],
        $shop['address'],
        $purpose,
        $remark,
        $assignedBy
    ]);

    $visitId = $db->lastInsertId();

    logActivity('create', $assignedBy, 'visit', 'Assigned visit to agent ID: ' . $agentId . ' for shop: ' . $shop['shop_name']);

    return ['success' => true, 'message' => 'Visit assigned successfully', 'visit_id' => $visitId];
}
