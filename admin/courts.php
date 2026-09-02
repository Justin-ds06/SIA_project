<?php

session_start();

require_once __DIR__ . "/../config.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: index.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| ADD / UPDATE COURTS
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";


    if ($action === "add") {

        $sportId = (int)($_POST["sport_id"] ?? 0);
        $courtName = trim($_POST["court_name"] ?? "");

        if ($sportId > 0 && $courtName !== "") {

            $stmt = $conn->prepare("
                INSERT INTO courts
                (
                    sport_id,
                    court_name,
                    status
                )
                VALUES (?, ?, 'Available')
            ");

            if ($stmt) {

                $stmt->bind_param(
                    "is",
                    $sportId,
                    $courtName
                );

                $stmt->execute();
                $stmt->close();
            }
        }
    }


    if ($action === "toggle") {

        $id = (int)($_POST["id"] ?? 0);

        $stmt = $conn->prepare("
            UPDATE courts
            SET status =
                CASE
                    WHEN status = 'Available'
                    THEN 'Unavailable'
                    ELSE 'Available'
                END
            WHERE id = ?
        ");

        if ($stmt) {

            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    }


    if ($action === "delete") {

        $id = (int)($_POST["id"] ?? 0);

        $stmt = $conn->prepare("
            DELETE FROM courts
            WHERE id = ?
        ");

        if ($stmt) {

            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: courts.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| SPORTS
|--------------------------------------------------------------------------
*/

$sports = [];

$query = $conn->query("
    SELECT id, name
    FROM sports
    ORDER BY name ASC
");

if ($query) {

    while ($row = $query->fetch_assoc()) {
        $sports[] = $row;
    }
}


/*
|--------------------------------------------------------------------------
| COURTS
|--------------------------------------------------------------------------
*/

$courts = [];

$query = $conn->query("
    SELECT
        c.id,
        c.sport_id,
        c.court_name,
        c.status,
        s.name AS sport_name
    FROM courts c
    LEFT JOIN sports s
        ON s.id = c.sport_id
    ORDER BY c.id DESC
");

if ($query) {

    while ($row = $query->fetch_assoc()) {
        $courts[] = $row;
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

<title>Courts - Court22 Admin</title>

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

<h1>Courts</h1>

<p>
Manage Court22 courts.
</p>

</div>

</header>


<div class="admin-grid-2">


<section class="content-card">

<h2>
Add Court
</h2>

<form method="POST">

<input
    type="hidden"
    name="action"
    value="add"
>

<div class="form-group">

<label>
Sport
</label>

<select
    name="sport_id"
    required
>

<option value="">
Select sport
</option>

<?php foreach ($sports as $sport): ?>

<option
    value="<?= (int)$sport["id"] ?>"
>
<?= htmlspecialchars($sport["name"]) ?>
</option>

<?php endforeach; ?>

</select>

</div>

<div class="form-group">

<label>
Court Name
</label>

<input
    type="text"
    name="court_name"
    placeholder="Court 1"
    required
>

</div>

<button
    type="submit"
    class="btn primary"
>
Add Court
</button>

</form>

</section>


<section class="content-card">

<h2>
All Courts
</h2>

<div class="table-wrapper">

<table>

<thead>

<tr>

<th>Court</th>
<th>Sport</th>
<th>Status</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php foreach ($courts as $court): ?>

<tr>

<td>
<?= htmlspecialchars($court["court_name"]) ?>
</td>

<td>
<?= htmlspecialchars($court["sport_name"] ?? "—") ?>
</td>

<td>

<span class="status
<?= strtolower(
    htmlspecialchars($court["status"])
) ?>">

<?= htmlspecialchars($court["status"]) ?>

</span>

</td>

<td>

<form
    method="POST"
    style="display:inline;"
>

<input
    type="hidden"
    name="action"
    value="toggle"
>

<input
    type="hidden"
    name="id"
    value="<?= (int)$court["id"] ?>"
>

<button
    class="btn outline small"
>
Toggle
</button>

</form>


<form
    method="POST"
    style="display:inline;"
    onsubmit="return confirm('Delete this court?');"
>

<input
    type="hidden"
    name="action"
    value="delete"
>

<input
    type="hidden"
    name="id"
    value="<?= (int)$court["id"] ?>"
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