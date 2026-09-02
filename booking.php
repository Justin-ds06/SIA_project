<?php

session_start();

require_once "config.php";
require_once "send_email.php";


/* =========================================================
   LOGIN CHECK
========================================================= */

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];


/* =========================================================
   VARIABLES
========================================================= */

$error = "";
$success = "";

$selected_sport_id = "";
$selected_court_id = "";
$booking_date = "";
$start_time = "";
$duration = 1;
$payment_method = "";
$gcash_reference = "";


/* =========================================================
   LOAD SPORTS
========================================================= */

$sports = [];

$sportQuery = $conn->query("
    SELECT
        id,
        name,
        price_per_hour
    FROM sports
    WHERE status = 'Active'
    ORDER BY id ASC
");

if ($sportQuery) {

    while ($row = $sportQuery->fetch_assoc()) {
        $sports[] = $row;
    }

}


/* =========================================================
   LOAD COURTS
========================================================= */

$courts = [];

$courtQuery = $conn->query("
    SELECT
        id,
        sport_id,
        court_name,
        status
    FROM courts
    WHERE status = 'Available'
    ORDER BY sport_id ASC, id ASC
");

if ($courtQuery) {

    while ($row = $courtQuery->fetch_assoc()) {
        $courts[] = $row;
    }

}


/* =========================================================
   POST REQUEST
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $selected_sport_id = (int) ($_POST["sport_id"] ?? 0);
    $selected_court_id = (int) ($_POST["court_id"] ?? 0);

    $booking_date = trim($_POST["booking_date"] ?? "");
    $start_time = trim($_POST["start_time"] ?? "");

    $duration = (int) ($_POST["duration"] ?? 0);

    $payment_method = trim($_POST["payment_method"] ?? "");

    $gcash_reference = trim($_POST["gcash_reference"] ?? "");


    /* =====================================================
       BASIC VALIDATION
    ===================================================== */

    if ($selected_sport_id <= 0) {

        $error = "Please select a sport.";

    } elseif ($selected_court_id <= 0) {

        $error = "Please select a court.";

    } elseif (empty($booking_date)) {

        $error = "Please select a booking date.";

    } elseif (empty($start_time)) {

        $error = "Please select a starting time.";

    } elseif (!in_array($duration, [1, 2, 3], true)) {

        $error = "Please select a valid duration.";

    } elseif (!in_array($payment_method, ["GCash", "Cash"], true)) {

        $error = "Please select a valid payment method.";

    }


    /* =====================================================
       DATE VALIDATION
    ===================================================== */

    if (empty($error)) {

        $dateObject = DateTime::createFromFormat(
            "Y-m-d",
            $booking_date
        );

        if (
            !$dateObject ||
            $dateObject->format("Y-m-d") !== $booking_date
        ) {

            $error = "Invalid booking date.";

        } elseif ($booking_date < date("Y-m-d")) {

            $error = "You cannot book a date in the past.";

        }

    }


    /* =====================================================
       TIME VALIDATION
    ===================================================== */

    if (empty($error)) {

        $timeObject = DateTime::createFromFormat(
            "H:i",
            $start_time
        );

        if (
            !$timeObject ||
            $timeObject->format("H:i") !== $start_time
        ) {

            $error = "Invalid starting time.";

        }

    }


    /* =====================================================
       GCash VALIDATION
    ===================================================== */

    if (
        empty($error) &&
        $payment_method === "GCash" &&
        empty($gcash_reference)
    ) {

        $error = "Please enter your GCash reference number.";

    }


    /* =====================================================
       GET SPORT
    ===================================================== */

    if (empty($error)) {

        $stmt = $conn->prepare("
            SELECT
                id,
                name,
                price_per_hour
            FROM sports
            WHERE id = ?
              AND status = 'Active'
            LIMIT 1
        ");

        if (!$stmt) {

            $error = "Database error: " . $conn->error;

        } else {

            $stmt->bind_param(
                "i",
                $selected_sport_id
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $sportData = $result->fetch_assoc();

            $stmt->close();


            if (!$sportData) {

                $error = "The selected sport is not available.";

            }

        }

    }


    /* =====================================================
       GET COURT
    ===================================================== */

    if (empty($error)) {

        $stmt = $conn->prepare("
            SELECT
                id,
                court_name
            FROM courts
            WHERE id = ?
              AND sport_id = ?
              AND status = 'Available'
            LIMIT 1
        ");

        if (!$stmt) {

            $error = "Database error: " . $conn->error;

        } else {

            $stmt->bind_param(
                "ii",
                $selected_court_id,
                $selected_sport_id
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $courtData = $result->fetch_assoc();

            $stmt->close();


            if (!$courtData) {

                $error = "The selected court is not available.";

            }

        }

    }


    /* =====================================================
       CALCULATE PAYMENT
    ===================================================== */

    if (empty($error)) {

        $sport_name = $sportData["name"];

        $price_per_hour = (float) $sportData["price_per_hour"];

        $court_name = $courtData["court_name"];


        $total_fee = $price_per_hour * $duration;


        if ($payment_method === "GCash") {

            $amount_paid = $total_fee;

            $balance = 0;

            $payment_status = "Paid";

        } else {

            $amount_paid = $total_fee * 0.40;

            $balance = $total_fee - $amount_paid;

            $payment_status = "Downpayment";

        }

    }


    /* =====================================================
       CHECK COURT AVAILABILITY / OVERLAPPING BOOKING
    ===================================================== */

    if (empty($error)) {

        $stmt = $conn->prepare("
            SELECT id
            FROM bookings
            WHERE court = ?
              AND booking_date = ?
              AND status IN ('Confirmed', 'Pending')
              AND (
                    start_time < ADDTIME(
                        ?,
                        SEC_TO_TIME(? * 3600)
                    )
                    AND
                    ADDTIME(
                        start_time,
                        SEC_TO_TIME(duration * 3600)
                    ) > ?
              )
            LIMIT 1
        ");


        if (!$stmt) {

            $error = "Database error: " . $conn->error;

        } else {

            $stmt->bind_param(
                "sssis",
                $court_name,
                $booking_date,
                $start_time,
                $duration,
                $start_time
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $existingBooking = $result->fetch_assoc();

            $stmt->close();


            if ($existingBooking) {

                $error = "Sorry, this court is already booked during the selected time.";

            }

        }

    }


    /* =====================================================
       INSERT BOOKING + PAYMENT
    ===================================================== */

    if (empty($error)) {

        try {

            $conn->begin_transaction();


            /* =============================================
               BOOKING STATUS
            ============================================= */

            $booking_status = "Confirmed";


            /* =============================================
               INSERT BOOKING
            ============================================= */

            $stmt = $conn->prepare("
                INSERT INTO bookings
                (
                    user_id,
                    sport,
                    court,
                    booking_date,
                    start_time,
                    duration,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");


            if (!$stmt) {

                throw new Exception(
                    "Booking statement error: " . $conn->error
                );

            }


            $stmt->bind_param(
                "isssiss",
                $user_id,
                $sport_name,
                $court_name,
                $booking_date,
                $start_time,
                $duration,
                $booking_status
            );


            if (!$stmt->execute()) {

                throw new Exception(
                    "Booking insert failed: " . $stmt->error
                );

            }


            $booking_id = $stmt->insert_id;

            $stmt->close();


            /* =============================================
               INSERT PAYMENT

               IMPORTANT:
               Your payments table does NOT have total_fee.
            ============================================= */

            $paymentStmt = $conn->prepare("
                INSERT INTO payments
                (
                    booking_id,
                    payment_method,
                    amount_paid,
                    balance,
                    payment_status,
                    gcash_reference
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");


            if (!$paymentStmt) {

                throw new Exception(
                    "Payment statement error: " . $conn->error
                );

            }


            /*
             * For Cash bookings:
             * gcash_reference = NULL
             *
             * For GCash bookings:
             * gcash_reference = supplied reference
             */

            $paymentReference = null;

            if ($payment_method === "GCash") {
                $paymentReference = $gcash_reference;
            }


            $paymentStmt->bind_param(
                "isddss",
                $booking_id,
                $payment_method,
                $amount_paid,
                $balance,
                $payment_status,
                $paymentReference
            );


            if (!$paymentStmt->execute()) {

                throw new Exception(
                    "Payment insert failed: " .
                    $paymentStmt->error
                );

            }


            $paymentStmt->close();


            /* =============================================
               COMMIT
            ============================================= */

            $conn->commit();


            /* =============================================
               GET USER INFORMATION
            ============================================= */

            $userStmt = $conn->prepare("
                SELECT
                    first_name,
                    last_name,
                    email
                FROM users
                WHERE id = ?
                LIMIT 1
            ");


            $customerName = "";
            $customerEmail = "";


            if ($userStmt) {

                $userStmt->bind_param(
                    "i",
                    $user_id
                );

                $userStmt->execute();

                $userResult = $userStmt->get_result();

                $userData = $userResult->fetch_assoc();

                $userStmt->close();


                if ($userData) {

                    $customerName = trim(
                        $userData["first_name"] .
                        " " .
                        $userData["last_name"]
                    );

                    $customerEmail = $userData["email"];

                }

            }


            /* =============================================
               SEND CONFIRMATION EMAIL
            ============================================= */

            if (
                !empty($customerEmail) &&
                filter_var($customerEmail, FILTER_VALIDATE_EMAIL)
            ) {

                try {

                    sendBookingConfirmation(
                        $customerName,
                        $customerEmail,
                        $booking_id,
                        $sport_name,
                        $court_name,
                        $booking_date,
                        $start_time,
                        $duration,
                        $total_fee,
                        $amount_paid,
                        $balance,
                        $payment_method
                    );

                } catch (Throwable $emailError) {

                    /*
                     * Do NOT cancel the booking if the email fails.
                     * The booking and payment were already saved.
                     */

                }

            }


            /* =============================================
               SUCCESS
            ============================================= */

            header(
                "Location: dashboard.php?booking_success=" .
                urlencode($booking_id)
            );

            exit();


        } catch (Throwable $e) {

            /* =============================================
               ROLLBACK
            ============================================= */

            $conn->rollback();

            $error = "Database Error: " . $e->getMessage();

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

    <title>Court22 - Book a Court</title>

    <link rel="stylesheet" href="css/booking.css">


</head>


<body>

<div class="booking-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <header class="site-header">

        <div class="logo">
            Court<span>22</span>
        </div>

        <nav>

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="logout.php">
                Logout
            </a>

        </nav>

    </header>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="booking-container">


        <div class="booking-header">

            <h1>Book a Court</h1>

            <p>
                Choose your sport, court, schedule and payment method.
            </p>

        </div>


        <?php if (!empty($error)): ?>

            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <form
            method="POST"
            action=""
            id="bookingForm"
        >


            <!-- =================================================
                 SPORT
            ================================================== -->

            <div class="section-card">

                <div class="section-title">
                    1. Select Sport
                </div>

                <input
                    type="hidden"
                    name="sport_id"
                    id="sport_id"
                    value="<?= htmlspecialchars($selected_sport_id) ?>"
                >

                <div class="sport-grid">

                    <?php if (empty($sports)): ?>

                        <p class="no-courts">
                            No sports are currently available.
                        </p>

                    <?php else: ?>

                        <?php foreach ($sports as $sport): ?>

                            <div
                                class="sport-card"
                                data-sport-id="<?= (int)$sport["id"] ?>"
                                data-price="<?= htmlspecialchars($sport["price_per_hour"]) ?>"
                            >

                                <input
                                    type="radio"
                                    name="sport_radio"
                                    value="<?= (int)$sport["id"] ?>"
                                >

                                <div class="sport-name">
                                    <?= htmlspecialchars($sport["name"]) ?>
                                </div>

                                <div class="sport-price">
                                    ₱<?= number_format(
                                        (float)$sport["price_per_hour"],
                                        2
                                    ) ?>
                                    / hour
                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </div>


            <!-- =================================================
                 COURT
            ================================================== -->

            <div class="section-card">

                <div class="section-title">
                    2. Select Court
                </div>

                <input
                    type="hidden"
                    name="court_id"
                    id="court_id"
                    value="<?= htmlspecialchars($selected_court_id) ?>"
                >

                <div
                    class="court-grid"
                    id="courtGrid"
                >

                    <?php if (empty($courts)): ?>

                        <p class="no-courts">
                            No courts are currently available.
                        </p>

                    <?php else: ?>

                        <?php foreach ($courts as $court): ?>

                            <div
                                class="court-card"
                                data-court-id="<?= (int)$court["id"] ?>"
                                data-sport-id="<?= (int)$court["sport_id"] ?>"
                            >

                                <input
                                    type="radio"
                                    name="court_radio"
                                    value="<?= (int)$court["id"] ?>"
                                >

                                <div class="court-name">
                                    <?= htmlspecialchars(
                                        $court["court_name"]
                                    ) ?>
                                </div>

                                <div class="court-status">
                                    Available
                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </div>


            <!-- =================================================
                 SCHEDULE
            ================================================== -->

            <div class="section-card">

                <div class="section-title">
                    3. Schedule
                </div>

                <div class="form-grid">


                    <div class="form-group">

                        <label for="booking_date">
                            Booking Date
                        </label>

                        <input
                            type="date"
                            name="booking_date"
                            id="booking_date"
                            min="<?= date("Y-m-d") ?>"
                            value="<?= htmlspecialchars($booking_date) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="start_time">
                            Start Time
                        </label>

                        <input
                            type="time"
                            name="start_time"
                            id="start_time"
                            value="<?= htmlspecialchars($start_time) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="duration">
                            Duration
                        </label>

                        <select
                            name="duration"
                            id="duration"
                            required
                        >

                            <option
                                value="1"
                                <?= $duration === 1 ? "selected" : "" ?>
                            >
                                1 Hour
                            </option>

                            <option
                                value="2"
                                <?= $duration === 2 ? "selected" : "" ?>
                            >
                                2 Hours
                            </option>

                            <option
                                value="3"
                                <?= $duration === 3 ? "selected" : "" ?>
                            >
                                3 Hours
                            </option>

                        </select>

                    </div>


                </div>

            </div>


            <!-- =================================================
                 PAYMENT
            ================================================== -->

            <div class="section-card">

                <div class="section-title">
                    4. Payment Method
                </div>


                <div class="payment-grid">


                    <label
                        class="payment-option"
                        data-payment="GCash"
                    >

                        <input
                            type="radio"
                            name="payment_method"
                            value="GCash"
                            <?= $payment_method === "GCash"
                                ? "checked"
                                : "" ?>
                        >

                        <div class="payment-title">
                            GCash
                        </div>

                        <div class="payment-description">
                            Pay the full amount through GCash.
                        </div>

                    </label>


                    <label
                        class="payment-option"
                        data-payment="Cash"
                    >

                        <input
                            type="radio"
                            name="payment_method"
                            value="Cash"
                            <?= $payment_method === "Cash"
                                ? "checked"
                                : "" ?>
                        >

                        <div class="payment-title">
                            Cash
                        </div>

                        <div class="payment-description">
                            Pay 40% downpayment and the remaining balance
                            at the venue.
                        </div>

                    </label>


                </div>


                <!-- =============================================
                     GCASH
                ============================================== -->

                <div
                    class="gcash-box"
                    id="gcashBox"
                >

                    <div class="qr-container">

                        <p style="margin-bottom:12px;font-weight:600;">
                            Scan the GCash QR code
                        </p>

                        <img
                            src="img/gcash-qr.png"
                            alt="Court22 GCash QR Code"
                        >

                    </div>


                    <div class="form-group">

                        <label for="gcash_reference">
                            GCash Reference Number
                        </label>

                        <input
                            type="text"
                            name="gcash_reference"
                            id="gcash_reference"
                            maxlength="100"
                            placeholder="Enter your GCash reference number"
                            value="<?= htmlspecialchars($gcash_reference) ?>"
                        >

                    </div>

                </div>

            </div>


            <!-- =================================================
                 SUMMARY
            ================================================== -->

            <div class="section-card">

                <div class="section-title">
                    5. Booking Summary
                </div>


                <div class="summary">


                    <div class="summary-row">

                        <span>
                            Sport
                        </span>

                        <strong id="summarySport">
                            —
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Court
                        </span>

                        <strong id="summaryCourt">
                            —
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Date
                        </span>

                        <strong id="summaryDate">
                            —
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Start Time
                        </span>

                        <strong id="summaryTime">
                            —
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Duration
                        </span>

                        <strong>
                            <span id="summaryDuration">
                                1
                            </span>
                            hour(s)
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Payment
                        </span>

                        <strong id="summaryPayment">
                            —
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Amount to Pay
                        </span>

                        <strong id="summaryAmount">
                            ₱0.00
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Remaining Balance
                        </span>

                        <strong id="summaryBalance">
                            ₱0.00
                        </strong>

                    </div>


                    <div class="summary-row total">

                        <span>
                            Total Fee
                        </span>

                        <strong id="summaryTotal">
                            ₱0.00
                        </strong>

                    </div>


                </div>

            </div>


            <!-- =================================================
                 SUBMIT
            ================================================== -->

            <div class="section-card">

                <button
                    type="submit"
                    class="submit-btn"
                    id="submitBtn"
                >
                    Confirm Booking
                </button>

            </div>


        </form>

    </main>

</div>


<script>

document.addEventListener("DOMContentLoaded", function () {


    /* =========================================================
       ELEMENTS
    ========================================================= */

    const bookingForm =
        document.getElementById("bookingForm");

    const sportInput =
        document.getElementById("sport_id");

    const courtInput =
        document.getElementById("court_id");

    const durationInput =
        document.getElementById("duration");

    const dateInput =
        document.getElementById("booking_date");

    const timeInput =
        document.getElementById("start_time");

    const gcashBox =
        document.getElementById("gcashBox");

    const gcashReference =
        document.getElementById("gcash_reference");


    const summarySport =
        document.getElementById("summarySport");

    const summaryCourt =
        document.getElementById("summaryCourt");

    const summaryDate =
        document.getElementById("summaryDate");

    const summaryTime =
        document.getElementById("summaryTime");

    const summaryDuration =
        document.getElementById("summaryDuration");

    const summaryPayment =
        document.getElementById("summaryPayment");

    const summaryAmount =
        document.getElementById("summaryAmount");

    const summaryBalance =
        document.getElementById("summaryBalance");

    const summaryTotal =
        document.getElementById("summaryTotal");


    /* =========================================================
       SPORT SELECTION
    ========================================================= */

    const sportCards =
        document.querySelectorAll(".sport-card");


    sportCards.forEach(function (card) {

        card.addEventListener("click", function () {

            sportCards.forEach(function (item) {

                item.classList.remove("active");

            });


            card.classList.add("active");


            const sportId =
                card.dataset.sportId;


            sportInput.value =
                sportId;


            courtInput.value = "";


            document
                .querySelectorAll(".court-card")
                .forEach(function (court) {

                    court.classList.remove("active");

                    const courtSportId =
                        court.dataset.sportId;


                    if (
                        courtSportId === sportId
                    ) {

                        court.style.display =
                            "block";

                    } else {

                        court.style.display =
                            "none";

                    }

                });


            updateSummary();

        });

    });


    /* =========================================================
       COURT SELECTION
    ========================================================= */

    const courtCards =
        document.querySelectorAll(".court-card");


    courtCards.forEach(function (card) {

        card.addEventListener("click", function () {

            if (
                card.style.display === "none"
            ) {
                return;
            }


            courtCards.forEach(function (item) {

                item.classList.remove("active");

            });


            card.classList.add("active");


            courtInput.value =
                card.dataset.courtId;


            updateSummary();

        });

    });


    /* =========================================================
       PAYMENT SELECTION
    ========================================================= */

    const paymentOptions =
        document.querySelectorAll(".payment-option");


    paymentOptions.forEach(function (option) {

        option.addEventListener("click", function () {

            paymentOptions.forEach(function (item) {

                item.classList.remove("active");

            });


            option.classList.add("active");


            const radio =
                option.querySelector("input");

            radio.checked = true;


            updatePaymentDisplay();

            updateSummary();

        });

    });


    /* =========================================================
       UPDATE PAYMENT DISPLAY
    ========================================================= */

    function updatePaymentDisplay() {

        const selected =
            document.querySelector(
                'input[name="payment_method"]:checked'
            );


        if (!selected) {

            gcashBox.classList.remove("show");

            return;

        }


        if (selected.value === "GCash") {

            gcashBox.classList.add("show");

            gcashReference.required = true;

        } else {

            gcashBox.classList.remove("show");

            gcashReference.required = false;

        }


        paymentOptions.forEach(function (option) {

            const radio =
                option.querySelector("input");


            if (radio.checked) {

                option.classList.add("active");

            } else {

                option.classList.remove("active");

            }

        });

    }


    /* =========================================================
       CALCULATE TOTAL
    ========================================================= */

    function getSelectedPrice() {

        const selectedSportId =
            sportInput.value;


        if (!selectedSportId) {

            return 0;

        }


        const selectedSport =
            document.querySelector(
                '.sport-card[data-sport-id="' +
                selectedSportId +
                '"]'
            );


        if (!selectedSport) {

            return 0;

        }


        return parseFloat(
            selectedSport.dataset.price
        ) || 0;

    }


    /* =========================================================
       UPDATE SUMMARY
    ========================================================= */

    function updateSummary() {


        /* ==============================================
           SPORT
        =============================================== */

        const selectedSportId =
            sportInput.value;


        const selectedSport =
            document.querySelector(
                '.sport-card[data-sport-id="' +
                selectedSportId +
                '"]'
            );


        if (selectedSport) {

            const name =
                selectedSport.querySelector(
                    ".sport-name"
                ).textContent.trim();


            summarySport.textContent =
                name;

        } else {

            summarySport.textContent =
                "—";

        }


        /* ==============================================
           COURT
        =============================================== */

        const selectedCourtId =
            courtInput.value;


        const selectedCourt =
            document.querySelector(
                '.court-card[data-court-id="' +
                selectedCourtId +
                '"]'
            );


        if (selectedCourt) {

            const name =
                selectedCourt.querySelector(
                    ".court-name"
                ).textContent.trim();


            summaryCourt.textContent =
                name;

        } else {

            summaryCourt.textContent =
                "—";

        }


        /* ==============================================
           DATE
        =============================================== */

        if (dateInput.value) {

            summaryDate.textContent =
                dateInput.value;

        } else {

            summaryDate.textContent =
                "—";

        }


        /* ==============================================
           TIME
        =============================================== */

        if (timeInput.value) {

            summaryTime.textContent =
                timeInput.value;

        } else {

            summaryTime.textContent =
                "—";

        }


        /* ==============================================
           DURATION
        =============================================== */

        const duration =
            parseInt(
                durationInput.value
            ) || 1;


        summaryDuration.textContent =
            duration;


        /* ==============================================
           PAYMENT
        =============================================== */

        const selectedPayment =
            document.querySelector(
                'input[name="payment_method"]:checked'
            );


        const paymentMethod =
            selectedPayment
                ? selectedPayment.value
                : "";


        summaryPayment.textContent =
            paymentMethod || "—";


        /* ==============================================
           PRICE
        =============================================== */

        const pricePerHour =
            getSelectedPrice();


        const total =
            pricePerHour * duration;


        let amountPaid = 0;

        let balance = 0;


        if (paymentMethod === "GCash") {

            amountPaid =
                total;

            balance =
                0;

        } else if (paymentMethod === "Cash") {

            amountPaid =
                total * 0.40;

            balance =
                total * 0.60;

        }


        summaryAmount.textContent =
            "₱" +
            amountPaid.toFixed(2);


        summaryBalance.textContent =
            "₱" +
            balance.toFixed(2);


        summaryTotal.textContent =
            "₱" +
            total.toFixed(2);

    }


    /* =========================================================
       DATE / TIME / DURATION EVENTS
    ========================================================= */

    dateInput.addEventListener(
        "change",
        updateSummary
    );


    timeInput.addEventListener(
        "change",
        updateSummary
    );


    durationInput.addEventListener(
        "change",
        updateSummary
    );


    /* =========================================================
       INITIAL COURT DISPLAY
    ========================================================= */

    if (sportInput.value) {

        sportCards.forEach(function (card) {

            if (
                card.dataset.sportId ===
                sportInput.value
            ) {

                card.classList.add("active");

            }

        });


        courtCards.forEach(function (court) {

            if (
                court.dataset.sportId ===
                sportInput.value
            ) {

                court.style.display =
                    "block";

            } else {

                court.style.display =
                    "none";

            }

        });

    } else {

        courtCards.forEach(function (court) {

            court.style.display =
                "none";

        });

    }


    /* =========================================================
       INITIAL COURT SELECTION
    ========================================================= */

    if (courtInput.value) {

        const selectedCourt =
            document.querySelector(
                '.court-card[data-court-id="' +
                courtInput.value +
                '"]'
            );


        if (selectedCourt) {

            selectedCourt.classList.add(
                "active"
            );

        }

    }


    /* =========================================================
       INITIAL PAYMENT
    ========================================================= */

    updatePaymentDisplay();


    /* =========================================================
       INITIAL SUMMARY
    ========================================================= */

    updateSummary();


    /* =========================================================
       FORM VALIDATION
    ========================================================= */

    bookingForm.addEventListener(
        "submit",
        function (event) {

            if (!sportInput.value) {

                event.preventDefault();

                alert(
                    "Please select a sport."
                );

                return;

            }


            if (!courtInput.value) {

                event.preventDefault();

                alert(
                    "Please select a court."
                );

                return;

            }


            if (!dateInput.value) {

                event.preventDefault();

                alert(
                    "Please select a booking date."
                );

                return;

            }


            if (!timeInput.value) {

                event.preventDefault();

                alert(
                    "Please select a starting time."
                );

                return;

            }


            const selectedPayment =
                document.querySelector(
                    'input[name="payment_method"]:checked'
                );


            if (!selectedPayment) {

                event.preventDefault();

                alert(
                    "Please select a payment method."
                );

                return;

            }


            if (
                selectedPayment.value ===
                "GCash" &&
                !gcashReference.value.trim()
            ) {

                event.preventDefault();

                alert(
                    "Please enter your GCash reference number."
                );

                gcashReference.focus();

                return;

            }


            const submitButton =
                document.getElementById(
                    "submitBtn"
                );


            submitButton.disabled =
                true;

            submitButton.textContent =
                "Processing Booking...";

        }
    );

});

</script>


</body>

</html>