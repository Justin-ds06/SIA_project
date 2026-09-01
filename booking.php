<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['book'])) {

    $user_id = $_SESSION['user_id'];
    $sport = $_POST['sport'];
    $court = $_POST['court'];
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $duration = $_POST['duration'];

    $stmt = $conn->prepare("SELECT id FROM bookings WHERE court = ? AND booking_date = ? AND start_time = ? AND status = 'Confirmed'");

    $stmt->bind_param(
        "sss",
        $court,
        $booking_date,
        $start_time
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $error = "This court is already booked for that time.";

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO bookings
            (user_id, sport, court, booking_date, start_time, duration)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "issssi",
            $user_id,
            $sport,
            $court,
            $booking_date,
            $start_time,
            $duration
        );

        if ($stmt->execute()) {
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Court - Court22</title>
    <link rel="stylesheet" href="css/booking.css">
</head>

<body>

<header>
    <div class="logo">
        <a href="dashboard.php">Court22</a>
    </div>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php" class="logout">Logout</a>
    </nav>
</header>

<div class="booking-container">

    <div class="booking-box">

        <h1>Book a Court</h1>
        <p>Choose your sport, court, date and time.</p>

        <?php if (isset($error)): ?>
            <div class="error">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <label>Sport</label>

            <select name="sport" required>
                <option value="">Select Sport</option>
                <option value="Basketball">Basketball</option>
                <option value="Badminton">Badminton</option>
                <option value="Pickleball">Pickleball</option>
            </select>

            <label>Court</label>

            <select name="court" required>
                <option value="">Select Court</option>
                <option value="Court 1">Court 1</option>
                <option value="Court 2">Court 2</option>
                <option value="Court 3">Court 3</option>
            </select>

            <label>Date</label>

            <input
                type="date"
                name="booking_date"
                min="<?= date('Y-m-d'); ?>"
                required
            >

            <label>Start Time</label>

            <input
                type="time"
                name="start_time"
                required
            >

            <label>Duration</label>

            <select name="duration" required>
                <option value="1">1 Hour</option>
                <option value="2">2 Hours</option>
                <option value="3">3 Hours</option>
            </select>

            <button type="submit" name="book">
                Confirm Booking
            </button>

        </form>

    </div>

</div>

</body>
</html>