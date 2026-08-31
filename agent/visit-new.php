<?php

/**
 * SAMRIDHI AGRO - New Visit
 *
 * Visit Types:
 * 1. Assigned Shop Visit
 *    - Shop comes from shops table
 *    - visits.visit_type = self
 *
 * 2. Self / New Shop Visit
 *    - Shop details entered manually
 *    - visits.visit_type = new_shop
 *
 * @package SamridhiAgro
 * @subpackage Agent
 * @version 4.0.0
 */

$pageTitle = 'New Visit';

require_once __DIR__ . '/../includes/agent_header.php';

requireLogin();
requireRole('agent');

$db = getDB();


// ============================================================
// GET LOGGED-IN AGENT
// ============================================================

$agentSql = "
    SELECT
        a.id AS agent_id,
        a.agent_code,
        a.user_id,
        a.status AS agent_status
    FROM agents a
    WHERE a.user_id = ?
    LIMIT 1
";

$agent = $db->fetchOne(
    $agentSql,
    [$_SESSION['user_id']]
);

if (!$agent) {

    setFlashMessage(
        'error',
        'Agent profile not found.'
    );

    redirect('agent/visits.php');
    exit;
}

$agentId = (int)$agent['agent_id'];


// ============================================================
// GET ASSIGNED SHOPS
//
// These are shops where agent_id = logged-in agent ID.
// This is NOT admin visit assignment.
// ============================================================

$shopSql = "
    SELECT
        id,
        shop_name,
        shop_code,
        address,
        city,
        state,
        owner_name,
        phone,
        email,
        latitude,
        longitude
    FROM shops
    WHERE agent_id = ?
      AND status = 'approved'
    ORDER BY shop_name ASC
";

$assignedShops = $db->fetchAll(
    $shopSql,
    [$agentId]
);

$errors = [];


