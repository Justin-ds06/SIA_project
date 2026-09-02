<?php

session_start();

require_once __DIR__ . "/../config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| ADD SPORT
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";


    if ($action === "add") {

        $name = trim($_POST["name"] ?? "");
        $price = (float)($_POST["price_per_hour"] ?? 0);

        if ($name !== "" && $price >= 0) {

            $stmt = $conn->prepare("
                INSERT INTO sports
                (
                    name,
                    price_per_hour,
                    status
                )
                VALUES (?, ?, 'Active')
            ");

            if ($stmt) {

                $stmt->bind_param(
                    "sd",
                    $name,
                    $price
                );

                $stmt->execute();
                $stmt->close();
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | TOGGLE STATUS
    |--------------------------------------------------------------------------
    */

    if ($action === "toggle") {

        $id = (int)($_POST["id"] ?? 0);

        $stmt = $conn->prepare("
            UPDATE sports
            SET status =
                CASE
                    WHEN status = 'Active'
                    THEN 'Inactive'
                    ELSE 'Active'
                END
            WHERE id = ?
        ");

        if ($stmt) {

            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    if ($action === "delete") {

        $id = (int)($_POST["id"] ?? 0);

        $stmt = $conn->prepare("
            DELETE FROM sports
            WHERE id = ?
        ");

        if ($stmt) {

            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: sports.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| LOAD SPORTS
|--------------------------------------------------------------------------
*/

$sports = [];

$query = $conn->query("
    SELECT
        id,
        name,
        price_per_hour,
        status
    FROM sports
    ORDER BY id DESC
");

if ($query) {

    while ($row = $query->fetch_assoc()) {
        $sports[] = $row;
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

<title>Sports - Court22 Admin</title>

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

<h1>Sports</h1>

<p>
Manage Court22 sports and hourly rates.
</p>

</div>

</header>


<div class="admin-grid-2">


<section class="content-card">

<h2>
Add Sport
</h2>

<form method="POST">

<input
    type="hidden"
    name="action"
    value="add"
>

<div class="form-group">

<label>
Sport Name
</label>

<input
    type="text"
    name="name"
    placeholder="e.g. Basketball"
    required
>

</div>

<div class="form-group">

<label>
Price Per Hour
</label>

<input
    type="number"
    name="price_per_hour"
    step="0.01"
    min="0"
    placeholder="500"
    required
>

</div>

<button
    type="submit"
    class="btn primary"
>
Add Sport
</button>

</form>

</section>


<section class="content-card">

<h2>
Sports
</h2>

<div class="table-wrapper">

<table>

<thead>

<tr>

<th>Name</th>
<th>Price</th>
<th>Status</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php foreach ($sports as $sport): ?>

<tr>

<td>
<?= htmlspecialchars($sport["name"]) ?>
</td>

<td>
₱<?= number_format(
    (float)$sport["price_per_hour"],
    2
) ?>
</td>

<td>

<span class="status
<?= strtolower(
    htmlspecialchars($sport["status"])
) ?>">

<?= htmlspecialchars($sport["status"]) ?>

</span>

</td>

<td>

<form
    method="POST"
    class="action-row"
>

<input
    type="hidden"
    name="id"
    value="<?= (int)$sport["id"] ?>"
>

<input
    type="hidden"
    name="action"
    value="toggle"
>

<button
    class="btn outline small"
>
Toggle
</button>

</form>

<form
    method="POST"
    onsubmit="return confirm('Delete this sport?');"
>

<input
    type="hidden"
    name="id"
    value="<?= (int)$sport["id"] ?>"
>

<input
    type="hidden"
    name="action"
    value="delete"
>

<button
    class="btn danger small"
>
Delete
</button>

</form>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</section>

</div>

</main>

</div>

</body>
</html>