<?php

/**
 * SAMRIDHI AGRO - Agent Start Visit
 *
 * This page allows agents to start an assigned visit by capturing
 * location and taking a visit photo proof.
 *
 * IMPORTANT:
 * visits.agent_id stores agents.id
 * Session user_id stores users.id
 *
 * @package SamridhiAgro
 * @subpackage Agent
 * @author Samridhi Agro Team
 * @version 2.0.0
 */

// ============================================
// PAGE SETUP
// ============================================

$pageTitle = 'Start Visit';

require_once __DIR__ . '/../includes/agent_header.php';

requireLogin();
requireRole('agent');

$db = getDB();


// ============================================
// GET VISIT ID
// ============================================

$visitId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($visitId <= 0) {
    setFlashMessage('error', 'Invalid visit ID.');
    redirect('agent/visits.php');
    exit;
}


// ============================================
// GET LOGGED-IN AGENT ID
// ============================================
//
// $_SESSION['user_id'] = users.id
//
// visits.agent_id = agents.id
//
// Therefore first find agents.id using users.id.
//

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

$agent = $db->fetchOne($agentSql, [
    $_SESSION['user_id']
]);

if (!$agent) {
    setFlashMessage('error', 'Agent profile not found.');
    redirect('agent/visits.php');
    exit;
}

$agentId = (int) $agent['agent_id'];


// ============================================
// GET ASSIGNED VISIT
// ============================================
//
// IMPORTANT:
// v.agent_id is agents.id
//

$sql = "
    SELECT 
        v.*,

        s.shop_name,
        s.shop_code,
        s.owner_name,
        s.phone,
        s.address,
        s.city,
        s.state,
        s.pincode,
        s.latitude AS shop_latitude,
        s.longitude AS shop_longitude

    FROM visits v

    LEFT JOIN shops s 
        ON v.shop_id = s.id

    WHERE v.id = ?
      AND v.agent_id = ?
      AND v.status = 'assigned'

    LIMIT 1
";

$visit = $db->fetchOne($sql, [
    $visitId,
    $agentId
]);


// ============================================
// VISIT NOT FOUND
// ============================================

if (!$visit) {
    setFlashMessage(
        'error',
        'Visit not found, already completed, cancelled, or not assigned to you.'
    );

    redirect('agent/visits.php');
    exit;
}


// ============================================
// FORM ERRORS
// ============================================

$errors = [];


