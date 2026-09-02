<?php

session_start();

require_once __DIR__ . "/../config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| UPDATE BOOKING STATUS
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $bookingId = (int)($_POST["booking_id"] ?? 0);
    $status = trim($_POST["status"] ?? "");

    $allowed = [
        "Pending",
        "Confirmed",
        "Cancelled",
        "Completed"
    ];

    if (
        $bookingId > 0 &&
        in_array($status, $allowed, true)
    ) {

        $stmt = $conn->prepare("
            UPDATE bookings
            SET status = ?
            WHERE id = ?
        ");

        if ($stmt) {

            $stmt->bind_param(
                "si",
                $status,
                $bookingId
            );

            $stmt->execute();

            $stmt->close();
        }
    }

    header("Location: bookings.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| BOOKINGS
|--------------------------------------------------------------------------
*/

$bookings = [];

$query = $conn->query("
    SELECT
        b.id,
        b.user_id,
        b.sport,
        b.court,
        b.booking_date,
        b.start_time,
        b.duration,
        b.status,
        u.first_name,
        u.last_name,
        u.email
    FROM bookings b
    LEFT JOIN users u
        ON u.id = b.user_id
    ORDER BY b.id DESC
");

if ($query) {

    while ($row = $query->fetch_assoc()) {
        $bookings[] = $row;
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

<title>Bookings - Court22 Admin</title>

<link
    rel="stylesheet"
    href="css/admin.css"
>

</head>

<body>

<div class="admin-layout">

<?php include __DIR__ . "/sidebar.php"; ?>

<main class="admin-main">

<header class="topbar">

<div>

<h1>Bookings</h1>

<p>
Manage all Court22 reservations.
</p>

</div>

</header>


<section class="content-card">

<div class="content-header">

<div>

<h2>
All Bookings
</h2>

<p>
<?= count($bookings) ?> booking(s)
</p>

</div>

</div>


<div class="table-wrapper">

<table>

<thead>

<tr>

<th>ID</th>
<th>Customer</th>
<th>Email</th>
<th>Sport</th>
<th>Court</th>
<th>Date</th>
<th>Time</th>
<th>Duration</th>
<th>Status</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php if (empty($bookings)): ?>

<tr>

<td
    colspan="10"
    class="empty"
>
No bookings found.
</td>

</tr>

<?php else: ?>

<?php foreach ($bookings as $booking): ?>

<tr>

<td>
#<?= (int)$booking["id"] ?>
</td>

<td>

<?= htmlspecialchars(
    trim(
        ($booking["first_name"] ?? "") .
        " " .
        ($booking["last_name"] ?? "")
    )
) ?>

</td>

<td>
<?= htmlspecialchars($booking["email"] ?? "—") ?>
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

<td>

<form method="POST" class="inline-form">

<input
    type="hidden"
    name="booking_id"
    value="<?= (int)$booking["id"] ?>"
>

<select
    name="status"
    onchange="this.form.submit()"
>

<option
    value=""
>
Change
</option>

<option value="Pending">
Pending
</option>

<option value="Confirmed">
Confirmed
</option>

<option value="Completed">
Completed
</option>

<option value="Cancelled">
Cancelled
</option>

</select>

</form>

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