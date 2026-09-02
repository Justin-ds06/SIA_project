<?php

session_start();

require_once "config.php";

/* =========================================================
   LOGIN CHECK
========================================================= */

if (!isset($_SESSION["user_id"]) || empty($_SESSION["user_id"])) {

    header("Location: login.php");
    exit();

}

$user_id = (int) $_SESSION["user_id"];

$reviews = [];
$reviewedBookingIds = [];

/* =========================================================
   SUCCESS MESSAGE
========================================================= */

$success = trim(
    $_GET["success"] ?? ""
);


/* =========================================================
   LOAD USER REVIEWS
========================================================= */

$stmt = $conn->prepare("
    SELECT
        r.id,
        r.booking_id,
        r.rating,
        r.comment,
        r.created_at,
        r.updated_at,
        b.sport,
        b.court,
        b.booking_date,
        b.start_time,
        b.duration
    FROM reviews r
    INNER JOIN bookings b
        ON b.id = r.booking_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");

if ($stmt) {

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $reviews[] = $row;

        $reviewedBookingIds[] =
            (int) $row["booking_id"];
    }

    $stmt->close();
}


/* =========================================================
   LOAD ELIGIBLE BOOKINGS
========================================================= */

$eligibleBookings = [];

$stmt = $conn->prepare("
    SELECT
        b.id,
        b.sport,
        b.court,
        b.booking_date,
        b.start_time,
        b.duration,
        b.status
    FROM bookings b
    WHERE b.user_id = ?
      AND b.status IN ('Confirmed', 'Completed')
    ORDER BY
        b.booking_date DESC,
        b.start_time DESC
");

if ($stmt) {

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        if (
            !in_array(
                (int) $row["id"],
                $reviewedBookingIds,
                true
            )
        ) {

            $eligibleBookings[] = $row;

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

<title>Court22 - My Reviews</title>

<link
    rel="stylesheet"
    href="css/reviews.css"
>


</head>

<body>

<header class="site-header">


<a
    href="dashboard.php"
    class="logo"
>
    COURT<span>22</span>
</a>


<nav>

    <a href="dashboard.php">
        Dashboard
    </a>

    <a href="mybooking.php">
        My Bookings
    </a>

    <a
        href="reviews.php"
        class="active"
    >
        Reviews
    </a>

    <a href="profile.php">
        Profile
    </a>

    <a href="logout.php">
        Logout
    </a>

</nav>


</header>

<main class="reviews-container">


<!-- =============================================
     PAGE HEADER
============================================== -->

<div class="page-heading">

    <div>

        <span class="eyebrow">
            COURT22
        </span>

        <h1>
            My Reviews
        </h1>

        <p>
            Share your experience and help us improve.
        </p>

    </div>

    <a
        href="dashboard.php"
        class="back-btn"
    >
        Back to Dashboard
    </a>

</div>


<!-- =============================================
     SUCCESS
============================================== -->

<?php if (!empty($success)): ?>

    <div class="alert success">

        <span class="alert-icon">
            ✓
        </span>

        <?= htmlspecialchars($success) ?>

    </div>

<?php endif; ?>


<!-- =============================================
     WRITE REVIEW SECTION
============================================== -->

<?php if (!empty($eligibleBookings)): ?>

    <section class="review-section">

        <div class="section-heading">

            <div>

                <span class="section-label">
                    SHARE YOUR EXPERIENCE
                </span>

                <h2>
                    Bookings You Can Review
                </h2>

            </div>

        </div>


        <div class="booking-review-grid">

            <?php foreach ($eligibleBookings as $booking): ?>

                <div class="booking-review-card">

                    <div class="booking-card-top">

                        <div>

                            <span class="sport-label">
                                <?= htmlspecialchars(
                                    $booking["sport"]
                                ) ?>
                            </span>

                            <h3>
                                <?= htmlspecialchars(
                                    $booking["court"]
                                ) ?>
                            </h3>

                        </div>

                        <span class="status-badge">
                            <?= htmlspecialchars(
                                $booking["status"]
                            ) ?>
                        </span>

                    </div>


                    <div class="booking-details">

                        <div>

                            <span>
                                Date
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $booking["booking_date"]
                                ) ?>
                            </strong>

                        </div>


                        <div>

                            <span>
                                Time
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $booking["start_time"]
                                ) ?>
                            </strong>

                        </div>


                        <div>

                            <span>
                                Duration
                            </span>

                            <strong>
                                <?= (int)
                                    $booking["duration"] ?>
                                hour(s)
                            </strong>

                        </div>

                    </div>


                    <a
                        href="review.php?booking_id=<?= (int)$booking["id"] ?>"
                        class="review-btn"
                    >
                        ★ Write a Review
                    </a>

                </div>

            <?php endforeach; ?>

        </div>

    </section>

<?php endif; ?>


<!-- =============================================
     EXISTING REVIEWS
============================================== -->

<section class="review-section">

    <div class="section-heading">

        <div>

            <span class="section-label">
                YOUR FEEDBACK
            </span>

            <h2>
                Reviews You've Submitted
            </h2>

        </div>

    </div>


    <?php if (empty($reviews)): ?>

        <div class="empty-state">

            <div class="empty-icon">
                ★
            </div>

            <h3>
                No reviews yet
            </h3>

            <p>
                After your court booking, come back here
                and share your experience.
            </p>

        </div>

    <?php else: ?>


        <div class="reviews-list">

            <?php foreach ($reviews as $review): ?>

                <article class="review-card">

                    <div class="review-top">

                        <div>

                            <span class="sport-label">
                                <?= htmlspecialchars(
                                    $review["sport"]
                                ) ?>
                            </span>

                            <h3>
                                <?= htmlspecialchars(
                                    $review["court"]
                                ) ?>
                            </h3>

                        </div>


                        <div class="rating">

                            <?php

                            $rating =
                                (int) $review["rating"];

                            for (
                                $i = 1;
                                $i <= 5;
                                $i++
                            ):

                            ?>

                                <span
                                    class="<?= $i <= $rating
                                        ? "filled"
                                        : "" ?>"
                                >
                                    ★
                                </span>

                            <?php endfor; ?>

                        </div>

                    </div>


                    <div class="review-date">

                        Booking date:
                        <?= htmlspecialchars(
                            $review["booking_date"]
                        ) ?>

                        <?php if (
                            !empty(
                                $review["updated_at"]
                            )
                        ): ?>

                            · Updated

                        <?php endif; ?>

                    </div>


                    <p class="review-comment">

                        <?= nl2br(
                            htmlspecialchars(
                                $review["comment"]
                            )
                        ) ?>

                    </p>


                    <div class="review-footer">

                        <span>
                            <?= htmlspecialchars(
                                date(
                                    "M d, Y",
                                    strtotime(
                                        $review["created_at"]
                                    )
                                )
                            ) ?>
                        </span>


                        <a
                            href="review.php?booking_id=<?= (int)$review["booking_id"] ?>"
                            class="edit-review"
                        >
                            Edit Review
                        </a>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>


</main>

</body>

</html>