// ============================================
// HANDLE FORM SUBMISSION
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ----------------------------------------
    // CSRF
    // ----------------------------------------

    if (
        !isset($_POST[CSRF_TOKEN_NAME]) ||
        !verifyCsrfToken($_POST[CSRF_TOKEN_NAME])
    ) {
        setFlashMessage('error', 'Invalid security token.');
        redirect('agent/visit-start.php?id=' . $visitId);
        exit;
    }


    // ----------------------------------------
    // GET LOCATION
    // ----------------------------------------

    $latitude = (
        isset($_POST['latitude']) &&
        $_POST['latitude'] !== ''
    )
        ? (float) $_POST['latitude']
        : null;

    $longitude = (
        isset($_POST['longitude']) &&
        $_POST['longitude'] !== ''
    )
        ? (float) $_POST['longitude']
        : null;

    $accuracy = (
        isset($_POST['accuracy']) &&
        $_POST['accuracy'] !== ''
    )
        ? (float) $_POST['accuracy']
        : null;

    $location = (
        isset($_POST['location']) &&
        $_POST['location'] !== ''
    )
        ? sanitizeInput($_POST['location'])
        : null;

    $remark = isset($_POST['remark'])
        ? sanitizeInput($_POST['remark'])
        : null;


    // ----------------------------------------
    // VALIDATION
    // ----------------------------------------

    $hasErrors = false;


    // Location validation

    if ($latitude === null || $longitude === null) {

        $errors['location'] =
            'Location is required. Please allow location access.';

        $hasErrors = true;
    }


    // Photo validation

    if (
        !isset($_FILES['photo']) ||
        $_FILES['photo']['error'] !== UPLOAD_ERR_OK
    ) {

        $errors['photo'] =
            'Visit photo is required. Please take a photo using the camera.';

        $hasErrors = true;
    }


    // ----------------------------------------
    // PROCESS PHOTO
    // ----------------------------------------

    $photoName = null;
    $photoThumb = null;

    if (!$hasErrors) {

        $uploadDir = __DIR__ . '/../uploads/visits/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // ========================================
        // UPLOAD MAIN PHOTO
        // ========================================

        if (function_exists('uploadVisitPhoto')) {

            $uploadResult = uploadVisitPhoto(
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

                // ====================================
                // MAIN PHOTO
                // ====================================

                $photoName =
                    $uploadResult['filename'] ?? null;


                // ====================================
                // CREATE THUMBNAIL
                // ====================================
                //
                // Same logic as visit-new.php
                //
                // 300 x 300 maximum
                // crop = false
                // aspect ratio maintained
                //

                if (!empty($photoName)) {

                    $mainPhotoPath =
                        $uploadDir . $photoName;

                    $thumbFileName =
                        'thumb_' . $photoName;

                    $thumbPath =
                        $uploadDir . $thumbFileName;


                    try {

                        if (
                            function_exists('createThumbnail') &&
                            file_exists($mainPhotoPath)
                        ) {

                            $thumbCreated =
                                createThumbnail(
                                    $mainPhotoPath,
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

                        // Main photo already uploaded.
                        // Thumbnail failure should not
                        // stop visit completion.

                        $photoThumb = null;
                    }
                }
            } else {

                $errors['photo'] =
                    $uploadResult['error']
                    ?? $uploadResult['message']
                    ?? 'Photo upload failed.';

                $hasErrors = true;
            }
        } else {

            $errors['photo'] =
                'Photo upload function is not available.';

            $hasErrors = true;
        }
    }


    // ----------------------------------------
    // COMPLETE VISIT
    // ----------------------------------------

    if (!$hasErrors) {

        /*
         * IMPORTANT:
         * v.agent_id = agents.id
         *
         * Therefore UPDATE also uses $agentId,
         * NOT $_SESSION['user_id'].
         */

        $sql = "
            UPDATE visits

            SET
                latitude = ?,
                longitude = ?,
                accuracy = ?,
                photo = ?,
                photo_thumbnail = ?,
                remark = CASE
                    WHEN remark IS NULL OR remark = ''
                        THEN ?
                    ELSE CONCAT(
                        remark,
                        '\nVisit completed: ',
                        ?
                    )
                END,
                status = 'completed',
                updated_at = NOW()

            WHERE id = ?
              AND agent_id = ?
              AND status = 'assigned'
        ";


        $db->query($sql, [

            $latitude,
            $longitude,
            $accuracy,

            $photoName,
            $photoThumb,

            $remark ?? 'Visit completed successfully',
            $remark ?? 'Visit completed successfully',

            $visitId,
            $agentId
        ]);


        // ----------------------------------------
        // VERIFY UPDATE
        // ----------------------------------------

        $updatedVisit = $db->fetchOne(
            "
            SELECT id, status
            FROM visits
            WHERE id = ?
              AND agent_id = ?
            LIMIT 1
            ",
            [
                $visitId,
                $agentId
            ]
        );


        if (!$updatedVisit || $updatedVisit['status'] !== 'completed') {

            setFlashMessage(
                'error',
                'Visit could not be completed. Please try again.'
            );

            redirect('agent/visit-start.php?id=' . $visitId);
            exit;
        }


        // ----------------------------------------
        // ACTIVITY LOG
        // ----------------------------------------
        //
        // activity_logs.user_id is users.id,
        // so here session user_id is correct.
        //

        logActivity(
            'update',
            $_SESSION['user_id'],
            'visit',
            'Completed visit #' .
                $visitId .
                ' for shop: ' .
                ($visit['shop_name'] ?? 'Unknown Shop')
        );


        // ----------------------------------------
        // SUCCESS
        // ----------------------------------------

        setFlashMessage(
            'success',
            'Visit completed successfully!'
        );

        redirect(
            'agent/visit-view.php?id=' . $visitId
        );

        exit;
    }
}


// ============================================
// CSRF TOKEN
// ============================================

$csrfToken = generateCsrfToken();


// ============================================
// SHOP LOCATION
// ============================================

$hasShopLocation =
    !empty($visit['shop_latitude']) &&
    !empty($visit['shop_longitude']);


// ============================================
// EXISTING PHOTO
// ============================================

$hasPhoto =
    !empty($visit['photo']) &&
    file_exists('../uploads/visits/' . $visit['photo']);

$photoPath = $hasPhoto
    ? '../uploads/visits/' . $visit['photo']
    : '';

?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<style>
    .visit-info-card {
        background: #F7FCF7;
        border-radius: 12px;
        padding: 16px 20px;
        border: 1px solid #E5EDE7;
        margin-bottom: 20px;
    }

    .visit-info-card .info-row {
        display: flex;
        padding: 6px 0;
        font-size: 14px;
    }

    .visit-info-card .info-row .label {
        font-weight: 600;
        color: #14532D;
        width: 110px;
        flex-shrink: 0;
    }

    .visit-info-card .info-row .value {
        color: #052E16;
        flex: 1;
    }


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
        padding: 10px 14px;
        border: 2px solid #E5EDE7;
        border-radius: 8px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        background: white;
    }


    .form-input:focus {
        outline: none;
        border-color: #16A34A;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
    }


    .form-error {
        color: #DC2626;
        font-size: 13px;
        margin-top: 5px;
    }


    .form-hint {
        font-size: 12px;
        color: #6B7A7B;
        margin-top: 6px;
    }


    /* LOCATION */

    .location-status {
        padding: 11px 14px;
        border-radius: 8px;
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


    /* SHOP MAP */

    .shop-map-container {
        width: 100%;
        height: 250px;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #E5EDE7;
        margin-top: 12px;
    }


    .shop-map-container iframe {
        width: 100%;
        height: 100%;
        border: 0;
    }


    .map-button {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-top: 8px;
        padding: 7px 14px;
        background: #DCFCE7;
        color: #166534;
        border-radius: 7px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
    }


    /* CAMERA */

    .camera-area {
        border: 2px dashed #BBF7D0;
        background: #F7FCF7;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
    }


    .camera-btn {
        padding: 13px 24px;
        background: #14532D;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.3s ease;
    }


    .camera-btn:hover {
        background: #052E16;
        transform: translateY(-1px);
    }


    .camera-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }


    /* PHOTO PREVIEW */

    .photo-preview {
        width: 100%;
        max-width: 350px;
        margin: 15px auto 0;
        border-radius: 10px;
        overflow: hidden;
        border: 2px solid #E5EDE7;
        position: relative;
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
        background: rgba(0, 0, 0, 0.75);
        color: white;
        border: none;
        border-radius: 6px;
        padding: 6px 12px;
        font-size: 12px;
        cursor: pointer;
    }


    /* SUBMIT */

    .btn-submit {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #14532D, #16A34A);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }


    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(22, 163, 74, 0.3);
    }


    .btn-submit:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }


    .btn-submit .spinner {
        display: none;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin: 0 auto;
    }


    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }


    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }


    .status-badge.assigned {
        background: #FEF3C7;
        color: #92400E;
    }


    @media (max-width: 480px) {

        .camera-btn {
            width: 100%;
        }

        .visit-info-card .info-row {
            flex-direction: column;
        }

        .visit-info-card .info-row .label {
            width: 100%;
            margin-bottom: 2px;
        }

        .shop-map-container {
            height: 220px;
        }
    }
