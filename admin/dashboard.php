<?php

session_start();

require_once __DIR__ . "/../config.php";


if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php");
    exit();
}

$adminName = $_SESSION["admin_name"] ?? "Administrator";


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

function getCount($conn, $sql)
{
    $result = $conn->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int)($row["total"] ?? 0);
}


$totalUsers = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM users"
);

$totalBookings = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM bookings"
);

$confirmedBookings = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM bookings WHERE status = 'Confirmed'"
);

$pendingBookings = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM bookings WHERE status = 'Pending'"
);

$totalSports = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM sports"
);

$activeSports = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM sports WHERE status = 'Active'"
);

$totalCourts = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM courts"
);

$availableCourts = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM courts WHERE status = 'Available'"
);

$totalReviews = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM reviews"
);


/*
|--------------------------------------------------------------------------
| PAYMENTS
|--------------------------------------------------------------------------
*/

$totalPaid = 0;

$paymentQuery = $conn->query("
    SELECT COALESCE(SUM(amount_paid), 0) AS total
    FROM payments
");

if ($paymentQuery) {

    $paymentRow = $paymentQuery->fetch_assoc();

    $totalPaid = (float)$paymentRow["total"];
}


/*
|--------------------------------------------------------------------------
| RECENT BOOKINGS
|--------------------------------------------------------------------------
*/

$recentBookings = [];

$query = $conn->query("
    SELECT
        id,
        user_id,
        sport,
        court,
        booking_date,
        start_time,
        duration,
        status
    FROM bookings
    ORDER BY id DESC
    LIMIT 8
");

if ($query) {

    while ($row = $query->fetch_assoc()) {
        $recentBookings[] = $row;
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

<title>Court22 Admin Dashboard</title>

<link
    rel="stylesheet"
    href="css/admin.css"
>

</head>

<body>

<div class="admin-layout">


    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="sidebar-logo">
            COURT<span>22</span>
        </div>

        <div class="admin-label">
            ADMIN PANEL
        </div>

        <nav class="sidebar-nav">

            <a
                href="dashboard.php"
                class="active"
            >
                <span>▣</span>
                Dashboard
            </a>

            <a href="bookings.php">
                <span>▤</span>
                Bookings
            </a>

            <a href="users.php">
                <span>♙</span>
                Users
            </a>

            <a href="sports.php">
                <span>⚽</span>
                Sports
            </a>

            <a href="courts.php">
                <span>▦</span>
                Courts
            </a>

            <a href="payments.php">
                <span>₱</span>
                Payments
            </a>

            <a href="reviews.php">
                <span>★</span>
                Reviews
            </a>

        </nav>

        <div class="sidebar-bottom">

            <div class="admin-user">

                <strong>
                    <?= htmlspecialchars($adminName) ?>
                </strong>

                <small>
                    Administrator
                </small>

            </div>

            <a
                href="logout.php"
                class="logout-link"
            >
                Logout
            </a>

        </div>

    </aside>


    <?php include __DIR__ . "/sidebar.php"; ?>

    <main class="admin-main">

        <header class="topbar">

            <div>

                <h1>
                    Dashboard
                </h1>

                <p>
                    Welcome back,
                    <?= htmlspecialchars($adminName) ?>.
                </p>

            </div>

            <div class="topbar-brand">
                COURT22
            </div>

        </header>


        <!-- STAT CARDS -->

        <section class="stats-grid">

            <div class="stat-card">

                <div class="stat-icon">
                    👥
                </div>

                <div>
                    <span>Total Users</span>
                    <strong><?= $totalUsers ?></strong>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    📅
                </div>

                <div>
                    <span>Total Bookings</span>
                    <strong><?= $totalBookings ?></strong>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ✓
                </div>

                <div>
                    <span>Confirmed</span>
                    <strong><?= $confirmedBookings ?></strong>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ⏳
                </div>

                <div>
                    <span>Pending</span>
                    <strong><?= $pendingBookings ?></strong>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    🏟
                </div>

                <div>
                    <span>Total Courts</span>
                    <strong><?= $totalCourts ?></strong>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ✓
                </div>

                <div>
                    <span>Available Courts</span>
                    <strong><?= $availableCourts ?></strong>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ⚽
                </div>

                <div>
                    <span>Active Sports</span>
                    <strong><?= $activeSports ?></strong>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ★
                </div>

                <div>
                    <span>Reviews</span>
                    <strong><?= $totalReviews ?></strong>
                </div>

            </div>

        </section>


        <!-- REVENUE -->

        <section class="revenue-card">

            <div>

                <span>
                    Total Amount Paid
                </span>

                <h2>
                    ₱<?= number_format($totalPaid, 2) ?>
                </h2>

                <p>
                    Recorded payments from Court22 bookings
                </p>

            </div>

            <a
                href="payments.php"
                class="btn primary"
            >
                View Payments
            </a>

        </section>


        <!-- RECENT BOOKINGS -->

        <section class="content-card">

            <div class="content-header">

                <div>

                    <h2>
                        Recent Bookings
                    </h2>

                    <p>
                        Latest Court22 reservations
                    </p>

                </div>

                <a
                    href="bookings.php"
                    class="btn outline"
                >
                    View All
                </a>

            </div>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>ID</th>
                            <th>Sport</th>
                            <th>Court</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Duration</th>
                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($recentBookings)): ?>

                        <tr>

                            <td
                                colspan="7"
                                class="empty"
                            >
                                No bookings found.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($recentBookings as $booking): ?>

                            <tr>

                                <td>
                                    #<?= (int)$booking["id"] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($booking["sport"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($booking["court"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($booking["booking_date"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($booking["start_time"]) ?>
                                </td>

                                <td>
                                    <?= (int)$booking["duration"] ?> hr
                                </td>

                                <td>

                                    <span class="status
                                    <?= strtolower(
                                        htmlspecialchars($booking["status"])
                                    ) ?>">

                                        <?= htmlspecialchars($booking["status"]) ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

</body>
</html>