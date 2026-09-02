<?php

/* =========================================================
   COURT22 - DATABASE CONFIGURATION
   ========================================================= */

$host = "localhost";
$user = "root";
$password = "";
$database = "booking_db";

$conn = new mysqli(
    $host,
    $user,
    $password,
    $database
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


/* =========================================================
   LOGIN CHECK
   ========================================================= */

function isLoggedIn()
{
    return isset($_SESSION['user_id']) &&
           !empty($_SESSION['user_id']);
}


/* =========================================================
   ADMIN CHECK
   ========================================================= */

function isAdmin()
{
    global $conn;

    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $user_id = (int) $_SESSION['user_id'];

    $stmt = $conn->prepare(
        "SELECT id
         FROM admins
         WHERE user_id = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    $is_admin = $result->num_rows > 0;

    $stmt->close();

    return $is_admin;
}


/* =========================================================
   REDIRECT
   ========================================================= */

function redirect($url)
{
    header("Location: " . $url);
    exit();
}


/* =========================================================
   CLEAN INPUT
   ========================================================= */

function clean($value)
{
    return htmlspecialchars(
        trim((string)$value),
        ENT_QUOTES,
        "UTF-8"
    );
}


/* =========================================================
   EMAIL CONFIGURATION
   ========================================================= */

$mail_username = "court.22xyz@gmail.com";
$mail_password = "ucgbtmrmkmovesnx";

$mail_from_name = "Court22 Court Reservation";

?>