// ============================================================
// HANDLE FORM SUBMISSION
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --------------------------------------------------------
    // CSRF
    // --------------------------------------------------------

    if (
        !isset($_POST[CSRF_TOKEN_NAME]) ||
        !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])
    ) {

        setFlashMessage(
            'error',
            'Invalid security token.'
        );

        redirect('agent/visit-new.php');
        exit;
    }


    // --------------------------------------------------------
    // BASIC FORM DATA
    // --------------------------------------------------------

    $visitType = sanitizeInput(
        $_POST['visit_type'] ?? 'self'
    );

    $shopId = (int)(
        $_POST['shop_id'] ?? 0
    );

    $shopName = sanitizeInput(
        $_POST['shop_name'] ?? ''
    );

    $ownerName = sanitizeInput(
        $_POST['owner_name'] ?? ''
    );

    $contactNumber = sanitizeInput(
        $_POST['contact_number'] ?? ''
    );

    $address = sanitizeInput(
        $_POST['address'] ?? ''
    );

    $purpose = sanitizeInput(
        $_POST['purpose'] ?? ''
    );

    $remark = sanitizeInput(
        $_POST['remark'] ?? ''
    );


    // --------------------------------------------------------
    // CURRENT AGENT LOCATION
    // --------------------------------------------------------

    $latitude = (
        isset($_POST['latitude']) &&
        $_POST['latitude'] !== ''
    )
        ? (float)$_POST['latitude']
        : null;

    $longitude = (
        isset($_POST['longitude']) &&
        $_POST['longitude'] !== ''
    )
        ? (float)$_POST['longitude']
        : null;

    $accuracy = (
        isset($_POST['accuracy']) &&
        $_POST['accuracy'] !== ''
    )
        ? (float)$_POST['accuracy']
        : null;


    $hasErrors = false;


    // ========================================================
    // VALIDATE VISIT TYPE
    // ========================================================

    if (
        $visitType !== 'assigned' &&
        $visitType !== 'self'
    ) {

        $visitType = 'self';
    }


    // ========================================================
    // VARIABLES FOR FINAL VISIT DATA
    // ========================================================

    $finalShopId = null;
    $finalShopName = '';
    $finalOwnerName = '';
    $finalContactNumber = '';
    $finalAddress = '';

    $selectedShop = null;


    // ========================================================
    // ASSIGNED SHOP VISIT
    // ========================================================

    if ($visitType === 'assigned') {

        // ----------------------------------------------------
        // Shop must be selected
        // ----------------------------------------------------

        if ($shopId <= 0) {

            $errors['shop'] = 'Please select an assigned shop.';
            $hasErrors = true;
        } else {

            // ------------------------------------------------
            // IMPORTANT SECURITY CHECK
            //
            // Selected shop must:
            // 1. Exist
            // 2. Belong to current agent
            // 3. Be approved
            // ------------------------------------------------

            $selectedShopSql = "
                SELECT
                    id,
                    shop_name,
                    shop_code,
                    address,
                    city,
                    state,
                    owner_name,
                    phone,
                    email,
                    latitude,
                    longitude
                FROM shops
                WHERE id = ?
                  AND agent_id = ?
                  AND status = 'approved'
                LIMIT 1
            ";

            $selectedShop = $db->fetchOne(
                $selectedShopSql,
                [
                    $shopId,
                    $agentId
                ]
            );


            if (!$selectedShop) {

                $errors['shop'] =
                    'Invalid assigned shop selected.';

                $hasErrors = true;
            } else {

                // ------------------------------------------------
                // COPY SHOP DETAILS FROM shops TABLE
                // ------------------------------------------------

                $finalShopId = (int)$selectedShop['id'];

                $finalShopName = trim(
                    $selectedShop['shop_name'] ?? ''
                );

                $finalOwnerName = trim(
                    $selectedShop['owner_name'] ?? ''
                );

                $finalContactNumber = trim(
                    $selectedShop['phone'] ?? ''
                );


                // ------------------------------------------------
                // BUILD COMPLETE ADDRESS
                // ------------------------------------------------

                $addressParts = [];

                if (
                    !empty($selectedShop['address'])
                ) {
                    $addressParts[] =
                        trim($selectedShop['address']);
                }

                if (
                    !empty($selectedShop['city'])
                ) {
                    $addressParts[] =
                        trim($selectedShop['city']);
                }

                if (
                    !empty($selectedShop['state'])
                ) {
                    $addressParts[] =
                        trim($selectedShop['state']);
                }

                $finalAddress = implode(
                    ', ',
                    $addressParts
                );


                // ------------------------------------------------
                // SHOP NAME MUST EXIST IN shops TABLE
                // ------------------------------------------------

                if ($finalShopName === '') {

                    $errors['shop'] =
                        'Selected shop does not have a shop name.';

                    $hasErrors = true;
                }
            }
        }


        // ----------------------------------------------------
        // IMPORTANT:
        //
        // Assigned Shop Visit
        // visits.visit_type = self
        // ----------------------------------------------------

        $visitTypeValue = 'self';


        // ========================================================
        // SELF / NEW SHOP VISIT
        // ========================================================

    } else {

        // ----------------------------------------------------
        // Shop Name
        // ----------------------------------------------------

        if ($shopName === '') {

            $errors['shop_name'] =
                'Shop name is required.';

            $hasErrors = true;
        }


        // ----------------------------------------------------
        // Owner Name
        // ----------------------------------------------------

        if ($ownerName === '') {

            $errors['owner_name'] =
                'Owner name is required.';

            $hasErrors = true;
        }


        // ----------------------------------------------------
        // Self / New Shop
        // No existing shop ID
        // ----------------------------------------------------

        $finalShopId = null;

        $finalShopName = $shopName;

        $finalOwnerName = $ownerName;

        $finalContactNumber = $contactNumber;

        $finalAddress = $address;


        // ----------------------------------------------------
        // Self / New Shop
        // visits.visit_type = new_shop
        // ----------------------------------------------------

        $visitTypeValue = 'new_shop';
    }


    // ========================================================
    // LOCATION VALIDATION
    // ========================================================

    if (
        $latitude === null ||
        $longitude === null
    ) {

        $errors['location'] =
            'Location is required. Please allow location access.';

        $hasErrors = true;
    }


    // ========================================================
    // PHOTO VALIDATION
    // ========================================================

    $hasPhotoData = !empty($_POST['photo_data'] ?? '');

    $hasUploadedPhoto = (
        isset($_FILES['photo']) &&
        $_FILES['photo']['error'] === UPLOAD_ERR_OK
    );

    if (
        !$hasPhotoData &&
        !$hasUploadedPhoto
    ) {

        $errors['photo'] =
            'Visit photo is required. Please take a photo using the camera.';

        $hasErrors = true;
    }


    // ========================================================
    // PHOTO PROCESSING
    // ========================================================

    $photoName = null;
    $photoThumb = null;


    if (!$hasErrors) {

        // ----------------------------------------------------
        // CAMERA BASE64 PHOTO
        // ----------------------------------------------------

        if ($hasPhotoData) {

            $photoData = $_POST['photo_data'];

            $uploadDir = __DIR__ . '/../uploads/visits/';

            if (!is_dir($uploadDir)) {

                mkdir(
                    $uploadDir,
                    0755,
                    true
                );
            }


            // Remove possible data URL prefix
            $imageData = explode(
                ',',
                $photoData,
                2
            );


            if (
                count($imageData) === 2
            ) {

                $imageContent = base64_decode(
                    $imageData[1],
                    true
                );


                if ($imageContent !== false) {

                    $fileName =
                        'visit_' .
                        time() .
                        '_' .
                        bin2hex(random_bytes(4)) .
                        '.jpg';

                    $filePath =
                        $uploadDir .
                        $fileName;


                    if (
                        file_put_contents(
                            $filePath,
                            $imageContent
                        ) !== false
                    ) {

                        $photoName = $fileName;


                        // ------------------------------------
                        // Thumbnail
                        // ------------------------------------

                        try {

                            $thumbFileName =
                                'thumb_' .
                                $fileName;

                            $thumbPath =
                                $uploadDir .
                                $thumbFileName;


                            if (
                                function_exists(
                                    'createThumbnail'
                                )
                            ) {

                                $thumbCreated =
                                    createThumbnail(
                                        $filePath,
                                        $thumbPath,
                                        300,
                                        300,
                                        false
                                    );

                                if ($thumbCreated) {

                                    $photoThumb =
                                        $thumbFileName;
                                }
                            }
                        } catch (Throwable $e) {

                            // Main photo is already saved.
                            // Thumbnail failure should not
                            // stop the visit.
                            $photoThumb = null;
                        }
                    } else {

                        $errors['photo'] =
                            'Failed to save photo.';

                        $hasErrors = true;
                    }
                } else {

                    $errors['photo'] =
                        'Invalid photo data.';

                    $hasErrors = true;
                }
            } else {

                $errors['photo'] =
                    'Invalid camera image data.';

                $hasErrors = true;
            }


            // ----------------------------------------------------
            // NORMAL FILE UPLOAD
            // ----------------------------------------------------

        } elseif ($hasUploadedPhoto) {

            $uploadDir =
                '../uploads/visits/';

            if (!is_dir($uploadDir)) {

                mkdir(
                    $uploadDir,
                    0755,
                    true
                );
            }


            if (
                function_exists(
                    'uploadVisitPhoto'
                )
            ) {

                $uploadResult =
                    uploadVisitPhoto(
                        $_FILES['photo'],
                        $uploadDir,
                        70,
                        1920,
                        1920
                    );


                if (
                    isset($uploadResult['success']) &&
                    $uploadResult['success']
                ) {

                    $photoName =
                        $uploadResult['filename']
                        ?? null;

                    $photoThumb =
                        $uploadResult['thumbnail']
                        ?? $uploadResult['thumb_path']
                        ?? null;
                } else {

                    $errors['photo'] =
                        $uploadResult['error']
                        ?? $uploadResult['message']
                        ?? 'Failed to upload photo.';

                    $hasErrors = true;
                }
            } else {

                $errors['photo'] =
                    'Photo upload function is not available.';

                $hasErrors = true;
            }
        }
    }


    // ========================================================
    // CREATE VISIT
    // ========================================================

    if (!$hasErrors) {

        $visitData = [

            // Assigned = self
            // Self/New = new_shop
            'visit_type' =>
            $visitTypeValue,

            // Existing shop ID for assigned
            // NULL for new shop
            'shop_id' =>
            $finalShopId,

            // Shop details
            'shop_name' =>
            $finalShopName,

            'owner_name' =>
            $finalOwnerName,

            'contact_number' =>
            $finalContactNumber,

            'address' =>
            $finalAddress,

            // Visit information
            'purpose' =>
            $purpose,

            'remark' =>
            $remark,

            // Agent's CURRENT GPS location
            'latitude' =>
            $latitude,

            'longitude' =>
            $longitude,

            'accuracy' =>
            $accuracy,

            // Visit photo
            'photo' =>
            $photoName,

            'photo_thumbnail' =>
            $photoThumb
        ];


        // ----------------------------------------------------
        // CREATE VISIT
        // ----------------------------------------------------

        $result = createVisit(
            $agentId,
            $visitData
        );


        if (
            isset($result['success']) &&
            $result['success']
        ) {

            // ------------------------------------------------
            // ACTIVITY LOG
            // ------------------------------------------------
            // activity_logs.user_id = users.id
            // Session user_id = users.id
            //
            // Therefore session user_id is correct here.
            // ------------------------------------------------

            logActivity(
                'create',
                $_SESSION['user_id'],
                'visit',
                'Created visit #' .
                    $result['visit_id'] .
                    ' for shop: ' .
                    ($finalShopName ?: 'Unknown Shop')
            );


            // ------------------------------------------------
            // SUCCESS MESSAGE
            // ------------------------------------------------

            setFlashMessage(
                'success',
                'Visit recorded successfully!'
            );


            // ------------------------------------------------
            // REDIRECT TO VISIT VIEW
            // ------------------------------------------------

            redirect(
                'agent/visit-view.php?id=' .
                    $result['visit_id']
            );

            exit;
        } else {

            setFlashMessage(
                'error',
                $result['message']
                    ?? 'Failed to create visit.'
            );
        }
    }
}


