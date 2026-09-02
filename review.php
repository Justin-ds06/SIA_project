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

$error = "";
$success = "";

/* =========================================================
   GET BOOKING ID
========================================================= */

$booking_id = (int) ($_GET["booking_id"] ?? $_POST["booking_id"] ?? 0);

if ($booking_id <= 0) {
    header("Location: reviews.php");
    exit();
}

/* =========================================================
   GET BOOKING
========================================================= */

$booking = null;

$stmt = $conn->prepare("
    SELECT
        id,
        sport,
        court,
        booking_date,
        start_time,
        duration,
        status
    FROM bookings
    WHERE id = ?
      AND user_id = ?
    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param(
        "ii",
        $booking_id,
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $booking = $result->fetch_assoc();

    $stmt->close();
}

if (!$booking) {
    header("Location: reviews.php");
    exit();
}

/* =========================================================
   CHECK IF ALREADY REVIEWED
========================================================= */

$existingReview = null;

$stmt = $conn->prepare("
    SELECT
        id,
        rating,
        comment
    FROM reviews
    WHERE booking_id = ?
      AND user_id = ?
    LIMIT 1
");

if ($stmt) {

    $stmt->bind_param(
        "ii",
        $booking_id,
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $existingReview = $result->fetch_assoc();

    $stmt->close();
}

/* =========================================================
   SUBMIT REVIEW
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $rating = (int) ($_POST["rating"] ?? 0);

    $comment = trim(
        $_POST["comment"] ?? ""
    );

    /* =============================================
       VALIDATION
    ============================================= */

    if ($rating < 1 || $rating > 5) {

        $error = "Please select a rating from 1 to 5.";

    } elseif (empty($comment)) {

        $error = "Please write a review.";

    } elseif (strlen($comment) < 5) {

        $error = "Your review must contain at least 5 characters.";

    } elseif (strlen($comment) > 1000) {

        $error = "Your review cannot exceed 1000 characters.";

    }

    /* =============================================
       ONLY ALLOW COMPLETED/CONFIRMED BOOKINGS
    ============================================= */

    if (empty($error)) {

        if (
            !in_array(
                $booking["status"],
                ["Confirmed", "Completed"],
                true
            )
        ) {

            $error =
                "You can only review a confirmed or completed booking.";

        }

    }

    /* =============================================
       INSERT REVIEW
    ============================================= */

    if (empty($error)) {

        if ($existingReview) {

            $stmt = $conn->prepare("
                UPDATE reviews
                SET
                    rating = ?,
                    comment = ?
                WHERE id = ?
                  AND user_id = ?
            ");

            if (!$stmt) {

                $error =
                    "Database error: " .
                    $conn->error;

            } else {

                $review_id =
                    (int) $existingReview["id"];

                $stmt->bind_param(
                    "isii",
                    $rating,
                    $comment,
                    $review_id,
                    $user_id
                );

                if ($stmt->execute()) {

                    $success =
                        "Your review has been updated successfully.";

                } else {

                    $error =
                        "Unable to update your review: " .
                        $stmt->error;
                }

                $stmt->close();
            }

        } else {

            $stmt = $conn->prepare("
                INSERT INTO reviews
                (
                    user_id,
                    booking_id,
                    rating,
                    comment
                )
                VALUES
                (?, ?, ?, ?)
            ");

            if (!$stmt) {

                $error =
                    "Database error: " .
                    $conn->error;

            } else {

                $stmt->bind_param(
                    "iiis",
                    $user_id,
                    $booking_id,
                    $rating,
                    $comment
                );

                if ($stmt->execute()) {

                    $success =
                        "Thank you! Your review has been submitted.";

                } else {

                    $error =
                        "Unable to submit your review: " .
                        $stmt->error;
                }

                $stmt->close();
            }
        }

        /* =============================================
           REDIRECT AFTER SUCCESS
        ============================================= */

        if (!empty($success)) {

            header(
                "Location: reviews.php?success=" .
                urlencode($success)
            );

            exit();
        }
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

<title>Court22 - Write a Review</title>

<link
    rel="stylesheet"
    href="css/reviews.css"
>


</head>

<body>

<header class="site-header">


<a href="dashboard.php" class="logo">
    COURT<span>22</span>
</a>

<nav>

    <a href="dashboard.php">
        Dashboard
    </a>

    <a href="mybooking.php">
        My Bookings
    </a>

    <a href="reviews.php" class="active">
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

<main class="review-container">


<div class="page-heading">

    <h1>
        <?= $existingReview
            ? "Edit Your Review"
            : "Write a Review" ?>
    </h1>

    <p>
        Tell us about your Court22 experience.
    </p>

</div>


<?php if (!empty($error)): ?>

    <div class="alert error">
        <?= htmlspecialchars($error) ?>
    </div>

<?php endif; ?>


<div class="review-layout">

    <!-- =========================================
         BOOKING INFORMATION
    ========================================== -->

    <div class="booking-card">

        <div class="card-label">
            YOUR BOOKING
        </div>

        <h2>
            <?= htmlspecialchars($booking["sport"]) ?>
        </h2>

        <div class="booking-info">

            <div>
                <span>Court</span>
                <strong>
                    <?= htmlspecialchars($booking["court"]) ?>
                </strong>
            </div>

            <div>
                <span>Date</span>
                <strong>
                    <?= htmlspecialchars($booking["booking_date"]) ?>
                </strong>
            </div>

            <div>
                <span>Time</span>
                <strong>
                    <?= htmlspecialchars($booking["start_time"]) ?>
                </strong>
            </div>

            <div>
                <span>Duration</span>
                <strong>
                    <?= (int) $booking["duration"] ?>
                    hour(s)
                </strong>
            </div>

        </div>

    </div>


    <!-- =========================================
         REVIEW FORM
    ========================================== -->

    <form
        method="POST"
        class="review-form"
        id="reviewForm"
    >

        <input
            type="hidden"
            name="booking_id"
            value="<?= $booking_id ?>"
        >


        <div class="form-section">

            <label>
                How was your experience?
            </label>

            <div class="rating-container">

                <input
                    type="radio"
                    id="star5"
                    name="rating"
                    value="5"
                    <?= (
                        (int)($existingReview["rating"] ?? 0) === 5
                    ) ? "checked" : "" ?>
                >

                <label for="star5">★</label>


                <input
                    type="radio"
                    id="star4"
                    name="rating"
                    value="4"
                    <?= (
                        (int)($existingReview["rating"] ?? 0) === 4
                    ) ? "checked" : "" ?>
                >

                <label for="star4">★</label>


                <input
                    type="radio"
                    id="star3"
                    name="rating"
                    value="3"
                    <?= (
                        (int)($existingReview["rating"] ?? 0) === 3
                    ) ? "checked" : "" ?>
                >

                <label for="star3">★</label>


                <input
                    type="radio"
                    id="star2"
                    name="rating"
                    value="2"
                    <?= (
                        (int)($existingReview["rating"] ?? 0) === 2
                    ) ? "checked" : "" ?>
                >

                <label for="star2">★</label>


                <input
                    type="radio"
                    id="star1"
                    name="rating"
                    value="1"
                    <?= (
                        (int)($existingReview["rating"] ?? 0) === 1
                    ) ? "checked" : "" ?>
                >

                <label for="star1">★</label>

            </div>

            <div
                class="rating-text"
                id="ratingText"
            >
                Select a rating
            </div>

        </div>


        <div class="form-section">

            <label for="comment">
                Your Review
            </label>

            <textarea
                name="comment"
                id="comment"
                maxlength="1000"
                placeholder="Tell us about the court, facilities, service, and your overall experience..."
                required
            ><?= htmlspecialchars(
                $existingReview["comment"] ?? ""
            ) ?></textarea>

            <div class="character-count">
                <span id="characterCount">0</span>/1000
            </div>

        </div>


        <div class="form-actions">

            <a
                href="reviews.php"
                class="cancel-btn"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="submit-btn"
                id="submitBtn"
            >
                <?= $existingReview
                    ? "Update Review"
                    : "Submit Review" ?>
            </button>

        </div>

    </form>

</div>


</main>

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const comment =
            document.getElementById("comment");

        const characterCount =
            document.getElementById("characterCount");

        const ratingText =
            document.getElementById("ratingText");

        const submitBtn =
            document.getElementById("submitBtn");

        const ratingLabels = {
            1: "Very Poor",
            2: "Poor",
            3: "Average",
            4: "Good",
            5: "Excellent"
        };


        function updateCharacterCount() {

            characterCount.textContent =
                comment.value.length;

        }


        function updateRatingText() {

            const selected =
                document.querySelector(
                    'input[name="rating"]:checked'
                );

            if (selected) {

                ratingText.textContent =
                    ratingLabels[selected.value];

            } else {

                ratingText.textContent =
                    "Select a rating";

            }

        }


        comment.addEventListener(
            "input",
            updateCharacterCount
        );


        document
            .querySelectorAll(
                'input[name="rating"]'
            )
            .forEach(function (radio) {

                radio.addEventListener(
                    "change",
                    updateRatingText
                );

            });


        document
            .getElementById("reviewForm")
            .addEventListener(
                "submit",
                function () {

                    submitBtn.disabled = true;

                    submitBtn.textContent =
                        "Submitting...";

                }
            );


        updateCharacterCount();

        updateRatingText();

    }
);

</script>

</body>

</html>
