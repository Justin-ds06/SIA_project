<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$first_name = $_SESSION['first_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Court22 Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>

<header>
    <div class="logo">
        <a href="landingPage.php">Court22</a>
    </div>

    <nav>
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="#courts">Courts</a>
        <a href="#bookings">My Bookings</a>
        <a href="#profile">Profile</a>
        <a href="logout.php" class="logout">Logout</a>
    </nav>
</header>

<main>

    <section class="welcome">
        <h1>Welcome back, <?= htmlspecialchars($first_name); ?>!</h1>
        <p>Ready to play today?</p>
    </section>

    <section class="courts" id="courts">

        <h2>Book a Court</h2>

<div class="court-grid">

    <div class="court-card">
        <img src="img/basketball.jpg" alt="Basketball Court">
        <div class="court-info">
            <h3>Basketball</h3>
            <p>Book a basketball court and enjoy the game.</p>
            <a href="booking.php?sport=basketball" class="book-btn">Book Court</a>
        </div>
    </div>

    <div class="court-card">
        <img src="img/badminton.jpg" alt="Badminton Court">
        <div class="court-info">
            <h3>Badminton</h3>
            <p>Reserve a badminton court for your next match.</p>
            <a href="booking.php?sport=badminton" class="book-btn">Book Court</a>
        </div>
    </div>

    <div class="court-card">
        <img src="img/pickleball.jpg" alt="Pickleball Court">
        <div class="court-info">
            <h3>Pickleball</h3>
            <p>Grab a court and enjoy your pickleball session.</p>
            <a href="booking.php?sport=pickleball" class="book-btn">Book Court</a>
        </div>
    </div>

</div>


    </section>

    <section class="bookings" id="bookings">

        <h2>Upcoming Bookings</h2>

        <div class="booking-card">
            <div>
                <h3>Basketball Court 1</h3>
                <p>September 5, 2026 • 5:00 PM</p>
            </div>

            <span class="status">Confirmed</span>
        </div>

    </section>

</main>

</body>
</html>
