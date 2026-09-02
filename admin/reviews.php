<?php

session_start();

require_once __DIR__ . "/../config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php");
    exit();
}


$payments = [];

$query = $conn->query("
    SELECT
        p.id,
        p.booking_id,
        p.payment_method,
        p.amount_paid,
        p.balance,
        p.payment_status,
        p.gcash_reference,
        p.payment_date,
        b.sport,
        b.court,
        b.booking_date,
        u.first_name,
        u.last_name
    FROM payments p
    LEFT JOIN bookings b
        ON b.id = p.booking_id
    LEFT JOIN users u
        ON u.id = b.user_id
    ORDER BY p.id DESC
");

if ($query) {

    while ($row = $query->fetch_assoc()) {
        $payments[] = $row;
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

<title>Payments - Court22 Admin</title>

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

<h1>Payments</h1>

<p>
Monitor Court22 payment records.
</p>

</div>

</header>


<section class="content-card">

<div class="content-header">

<h2>
Payment Records
</h2>

<span>
<?= count($payments) ?> payments
</span>

</div>


<div class="table-wrapper">

<table>

<thead>

<tr>

<th>ID</th>
<th>Booking</th>
<th>Customer</th>
<th>Sport</th>
<th>Court</th>
<th>Method</th>
<th>Paid</th>
<th>Balance</th>
<th>Status</th>
<th>GCash Ref.</th>
<th>Date</th>

</tr>

</thead>

<tbody>

<?php if (empty($payments)): ?>

<tr>

<td
    colspan="11"
    class="empty"
>
No payment records found.
</td>

</tr>

<?php else: ?>

<?php foreach ($payments as $payment): ?>

<tr>

<td>
#<?= (int)$payment["id"] ?>
</td>

<td>
#<?= (int)$payment["booking_id"] ?>
</td>

<td>

<?= htmlspecialchars(
    trim(
        ($payment["first_name"] ?? "") .
        " " .
        ($payment["last_name"] ?? "")
    )
) ?>

</td>

<td>
<?= htmlspecialchars($payment["sport"] ?? "—") ?>
</td>

<td>
<?= htmlspecialchars($payment["court"] ?? "—") ?>
</td>

<td>
<?= htmlspecialchars($payment["payment_method"]) ?>
</td>

<td>
₱<?= number_format(
    (float)$payment["amount_paid"],
    2
) ?>
</td>

<td>
₱<?= number_format(
    (float)$payment["balance"],
    2
) ?>
</td>

<td>

<span class="status
<?= strtolower(
    htmlspecialchars($payment["payment_status"])
) ?>">

<?= htmlspecialchars($payment["payment_status"]) ?>

</span>

</td>

<td>
<?= htmlspecialchars(
    $payment["gcash_reference"] ?: "—"
) ?>
</td>

<td>
<?= htmlspecialchars($payment["payment_date"]) ?>
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