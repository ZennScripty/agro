<?php
/**
 * SAMRIDHI AGRO - Agent Shop Payment View
 *
 * Detailed view of a single payment (pay_to = 'agent' OR pay_to = 'admin')
 * assigned to the logged-in agent's shop.
 *
 * Flow:
 * Agent Route: Created -> Collected by Agent -> Submitted to Admin -> Confirmed
 * Direct Route: Created -> Confirmed by Admin
 *
 * @package SamridhiAgro
 * @subpackage Agent
 * @version 3.2.0
 */

// ============================================
// BASIC CONFIGURATION
// ============================================

$pageTitle = 'Payment Details';

// Include required files
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/functions.php';

// ============================================
// AUTHENTICATION
// ============================================

requireLogin();
requireRole('agent');

// ============================================
// DATABASE
// ============================================

$db = getDB();

// ============================================
// GET LOGGED-IN AGENT
// ============================================

$sql = "SELECT 
            a.*,
            u.full_name
        FROM agents a
        JOIN users u ON a.user_id = u.id
        WHERE a.user_id = ?";

$agent = $db->fetchOne($sql, [
    $_SESSION['user_id']
]);

if (!$agent) {
    setFlashMessage('error', 'Agent profile not found.');
    redirect('agent/logout.php');
    exit;
}

// Actual Agent ID
$agentId = (int)$agent['id'];

// ============================================
// GET PAYMENT ID
// ============================================

$paymentId = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($paymentId <= 0) {
    setFlashMessage('error', 'Invalid payment ID.');
    redirect('agent/shop-payments.php');
    exit;
}

// ============================================
// GET PAYMENT DETAILS - SHOW BOTH AGENT AND DIRECT PAYMENTS
// ============================================
//
// IMPORTANT:
// Payment is checked using shop's agent_id = $agentId.
// This allows agent to see both:
// 1. pay_to = 'agent' (agent collects)
// 2. pay_to = 'admin' (direct payments from shops)
//

$sql = "SELECT 
            p.*,

            /* Shop information */
            s.shop_name,
            s.shop_code,
            s.owner_name,
            s.phone,
            s.email,
            s.address,
            s.city,
            s.state,
            s.pincode,

            /* Agent information */
            ua.full_name AS agent_name,

            /* Admin confirmer */
            uc.full_name AS confirmed_by_name,

            /* Shop remaining balance */
            (
                SELECT COALESCE(SUM(total_amount), 0) 
                FROM orders 
                WHERE shop_id = s.id AND status != 'cancelled'
            ) as total_dues,
            (
                SELECT COALESCE(SUM(amount), 0) 
                FROM payments 
                WHERE shop_id = s.id AND status = 'confirmed'
            ) as total_confirmed

        FROM payments p

        JOIN shops s 
            ON p.shop_id = s.id

        LEFT JOIN agents ag 
            ON s.agent_id = ag.id

        LEFT JOIN users ua 
            ON ag.user_id = ua.id

        LEFT JOIN users uc 
            ON p.confirmed_by = uc.id

        WHERE p.id = ?
          AND s.agent_id = ?
        LIMIT 1";

$payment = $db->fetchOne($sql, [
    $paymentId,
    $agentId
]);

// ============================================
// PAYMENT NOT FOUND
// ============================================

if (!$payment) {
    setFlashMessage(
        'error',
        'Payment not found or not assigned to your shop.'
    );

    redirect('agent/shop-payments.php');
    exit;
}

// Calculate remaining amount
$payment['remaining_amount'] = max(0, ($payment['total_dues'] ?? 0) - ($payment['total_confirmed'] ?? 0));

// ============================================
// CSRF TOKEN
// ============================================

$csrfToken = generateCsrfToken();

// ============================================
// INCLUDE HEADER
// ============================================

require_once __DIR__ . '/../includes/agent_header.php';
?>

<!-- ============================================
     SWEETALERT2