</style>


<div class="content-card">

    <!-- HEADER -->

    <div class="card-header">

        <h3 class="card-title">

            <i class="fas fa-play-circle" style="color:#16A34A;"></i>

            Start Visit

            <span style="
                font-size:14px;
                font-weight:400;
                color:#6B7A7B;
                margin-left:8px;
            ">
                #<?php echo str_pad(
                        $visit['id'],
                        6,
                        '0',
                        STR_PAD_LEFT
                    ); ?>
            </span>

        </h3>


        <a href="visits.php" class="card-action">

            <i class="fas fa-arrow-left"></i>

            Back to Visits

        </a>

    </div>


    <!-- ============================================
         VISIT INFORMATION
    ============================================= -->

    <div class="visit-info-card">

        <div class="info-row">

            <span class="label">
                Shop
            </span>

            <span class="value">

                <?php echo escapeHtml(
                    $visit['shop_name'] ?? 'Unknown Shop'
                ); ?>

                <?php if (!empty($visit['shop_code'])): ?>

                    <span style="color:#6B7A7B;font-size:13px;">

                        (<?php echo escapeHtml(
                                $visit['shop_code']
                            ); ?>)

                    </span>

                <?php endif; ?>

            </span>

        </div>


        <?php if (!empty($visit['owner_name'])): ?>

            <div class="info-row">

                <span class="label">
                    Owner
                </span>

                <span class="value">

                    <?php echo escapeHtml(
                        $visit['owner_name']
                    ); ?>

                </span>

            </div>

        <?php endif; ?>


        <?php if (!empty($visit['phone'])): ?>

            <div class="info-row">

                <span class="label">
                    Contact
                </span>

                <span class="value">

                    <?php echo escapeHtml(
                        $visit['phone']
                    ); ?>

                </span>

            </div>

        <?php endif; ?>


        <?php if (!empty($visit['address'])): ?>

            <div class="info-row">

                <span class="label">
                    Address
                </span>

                <span class="value">

                    <?php echo escapeHtml(
                        $visit['address']
                    ); ?>

                    <?php if (!empty($visit['city'])): ?>

                        <br>

                        <?php echo escapeHtml(
                            $visit['city']
                        ); ?>

                        <?php if (!empty($visit['state'])): ?>

                            , <?php echo escapeHtml(
                                    $visit['state']
                                ); ?>

                        <?php endif; ?>

                        <?php if (!empty($visit['pincode'])): ?>

                            - <?php echo escapeHtml(
                                    $visit['pincode']
                                ); ?>

                        <?php endif; ?>

                    <?php endif; ?>

                </span>

            </div>

        <?php endif; ?>


        <div class="info-row">

            <span class="label">
                Status
            </span>

            <span class="value">

                <span class="status-badge assigned">
                    Assigned
                </span>

            </span>

        </div>


        <?php if (!empty($visit['purpose'])): ?>

            <div class="info-row">

                <span class="label">
                    Purpose
                </span>

                <span class="value">

                    <?php echo escapeHtml(
                        $visit['purpose']
                    ); ?>

                </span>

            </div>

        <?php endif; ?>

    </div>


    <!-- ============================================
         SHOP LOCATION
    ============================================= -->

    <?php if ($hasShopLocation): ?>

        <div class="visit-info-card">

            <div style="
            font-weight:600;
            color:#14532D;
            margin-bottom:8px;
        ">

                <i class="fas fa-map-marker-alt"></i>

                Shop Location

            </div>


            <div class="shop-map-container">

                <iframe src="https://www.google.com/maps?q=<?php
                                                            echo $visit['shop_latitude'];
                                                            ?>,<?php
                                                                echo $visit['shop_longitude'];
                                                                ?>&z=16&output=embed" loading="lazy" allowfullscreen>
                </iframe>

            </div>


            <a href="https://www.google.com/maps?q=<?php
                                                    echo $visit['shop_latitude'];
                                                    ?>,<?php
                                                        echo $visit['shop_longitude'];
                                                        ?>" target="_blank" class="map-button">

                <i class="fas fa-external-link-alt"></i>

                Open Shop Location

            </a>

        </div>

    <?php endif; ?>


    <!-- ============================================
         ERRORS
    ============================================= -->

    <?php if (!empty($errors)): ?>

        <div style="
        background:#FEE2E2;
        border:1px solid #FECACA;
        border-radius:8px;
        padding:12px 16px;
        margin-bottom:16px;
    ">

            <p style="
            color:#991B1B;
            font-weight:600;
            margin-bottom:4px;
        ">

                <i class="fas fa-exclamation-circle"></i>

                Please fix the following errors:

            </p>


            <ul style="
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


    <!-- ============================================
         VISIT FORM
    ============================================= -->

    <form method="POST" action="" id="visitForm" enctype="multipart/form-data">


        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrfToken; ?>">

        <input type="hidden" name="latitude" id="latitude" value="">
        <input type="hidden" name="longitude" id="longitude" value="">
        <input type="hidden" name="accuracy" id="accuracy" value="">
        <input type="hidden" name="location" id="location" value="">

        <!-- LOCATION -->

        <div class="form-group">

            <label class="form-label">

                <i class="fas fa-map-marker-alt" style="color:#16A34A;"></i>

                Current Location

                <span style="color:#DC2626;">
                    *
                </span>

            </label>


            <div id="locationStatus" class="location-status warning">

                <i class="fas fa-spinner fa-spin"></i>

                Detecting location...

            </div>


            <div id="locationDetails" style="
                    font-size:13px;
                    color:#6B7A7B;
                    margin-top:5px;
                "></div>


            <button type="button" class="camera-btn" id="getLocationBtn" style="
                    margin-top:8px;
                    background:#2563EB;
                    padding:8px 20px;
                    font-size:13px;
                ">

                <i class="fas fa-sync"></i>

                Refresh Location

            </button>


            <?php if (isset($errors['location'])): ?>

                <div class="form-error">

                    <?php echo escapeHtml(
                        $errors['location']
                    ); ?>

                </div>

            <?php endif; ?>

        </div>


        <!-- ============================================
             CAMERA ONLY
        ============================================= -->

        <div class="form-group">

            <label class="form-label">

                <i class="fas fa-camera" style="color:#16A34A;"></i>

                Visit Photo

                <span style="color:#DC2626;">
                    *
                </span>

            </label>


            <div class="camera-area">

                <!--
                    ONLY CAMERA INPUT

                    No gallery button.
                    No separate file input.
                -->

                <input type="file" name="photo" id="photoInput" accept="image/*" capture="environment"
                    style="display:none;">


                <button type="button" class="camera-btn" id="cameraBtn">

                    <i class="fas fa-camera"></i>

                    Take Photo

                </button>


                <div class="form-hint">

                    <i class="fas fa-info-circle"></i>

                    Camera se shop/person ki photo lena mandatory hai.

                </div>


                <!-- PHOTO PREVIEW -->

                <div id="photoPreview" style="display:none;" class="photo-preview">

                    <img id="previewImage" src="" alt="Visit Photo Preview">


                    <button type="button" class="btn-retake" id="retakeBtn">

                        <i class="fas fa-redo"></i>

                        Retake

                    </button>

                </div>


                <?php if (isset($errors['photo'])): ?>

                    <div class="form-error">

                        <?php echo escapeHtml(
                            $errors['photo']
                        ); ?>

                    </div>

                <?php endif; ?>

            </div>

        </div>


        <!-- ============================================
             REMARK
        ============================================= -->

        <div class="form-group">

            <label class="form-label">

                Remark / Notes

            </label>


            <textarea name="remark" class="form-input" rows="3"
                placeholder="Any additional notes about this visit..."></textarea>

        </div>


        <!-- ============================================
             SUBMIT
        ============================================= -->

        <button type="submit" class="btn-submit" id="submitBtn" disabled>

            <span id="submitText">

                <i class="fas fa-check"></i>

                Complete Visit

            </span>


            <span class="spinner" id="submitSpinner"></span>

        </button>

    </form>

