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
   HELPER FUNCTIONS
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}


function formatDateDashboard($date)
{
    if (empty($date)) {
        return "-";
    }

    return date(
        "M d, Y",
        strtotime($date)
    );
}


function formatTimeDashboard($time)
{
    if (empty($time)) {
        return "-";
    }

    return date(
        "h:i A",
        strtotime($time)
    );
}


function bookingStatusClass($status)
{
    switch ($status) {

        case "Confirmed":
            return "status-confirmed";

        case "Pending":
            return "status-pending";

        case "Cancelled":
            return "status-cancelled";

        default:
            return "status-default";
    }
}


function paymentStatusClass($status)
{
    switch ($status) {

        case "Paid":
            return "payment-paid";

        case "Downpayment":
            return "payment-downpayment";

        case "Pending":
            return "payment-pending";

        default:
            return "payment-default";
    }
}


/* =========================================================
   GET CURRENT USER
========================================================= */

$user = null;

$stmt = $conn->prepare("
    SELECT
        id,
        first_name,
        last_name,
        email
    FROM users
    WHERE id = ?
    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $user = $result->fetch_assoc();

    $stmt->close();
}


if (!$user) {
    session_destroy();

    header("Location: login.php");
    exit();
}


$firstName = $user['first_name'] ?? "Player";
$lastName  = $user['last_name'] ?? "";
$email     = $user['email'] ?? "";

$fullName = trim(
    $firstName . " " . $lastName
);

$initial = strtoupper(
    substr($firstName, 0, 1)
);


/* =========================================================
   BOOKING STATISTICS
========================================================= */

$totalBookings = 0;
$confirmedBookings = 0;
$pendingBookings = 0;
$cancelledBookings = 0;


$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_bookings,

        SUM(
            CASE
                WHEN status = 'Confirmed'
                THEN 1
                ELSE 0
            END
        ) AS confirmed_bookings,

        SUM(
            CASE
                WHEN status = 'Pending'
                THEN 1
                ELSE 0
            END
        ) AS pending_bookings,

        SUM(
            CASE
                WHEN status = 'Cancelled'
                THEN 1
                ELSE 0
            END
        ) AS cancelled_bookings

    FROM bookings

    WHERE user_id = ?
");

if ($stmt) {

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $stats = $result->fetch_assoc();

    $stmt->close();

    if ($stats) {

        $totalBookings =
            (int) ($stats['total_bookings'] ?? 0);

        $confirmedBookings =
            (int) ($stats['confirmed_bookings'] ?? 0);

        $pendingBookings =
            (int) ($stats['pending_bookings'] ?? 0);

        $cancelledBookings =
            (int) ($stats['cancelled_bookings'] ?? 0);
    }
}


/* =========================================================
   TOTAL PAID
========================================================= */

$totalPaid = 0;

