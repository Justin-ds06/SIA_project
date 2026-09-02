<?php

require_once "config.php";

header("Content-Type: application/json");

$court_id = (int)($_GET['court_id'] ?? 0);
$booking_date = $_GET['booking_date'] ?? "";
$start_time = $_GET['start_time'] ?? "";
$duration = (int)($_GET['duration'] ?? 0);

if (
    $court_id <= 0 ||
    empty($booking_date) ||
    empty($start_time) ||
    $duration <= 0
) {

    echo json_encode([
        "success" => false,
        "available" => false,
        "message" => "Missing booking information."
    ]);

    exit();
}


$stmt = $conn->prepare("
    SELECT court_name
    FROM courts
    WHERE id = ?
    AND status = 'Available'
");

$stmt->bind_param(
    "i",
    $court_id
);

$stmt->execute();

$courtResult = $stmt->get_result();


if ($courtResult->num_rows === 0) {

    echo json_encode([
        "success" => true,
        "available" => false,
        "message" => "Court is unavailable."
    ]);

    exit();
}


$court = $courtResult->fetch_assoc();

$court_name = $court['court_name'];


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

$stmt->bind_param(
    "sssds",
    $court_name,
    $booking_date,
    $start_time,
    $duration,
    $start_time
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows > 0) {

    echo json_encode([
        "success" => true,
        "available" => false,
        "message" => "Booked during this time."
    ]);

} else {

    echo json_encode([
        "success" => true,
        "available" => true,
        "message" => "Available"
    ]);
}