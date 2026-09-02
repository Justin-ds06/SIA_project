<?php

session_start();

require_once __DIR__ . "/../config.php";

/*
|--------------------------------------------------------------------------
| ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/

if (isset($_SESSION["admin_id"]) && !empty($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {

        $error = "Please enter your email and password.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | ADMIN TABLE
        |--------------------------------------------------------------------------
        | Expected:
        | id
        | name
        | email
        | password
        */

        $stmt = $conn->prepare("
            SELECT id, name, email, password
            FROM admins
            WHERE email = ?
            LIMIT 1
        ");

        if (!$stmt) {

            $error = "Database error: " . $conn->error;

        } else {

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();

            $admin = $result->fetch_assoc();

            $stmt->close();

            if ($admin && password_verify($password, $admin["password"])) {

                $_SESSION["admin_id"] = (int)$admin["id"];
                $_SESSION["admin_name"] = $admin["name"];
                $_SESSION["admin_email"] = $admin["email"];

                header("Location: dashboard.php");
                exit();

            } else {

                $error = "Invalid email or password.";
            }
        }
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

<title>Court22 Admin Login</title>

<link
    rel="stylesheet"
    href="css/admin.css"
>

</head>

<body class="admin-login-page">

<div class="login-box">

    <div class="login-logo">
        COURT<span>22</span>
    </div>

    <div class="login-subtitle">
        ADMIN PANEL
    </div>

    <?php if ($error): ?>

        <div class="alert error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <form method="POST">

        <div class="form-group">

            <label>Email</label>

            <input
                type="email"
                name="email"
                placeholder="Admin email"
                required
            >

        </div>

        <div class="form-group">

            <label>Password</label>

            <input
                type="password"
                name="password"
                placeholder="Password"
                required
            >

        </div>

        <button
            type="submit"
            class="btn primary full"
        >
            Login to Admin Panel
        </button>

    </form>

    <a
        href="../index.php"
        class="back-link"
    >
        ← Back to Court22
    </a>

</div>

</body>

</html>