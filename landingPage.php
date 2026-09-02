<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Website</title>
    <link rel="stylesheet" href="css/landingPage.css">
</head>
<body>

    <header>
        <div class="logo">Court22</div>

        <nav>
            <a href="#home">Home</a>
            <a href="#about">About</a>
            <a href="#services">Services</a>
            <a href="#contact">Contact</a>
            <a href="index.php" class="login-btn">Login</a>
        </nav>
    </header>

   <section class="hero">
    <div class="slideshow">
        <img src="img/basketball.jpg" class="slide active" alt="Court 22">
        <img src="img/pickleball.jpg" class="slide" alt="Court 22">
        <img src="img/badminton.jpg" class="slide" alt="Court 22">

        <div class="hero-content">
            <h1>COURT 22</h1>
            <p>Step In. Ball Out.</p>
            <a href="index.php" class="btn">Book Now</a>
        </div>
    </div>
    </section>

    <section class="about" id="about">
        <h2>About Us</h2>
        <p>
            MyWebsite provides a simple platform where users can
            create an account, securely log in, and access their
            personal dashboard.
        </p>
    </section>

    <section class="services" id="services">
        <h2>Our Features</h2>

        <div class="cards">
            <div class="card">
                <h3>Easy Registration</h3>
                <p>Create your account quickly and easily.</p>
            </div>

            <div class="card">
                <h3>Secure Login</h3>
                <p>Access your account using your registered email and password.</p>
            </div>

            <div class="card">
                <h3>User Dashboard</h3>
                <p>View your account information from your personal dashboard.</p>
            </div>
        </div>
    </section>

    <section class="contact" id="contact">
        <h2>Contact Us</h2>
        <p>Have questions? Get in touch with us today.</p>
    </section>

    <footer>
        <p>&copy; 2026 MyWebsite. All Rights Reserved.</p>
    </footer>
    <script src="js/slide.js"></script>
</body>
</html>