============================================= -->

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>

    /* ============================================
       DETAIL SECTION
    ============================================ */

    .detail-section {
        background: #ffffff;
        border: 1px solid #E5EDE7;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 20px;
    }

    .detail-section .section-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 16px;
        font-weight: 600;
        color: #052E16;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid #F0FDF4;
    }

    /* ============================================
       DETAIL ROW
    ============================================ */

    .detail-row {
        display: flex;
        padding: 8px 0;
        border-bottom: 1px solid #F7FCF7;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        font-weight: 500;
        color: #6B7A7B;
        width: 160px;
        flex-shrink: 0;
    }

    .detail-value {
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #052E16;
        flex: 1;
        word-break: break-word;
    }

    /* ============================================
       STATUS BADGES
    ============================================ */

    .badge-status {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
    }

    .badge-success {
        background: #DCFCE7;
        color: #065F46;
    }

    .badge-warning {
        background: #FEF3C7;
        color: #92400E;
    }

    .badge-info {
        background: #DBEAFE;
        color: #1E40AF;
    }

    .badge-primary {
        background: #EDE9FE;
        color: #5B21B6;
    }

    .badge-danger {
        background: #FEE2E2;
        color: #991B1B;
    }

    /* ============================================
       PAYMENT SUMMARY
    ============================================ */

    .payment-summary {
        background: linear-gradient(
            135deg,
            #F7FCF7 0%,
            #DCFCE7 100%
        );

        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 20px;

        display: grid;
        grid-template-columns: 1.5fr 1fr 1fr;
        gap: 20px;
        align-items: center;
    }

    .summary-label {
        font-size: 13px;
        color: #6B7A7B;
        margin-bottom: 4px;
    }

    .summary-amount {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 32px;
        font-weight: 700;
        color: #14532D;
    }

    .summary-value {
        font-weight: 600;
        color: #052E16;
    }

    /* ============================================
       PAYMENT TIMELINE
    ============================================ */

    .payment-timeline {
        position: relative;
        padding-left: 24px;
    }

    .payment-timeline::before {
        content: '';
        position: absolute;
        left: 6px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #E5EDE7;
    }

    .timeline-item {
        position: relative;
        padding: 8px 0 16px 20px;
    }

    .timeline-item:last-child {
        padding-bottom: 4px;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -18px;
        top: 14px;

        width: 10px;
        height: 10px;

        border-radius: 50%;

        background: #16A34A;
        border: 2px solid #ffffff;
        box-shadow: 0 0 0 2px #16A34A;
    }

    .timeline-item.completed::before {
        background: #16A34A;
        box-shadow: 0 0 0 2px #16A34A;
    }

    .timeline-item.pending::before {
        background: #F59E0B;
        box-shadow: 0 0 0 2px #F59E0B;
    }

    .timeline-item.failed::before {
        background: #DC2626;
        box-shadow: 0 0 0 2px #DC2626;
    }

    .timeline-title {
        font-weight: 600;
        color: #052E16;
        font-size: 14px;
    }

    .timeline-time {
        font-size: 12px;
        color: #6B7A7B;
        margin-top: 2px;
    }

    .timeline-desc {
        font-size: 13px;
        color: #4A5B5D;
        margin-top: 3px;
    }

    /* ============================================
       PAYMENT ROUTE BADGE
    ============================================ */

    .pay-to-badge {
        display: inline-block;
        padding: 2px 12px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }

    .pay-to-badge.agent {
        background: #EDE9FE;
        color: #5B21B6;
    }

    .pay-to-badge.admin {
        background: #FEE2E2;
        color: #991B1B;
    }

    /* ============================================
       REMAINING AMOUNT
    ============================================ */

    .remaining-amount {
        font-weight: 700;
        color: #14532D;
    }

    .remaining-amount.zero {
        color: #16A34A;
    }

    /* ============================================
       ACTION BUTTONS
    ============================================ */

    .payment-actions {
        display: flex;
        gap: 12px;
        margin-top: 8px;
        flex-wrap: wrap;
    }

    .btn-action {
        padding: 9px 20px;
        border-radius: 8px;
        border: none;

        font-size: 14px;
        font-weight: 500;

        text-decoration: none;

        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;

        transition: all 0.25s ease;
        cursor: pointer;
    }

    .btn-action:hover {
        transform: translateY(-1px);
    }

    .btn-collect {
        background: #16A34A;
        color: #ffffff;
    }

    .btn-collect:hover {
        background: #14532D;
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
    }

    .btn-submit {
        background: #7C3AED;
        color: #ffffff;
    }

    .btn-submit:hover {
        background: #5B21B6;
        box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25);
    }

    .btn-back {
        background: #F3F4F6;
        color: #4A5B5D;
    }

    .btn-back:hover {
        background: #E5E7EB;
    }

    /* ============================================
       MOBILE
    ============================================ */

    @media (max-width: 768px) {

        .payment-summary {
            grid-template-columns: 1fr;
            gap: 14px;
            padding: 18px;
        }

        .summary-amount {
            font-size: 28px;
        }

        .detail-section {
            padding: 16px;
        }

        .detail-row {
            flex-direction: column;
            gap: 4px;
            padding: 10px 0;
        }

        .detail-label {
            width: 100%;
            font-size: 12px;
        }

        .detail-value {
            width: 100%;
            font-size: 14px;
        }

        .payment-actions {
            flex-direction: column;
        }

        .payment-actions .btn-action {
            width: 100%;
        }

        .payment-timeline {
            padding-left: 16px;
        }
    }

    @media (max-width: 480px) {

        .summary-amount {
            font-size: 25px;
        }

        .detail-section .section-title {
            font-size: 15px;
        }

        .timeline-title {
            font-size: 13px;
        }

        .timeline-desc {
            font-size: 12px;
        }
    }