</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {

        // =========================================================
        // ELEMENTS
        // =========================================================

        const getLocationBtn = document.getElementById('getLocationBtn');
        const locationStatus = document.getElementById('locationStatus');
        const locationDetails = document.getElementById('locationDetails');

        const cameraBtn = document.getElementById('cameraBtn');
        const photoInput = document.getElementById('photoInput');
        const photoPreview = document.getElementById('photoPreview');
        const previewImage = document.getElementById('previewImage');
        const retakeBtn = document.getElementById('retakeBtn');

        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const submitSpinner = document.getElementById('submitSpinner');

        const visitForm = document.getElementById('visitForm');


        // =========================================================
        // CREATE HIDDEN FIELD IF MISSING
        // =========================================================
        // Isse latitude/longitude/accuracy ke missing element ki
        // wajah se JS error nahi aayega.
        // =========================================================

        function getOrCreateHiddenField(name) {

            let field = document.getElementById(name);

            if (!field) {

                field = document.createElement('input');

                field.type = 'hidden';
                field.name = name;
                field.id = name;

                if (visitForm) {
                    visitForm.appendChild(field);
                }
            }

            return field;
        }


        // Required hidden fields
        const latitudeInput = getOrCreateHiddenField('latitude');
        const longitudeInput = getOrCreateHiddenField('longitude');
        const accuracyInput = getOrCreateHiddenField('accuracy');
        const locationInput = getOrCreateHiddenField('location');


        // =========================================================
        // SAFE CHECK
        // =========================================================

        function checkRequiredElements() {

            if (!visitForm) {
                console.error('visitForm not found.');
                return false;
            }

            if (!locationStatus) {
                console.error('locationStatus not found.');
                return false;
            }

            if (!cameraBtn) {
                console.error('cameraBtn not found.');
                return false;
            }

            if (!photoInput) {
                console.error('photoInput not found.');
                return false;
            }

            return true;
        }


        if (!checkRequiredElements()) {
            return;
        }


        // =========================================================
        // FORM COMPLETE CHECK
        // =========================================================

        function checkFormComplete() {

            const lat = latitudeInput ? latitudeInput.value.trim() : '';
            const lng = longitudeInput ? longitudeInput.value.trim() : '';

            const hasPhoto =
                photoInput &&
                photoInput.files &&
                photoInput.files.length > 0;

            if (submitBtn) {

                if (lat && lng && hasPhoto) {
                    submitBtn.disabled = false;
                } else {
                    submitBtn.disabled = true;
                }
            }
        }


        // =========================================================
        // LOCATION
        // =========================================================

        function getLocation() {

            if (!navigator.geolocation) {

                locationStatus.className =
                    'location-status error';

                locationStatus.innerHTML =
                    '<i class="fas fa-exclamation-circle"></i> ' +
                    'Your browser does not support GPS location.';

                if (locationDetails) {
                    locationDetails.innerHTML = '';
                }

                checkFormComplete();
                return;
            }


            // Reset UI
            locationStatus.className =
                'location-status warning';

            locationStatus.innerHTML =
                '<i class="fas fa-spinner fa-spin"></i> ' +
                'Detecting location...';

            if (locationDetails) {
                locationDetails.innerHTML = '';
            }

            if (getLocationBtn) {
                getLocationBtn.disabled = true;
            }


            // =====================================================
            // GET CURRENT LOCATION
            // =====================================================

            navigator.geolocation.getCurrentPosition(

                // =================================================
                // SUCCESS
                // =================================================
                function(position) {

                    const lat =
                        position.coords.latitude;

                    const lng =
                        position.coords.longitude;

                    const acc =
                        position.coords.accuracy || 0;


                    console.log('Location detected:', {
                        latitude: lat,
                        longitude: lng,
                        accuracy: acc
                    });


                    // =================================================
                    // SAVE LOCATION IN HIDDEN INPUTS
                    // =================================================

                    if (latitudeInput) {
                        latitudeInput.value = lat;
                    }

                    if (longitudeInput) {
                        longitudeInput.value = lng;
                    }

                    if (accuracyInput) {
                        accuracyInput.value = acc;
                    }


                    // =================================================
                    // DEFAULT LOCATION TEXT
                    // =================================================

                    const fallbackLocation =
                        lat.toFixed(6) + ', ' + lng.toFixed(6);


                    // Save fallback immediately
                    if (locationInput) {
                        locationInput.value = fallbackLocation;
                    }


                    // =================================================
                    // SHOW SUCCESS IMMEDIATELY
                    // =================================================

                    locationStatus.className =
                        'location-status success';

                    locationStatus.innerHTML =
                        '<i class="fas fa-check-circle"></i> ' +
                        'Location captured successfully!';


                    if (locationDetails) {

                        locationDetails.innerHTML =
                            '📍 Location captured' +
                            '<br>' +
                            'Lat: ' +
                            lat.toFixed(6) +
                            ' | Lng: ' +
                            lng.toFixed(6) +
                            (acc ?
                                ' | ± ' +
                                acc.toFixed(0) +
                                'm' :
                                ''
                            );
                    }


                    if (getLocationBtn) {
                        getLocationBtn.disabled = false;
                    }


                    // =================================================
                    // OPTIONAL REVERSE GEOCODING
                    // =================================================
                    // Agar Nominatim fail bhi ho jaye to location
                    // already captured hai aur visit complete ho
                    // sakta hai.
                    // =================================================

                    const reverseUrl =
                        'https://nominatim.openstreetmap.org/reverse' +
                        '?format=json' +
                        '&lat=' + encodeURIComponent(lat) +
                        '&lon=' + encodeURIComponent(lng) +
                        '&zoom=18' +
                        '&addressdetails=1';


                    fetch(reverseUrl)
                        .then(function(response) {

                            if (!response.ok) {
                                throw new Error(
                                    'Reverse geocoding failed'
                                );
                            }

                            return response.json();
                        })
                        .then(function(data) {

                            if (
                                data &&
                                data.display_name &&
                                locationInput
                            ) {

                                locationInput.value =
                                    data.display_name;

                                if (locationDetails) {

                                    locationDetails.innerHTML =
                                        '📍 ' +
                                        data.display_name +
                                        '<br>' +
                                        'Lat: ' +
                                        lat.toFixed(6) +
                                        ' | Lng: ' +
                                        lng.toFixed(6) +
                                        (acc ?
                                            ' | ± ' +
                                            acc.toFixed(0) +
                                            'm' :
                                            ''
                                        );
                                }
                            }

                        })
                        .catch(function(error) {

                            console.log(
                                'Reverse geocoding skipped:',
                                error
                            );

                            // GPS location already available.
                        });


                    checkFormComplete();
                },


                // =================================================
                // ERROR
                // =================================================
                function(error) {

                    console.error(
                        'Geolocation error:',
                        error
                    );

                    let message =
                        'Unable to detect location.';


                    switch (error.code) {

                        case error.PERMISSION_DENIED:

                            message =
                                'Location permission denied. ' +
                                'Please allow location access in your browser.';

                            break;


                        case error.POSITION_UNAVAILABLE:

                            message =
                                'Location information is unavailable. ' +
                                'Please check GPS/location services.';

                            break;


                        case error.TIMEOUT:

                            message =
                                'Location request timed out. ' +
                                'Please try again.';

                            break;


                        default:

                            message =
                                'Unable to detect your current location.';

                            break;
                    }


                    locationStatus.className =
                        'location-status error';

                    locationStatus.innerHTML =
                        '<i class="fas fa-exclamation-circle"></i> ' +
                        message +
                        '<br>' +
                        '<small>Error code: ' +
                        error.code +
                        '</small>';


                    if (locationDetails) {

                        locationDetails.innerHTML =
                            'Click "Refresh Location" and try again.';
                    }


                    if (getLocationBtn) {
                        getLocationBtn.disabled = false;
                    }


                    checkFormComplete();
                },


                // =================================================
                // OPTIONS
                // =================================================
                {
                    enableHighAccuracy: true,
                    timeout: 20000,
                    maximumAge: 0
                }
            );
        }


        // =========================================================
        // REFRESH LOCATION BUTTON
        // =========================================================

        if (getLocationBtn) {

            getLocationBtn.addEventListener(
                'click',
                function() {

                    getLocation();
                }
            );
        }


        // =========================================================
        // AUTO DETECT LOCATION
        // =========================================================

        getLocation();


        // =========================================================
        // CAMERA ONLY
        // =========================================================

        if (cameraBtn && photoInput) {

            cameraBtn.addEventListener(
                'click',
                function() {

                    photoInput.click();
                }
            );
        }


        // =========================================================
        // PHOTO SELECT / CAMERA CAPTURE
        // =========================================================

        if (photoInput) {

            photoInput.addEventListener(
                'change',
                function(event) {

                    const file =
                        event.target.files &&
                        event.target.files[0];


                    if (!file) {

                        checkFormComplete();
                        return;
                    }


                    // =================================================
                    // IMAGE VALIDATION
                    // =================================================

                    if (!file.type.startsWith('image/')) {

                        if (typeof Swal !== 'undefined') {

                            Swal.fire({
                                icon: 'error',
                                title: 'Invalid Photo',
                                text: 'Please capture a valid image using the camera.'
                            });

                        } else {

                            alert(
                                'Please capture a valid image using the camera.'
                            );
                        }


                        photoInput.value = '';

                        if (photoPreview) {
                            photoPreview.style.display = 'none';
                        }

                        checkFormComplete();

                        return;
                    }


                    // =================================================
                    // PREVIEW
                    // =================================================

                    const reader =
                        new FileReader();


                    reader.onload =
                        function(event) {

                            if (previewImage) {

                                previewImage.src =
                                    event.target.result;
                            }


                            if (photoPreview) {

                                photoPreview.style.display =
                                    'block';
                            }


                            checkFormComplete();
                        };


                    reader.onerror =
                        function() {

                            console.error(
                                'Unable to preview image.'
                            );

                            photoInput.value = '';

                            if (photoPreview) {
                                photoPreview.style.display = 'none';
                            }

                            checkFormComplete();
                        };


                    reader.readAsDataURL(file);
                }
            );
        }


        // =========================================================
        // RETAKE PHOTO
        // =========================================================

        if (retakeBtn) {

            retakeBtn.addEventListener(
                'click',
                function() {

                    if (photoInput) {
                        photoInput.value = '';
                    }

                    if (previewImage) {
                        previewImage.src = '';
                    }

                    if (photoPreview) {
                        photoPreview.style.display = 'none';
                    }

                    checkFormComplete();
                }
            );
        }


        // =========================================================
        // FORM SUBMIT
        // =========================================================

        if (visitForm) {

            visitForm.addEventListener(
                'submit',
                function(event) {

                    const lat =
                        latitudeInput ?
                        latitudeInput.value.trim() :
                        '';

                    const lng =
                        longitudeInput ?
                        longitudeInput.value.trim() :
                        '';

                    const hasPhoto =
                        photoInput &&
                        photoInput.files &&
                        photoInput.files.length > 0;


                    // =================================================
                    // LOCATION REQUIRED
                    // =================================================

                    if (!lat || !lng) {

                        event.preventDefault();


                        if (typeof Swal !== 'undefined') {

                            Swal.fire({
                                icon: 'warning',
                                title: 'Location Required',
                                text: 'Please capture your current location first.'
                            });

                        } else {

                            alert(
                                'Please capture your current location first.'
                            );
                        }

                        return;
                    }


                    // =================================================
                    // PHOTO REQUIRED
                    // =================================================

                    if (!hasPhoto) {

                        event.preventDefault();


                        if (typeof Swal !== 'undefined') {

                            Swal.fire({
                                icon: 'warning',
                                title: 'Photo Required',
                                text: 'Please take a visit photo using the camera.'
                            });

                        } else {

                            alert(
                                'Please take a visit photo using the camera.'
                            );
                        }

                        return;
                    }


                    // =================================================
                    // SUBMITTING
                    // =================================================

                    if (submitBtn) {
                        submitBtn.disabled = true;
                    }

                    if (submitText) {
                        submitText.style.display = 'none';
                    }

                    if (submitSpinner) {
                        submitSpinner.style.display = 'block';
                    }
                }
            );
        }


        // =========================================================
        // INITIAL FORM STATE
        // =========================================================

        checkFormComplete();

    });
</script>


<?php
require_once __DIR__ . '/../includes/agent_footer.php';
?>