<?php

session_start();

require_once "config.php";

/* =========================================================
   LOGIN CHECK
========================================================= */

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

/* =========================================================
   GET USER INFORMATION
========================================================= */

$user_name = "User";

$userStmt = $conn->prepare("
    SELECT first_name, last_name
    FROM users
    WHERE id = ?
    LIMIT 1
");

if ($userStmt) {
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();

    $userResult = $userStmt->get_result();

    if ($userResult->num_rows > 0) {
        $user = $userResult->fetch_assoc();

        $user_name = trim(
            ($user['first_name'] ?? '') . " " .
            ($user['last_name'] ?? '')
        );
    }

    $userStmt->close();
}

/* =========================================================
   GET BOOKINGS
========================================================= */

$bookings = [];

$bookingStmt = $conn->prepare("
    SELECT
        b.id,
        b.sport,
        b.court,
        b.booking_date,
        b.start_time,
        b.duration,
        b.status,

        p.payment_method,
        p.amount_paid,
        p.balance,
        p.payment_status,
        p.gcash_reference,
        p.payment_date

    FROM bookings b

    LEFT JOIN payments p
        ON p.booking_id = b.id

    WHERE b.user_id = ?

    ORDER BY
        b.booking_date DESC,
        b.start_time DESC,
        b.id DESC
");

if ($bookingStmt) {

    $bookingStmt->bind_param("i", $user_id);
    $bookingStmt->execute();

    $bookingResult = $bookingStmt->get_result();

    while ($row = $bookingResult->fetch_assoc()) {
        $bookings[] = $row;
    }

    $bookingStmt->close();
}

/* =========================================================
   STATISTICS
========================================================= */

$totalBookings = count($bookings);
$confirmedBookings = 0;
$pendingBookings = 0;
$cancelledBookings = 0;

foreach ($bookings as $booking) {

    $status = strtolower($booking['status'] ?? '');

    if ($status === 'confirmed') {
        $confirmedBookings++;
    }

    if ($status === 'pending') {
        $pendingBookings++;
    }

    if ($status === 'cancelled') {
        $cancelledBookings++;
    }
}

/* =========================================================
   HELPERS
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}

function formatDate($date)
{
    if (empty($date)) {
        return "—";
    }

    return date("F d, Y", strtotime($date));
}

function formatTime($time)
{
    if (empty($time)) {
        return "—";
    }

    return date("h:i A", strtotime($time));
}

function statusClass($status)
{
    $status = strtolower(trim((string)$status));

    switch ($status) {

        case "confirmed":
            return "confirmed";

        case "pending":
            return "pending";

        case "cancelled":
        case "canceled":
            return "cancelled";

        case "completed":
            return "completed";

        default:
            return "default";
    }
}

function paymentClass($status)
{
    $status = strtolower(trim((string)$status));

    switch ($status) {

        case "paid":
            return "paid";

        case "downpayment":
            return "downpayment";

        case "pending":
            return "payment-pending";

        default:
            return "default";
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>


<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>My Bookings | Court22</title>

<link
    rel="stylesheet"
    href="css/mybooking.css"
>


</head>

<body>

<div class="mybooking-page">


<!-- =====================================================
     HEADER
====================================================== -->

<header class="site-header">

    <div class="header-inner">

        <a href="dashboard.php" class="logo">
            COURT<span>22</span>
        </a>

        <nav>

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="court.php">
                Courts
            </a>

            <a href="booking.php">
                Book a Court
            </a>

            <a href="mybooking.php" class="active">
                My Bookings
            </a>

            <a href="profile.php">
                Profile
            </a>

            <a href="logout.php" class="logout-link">
                Logout
            </a>

        </nav>

    </div>

</header>


<!-- =====================================================
     MAIN
====================================================== -->

<main class="booking-container">

    <!-- PAGE HEADER -->

    <section class="booking-header">

        <div>

            <p class="eyebrow">
                COURT22 RESERVATIONS
            </p>

            <h1>
                My Bookings
            </h1>

            <p>
                Welcome back,
                <strong><?= e($user_name) ?></strong>.
                Manage and view all your court reservations here.
            </p>

        </div>

        <a
            href="booking.php"
            class="new-booking-btn"
        >
            + Book a Court
        </a>

    </section>


    <!-- =================================================
         STATISTICS
    ================================================== -->

    <section class="stats-grid">

        <div class="stat-card">

            <div class="stat-icon">
                📅
            </div>

            <div>

                <span>
                    Total Bookings
                </span>

                <strong>
                    <?= $totalBookings ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ✓
            </div>

            <div>

                <span>
                    Confirmed
                </span>

                <strong>
                    <?= $confirmedBookings ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ⏳
            </div>

            <div>

                <span>
                    Pending
                </span>

                <strong>
                    <?= $pendingBookings ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ×
            </div>

            <div>

                <span>
                    Cancelled
                </span>

                <strong>
                    <?= $cancelledBookings ?>
                </strong>

            </div>

        </div>

    </section>


    <!-- =================================================
         BOOKINGS
    ================================================== -->

    <section class="bookings-section">

        <div class="section-heading">

            <div>

                <p class="eyebrow">
                    RESERVATION HISTORY
                </p>

                <h2>
                    Your Reservations
                </h2>

            </div>

            <span class="booking-count">
                <?= $totalBookings ?>
                Booking<?= $totalBookings == 1 ? '' : 's' ?>
            </span>

        </div>


        <?php if (empty($bookings)): ?>

            <!-- EMPTY STATE -->

            <div class="empty-state">

                <div class="empty-icon">
                    📅
                </div>

                <h3>
                    No bookings yet
                </h3>

                <p>
                    You haven't made any court reservations.
                    Book your first court and start playing!
                </p>

                <a
                    href="booking.php"
                    class="empty-btn"
                >
                    Book Your First Court
                </a>

            </div>

        <?php else: ?>

            <div class="booking-list">

                <?php foreach ($bookings as $booking): ?>

                    <?php

                    $status = $booking['status'] ?? 'Pending';

                    $paymentStatus =
                        $booking['payment_status'] ?? 'Pending';

                    $amountPaid =
                        (float)($booking['amount_paid'] ?? 0);

                    $balance =
                        (float)($booking['balance'] ?? 0);

                    $duration =
                        (int)($booking['duration'] ?? 1);

                    ?>

                    <article class="booking-card">

                        <!-- CARD TOP -->

                        <div class="booking-card-top">

                            <div class="booking-title">

                                <div class="sport-icon">
                                    🏀
                                </div>

                                <div>

                                    <span class="booking-label">
                                        BOOKING #<?= e($booking['id']) ?>
                                    </span>

                                    <h3>
                                        <?= e($booking['sport']) ?>
                                    </h3>

                                </div>

                            </div>


                            <span
                                class="status-badge <?= e(statusClass($status)) ?>"
                            >
                                <?= e($status) ?>
                            </span>

                        </div>


                        <!-- BOOKING DETAILS -->

                        <div class="booking-details">

                            <div class="detail-item">

                                <span class="detail-label">
                                    Court
                                </span>

                                <strong>
                                    <?= e($booking['court']) ?>
                                </strong>

                            </div>


                            <div class="detail-item">

                                <span class="detail-label">
                                    Date
                                </span>

                                <strong>
                                    <?= e(
                                        formatDate(
                                            $booking['booking_date']
                                        )
                                    ) ?>
                                </strong>

                            </div>


                            <div class="detail-item">

                                <span class="detail-label">
                                    Start Time
                                </span>

                                <strong>
                                    <?= e(
                                        formatTime(
                                            $booking['start_time']
                                        )
                                    ) ?>
                                </strong>

                            </div>


                            <div class="detail-item">

                                <span class="detail-label">
                                    Duration
                                </span>

                                <strong>
                                    <?= $duration ?>
                                    hour<?= $duration == 1 ? '' : 's' ?>
                                </strong>

                            </div>

                        </div>


                        <!-- PAYMENT -->

                        <div class="payment-area">

                            <div class="payment-header">

                                <h4>
                                    Payment Information
                                </h4>

                                <span
                                    class="payment-status <?= e(paymentClass($paymentStatus)) ?>"
                                >
                                    <?= e($paymentStatus) ?>
                                </span>

                            </div>


                            <div class="payment-details">

                                <div>

                                    <span>
                                        Payment Method
                                    </span>

                                    <strong>
                                        <?= e(
                                            $booking['payment_method']
                                            ?? "—"
                                        ) ?>
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Amount Paid
                                    </span>

                                    <strong>
                                        ₱<?= number_format(
                                            $amountPaid,
                                            2
                                        ) ?>
                                    </strong>

                                </div>


                                <div>

                                    <span>
                                        Balance
                                    </span>

                                    <strong
                                        class="<?= $balance > 0 ? 'has-balance' : 'fully-paid' ?>"
                                    >
                                        ₱<?= number_format(
                                            $balance,
                                            2
                                        ) ?>
                                    </strong>

                                </div>

                            </div>


                            <?php if (
                                !empty(
                                    $booking['gcash_reference']
                                )
                            ): ?>

                                <div class="reference-number">

                                    <span>
                                        GCash Reference
                                    </span>

                                    <strong>
                                        <?= e(
                                            $booking[
                                                'gcash_reference'
                                            ]
                                        ) ?>
                                    </strong>

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- CARD FOOTER -->

                        <div class="booking-card-footer">

                            <span class="booking-date-created">

                                <?php if (
                                    !empty(
                                        $booking['payment_date']
                                    )
                                ): ?>

                                    Payment:
                                    <?= e(
                                        date(
                                            "M d, Y",
                                            strtotime(
                                                $booking[
                                                    'payment_date'
                                                ]
                                            )
                                        )
                                    ) ?>

                                <?php else: ?>

                                    Reservation recorded

                                <?php endif; ?>

                            </span>


                            <div class="booking-actions">

                                <?php
                                $statusLower =
                                    strtolower(
                                        trim(
                                            (string)$status
                                        )
                                    );
                                ?>

                                <?php if (
                                    $statusLower ===
                                    "confirmed"
                                ): ?>

                                    <span class="action-confirmed">
                                        ✓ Reservation Confirmed
                                    </span>

                                <?php elseif (
                                    $statusLower ===
                                    "pending"
                                ): ?>

                                    <span class="action-pending">
                                        ⏳ Awaiting Confirmation
                                    </span>

                                <?php elseif (
                                    $statusLower ===
                                    "cancelled" ||
                                    $statusLower ===
                                    "canceled"
                                ): ?>

                                    <span class="action-cancelled">
                                        Reservation Cancelled
                                    </span>

                                <?php else: ?>

                                    <span>
                                        <?= e($status) ?>
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>


    <!-- =================================================
         CTA
    ================================================== -->

    <section class="bottom-cta">

        <div>

            <span>
                READY FOR YOUR NEXT GAME?
            </span>

            <h2>
                Book your next court with Court22.
            </h2>

        </div>

        <a
            href="booking.php"
            class="cta-button"
        >
            Book a Court →
        </a>

    </section>

</main>


<!-- =====================================================
     FOOTER
====================================================== -->

<footer class="site-footer">

    <div>

        <strong>
            COURT<span>22</span>
        </strong>

        <p>
            Your game. Your court. Your time.
        </p>

    </div>

    <p>
        © <?= date("Y") ?> Court22. All rights reserved.
    </p>

</footer>


</div>

</body>

</html>