</style>


<div class="content-card">

    <!-- ============================================
         HEADER
    ============================================= -->

    <div class="card-header">

        <h3 class="card-title">

            <i
                class="fas fa-receipt"
                style="color:#16A34A;"
            ></i>

            Payment Details

            <span
                style="
                    font-size:14px;
                    font-weight:400;
                    color:#6B7A7B;
                    margin-left:8px;
                "
            >
                #<?php echo (int)$payment['id']; ?>
            </span>

        </h3>

        <a
            href="shop-payments.php"
            class="card-action"
        >
            <i class="fas fa-arrow-left"></i>
            Back to Payments
        </a>

    </div>


    <!-- ============================================
         PAYMENT SUMMARY
    ============================================= -->

    <div class="payment-summary">

        <div>

            <div class="summary-label">
                Amount
            </div>

            <div class="summary-amount">
                ₹ <?php echo number_format(
                    (float)$payment['amount'],
                    2
                ); ?>
            </div>

        </div>


        <div>

            <div class="summary-label">
                Status
            </div>

            <?php

            $statusColors = [
                'pending'   => 'badge-warning',
                'collected' => 'badge-info',
                'submitted' => 'badge-primary',
                'confirmed' => 'badge-success',
                'failed'    => 'badge-danger'
            ];

            $color =
                $statusColors[$payment['status']]
                ?? 'badge-warning';

            ?>

            <span
                class="badge-status <?php echo $color; ?>"
                style="
                    font-size:14px;
                    padding:6px 14px;
                "
            >
                <?php
                echo escapeHtml(
                    ucfirst($payment['status'])
                );
                ?>
            </span>

        </div>


        <div>

            <div class="summary-label">
                Created On
            </div>

            <div class="summary-value">
                <?php
                echo formatDate(
                    $payment['created_at']
                );
                ?>
            </div>

        </div>

    </div>


    <!-- ============================================
         PAYMENT INFORMATION
    ============================================= -->

    <div class="detail-section">

        <div class="section-title">

            <i
                class="fas fa-info-circle"
                style="color:#16A34A;"
            ></i>

            Payment Information

        </div>


        <div class="detail-row">

            <span class="detail-label">
                Payment ID
            </span>

            <span class="detail-value">
                #<?php echo (int)$payment['id']; ?>
            </span>

        </div>


        <div class="detail-row">

            <span class="detail-label">
                Amount
            </span>

            <span
                class="detail-value"
                style="
                    font-weight:700;
                    color:#14532D;
                "
            >
                ₹ <?php echo number_format(
                    (float)$payment['amount'],
                    2
                ); ?>
            </span>

        </div>


        <div class="detail-row">

            <span class="detail-label">
                Payment Route
            </span>

            <span class="detail-value">

                <span class="pay-to-badge <?php echo $payment['pay_to']; ?>">
                    <i class="fas fa-<?php echo $payment['pay_to'] === 'agent' ? 'user-tie' : 'user-shield'; ?>"></i>
                    <?php echo $payment['pay_to'] === 'agent' ? 'Agent Collection' : 'Direct to Admin'; ?>
                </span>

            </span>

        </div>


        <div class="detail-row">

            <span class="detail-label">
                Payment Method
            </span>

            <span class="detail-value">

                <?php if (!empty($payment['payment_method'])): ?>

                    <?php
                    echo escapeHtml(
                        ucfirst(
                            str_replace(
                                '_',
                                ' ',
                                $payment['payment_method']
                            )
                        )
                    );
                    ?>

                    <?php if (!empty($payment['transaction_id'])): ?>

                        <span
                            style="
                                color:#6B7A7B;
                                font-size:13px;
                                margin-left:8px;
                            "
                        >
                            TXN:
                            <?php echo escapeHtml(
                                $payment['transaction_id']
                            ); ?>
                        </span>

                    <?php endif; ?>

                <?php else: ?>

                    <span style="color:#6B7A7B;">
                        Not specified
                    </span>

                <?php endif; ?>

            </span>

        </div>


        <div class="detail-row">

            <span class="detail-label">
                Shop Remaining Balance
            </span>

            <span class="detail-value">

                <span class="remaining-amount <?php echo ($payment['remaining_amount'] ?? 0) <= 0 ? 'zero' : ''; ?>">
                    ₹ <?php echo number_format($payment['remaining_amount'] ?? 0, 2); ?>
                </span>

                <?php if (($payment['remaining_amount'] ?? 0) <= 0): ?>
                    <span style="color: #16A34A; font-size: 13px; margin-left: 6px;">
                        <i class="fas fa-check-circle"></i> Fully Paid
                    </span>
                <?php endif; ?>

            </span>

        </div>


        <?php if (!empty($payment['notes'])): ?>

        <div class="detail-row">

            <span class="detail-label">
                Shop Notes
            </span>

            <span
                class="detail-value"
                style="white-space:pre-wrap;"
            >
                <?php echo nl2br(
                    escapeHtml($payment['notes'])
                ); ?>
            </span>

        </div>

        <?php endif; ?>


        <?php if (!empty($payment['admin_notes'])): ?>

        <div class="detail-row">

            <span class="detail-label">
                Admin Notes
            </span>

            <span
                class="detail-value"
                style="white-space:pre-wrap; color:#7C3AED;"
            >
                <?php echo nl2br(
                    escapeHtml($payment['admin_notes'])
                ); ?>
            </span>

        </div>

        <?php endif; ?>

    </div>


    <!-- ============================================
         SHOP INFORMATION
    ============================================= -->

    <div class="detail-section">

        <div class="section-title">

            <i
                class="fas fa-store"
                style="color:#16A34A;"
            ></i>

            Shop Information

        </div>


        <div class="detail-row">

            <span class="detail-label">
                Shop Name
            </span>

            <span class="detail-value">

                <a
                    href="shop-view.php?id=<?php echo (int)$payment['shop_id']; ?>"
                    style="
                        color:#16A34A;
                        text-decoration:none;
                        font-weight:600;
                    "
                >
                    <?php echo escapeHtml(
                        $payment['shop_name']
                    ); ?>
                </a>

            </span>

        </div>


        <div class="detail-row">

            <span class="detail-label">
                Shop Code
            </span>

            <span class="detail-value">
                <?php echo escapeHtml(
                    $payment['shop_code']
                ); ?>
            </span>

        </div>


        <div class="detail-row">

            <span class="detail-label">
                Owner
            </span>

            <span class="detail-value">
                <?php echo escapeHtml(
                    $payment['owner_name']
                ); ?>
            </span>

        </div>


        <div class="detail-row">

            <span class="detail-label">
                Contact
            </span>

            <span class="detail-value">

                <?php echo escapeHtml(
                    $payment['phone']
                ); ?>

                <?php if (!empty($payment['email'])): ?>

                    <span
                        style="
                            color:#6B7A7B;
                            font-size:13px;
                            margin-left:8px;
                        "
                    >
                        (<?php echo escapeHtml(
                            $payment['email']
                        ); ?>)
                    </span>

                <?php endif; ?>

            </span>

        </div>


        <?php if (!empty($payment['address'])): ?>

        <div class="detail-row">

            <span class="detail-label">
                Address
            </span>

            <span class="detail-value">

                <?php echo escapeHtml(
                    $payment['address']
                ); ?>

                <?php

                $locationParts = [];

                if (!empty($payment['city'])) {
                    $locationParts[] =
                        $payment['city'];
                }

                if (!empty($payment['state'])) {
                    $locationParts[] =
                        $payment['state'];
                }

                if (!empty($payment['pincode'])) {
                    $locationParts[] =
                        $payment['pincode'];
                }

                ?>

                <?php if (!empty($locationParts)): ?>

                    <br>

                    <?php echo escapeHtml(
                        implode(
                            ', ',
                            $locationParts
                        )
                    ); ?>

                <?php endif; ?>

            </span>

        </div>

        <?php endif; ?>

    </div>


    <!-- ============================================
         PAYMENT TIMELINE
    ============================================= -->

    <div class="detail-section">

        <div class="section-title">

            <i
                class="fas fa-clock"
                style="color:#16A34A;"
            ></i>

            Payment Timeline

        </div>


        <div class="payment-timeline">

            <!-- CREATED -->

            <div class="timeline-item completed">

                <div class="timeline-title">
                    Payment Initiated by Shop
                </div>

                <div class="timeline-time">
                    <?php echo formatDate(
                        $payment['created_at']
                    ); ?>
                </div>

                <div class="timeline-desc">

                    <?php echo escapeHtml(
                        $payment['shop_name']
                    ); ?>

                    initiated payment of

                    ₹<?php echo number_format(
                        (float)$payment['amount'],
                        2
                    ); ?>

                    <?php if ($payment['pay_to'] === 'agent'): ?>
                        to be collected by agent.
                    <?php else: ?>
                        directly to admin.
                    <?php endif; ?>

                </div>

            </div>

            <?php if ($payment['pay_to'] === 'agent'): ?>

                <!-- AGENT COLLECTED -->
                <?php if (!empty($payment['agent_collected_at'])): ?>

                    <div class="timeline-item completed">

                        <div class="timeline-title">
                            Collected by Agent
                        </div>

                        <div class="timeline-time">

                            <?php echo formatDate(
                                $payment['agent_collected_at']
                            ); ?>

                        </div>

                        <div class="timeline-desc">

                            Payment marked as collected by

                            <?php echo escapeHtml(
                                $payment['agent_name']
                                ?: $agent['full_name']
                            ); ?>

                        </div>

                    </div>

                <?php else: ?>

                    <div class="timeline-item pending">

                        <div class="timeline-title">
                            Pending Collection
                        </div>

                        <div class="timeline-time">
                            Awaiting agent collection
                        </div>

                        <div class="timeline-desc">
                            Collect the payment from the shop
                            and mark it as collected.
                        </div>

                    </div>

                <?php endif; ?>


                <!-- SUBMITTED TO ADMIN -->
                <?php if (!empty($payment['submitted_at'])): ?>

                    <div class="timeline-item completed">

                        <div class="timeline-title">
                            Submitted to Admin
                        </div>

                        <div class="timeline-time">

                            <?php echo formatDate(
                                $payment['submitted_at']
                            ); ?>

                        </div>

                        <div class="timeline-desc">
                            Payment submitted to admin
                            for confirmation.
                        </div>

                    </div>

                <?php elseif (!empty($payment['agent_collected_at'])): ?>

                    <div class="timeline-item pending">

                        <div class="timeline-title">
                            Awaiting Submission
                        </div>

                        <div class="timeline-time">
                            Ready to submit to admin
                        </div>

                        <div class="timeline-desc">
                            Click "Submit to Admin"
                            to forward this payment.
                        </div>

                    </div>

                <?php endif; ?>

            <?php endif; ?>


            <!-- ADMIN CONFIRMED / REJECTED -->

            <?php if (!empty($payment['confirmed_at'])): ?>

                <div class="timeline-item completed">

                    <div class="timeline-title">
                        <?php echo $payment['pay_to'] === 'agent' ? 'Confirmed by Admin' : 'Received & Confirmed by Admin'; ?>
                    </div>

                    <div class="timeline-time">

                        <?php echo formatDate(
                            $payment['confirmed_at']
                        ); ?>

                    </div>

                    <div class="timeline-desc">

                        <?php if ($payment['pay_to'] === 'agent'): ?>
                            Payment received and confirmed by admin.
                        <?php else: ?>
                            Direct payment received and confirmed by admin.
                        <?php endif; ?>

                        <?php if (!empty($payment['confirmed_by_name'])): ?>

                            Confirmed by

                            <?php echo escapeHtml(
                                $payment['confirmed_by_name']
                            ); ?>

                        <?php endif; ?>

                    </div>

                </div>

            <?php elseif ($payment['status'] === 'failed'): ?>

                <div class="timeline-item failed">

                    <div class="timeline-title">
                        Rejected by Admin
                    </div>

                    <div class="timeline-time">
                        <?php echo formatDate($payment['updated_at']); ?>
                    </div>

                    <div class="timeline-desc">
                        Payment was rejected.
                        <?php if (!empty($payment['admin_notes'])): ?>
                            Reason: <?php echo escapeHtml($payment['admin_notes']); ?>
                        <?php endif; ?>
                    </div>

                </div>

            <?php elseif ($payment['pay_to'] === 'admin' && $payment['status'] === 'pending'): ?>

                <div class="timeline-item pending">

                    <div class="timeline-title">
                        Awaiting Admin Confirmation
                    </div>

                    <div class="timeline-time">
                        Payment is pending admin approval
                    </div>

                    <div class="timeline-desc">
                        Admin needs to confirm receipt
                        of this direct payment.
                    </div>

                </div>

            <?php elseif ($payment['pay_to'] === 'agent' && !empty($payment['submitted_at'])): ?>

                <div class="timeline-item pending">

                    <div class="timeline-title">
                        Pending Admin Confirmation
                    </div>

                    <div class="timeline-time">
                        Awaiting admin confirmation
                    </div>

                    <div class="timeline-desc">
                        Admin needs to confirm receipt
                        of this payment.
                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- ============================================
         ACTIONS
    ============================================= -->

    <div class="payment-actions">

        <?php if ($payment['pay_to'] === 'agent'): ?>
            <?php if ($payment['status'] === 'pending'): ?>

                <button
                    type="button"
                    class="btn-action btn-collect"
                    onclick="collectPayment(<?php echo (int)$payment['id']; ?>)"
                >
                    <i class="fas fa-hand-holding-usd"></i>
                    Mark Collected
                </button>

            <?php elseif ($payment['status'] === 'collected'): ?>

                <button
                    type="button"
                    class="btn-action btn-submit"
                    onclick="submitToAdmin(<?php echo (int)$payment['id']; ?>)"
                >
                    <i class="fas fa-arrow-up"></i>
                    Submit to Admin
                </button>

            <?php endif; ?>
        <?php else: ?>
            <!-- Direct Payment - Agent can only view -->
            <span style="font-size: 13px; color: #6B7A7B; padding: 8px 0;">
                <i class="fas fa-info-circle"></i>
                This is a direct payment to admin.
                <?php if ($payment['status'] === 'confirmed'): ?>
                    <span style="color: #16A34A;">✓ Already confirmed by admin.</span>
                <?php elseif ($payment['status'] === 'pending'): ?>
                    <span style="color: #F59E0B;">⏳ Awaiting admin confirmation.</span>
                <?php endif; ?>
            </span>
        <?php endif; ?>

        <a
            href="shop-payments.php"
            class="btn-action btn-back"
        >
            <i class="fas fa-arrow-left"></i>
            Back
        </a>

    </div>