$stmt = $conn->prepare("
    SELECT
        COALESCE(
            SUM(p.amount_paid),
            0
        ) AS total_paid

    FROM payments p

    INNER JOIN bookings b
        ON p.booking_id = b.id

    WHERE b.user_id = ?
");

if ($stmt) {

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $paymentStats = $result->fetch_assoc();

    $stmt->close();

    if ($paymentStats) {

        $totalPaid =
            (float) ($paymentStats['total_paid'] ?? 0);
    }
}


/* =========================================================
   UPCOMING BOOKING
========================================================= */

$upcomingBooking = null;

$stmt = $conn->prepare("
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
        p.gcash_reference

    FROM bookings b

    LEFT JOIN payments p
        ON p.booking_id = b.id

    WHERE b.user_id = ?

      AND b.booking_date >= CURDATE()

      AND b.status IN (
          'Confirmed',
          'Pending'
      )

    ORDER BY
        b.booking_date ASC,
        b.start_time ASC,
        b.id ASC

    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $upcomingBooking =
        $result->fetch_assoc();

    $stmt->close();
}


/* =========================================================
   AVAILABLE COURTS
========================================================= */

$availableCourts = [];

$courtQuery = $conn->query("
    SELECT
        c.id,
        c.sport_id,
        c.court_name,
        c.status,

        s.name AS sport_name,
        s.price_per_hour

    FROM courts c

    INNER JOIN sports s
        ON c.sport_id = s.id

    WHERE c.status = 'Available'

      AND s.status = 'Active'

    ORDER BY
        s.id ASC,
        c.id ASC

    LIMIT 4
");

if ($courtQuery) {

    while ($row = $courtQuery->fetch_assoc()) {

        $availableCourts[] = $row;

    }
}


/* =========================================================
   RECENT BOOKINGS
========================================================= */

$recentBookings = [];

$stmt = $conn->prepare("
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
        p.payment_status

    FROM bookings b

    LEFT JOIN payments p
        ON p.booking_id = b.id

    WHERE b.user_id = ?

    ORDER BY
        b.id DESC

    LIMIT 5
");

if ($stmt) {

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $recentBookings[] = $row;

    }

    $stmt->close();
}


/* =========================================================
   REVIEWS
========================================================= */

$reviews = [];

$averageRating = 0;
$totalReviews = 0;


/* REVIEW STATISTICS */

$reviewStatsQuery = $conn->query("
    SELECT
        COUNT(*) AS total_reviews,
        COALESCE(
            AVG(rating),
            0
        ) AS average_rating

    FROM reviews

    WHERE status = 'Visible'
");

if ($reviewStatsQuery) {

    $reviewStats =
        $reviewStatsQuery->fetch_assoc();

    if ($reviewStats) {

        $totalReviews =
            (int) $reviewStats['total_reviews'];

        $averageRating =
            (float) $reviewStats['average_rating'];
    }
}


/* RECENT REVIEWS */

$reviewQuery = $conn->query("
    SELECT
        r.id,
        r.rating,
        r.comment,
        r.created_at,

        u.first_name,
        u.last_name

    FROM reviews r

    INNER JOIN users u
        ON r.user_id = u.id

    WHERE r.status = 'Visible'

    ORDER BY
        r.created_at DESC

    LIMIT 3
");

if ($reviewQuery) {

    while ($row = $reviewQuery->fetch_assoc()) {

        $reviews[] = $row;

    }
}


/* =========================================================
   CHECK IF USER CAN REVIEW A BOOKING
========================================================= */

$reviewedBookingIds = [];

$stmt = $conn->prepare("
    SELECT booking_id
    FROM reviews
    WHERE user_id = ?
");

if ($stmt) {

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $reviewedBookingIds[] =
            (int) $row['booking_id'];

    }

    $stmt->close();
}


/* =========================================================
   FIND REVIEWABLE BOOKING
========================================================= */

$reviewableBookingId = 0;

$stmt = $conn->prepare("
    SELECT id
    FROM bookings
    WHERE user_id = ?
      AND status = 'Confirmed'
    ORDER BY booking_date DESC, id DESC
");

if ($stmt) {

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $bookingId =
            (int) $row['id'];

        if (!in_array(
            $bookingId,
            $reviewedBookingIds,
            true
        )) {

            $reviewableBookingId =
                $bookingId;

            break;
        }
    }

    $stmt->close();
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

    <title>Dashboard | Court22</title>

    <link
        rel="stylesheet"
        href="css/dashboard.css"
    >

</head>

<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="site-header">

    <div class="header-inner">

        <a
            href="dashboard.php"
            class="logo"
        >
            COURT<span>22</span>
        </a>


        <nav>

            <a
                href="dashboard.php"
                class="active"
            >
                Dashboard
            </a>

            <a href="court.php">
                Courts
            </a>

            <a href="booking.php">
                Book a Court
            </a>

            <a href="mybooking.php">
                My Bookings
            </a>

            <a href="profile.php">
                Profile
            </a>

            <a
                href="logout.php"
                class="logout-link"
            >
                Logout
            </a>

        </nav>


        <div class="mobile-user">

            <div class="header-avatar">
                <?= e($initial); ?>
            </div>

        </div>

    </div>

</header>


<!-- =====================================================
     MAIN DASHBOARD
===================================================== -->

<main class="dashboard-page">

    <div class="dashboard-container">


        <!-- =================================================
             WELCOME
        ================================================= -->

        <section class="welcome-section">

            <div class="welcome-content">

                <span class="welcome-label">
                    COURT22 DASHBOARD
                </span>

                <h1>
                    Welcome back,
                    <span>
                        <?= e($firstName); ?>
                    </span>
                </h1>

                <p>
                    Ready to play?
                    Manage your bookings and find
                    your next court.
                </p>

            </div>


            <a
                href="booking.php"
                class="primary-btn"
            >
                <span>+</span>
                Book a Court
            </a>

        </section>


        <!-- =================================================
             STATISTICS
        ================================================= -->

        <section class="stats-grid">


            <!-- TOTAL BOOKINGS -->

            <div class="stat-card">

                <div class="stat-icon">
                    <span>01</span>
                </div>

                <div class="stat-content">

                    <span class="stat-label">
                        Total Bookings
                    </span>

                    <strong>
                        <?= $totalBookings; ?>
                    </strong>

                </div>

            </div>


            <!-- CONFIRMED -->

            <div class="stat-card">

                <div class="stat-icon">
                    <span>02</span>
                </div>

                <div class="stat-content">

                    <span class="stat-label">
                        Confirmed
                    </span>

                    <strong>
                        <?= $confirmedBookings; ?>
                    </strong>

                </div>

            </div>


            <!-- PENDING -->

            <div class="stat-card">

                <div class="stat-icon">
                    <span>03</span>
                </div>

                <div class="stat-content">

                    <span class="stat-label">
                        Pending
                    </span>

                    <strong>
                        <?= $pendingBookings; ?>
                    </strong>

                </div>

            </div>


            <!-- TOTAL PAID -->

            <div class="stat-card">

                <div class="stat-icon">
                    <span>₱</span>
                </div>

                <div class="stat-content">

                    <span class="stat-label">
                        Total Paid
                    </span>

                    <strong>
                        ₱<?= number_format(
                            $totalPaid,
                            2
                        ); ?>
                    </strong>

                </div>

            </div>

        </section>


        <!-- =================================================
             MAIN GRID
        ================================================= -->

        <section class="dashboard-main-grid">


            <!-- =============================================
                 UPCOMING BOOKING
            ============================================== -->

            <div class="upcoming-card">

                <div class="section-top">

                    <div>

                        <span class="section-label">
                            NEXT SESSION
                        </span>

                        <h2>
                            Upcoming Booking
                        </h2>

                    </div>

                    <a
                        href="mybooking.php"
                        class="text-link"
                    >
                        View All →
                    </a>

                </div>


                <?php if ($upcomingBooking): ?>

                    <div class="upcoming-content">


                        <div class="upcoming-sport">

                            <span class="sport-number">
                                <?= str_pad(
                                    (string)$upcomingBooking['id'],
                                    3,
                                    "0",
                                    STR_PAD_LEFT
                                ); ?>
                            </span>

                            <div>

                                <span class="small-label">
                                    SPORT
                                </span>

                                <h3>
                                    <?= e(
                                        $upcomingBooking['sport']
                                    ); ?>
                                </h3>

                            </div>

                        </div>


                        <div class="upcoming-status">

                            <span
                                class="status-badge
                                <?= e(
                                    bookingStatusClass(
                                        $upcomingBooking['status']
                                    )
                                ); ?>"
                            >
                                <?= e(
                                    $upcomingBooking['status']
                                ); ?>
                            </span>

                        </div>


                        <div class="upcoming-details">


                            <div class="upcoming-detail">

                                <span>
                                    COURT
                                </span>

                                <strong>
                                    <?= e(
                                        $upcomingBooking['court']
                                    ); ?>
                                </strong>

                            </div>


                            <div class="upcoming-detail">

                                <span>
                                    DATE
                                </span>

                                <strong>
                                    <?= e(
                                        formatDateDashboard(
                                            $upcomingBooking['booking_date']
                                        )
                                    ); ?>
                                </strong>

                            </div>


                            <div class="upcoming-detail">

                                <span>
                                    TIME
                                </span>

                                <strong>
                                    <?= e(
                                        formatTimeDashboard(
                                            $upcomingBooking['start_time']
                                        )
                                    ); ?>
                                </strong>

                            </div>


                            <div class="upcoming-detail">

                                <span>
                                    DURATION
                                </span>

                                <strong>
                                    <?= (int)
                                        $upcomingBooking['duration']; ?>
                                    hr
                                </strong>

                            </div>

                        </div>


                        <div class="upcoming-footer">

                            <div class="payment-info">

                                <span>
                                    PAYMENT
                                </span>

                                <strong>
                                    <?= e(
                                        $upcomingBooking[
                                            'payment_status'
                                        ] ?? "Pending"
                                    ); ?>
                                </strong>

                            </div>


                            <a
                                href="mybooking.php"
                                class="outline-btn"
                            >
                                View Booking
                            </a>

                        </div>

                    </div>

                <?php else: ?>

                    <div class="empty-upcoming">

                        <div class="empty-icon">
                            +
                        </div>

                        <h3>
                            No upcoming bookings
                        </h3>

                        <p>
                            Book your next court session
                            and get ready to play.
                        </p>

                        <a
                            href="booking.php"
                            class="primary-btn small"
                        >
                            Book a Court
                        </a>

                    </div>

                <?php endif; ?>

            </div>


            <!-- =============================================
                 QUICK ACTIONS
            ============================================== -->

            <div class="quick-actions-card">

                <div class="section-top">

                    <div>

                        <span class="section-label">
                            SHORTCUTS
                        </span>

                        <h2>
                            Quick Actions
                        </h2>

                    </div>

                </div>


                <div class="quick-actions">


                    <a
                        href="booking.php"
                        class="quick-action"
                    >

                        <div class="quick-number">
                            01
                        </div>

                        <div>

                            <strong>
                                Book a Court
                            </strong>

                            <span>
                                Reserve your next session
                            </span>

                        </div>

                        <span class="arrow">
                            →
                        </span>

                    </a>


                    <a
                        href="court.php"
                        class="quick-action"
                    >

                        <div class="quick-number">
                            02
                        </div>

                        <div>

                            <strong>
                                Explore Courts
                            </strong>

                            <span>
                                See available courts
                            </span>

                        </div>

                        <span class="arrow">
                            →
                        </span>

                    </a>


                    <a
                        href="mybooking.php"
                        class="quick-action"
                    >

                        <div class="quick-number">
                            03
                        </div>

                        <div>

                            <strong>
                                My Bookings
                            </strong>

                            <span>
                                Manage your reservations
                            </span>

                        </div>

                        <span class="arrow">
                            →
                        </span>

                    </a>


                    <a
                        href="reviews.php"
                        class="quick-action"
                    >

                        <div class="quick-number">
                            04
                        </div>

                        <div>

                            <strong>
                                Player Reviews
                            </strong>

                            <span>
                                Read player feedback
                            </span>

                        </div>

                        <span class="arrow">
                            →
                        </span>

                    </a>


                </div>

            </div>

        </section>


        <!-- =================================================
             AVAILABLE COURTS
        ================================================= -->

        <section class="dashboard-section">

            <div class="section-heading">

                <div>

                    <span class="section-label">
                        PLAY TODAY
                    </span>

                    <h2>
                        Available Courts
                    </h2>

                    <p>
                        Find a court and start your next game.
                    </p>

                </div>


                <a
                    href="court.php"
                    class="view-all-btn"
                >
                    View All Courts →
                </a>

            </div>


            <?php if (!empty($availableCourts)): ?>

                <div class="courts-grid">

                    <?php foreach (
                        $availableCourts
                        as $court
                    ): ?>

                        <article class="dashboard-court-card">


                            <div class="court-visual">

                                <span class="court-lines">
                                    C22
                                </span>

                                <span class="court-status">
                                    AVAILABLE
                                </span>

                            </div>


                            <div class="court-info">

                                <span class="court-sport">
                                    <?= e(
                                        $court['sport_name']
                                    ); ?>
                                </span>

                                <h3>
                                    <?= e(
                                        $court['court_name']
                                    ); ?>
                                </h3>


                                <div class="court-bottom">

                                    <div>

                                        <span>
                                            FROM
                                        </span>

                                        <strong>
                                            ₱<?= number_format(
                                                (float)$court[
                                                    'price_per_hour'
                                                ],
                                                2
                                            ); ?>
                                            <small>/hr</small>
                                        </strong>

                                    </div>


                                    <a
                                        href="booking.php"
                                        class="court-book-btn"
                                    >
                                        Book →
                                    </a>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-section">

                    <div class="empty-icon">
                        —
                    </div>

                    <h3>
                        No courts available right now
                    </h3>

                    <p>
                        Please check again later.
                    </p>

                </div>

            <?php endif; ?>

        </section>


        <!-- =================================================
             RECENT BOOKINGS
        ================================================= -->

        <section class="dashboard-section">

            <div class="section-heading">

                <div>

                    <span class="section-label">
                        ACTIVITY
                    </span>

                    <h2>
                        Recent Bookings
                    </h2>

                </div>


                <a
                    href="mybooking.php"
                    class="view-all-btn"
                >
                    View All Bookings →
                </a>

            </div>


            <?php if (!empty($recentBookings)): ?>

                <div class="booking-table-wrapper">

                    <table class="booking-table">

                        <thead>

                            <tr>

                                <th>
                                    BOOKING
                                </th>

                                <th>
                                    SPORT / COURT
                                </th>

                                <th>
                                    DATE
                                </th>

                                <th>
                                    STATUS
                                </th>

                                <th>
                                    PAYMENT
                                </th>

                                <th>
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach (
                                $recentBookings
                                as $booking
                            ): ?>

                                <tr>


                                    <td>

                                        <span class="booking-id">
                                            #<?= (int)
                                                $booking['id']; ?>
                                        </span>

                                    </td>


                                    <td>

                                        <div class="booking-sport">

                                            <strong>
                                                <?= e(
                                                    $booking['sport']
                                                ); ?>
                                            </strong>

                                            <span>
                                                <?= e(
                                                    $booking['court']
                                                ); ?>
                                            </span>

                                        </div>

                                    </td>


                                    <td>

                                        <div class="booking-date">

                                            <strong>
                                                <?= e(
                                                    formatDateDashboard(
                                                        $booking[
                                                            'booking_date'
                                                        ]
                                                    )
                                                ); ?>
                                            </strong>

                                            <span>
                                                <?= e(
                                                    formatTimeDashboard(
                                                        $booking[
                                                            'start_time'
                                                        ]
                                                    )
                                                ); ?>
                                            </span>

                                        </div>

                                    </td>


                                    <td>

                                        <span
                                            class="status-badge
                                            <?= e(
                                                bookingStatusClass(
                                                    $booking['status']
                                                )
                                            ); ?>"
                                        >
                                            <?= e(
                                                $booking['status']
                                            ); ?>
                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="payment-badge
                                            <?= e(
                                                paymentStatusClass(
                                                    $booking[
                                                        'payment_status'
                                                    ] ?? "Pending"
                                                )
                                            ); ?>"
                                        >
                                            <?= e(
                                                $booking[
                                                    'payment_status'
                                                ] ?? "Pending"
                                            ); ?>
                                        </span>

                                    </td>


                                    <td>

                                        <a
                                            href="mybooking.php"
                                            class="table-action"
                                        >
                                            View
                                        </a>

                                    </td>


                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty-section">

                    <div class="empty-icon">
                        +
                    </div>

                    <h3>
                        No bookings yet
                    </h3>

                    <p>
                        Your booking history will appear here.
                    </p>

                    <a
                        href="booking.php"
                        class="primary-btn small"
                    >
                        Make Your First Booking
                    </a>

                </div>

            <?php endif; ?>

        </section>


        <!-- =================================================
             REVIEWS
        ================================================= -->

        <section class="dashboard-section reviews-section">

            <div class="section-heading">

                <div>

                    <span class="section-label">
                        COMMUNITY
                    </span>

                    <h2>
                        What Players Say
                    </h2>

                    <p>
                        Feedback from the Court22 community.
                    </p>

                </div>


                <div class="review-heading-actions">

                    <div class="average-rating">

                        <strong>
                            <?= number_format(
                                $averageRating,
                                1
                            ); ?>
                        </strong>

                        <span>
                            ★
                        </span>

                    </div>


                    <a
                        href="reviews.php"
                        class="view-all-btn"
                    >
                        View All Reviews →
                    </a>

                </div>

            </div>


            <?php if (!empty($reviews)): ?>

                <div class="reviews-grid">

                    <?php foreach (
                        $reviews
                        as $review
                    ): ?>

                        <article class="review-card">


                            <div class="review-top">


                                <div class="reviewer">

                                    <div class="review-avatar">

                                        <?= e(
                                            strtoupper(
                                                substr(
                                                    $review[
                                                        'first_name'
                                                    ],
                                                    0,
                                                    1
                                                )
                                            )
                                        ); ?>

                                    </div>


                                    <div>

                                        <h3>

                                            <?= e(
                                                $review[
                                                    'first_name'
                                                ] .
                                                " " .
                                                $review[
                                                    'last_name'
                                                ]
                                            ); ?>

                                        </h3>

                                        <span>
                                            <?= e(
                                                formatDateDashboard(
                                                    $review[
                                                        'created_at'
                                                    ]
                                                )
                                            ); ?>
                                        </span>

                                    </div>

                                </div>


                                <div class="review-stars">

                                    <?php

                                    $reviewRating =
                                        (int)
                                        $review['rating'];

                                    for (
                                        $i = 1;
                                        $i <= 5;
                                        $i++
                                    ):

                                    ?>

                                        <span
                                            class="<?= $i <= $reviewRating
                                                ? 'filled'
                                                : ''; ?>"
                                        >
                                            ★
                                        </span>

                                    <?php endfor; ?>

                                </div>

                            </div>


                            <p class="review-comment">

                                "<?= e(
                                    $review['comment']
                                ); ?>"

                            </p>


                        </article>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-section">

                    <div class="empty-icon">
                        ★
                    </div>

                    <h3>
                        No reviews yet
                    </h3>

                    <p>
                        Be the first player to share
                        your Court22 experience.
                    </p>

                    <?php if ($reviewableBookingId > 0): ?>

                        <a
                            href="review.php?booking_id=<?= $reviewableBookingId; ?>"
                            class="primary-btn small"
                        >
                            ★ Leave a Review
                        </a>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </section>


        <!-- =================================================
             BOTTOM CTA
        ================================================= -->

        <section class="bottom-cta">

            <div>

                <span class="cta-label">
                    COURT22
                </span>

                <h2>
                    Ready for your next game?
                </h2>

                <p>
                    Find your court, choose your schedule,
                    and let's play.
                </p>

            </div>


            <a
                href="booking.php"
                class="cta-btn"
            >
                Book Your Court →
            </a>

        </section>


    </div>

</main>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="site-footer">

    <div class="footer-inner">

        <div>

            <a
                href="dashboard.php"
                class="footer-logo"
            >
                COURT<span>22</span>
            </a>

            <p>
                Your court. Your game.
            </p>

        </div>


        <div class="footer-links">

            <a href="court.php">
                Courts
            </a>

            <a href="booking.php">
                Book
            </a>

            <a href="mybooking.php">
                Bookings
            </a>

            <a href="reviews.php">
                Reviews
            </a>

        </div>

    </div>


    <div class="footer-bottom">

        <p>
            © <?= date("Y"); ?> Court22.
            All rights reserved.
        </p>

    </div>

</footer>


</body>

</html>