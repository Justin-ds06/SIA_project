<?php

session_start();

require_once "config.php";

/* =========================================================
   COURT22 - COURTS PAGE
   ========================================================= */

/* =========================
   LOGIN CHECK
========================= */

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


/* =========================================================
   GET ALL COURTS
========================================================= */

$courts = [];

$query = $conn->query("
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
    ORDER BY s.id ASC, c.id ASC
");

if ($query) {

    while ($row = $query->fetch_assoc()) {
        $courts[] = $row;
    }
}


/* =========================================================
   COUNT COURTS
========================================================= */

$totalCourts = count($courts);

$availableCourts = 0;
$unavailableCourts = 0;

foreach ($courts as $court) {

    if ($court["status"] === "Available") {
        $availableCourts++;
    } else {
        $unavailableCourts++;
    }
}


/* =========================================================
   GET USER NAME
========================================================= */

$userName = "User";

$userStmt = $conn->prepare("
    SELECT first_name, last_name
    FROM users
    WHERE id = ?
    LIMIT 1
");

if ($userStmt) {

    $user_id = (int) $_SESSION["user_id"];

    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();

    $userResult = $userStmt->get_result();

    if ($userResult->num_rows === 1) {

        $user = $userResult->fetch_assoc();

        $userName = trim(
            $user["first_name"] . " " . $user["last_name"]
        );
    }

    $userStmt->close();
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

<title>Courts | Court22</title>

<link
    rel="stylesheet"
    href="css/court.css"
>


</head>

<body>

<div class="court-page">


<!-- =====================================================
     HEADER
====================================================== -->

<header class="site-header">

    <div class="logo">
        COURT<span>22</span>
    </div>


    <nav>

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="court.php" class="active">
            Courts
        </a>

        <a href="booking.php">
            Book a Court
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

</header>


<!-- =====================================================
     MAIN CONTENT
====================================================== -->

<main class="court-container">


    <!-- =================================================
         PAGE HEADER
    ================================================== -->

    <section class="court-heading">

        <div>

            <span class="eyebrow">
                COURT22 FACILITIES
            </span>

            <h1>
                Our Courts
            </h1>

            <p>
                Choose from our available courts and book your
                preferred sports facility.
            </p>

        </div>


        <a
            href="booking.php"
            class="book-now-btn"
        >
            Book a Court
        </a>

    </section>


    <!-- =================================================
         STATISTICS
    ================================================== -->

    <section class="court-stats">


        <div class="stat-card">

            <div class="stat-icon">
                #
            </div>

            <div>

                <span>
                    Total Courts
                </span>

                <strong>
                    <?php echo $totalCourts; ?>
                </strong>

            </div>

        </div>


        <div class="stat-card available-stat">

            <div class="stat-icon">
                ✓
            </div>

            <div>

                <span>
                    Available
                </span>

                <strong>
                    <?php echo $availableCourts; ?>
                </strong>

            </div>

        </div>


        <div class="stat-card unavailable-stat">

            <div class="stat-icon">
                !
            </div>

            <div>

                <span>
                    Unavailable
                </span>

                <strong>
                    <?php echo $unavailableCourts; ?>
                </strong>

            </div>

        </div>


    </section>


    <!-- =================================================
         COURT LIST
    ================================================== -->

    <section class="courts-section">


        <div class="section-heading">

            <div>

                <h2>
                    All Courts
                </h2>

                <p>
                    Browse all courts currently registered in Court22.
                </p>

            </div>


            <div class="court-count">
                <?php echo $totalCourts; ?>
                <?php echo $totalCourts === 1 ? "Court" : "Courts"; ?>
            </div>

        </div>


        <?php if (empty($courts)): ?>


            <!-- NO COURTS -->

            <div class="empty-state">

                <div class="empty-icon">
                    🏟
                </div>

                <h3>
                    No Courts Available
                </h3>

                <p>
                    There are currently no courts registered in the system.
                </p>

            </div>


        <?php else: ?>


            <!-- COURT GRID -->

            <div class="court-grid">

                <?php foreach ($courts as $court): ?>

                    <?php

                    $isAvailable =
                        $court["status"] === "Available";

                    ?>

                    <article class="court-card">


                        <!-- Card Top -->

                        <div class="court-card-top">

                            <div class="court-number">
                                COURT
                                <?php echo (int) $court["id"]; ?>
                            </div>


                            <?php if ($isAvailable): ?>

                                <span class="status available">
                                    <span class="status-dot"></span>
                                    Available
                                </span>

                            <?php else: ?>

                                <span class="status unavailable">
                                    <span class="status-dot"></span>
                                    Unavailable
                                </span>

                            <?php endif; ?>

                        </div>


                        <!-- Court Icon -->

                        <div class="court-visual">

                            <div class="court-icon">
                                🏟
                            </div>

                        </div>


                        <!-- Court Information -->

                        <div class="court-content">

                            <span class="sport-label">
                                <?php
                                echo htmlspecialchars(
                                    $court["sport_name"]
                                );
                                ?>
                            </span>


                            <h3>
                                <?php
                                echo htmlspecialchars(
                                    $court["court_name"]
                                );
                                ?>
                            </h3>


                            <div class="court-details">


                                <div class="detail">

                                    <span class="detail-label">
                                        Sport
                                    </span>

                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $court["sport_name"]
                                        );
                                        ?>
                                    </strong>

                                </div>


                                <div class="detail">

                                    <span class="detail-label">
                                        Rate
                                    </span>

                                    <strong>
                                        ₱<?php
                                        echo number_format(
                                            (float) $court["price_per_hour"],
                                            2
                                        );
                                        ?>/hr
                                    </strong>

                                </div>


                            </div>


                            <?php if ($isAvailable): ?>

                                <a
                                    href="booking.php"
                                    class="court-btn"
                                >
                                    Book This Court
                                    <span>→</span>
                                </a>

                            <?php else: ?>

                                <button
                                    type="button"
                                    class="court-btn disabled"
                                    disabled
                                >
                                    Currently Unavailable
                                </button>

                            <?php endif; ?>


                        </div>

                    </article>

                <?php endforeach; ?>

            </div>


        <?php endif; ?>


    </section>


    <!-- =================================================
         BOTTOM CTA
    ================================================== -->

    <?php if (!empty($courts)): ?>

        <section class="bottom-cta">

            <div>

                <span>
                    READY TO PLAY?
                </span>

                <h2>
                    Reserve your court today.
                </h2>

                <p>
                    Select your preferred sport, court, date,
                    and time to make your reservation.
                </p>

            </div>


            <a
                href="booking.php"
                class="cta-btn"
            >
                Start Booking
                <span>→</span>
            </a>

        </section>

    <?php endif; ?>


</main>


<!-- =====================================================
     FOOTER
====================================================== -->

<footer class="site-footer">

    <p>
        © <?php echo date("Y"); ?> Court22.
        All rights reserved.
    </p>

</footer>


</div>

</body>

</html>