// ============================================================
// CSRF
// ============================================================

$csrfToken = generateCsrfToken();

?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<style>
    /* ============================================================
   GENERAL
============================================================ */

    .form-group {
        margin-bottom: 18px;
    }

    .form-label {
        display: block;
        font-weight: 600;
        font-size: 14px;
        color: #14532D;
        margin-bottom: 6px;
    }

    .form-input {
        width: 100%;
        padding: 11px 14px;
        border: 2px solid #E5EDE7;
        border-radius: 9px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        transition: all .25s ease;
        background: #fff;
        box-sizing: border-box;
    }

    .form-input:focus {
        outline: none;
        border-color: #16A34A;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, .10);
    }

    .form-input.error {
        border-color: #DC2626;
        background: #FFF7F7;
    }

    .form-error {
        color: #DC2626;
        font-size: 13px;
        margin-top: 5px;
    }

    .form-hint {
        font-size: 12px;
        color: #6B7A7B;
        margin-top: 5px;
    }


    /* ============================================================
   LOCATION STATUS
============================================================ */

    .location-status {
        padding: 11px 14px;
        border-radius: 9px;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .location-status.success {
        background: #DCFCE7;
        color: #065F46;
        border: 1px solid #BBF7D0;
    }

    .location-status.error {
        background: #FEE2E2;
        color: #991B1B;
        border: 1px solid #FECACA;
    }

    .location-status.warning {
        background: #FEF3C7;
        color: #92400E;
        border: 1px solid #FDE68A;
    }


    /* ============================================================
   VISIT TYPE
============================================================ */

    .visit-type-box {
        background: #F7FCF7;
        border: 1px solid #DCFCE7;
        border-radius: 12px;
        padding: 15px;
    }


    /* ============================================================
   ASSIGNED SHOP
============================================================ */

    .shop-details-card {
        background: linear-gradient(135deg,
                #F7FCF7,
                #FFFFFF);

        border-radius: 13px;

        padding: 17px;

        border: 1px solid #DCEBDD;

        margin-top: 12px;

        display: none;

        animation: slideDown .25s ease;
    }

    .shop-details-card.visible {
        display: block;
    }

    @keyframes slideDown {

        from {
            opacity: 0;
            transform: translateY(-8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .shop-name-large {
        font-family: 'Space Grotesk',
            'Inter',
            sans-serif;

        font-size: 19px;

        font-weight: 700;

        color: #052E16;

        margin-bottom: 8px;
    }

    .shop-badge {
        display: inline-flex;

        align-items: center;

        padding: 4px 10px;

        border-radius: 20px;

        font-size: 11px;

        font-weight: 600;

        background: #DCFCE7;

        color: #065F46;
    }

    .shop-info-grid {

        display: grid;

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

        gap: 5px 18px;

        font-size: 13px;

        color: #374151;
    }

    .info-item {

        display: flex;

        align-items: flex-start;

        gap: 7px;

        padding: 5px 0;

    }

    .info-item i {

        width: 17px;

        color: #16A34A;

        margin-top: 2px;
    }

    .info-item .label {

        color: #6B7A7B;

        font-weight: 600;

        margin-right: 3px;
    }


    /* ============================================================
   SHOP LOCATION
============================================================ */

    .shop-location-row {

        margin-top: 10px;

        padding-top: 11px;

        border-top: 1px solid #E5EDE7;

        display: flex;

        flex-wrap: wrap;

        align-items: center;

        gap: 8px;

        font-size: 13px;
    }

    .shop-location-row .coords {

        font-family: monospace;

        font-weight: 700;

        color: #14532D;

        background: #F0FDF4;

        padding: 3px 7px;

        border-radius: 5px;
    }

    .btn-map {

        padding: 6px 12px;

        border-radius: 7px;

        background: #DCFCE7;

        color: #15803D;

        text-decoration: none;

        font-size: 12px;

        font-weight: 600;

        display: inline-flex;

        align-items: center;

        gap: 5px;

        transition: .2s ease;
    }

    .btn-map:hover {

        background: #BBF7D0;

        color: #166534;
    }


    /* ============================================================
   EMBEDDED MAP
============================================================ */

    .shop-map-container {

        display: none;

        margin-top: 13px;
    }

    .shop-map-frame {

        border-radius: 11px;

        overflow: hidden;

        border: 1px solid #DDE7DF;

        background: #fff;

        box-shadow:
            0 3px 10px rgba(0, 0, 0, .04);
    }

    .shop-map-frame iframe {

        width: 100%;

        height: 250px;

        border: 0;

        display: block;
    }


    /* ============================================================
   CAMERA
============================================================ */

    .camera-area {

        border: 2px dashed #BBF7D0;

        background: #F7FCF7;

        border-radius: 12px;

        padding: 20px;

        text-align: center;
    }

    .camera-btn {

        padding: 11px 20px;

        background: #14532D;

        color: #fff;

        border: none;

        border-radius: 9px;

        font-size: 14px;

        font-weight: 600;

        cursor: pointer;

        display: inline-flex;

        align-items: center;

        justify-content: center;

        gap: 8px;

        transition: .25s ease;
    }

    .camera-btn:hover {

        background: #052E16;

        transform: translateY(-1px);
    }

    .camera-btn:disabled {

        opacity: .6;

        cursor: not-allowed;

        transform: none;
    }

    .photo-preview {

        width: 100%;

        max-width: 320px;

        margin: 12px auto 0;

        border-radius: 11px;

        overflow: hidden;

        border: 2px solid #E5EDE7;

        position: relative;

        background: #fff;
    }

    .photo-preview img {

        width: 100%;

        height: auto;

        display: block;
    }

    .btn-retake {

        position: absolute;

        top: 8px;

        right: 8px;

        background: rgba(0, 0, 0, .75);

        color: #fff;

        border: none;

        border-radius: 6px;

        padding: 5px 10px;

        cursor: pointer;

        font-size: 12px;
    }


    /* ============================================================
   SUBMIT
============================================================ */

    .btn-submit {

        width: 100%;

        padding: 14px;

        background:
            linear-gradient(135deg,
                #14532D,
                #16A34A);

        color: #fff;

        border: none;

        border-radius: 10px;

        font-size: 16px;

        font-weight: 600;

        cursor: pointer;

        transition: .25s ease;
    }

    .btn-submit:hover:not(:disabled) {

        transform: translateY(-2px);

        box-shadow:
            0 8px 24px rgba(22, 163, 74, .25);
    }

    .btn-submit:disabled {

        opacity: .6;

        cursor: not-allowed;
    }

    .spinner {

        display: none;

        width: 19px;

        height: 19px;

        border: 3px solid rgba(255, 255, 255, .3);

        border-top-color: #fff;

        border-radius: 50%;

        animation: spin .8s linear infinite;

        margin: 0 auto;
    }

    @keyframes spin {

        to {
            transform: rotate(360deg);
        }
    }


    /* ============================================================
   MOBILE
============================================================ */

    @media (max-width: 600px) {

        .shop-info-grid {
            grid-template-columns: 1fr;
        }

        .shop-location-row {
            align-items: flex-start;
            flex-direction: column;
        }

        .camera-btn {
            width: 100%;
        }

        .shop-map-frame iframe {
            height: 220px;
        }
    }
</style>


<div class="content-card">

    <!-- ======================================================
         HEADER
    ======================================================= -->

    <div class="card-header">

        <h3 class="card-title">

            <i
                class="fas fa-plus-circle"
                style="color:#16A34A;"></i>

            New Visit

        </h3>

        <a
            href="visits.php"
            class="card-action">

            <i class="fas fa-arrow-left"></i>

            Back to Visits

        </a>

    </div>


    <!-- ======================================================
         ERRORS
    ======================================================= -->

    <?php if (!empty($errors)): ?>

        <div
            style="
                background:#FEE2E2;
                border:1px solid #FECACA;
                border-radius:9px;
                padding:12px 16px;
                margin-bottom:18px;
            ">

            <p
                style="
                    color:#991B1B;
                    font-weight:600;
                    margin:0 0 5px;
                ">

                <i class="fas fa-exclamation-circle"></i>

                Please fix the following errors:

            </p>

            <ul
                style="
                    margin:0;
                    padding-left:20px;
                    color:#991B1B;
                ">

                <?php foreach ($errors as $error): ?>

                    <li>
                        <?php echo escapeHtml($error); ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>


    <!-- ======================================================
         FORM
    ======================================================= -->

    <form
        method="POST"
        action=""
        id="visitForm"
        enctype="multipart/form-data">

        <input
            type="hidden"
            name="<?php echo CSRF_TOKEN_NAME; ?>"
            value="<?php echo $csrfToken; ?>">

        <!-- CURRENT LOCATION -->
        <input
            type="hidden"
            name="latitude"
            id="latitude"
            value="">

        <input
            type="hidden"
            name="longitude"
            id="longitude"
            value="">

        <input
            type="hidden"
            name="accuracy"
            id="accuracy"
            value="">

        <!-- CAMERA BASE64 -->
        <input
            type="hidden"
            name="photo_data"
            id="photoData"
            value="">


        <!-- ==================================================
             VISIT TYPE
        =================================================== -->

        <div class="form-group">

            <label class="form-label">

                Visit Type

                <span style="color:#DC2626;">
                    *
                </span>

            </label>

            <div class="visit-type-box">

                <select
                    name="visit_type"
                    id="visit_type"
                    class="form-input"
                    onchange="toggleVisitType()">

                    <?php if (!empty($assignedShops)): ?>

                        <option
                            value="assigned"
                            <?php
                            echo (
                                ($_POST['visit_type'] ?? 'assigned')
                                === 'assigned'
                            )
                                ? 'selected'
                                : '';
                            ?>>
                            📌 Assigned Shop Visit
                        </option>

                    <?php endif; ?>


                    <option
                        value="self"
                        <?php
                        echo (
                            ($_POST['visit_type'] ?? '')
                            === 'self'
                        )
                            ? 'selected'
                            : (
                                empty($assignedShops)
                                ? 'selected'
                                : ''
                            );
                        ?>>
                        🏪 Self / New Shop Visit
                    </option>

                </select>


                <div class="form-hint">

                    <i class="fas fa-info-circle"></i>

                    <?php if (!empty($assignedShops)): ?>

                        Assigned Shop Visit =
                        shops assigned to this agent.

                        <br>

                        Self / New Shop Visit =
                        manually entered new shop.

                    <?php else: ?>

                        No approved shop is currently assigned
                        to this agent.

                    <?php endif; ?>

                </div>

            </div>

        </div>


        <!-- ==================================================
             ASSIGNED SHOP
        =================================================== -->

        <div
            id="assigned_shop_section"
            style="display:none;">

            <div class="form-group">

                <label class="form-label">

                    Select Assigned Shop

                    <span style="color:#DC2626;">
                        *
                    </span>

                </label>


                <select
                    name="shop_id"
                    id="assigned_shop_select"
                    class="form-input <?php echo isset($errors['shop']) ? 'error' : ''; ?>"
                    onchange="updateAssignedShopDetails()">

                    <option value="">
                        — Select a shop —
                    </option>


                    <?php foreach ($assignedShops as $shop): ?>

                        <option
                            value="<?php echo (int)$shop['id']; ?>"

                            data-shop-name="<?php echo escapeHtml($shop['shop_name'] ?? ''); ?>"

                            data-shop-code="<?php echo escapeHtml($shop['shop_code'] ?? ''); ?>"

                            data-owner-name="<?php echo escapeHtml($shop['owner_name'] ?? ''); ?>"

                            data-phone="<?php echo escapeHtml($shop['phone'] ?? ''); ?>"

                            data-email="<?php echo escapeHtml($shop['email'] ?? ''); ?>"

                            data-address="<?php echo escapeHtml($shop['address'] ?? ''); ?>"

                            data-city="<?php echo escapeHtml($shop['city'] ?? ''); ?>"

                            data-state="<?php echo escapeHtml($shop['state'] ?? ''); ?>"

                            data-latitude="<?php echo htmlspecialchars((string)($shop['latitude'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"

                            data-longitude="<?php echo htmlspecialchars((string)($shop['longitude'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                            <?php
                            echo escapeHtml(
                                $shop['shop_name'] ?? 'Unnamed Shop'
                            );
                            ?>

                            <?php if (!empty($shop['shop_code'])): ?>

                                -
                                <?php
                                echo escapeHtml(
                                    $shop['shop_code']
                                );
                                ?>

                            <?php endif; ?>

                        </option>

                    <?php endforeach; ?>

                </select>


                <?php if (isset($errors['shop'])): ?>

                    <div class="form-error">

                        <?php
                        echo escapeHtml(
                            $errors['shop']
                        );
                        ?>

                    </div>

                <?php endif; ?>

            </div>


            <!-- ==================================================
                 SHOP DETAILS CARD
            =================================================== -->

            <div
                class="shop-details-card"
                id="assigned_shop_details">

                <div
                    class="shop-name-large"
                    id="assigned_shop_name">
                    —
                </div>


                <div
                    style="
                        margin-bottom:10px;
                    ">

                    <span
                        class="shop-badge"
                        id="assigned_shop_code">
                        —
                    </span>

                </div>


                <div class="shop-info-grid">

                    <div class="info-item">

                        <i class="fas fa-user"></i>

                        <span class="label">
                            Owner:
                        </span>

                        <span id="assigned_shop_owner">
                            —
                        </span>

                    </div>


                    <div class="info-item">

                        <i class="fas fa-phone"></i>

                        <span class="label">
                            Phone:
                        </span>

                        <span id="assigned_shop_phone">
                            —
                        </span>

                    </div>


                    <div class="info-item">

                        <i class="fas fa-envelope"></i>

                        <span class="label">
                            Email:
                        </span>

                        <span id="assigned_shop_email">
                            —
                        </span>

                    </div>


                    <div class="info-item">

                        <i class="fas fa-map-marker-alt"></i>

                        <span class="label">
                            Address:
                        </span>

                        <span id="assigned_shop_address">
                            —
                        </span>

                    </div>

                </div>


                <!-- SHOP LOCATION -->

                <div class="shop-location-row">

                    <span>

                        <i
                            class="fas fa-map-pin"
                            style="color:#16A34A;"></i>

                        Location:

                    </span>


                    <span
                        class="coords"
                        id="assigned_shop_coords">
                        —
                    </span>


                    <span
                        id="assigned_shop_location_status"></span>


                    <a
                        href="#"
                        id="assigned_shop_map_link"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn-map"
                        style="display:none;">

                        <i class="fas fa-map"></i>

                        Open Map

                    </a>

                </div>


                <!-- EMBEDDED MAP -->

                <div
                    id="assigned_shop_map_container"
                    class="shop-map-container">

                    <div class="shop-map-frame">

                        <iframe
                            id="assigned_shop_map"
                            src=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            title="Assigned Shop Location"></iframe>

                    </div>


                    <div
                        style="
                            font-size:12px;
                            color:#6B7A7B;
                            margin-top:6px;
                        ">

                        <i class="fas fa-info-circle"></i>

                        This map shows the saved location
                        of the selected shop.

                    </div>

                </div>

            </div>

        </div>


        <!-- ==================================================
             SELF / NEW SHOP
        =================================================== -->

        <div
            id="shop_details_section">

            <div class="form-group">

                <label class="form-label">

                    Shop Name

                    <span style="color:#DC2626;">
                        *
                    </span>

                </label>

                <input
                    type="text"
                    name="shop_name"
                    class="form-input <?php echo isset($errors['shop_name']) ? 'error' : ''; ?>"
                    value="<?php echo escapeHtml($_POST['shop_name'] ?? ''); ?>"
                    placeholder="Enter shop name">

                <?php if (isset($errors['shop_name'])): ?>

                    <div class="form-error">

                        <?php
                        echo escapeHtml(
                            $errors['shop_name']
                        );
                        ?>

                    </div>

                <?php endif; ?>

            </div>


            <div class="form-group">

                <label class="form-label">

                    Owner Name

                    <span style="color:#DC2626;">
                        *
                    </span>

                </label>

                <input
                    type="text"
                    name="owner_name"
                    class="form-input <?php echo isset($errors['owner_name']) ? 'error' : ''; ?>"
                    value="<?php echo escapeHtml($_POST['owner_name'] ?? ''); ?>"
                    placeholder="Enter owner name">

                <?php if (isset($errors['owner_name'])): ?>

                    <div class="form-error">

                        <?php
                        echo escapeHtml(
                            $errors['owner_name']
                        );
                        ?>

                    </div>

                <?php endif; ?>

            </div>


            <div class="form-group">

                <label class="form-label">
                    Contact Number
                </label>

                <input
                    type="tel"
                    name="contact_number"
                    class="form-input"
                    value="<?php echo escapeHtml($_POST['contact_number'] ?? ''); ?>"
                    placeholder="Enter phone number">

            </div>


            <div class="form-group">

                <label class="form-label">
                    Address
                </label>

                <textarea
                    name="address"
                    class="form-input"
                    rows="2"
                    placeholder="Enter shop address"><?php echo escapeHtml($_POST['address'] ?? ''); ?></textarea>

            </div>

        </div>


        <!-- ==================================================
             PURPOSE
        =================================================== -->

        <div class="form-group">

            <label class="form-label">
                Purpose
            </label>

            <input
                type="text"
                name="purpose"
                class="form-input"
                value="<?php echo escapeHtml($_POST['purpose'] ?? ''); ?>"
                placeholder="e.g. Product demo, Collection">

        </div>


        <!-- ==================================================
             REMARK
        =================================================== -->

        <div class="form-group">

            <label class="form-label">
                Remark
            </label>

            <textarea
                name="remark"
                class="form-input"
                rows="2"
                placeholder="Any additional remarks"><?php echo escapeHtml($_POST['remark'] ?? ''); ?></textarea>

        </div>


        <!-- ==================================================
             CURRENT LOCATION
        =================================================== -->

        <div class="form-group">

            <label class="form-label">

                <i
                    class="fas fa-map-marker-alt"
                    style="color:#16A34A;"></i>

                Current Visit Location

                <span style="color:#DC2626;">
                    *
                </span>

            </label>


            <div
                id="locationStatus"
                class="location-status warning">

                <i class="fas fa-spinner fa-spin"></i>

                Detecting location...

            </div>


            <div
                id="locationDetails"
                style="
                    font-size:13px;
                    color:#6B7A7B;
                    margin-top:6px;
                "></div>


            <button
                type="button"
                class="camera-btn"
                id="getLocationBtn"
                style="
                    margin-top:8px;
                    background:#2563EB;
                ">

                <i class="fas fa-sync"></i>

                Refresh Location

            </button>


            <?php if (isset($errors['location'])): ?>

                <div class="form-error">

                    <?php
                    echo escapeHtml(
                        $errors['location']
                    );
                    ?>

                </div>

            <?php endif; ?>

        </div>


        <!-- ==================================================
             CAMERA
        =================================================== -->

        <div class="form-group">

            <label class="form-label">

                <i
                    class="fas fa-camera"
                    style="color:#16A34A;"></i>

                Visit Photo

                <span style="color:#DC2626;">
                    *
                </span>

            </label>


            <div class="camera-area">

                <input
                    type="file"
                    name="photo"
                    id="photoInput"
                    accept="image/*"
                    capture="environment"
                    style="display:none;">


                <button
                    type="button"
                    class="camera-btn"
                    id="cameraBtn">

                    <i class="fas fa-camera"></i>

                    Take Photo

                </button>


                <div class="form-hint">

                    <i class="fas fa-info-circle"></i>

                    Shop/person ki photo lena mandatory hai.

                </div>


                <div
                    id="photoPreview"
                    class="photo-preview"
                    style="display:none;">

                    <img
                        id="previewImage"
                        src=""
                        alt="Visit Photo Preview">


                    <button
                        type="button"
                        class="btn-retake"
                        id="retakeBtn">

                        ✕ Retake

                    </button>

                </div>


                <?php if (isset($errors['photo'])): ?>

                    <div class="form-error">

                        <?php
                        echo escapeHtml(
                            $errors['photo']
                        );
                        ?>

                    </div>

                <?php endif; ?>

            </div>

        </div>


        <!-- ==================================================
             SUBMIT
        =================================================== -->

        <button
            type="submit"
            class="btn-submit"
            id="submitBtn"
            disabled>

            <span id="submitText">

                <i class="fas fa-check"></i>

                Submit Visit

            </span>


            <span
                class="spinner"
                id="submitSpinner"></span>

        </button>

    </form>

</div>


<script>
    document.addEventListener(
        'DOMContentLoaded',
        function() {

            // ====================================================
            // ELEMENTS
            // ====================================================

            const visitType =
                document.getElementById('visit_type');

            const assignedSection =
                document.getElementById(
                    'assigned_shop_section'
                );

            const shopDetailsSection =
                document.getElementById(
                    'shop_details_section'
                );

            const assignedShopSelect =
                document.getElementById(
                    'assigned_shop_select'
                );

            const assignedShopDetails =
                document.getElementById(
                    'assigned_shop_details'
                );

            const getLocationBtn =
                document.getElementById(
                    'getLocationBtn'
                );

            const locationStatus =
                document.getElementById(
                    'locationStatus'
                );

            const locationDetails =
                document.getElementById(
                    'locationDetails'
                );

            const cameraBtn =
                document.getElementById(
                    'cameraBtn'
                );

            const photoInput =
                document.getElementById(
                    'photoInput'
                );

            const photoPreview =
                document.getElementById(
                    'photoPreview'
                );

            const previewImage =
                document.getElementById(
                    'previewImage'
                );

            const retakeBtn =
                document.getElementById(
                    'retakeBtn'
                );

            const photoDataInput =
                document.getElementById(
                    'photoData'
                );

            const submitBtn =
                document.getElementById(
                    'submitBtn'
                );

            const submitText =
                document.getElementById(
                    'submitText'
                );

            const submitSpinner =
                document.getElementById(
                    'submitSpinner'
                );

            const visitForm =
                document.getElementById(
                    'visitForm'
                );


            // ====================================================
            // TOGGLE VISIT TYPE
            // ====================================================

            window.toggleVisitType = function() {

                const type =
                    visitType.value;


                if (type === 'assigned') {

                    assignedSection.style.display =
                        'block';

                    shopDetailsSection.style.display =
                        'none';


                    // Clear manual shop details

                    const shopName =
                        document.querySelector(
                            'input[name="shop_name"]'
                        );

                    const ownerName =
                        document.querySelector(
                            'input[name="owner_name"]'
                        );

                    const contact =
                        document.querySelector(
                            'input[name="contact_number"]'
                        );

                    const address =
                        document.querySelector(
                            'textarea[name="address"]'
                        );


                    if (shopName) {
                        shopName.value = '';
                    }

                    if (ownerName) {
                        ownerName.value = '';
                    }

                    if (contact) {
                        contact.value = '';
                    }

                    if (address) {
                        address.value = '';
                    }


                    // Restore selected shop details

                    updateAssignedShopDetails();

                } else {

                    assignedSection.style.display =
                        'none';

                    shopDetailsSection.style.display =
                        'block';


                    assignedShopDetails.classList.remove(
                        'visible'
                    );

                    hideShopMap();
                }


                checkFormComplete();
            };


            // ====================================================
            // HIDE SHOP MAP
            // ====================================================

            function hideShopMap() {

                const mapContainer =
                    document.getElementById(
                        'assigned_shop_map_container'
                    );

                const map =
                    document.getElementById(
                        'assigned_shop_map'
                    );

                const mapLink =
                    document.getElementById(
                        'assigned_shop_map_link'
                    );


                if (mapContainer) {

                    mapContainer.style.display =
                        'none';
                }

                if (map) {

                    map.src = '';
                }

                if (mapLink) {

                    mapLink.style.display =
                        'none';
                }
            }


            // ====================================================
            // ASSIGNED SHOP DETAILS
            // ====================================================

            window.updateAssignedShopDetails =
                function() {

                    if (!assignedShopSelect) {
                        return;
                    }


                    const selected =
                        assignedShopSelect.options[
                            assignedShopSelect.selectedIndex
                        ];


                    const mapContainer =
                        document.getElementById(
                            'assigned_shop_map_container'
                        );

                    const map =
                        document.getElementById(
                            'assigned_shop_map'
                        );

                    const mapLink =
                        document.getElementById(
                            'assigned_shop_map_link'
                        );

                    const coordsSpan =
                        document.getElementById(
                            'assigned_shop_coords'
                        );

                    const locationStatusEl =
                        document.getElementById(
                            'assigned_shop_location_status'
                        );


                    // ------------------------------------------------
                    // Nothing selected
                    // ------------------------------------------------

                    if (
                        !selected ||
                        !selected.value
                    ) {

                        assignedShopDetails.classList.remove(
                            'visible'
                        );

                        hideShopMap();

                        coordsSpan.textContent =
                            '—';

                        locationStatusEl.innerHTML =
                            '';

                        return;
                    }


                    // ------------------------------------------------
                    // SHOP DATA
                    // ------------------------------------------------

                    const shopName =
                        selected.dataset.shopName ||
                        '';

                    const shopCode =
                        selected.dataset.shopCode ||
                        '';

                    const ownerName =
                        selected.dataset.ownerName ||
                        '';

                    const phone =
                        selected.dataset.phone ||
                        '';

                    const email =
                        selected.dataset.email ||
                        '';

                    const address =
                        selected.dataset.address ||
                        '';

                    const city =
                        selected.dataset.city ||
                        '';

                    const state =
                        selected.dataset.state ||
                        '';

                    const lat =
                        selected.dataset.latitude ||
                        '';

                    const lng =
                        selected.dataset.longitude ||
                        '';


                    // ------------------------------------------------
                    // DISPLAY SHOP DETAILS
                    // ------------------------------------------------

                    document.getElementById(
                            'assigned_shop_name'
                        ).textContent =
                        shopName || '—';


                    document.getElementById(
                            'assigned_shop_code'
                        ).textContent =
                        shopCode ?
                        'Code: ' + shopCode :
                        'Assigned Shop';


                    document.getElementById(
                            'assigned_shop_owner'
                        ).textContent =
                        ownerName || '—';


                    document.getElementById(
                            'assigned_shop_phone'
                        ).textContent =
                        phone || '—';


                    document.getElementById(
                            'assigned_shop_email'
                        ).textContent =
                        email || '—';


                    let fullAddress =
                        address || '';


                    if (city) {

                        fullAddress +=
                            (
                                fullAddress ?
                                ', ' :
                                ''
                            ) +
                            city;
                    }


                    if (state) {

                        fullAddress +=
                            (
                                fullAddress ?
                                ', ' :
                                ''
                            ) +
                            state;
                    }


                    document.getElementById(
                            'assigned_shop_address'
                        ).textContent =
                        fullAddress || '—';


                    // ------------------------------------------------
                    // SHOP LOCATION
                    // ------------------------------------------------

                    const shopLat =
                        parseFloat(lat);

                    const shopLng =
                        parseFloat(lng);


                    if (
                        Number.isFinite(shopLat) &&
                        Number.isFinite(shopLng)
                    ) {

                        const latText =
                            shopLat.toFixed(6);

                        const lngText =
                            shopLng.toFixed(6);


                        coordsSpan.textContent =
                            latText +
                            ', ' +
                            lngText;


                        locationStatusEl.innerHTML =
                            '<span style="color:#16A34A;">' +
                            '<i class="fas fa-check-circle"></i> ' +
                            'Location set' +
                            '</span>';


                        // --------------------------------------------
                        // GOOGLE MAP EXTERNAL
                        // --------------------------------------------

                        const googleUrl =
                            'https://www.google.com/maps?q=' +
                            encodeURIComponent(
                                shopLat +
                                ',' +
                                shopLng
                            );


                        mapLink.href =
                            googleUrl;

                        mapLink.style.display =
                            'inline-flex';


                        // --------------------------------------------
                        // GOOGLE EMBED
                        // --------------------------------------------

                        map.src =
                            'https://www.google.com/maps?q=' +
                            encodeURIComponent(
                                shopLat +
                                ',' +
                                shopLng
                            ) +
                            '&z=17&output=embed';


                        mapContainer.style.display =
                            'block';


                    } else {

                        coordsSpan.textContent =
                            'Location not available';


                        locationStatusEl.innerHTML =
                            '<span style="color:#6B7A7B;">' +
                            '<i class="fas fa-info-circle"></i> ' +
                            'No saved shop location' +
                            '</span>';


                        mapLink.style.display =
                            'none';


                        mapContainer.style.display =
                            'none';

                        map.src = '';
                    }


                    assignedShopDetails.classList.add(
                        'visible'
                    );
                };


            // ====================================================
            // LOCATION
            // ====================================================

            function getLocation() {

                if (!navigator.geolocation) {

                    locationStatus.className =
                        'location-status error';

                    locationStatus.innerHTML =
                        '<i class="fas fa-exclamation-circle"></i> ' +
                        'Your browser does not support GPS location.';

                    checkFormComplete();

                    return;
                }


                locationStatus.className =
                    'location-status warning';


                locationStatus.innerHTML =
                    '<i class="fas fa-spinner fa-spin"></i> ' +
                    'Detecting location...';


                locationDetails.innerHTML =
                    '';


                getLocationBtn.disabled =
                    true;


                navigator.geolocation.getCurrentPosition(

                    function(position) {

                        const lat =
                            position.coords.latitude;

                        const lng =
                            position.coords.longitude;

                        const acc =
                            position.coords.accuracy || 0;


                        document.getElementById(
                                'latitude'
                            ).value =
                            lat;


                        document.getElementById(
                                'longitude'
                            ).value =
                            lng;


                        document.getElementById(
                                'accuracy'
                            ).value =
                            acc;


                        locationStatus.className =
                            'location-status success';


                        locationStatus.innerHTML =
                            '<i class="fas fa-check-circle"></i> ' +
                            'Location captured successfully!';


                        locationDetails.innerHTML =
                            'Lat: ' +
                            lat.toFixed(6) +
                            ' | Lng: ' +
                            lng.toFixed(6) +
                            (
                                acc ?
                                ' | ± ' +
                                acc.toFixed(0) +
                                'm' :
                                ''
                            );


                        getLocationBtn.disabled =
                            false;


                        // --------------------------------------------
                        // Reverse Geocode
                        // --------------------------------------------

                        const reverseUrl =
                            'https://nominatim.openstreetmap.org/reverse' +
                            '?format=json' +
                            '&lat=' +
                            encodeURIComponent(lat) +
                            '&lon=' +
                            encodeURIComponent(lng) +
                            '&zoom=18';


                        fetch(reverseUrl)

                            .then(function(response) {

                                return response.json();

                            })

                            .then(function(data) {

                                if (
                                    data &&
                                    data.display_name
                                ) {

                                    locationDetails.innerHTML =
                                        '📍 ' +
                                        data.display_name +
                                        '<br>' +
                                        'Lat: ' +
                                        lat.toFixed(6) +
                                        ' | Lng: ' +
                                        lng.toFixed(6) +
                                        (
                                            acc ?
                                            ' | ± ' +
                                            acc.toFixed(0) +
                                            'm' :
                                            ''
                                        );
                                }

                            })

                            .catch(function() {

                                // Reverse geocoding is optional.

                            });


                        checkFormComplete();

                    },


                    function(error) {

                        let message =
                            'Location access denied. Please enable location in browser settings.';


                        if (
                            error.code ===
                            error.TIMEOUT
                        ) {

                            message =
                                'Location request timed out. Please try again.';

                        } else if (
                            error.code ===
                            error.POSITION_UNAVAILABLE
                        ) {

                            message =
                                'GPS signal unavailable. Please move to an open area.';

                        } else if (
                            error.code ===
                            error.PERMISSION_DENIED
                        ) {

                            message =
                                'Location permission denied. Please allow location for this site and try again.';
                        }


                        locationStatus.className =
                            'location-status error';


                        locationStatus.innerHTML =
                            '<i class="fas fa-exclamation-circle"></i> ' +
                            message;


                        getLocationBtn.disabled =
                            false;


                        checkFormComplete();
                    },


                    {
                        enableHighAccuracy: true,

                        timeout: 15000,

                        maximumAge: 0
                    }
                );
            }


            getLocationBtn.addEventListener(
                'click',
                getLocation
            );


            // ====================================================
            // CAMERA
            // ====================================================

            cameraBtn.addEventListener(
                'click',
                function() {

                    photoInput.click();

                }
            );


            photoInput.addEventListener(
                'change',
                function(e) {

                    const file =
                        e.target.files &&
                        e.target.files[0];


                    if (!file) {
                        return;
                    }


                    if (
                        !file.type.startsWith(
                            'image/'
                        )
                    ) {

                        Swal.fire({

                            icon: 'error',

                            title: 'Invalid Photo',

                            text: 'Please capture a valid image using the camera.'
                        });


                        photoInput.value =
                            '';

                        photoPreview.style.display =
                            'none';

                        photoDataInput.value =
                            '';

                        checkFormComplete();

                        return;
                    }


                    const reader =
                        new FileReader();


                    reader.onload =
                        function(event) {

                            previewImage.src =
                                event.target.result;


                            photoPreview.style.display =
                                'block';


                            photoDataInput.value =
                                event.target.result;


                            checkFormComplete();
                        };


                    reader.readAsDataURL(file);

                }
            );


            // ====================================================
            // RETAKE
            // ====================================================

            retakeBtn.addEventListener(
                'click',
                function() {

                    photoInput.value =
                        '';

                    previewImage.src =
                        '';

                    photoPreview.style.display =
                        'none';

                    photoDataInput.value =
                        '';

                    checkFormComplete();

                }
            );


            // ====================================================
            // FORM COMPLETE
            // ====================================================

            function checkFormComplete() {

                const lat =
                    document.getElementById(
                        'latitude'
                    ).value;

                const lng =
                    document.getElementById(
                        'longitude'
                    ).value;


                const hasPhoto =
                    (
                        photoPreview.style.display ===
                        'block'
                    ) ||
                    (
                        photoDataInput.value !== ''
                    );


                let valid = !!lat &&
                    !!lng &&
                    hasPhoto;


                // Assigned shop requires shop selection

                if (
                    visitType.value ===
                    'assigned'
                ) {

                    valid =
                        valid &&
                        !!(
                            assignedShopSelect &&
                            assignedShopSelect.value
                        );
                }


                submitBtn.disabled = !valid;
            }


            // ====================================================
            // FORM SUBMIT
            // ====================================================

            visitForm.addEventListener(
                'submit',
                function(e) {

                    const lat =
                        document.getElementById(
                            'latitude'
                        ).value;

                    const lng =
                        document.getElementById(
                            'longitude'
                        ).value;


                    const hasPhoto =
                        (
                            photoPreview.style.display ===
                            'block'
                        ) ||
                        (
                            photoDataInput.value !== ''
                        );


                    // --------------------------------------------
                    // Location
                    // --------------------------------------------

                    if (!lat || !lng) {

                        e.preventDefault();

                        Swal.fire({

                            icon: 'warning',

                            title: 'Location Required',

                            text: 'Please capture your current location first.'
                        });

                        return;
                    }


                    // --------------------------------------------
                    // Assigned Shop
                    // --------------------------------------------

                    if (
                        visitType.value ===
                        'assigned' &&
                        (
                            !assignedShopSelect ||
                            !assignedShopSelect.value
                        )
                    ) {

                        e.preventDefault();

                        Swal.fire({

                            icon: 'warning',

                            title: 'Shop Required',

                            text: 'Please select an assigned shop.'
                        });

                        return;
                    }


                    // --------------------------------------------
                    // Self / New Shop
                    // --------------------------------------------

                    if (
                        visitType.value ===
                        'self'
                    ) {

                        const shopName =
                            document.querySelector(
                                'input[name="shop_name"]'
                            ).value.trim();

                        const ownerName =
                            document.querySelector(
                                'input[name="owner_name"]'
                            ).value.trim();


                        if (!shopName) {

                            e.preventDefault();

                            Swal.fire({

                                icon: 'warning',

                                title: 'Shop Name Required',

                                text: 'Please enter the shop name.'
                            });

                            return;
                        }


                        if (!ownerName) {

                            e.preventDefault();

                            Swal.fire({

                                icon: 'warning',

                                title: 'Owner Name Required',

                                text: 'Please enter the owner name.'
                            });

                            return;
                        }
                    }


                    // --------------------------------------------
                    // Photo
                    // --------------------------------------------

                    if (!hasPhoto) {

                        e.preventDefault();

                        Swal.fire({

                            icon: 'warning',

                            title: 'Photo Required',

                            text: 'Please take a visit photo using the camera.'
                        });

                        return;
                    }


                    // --------------------------------------------
                    // Submit UI
                    // --------------------------------------------

                    submitBtn.disabled =
                        true;

                    submitText.style.display =
                        'none';

                    submitSpinner.style.display =
                        'block';
                }
            );


            // ====================================================
            // INITIAL SETUP
            // ====================================================

            window.toggleVisitType();

            // Auto detect current location
            getLocation();

        }
    );
</script>


<?php

require_once __DIR__ . '/../includes/agent_footer.php';

?>