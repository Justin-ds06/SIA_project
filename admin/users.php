<?php

session_start();

require_once __DIR__ . "/../config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| DELETE USER
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $userId = (int)($_POST["user_id"] ?? 0);

    if ($userId > 0) {

        /*
         * Delete payments connected to bookings first.
         */

        $stmt = $conn->prepare("
            DELETE p
            FROM payments p
            INNER JOIN bookings b
                ON b.id = p.booking_id
            WHERE b.user_id = ?
        ");

        if ($stmt) {

            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
        }


        /*
         * Delete bookings.
         */

        $stmt = $conn->prepare("
            DELETE FROM bookings
            WHERE user_id = ?
        ");

        if ($stmt) {

            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
        }


        /*
         * Delete user.
         */

        $stmt = $conn->prepare("
            DELETE FROM users
            WHERE id = ?
        ");

        if ($stmt) {

            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: users.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| LOAD USERS
|--------------------------------------------------------------------------
*/

$users = [];

$query = $conn->query("
    SELECT
        id,
        first_name,
        last_name,
        email
    FROM users
    ORDER BY id DESC
");

if ($query) {

    while ($row = $query->fetch_assoc()) {
        $users[] = $row;
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

<title>Users - Court22 Admin</title>

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

<h1>Users</h1>

<p>
Manage registered Court22 customers.
</p>

</div>

</header>


<section class="content-card">

<div class="content-header">

<h2>
Registered Users
</h2>

<span>
<?= count($users) ?> users
</span>

</div>


<div class="table-wrapper">

<table>

<thead>

<tr>

<th>ID</th>
<th>Name</th>
<th>Email</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php if (empty($users)): ?>

<tr>

<td
    colspan="4"
    class="empty"
>
No users found.
</td>

</tr>

<?php else: ?>

<?php foreach ($users as $user): ?>

<tr>

<td>
#<?= (int)$user["id"] ?>
</td>

<td>

<?= htmlspecialchars(
    trim(
        $user["first_name"] .
        " " .
        $user["last_name"]
    )
) ?>

</td>

<td>
<?= htmlspecialchars($user["email"]) ?>
</td>

<td>

<form
    method="POST"
    onsubmit="return confirm('Delete this user? This will also delete their bookings and payments.');"
>

<input
    type="hidden"
    name="user_id"
    value="<?= (int)$user["id"] ?>"
>

<button
    type="submit"
    class="btn danger small"
>
Delete
</button>

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