</div>


<script>

/* ============================================
   CSRF TOKEN
============================================= */

const csrfToken =
    '<?php echo addslashes($csrfToken); ?>';


/* ============================================
   MARK PAYMENT COLLECTED
============================================= */

function collectPayment(paymentId) {

    Swal.fire({

        title: 'Confirm?',

        text: 'Mark this payment as collected?',

        icon: 'question',

        showCancelButton: true,

        confirmButtonText: 'Yes',

        cancelButtonText: 'No',

        confirmButtonColor: '#16A34A',

        cancelButtonColor: '#6B7A7B',

        reverseButtons: true

    }).then((result) => {

        if (!result.isConfirmed) {
            return;
        }

        Swal.fire({

            title: 'Processing...',

            allowOutsideClick: false,

            allowEscapeKey: false,

            didOpen: () => {
                Swal.showLoading();
            }

        });


        fetch('shop-payments.php', {

            method: 'POST',

            headers: {
                'Content-Type':
                    'application/x-www-form-urlencoded',

                'X-Requested-With':
                    'XMLHttpRequest'
            },

            body: new URLSearchParams({

                '<?php echo CSRF_TOKEN_NAME; ?>':
                    csrfToken,

                'action':
                    'collect_payment',

                'payment_id':
                    paymentId

            })

        })

        .then(response => {

            return response.text();

        })

        .then(text => {

            console.log(
                'Collect Payment Response:',
                text
            );

            let data;

            try {

                data = JSON.parse(text);

            } catch (error) {

                throw new Error(
                    'Invalid server response. Please check shop-payments.php.'
                );

            }

            if (!data.success) {

                throw new Error(
                    data.message ||
                    'Failed to mark payment as collected.'
                );

            }

            Swal.fire({

                icon: 'success',

                title: 'Collected!',

                text:
                    data.message ||
                    'Payment marked as collected.',

                timer: 1500,

                showConfirmButton: false

            }).then(() => {

                window.location.reload();

            });

        })

        .catch(error => {

            console.error(
                'Collect Payment Error:',
                error
            );

            Swal.fire({

                icon: 'error',

                title: 'Error',

                text:
                    error.message ||
                    'Something went wrong. Please try again.'

            });

        });

    });

}


/* ============================================
   SUBMIT TO ADMIN
============================================= */

function submitToAdmin(paymentId) {

    Swal.fire({

        title: 'Confirm?',

        text: 'Submit this payment to admin?',

        icon: 'question',

        showCancelButton: true,

        confirmButtonText: 'Yes',

        cancelButtonText: 'No',

        confirmButtonColor: '#7C3AED',

        cancelButtonColor: '#6B7A7B',

        reverseButtons: true

    }).then((result) => {

        if (!result.isConfirmed) {
            return;
        }

        Swal.fire({

            title: 'Submitting...',

            allowOutsideClick: false,

            allowEscapeKey: false,

            didOpen: () => {
                Swal.showLoading();
            }

        });


        fetch('shop-payments.php', {

            method: 'POST',

            headers: {

                'Content-Type':
                    'application/x-www-form-urlencoded',

                'X-Requested-With':
                    'XMLHttpRequest'

            },

            body: new URLSearchParams({

                '<?php echo CSRF_TOKEN_NAME; ?>':
                    csrfToken,

                'action':
                    'submit_to_admin',

                'payment_id':
                    paymentId

            })

        })

        .then(response => {

            return response.text();

        })

        .then(text => {

            console.log(
                'Submit Payment Response:',
                text
            );

            let data;

            try {

                data = JSON.parse(text);

            } catch (error) {

                throw new Error(
                    'Invalid server response. Please check shop-payments.php.'
                );

            }

            if (!data.success) {

                throw new Error(
                    data.message ||
                    'Failed to submit payment.'
                );

            }

            Swal.fire({

                icon: 'success',

                title: 'Submitted!',

                text:
                    data.message ||
                    'Payment submitted to admin successfully.',

                timer: 1500,

                showConfirmButton: false

            }).then(() => {

                window.location.reload();

            });

        })

        .catch(error => {

            console.error(
                'Submit Payment Error:',
                error
            );

            Swal.fire({

                icon: 'error',

                title: 'Error',

                text:
                    error.message ||
                    'Something went wrong. Please try again.'

            });

        });

    });

}

</script>


<?php require_once __DIR__ . '/../includes/agent_footer.php'; ?>