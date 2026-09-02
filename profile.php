<?php

session_start();

require_once "config.php";

/* =========================================================
   COURT22 - PROFILE PAGE
   ========================================================= */

/* =========================
   LOGIN CHECK
========================= */

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$success = "";
$error = "";


/* =========================================================
   LOAD USER
========================================================= */

$stmt = $conn->prepare("
    SELECT id, first_name, last_name, email
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    session_destroy();

    header("Location: login.php");
    exit();
}

$user = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   UPDATE PROFILE
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {

    $first_name = trim($_POST["first_name"] ?? "");
    $last_name  = trim($_POST["last_name"] ?? "");
    $email      = trim($_POST["email"] ?? "");

    if ($first_name === "" || $last_name === "" || $email === "") {

        $error = "Please complete all profile fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        /* Check if email is already used by another user */

        $check = $conn->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            AND id != ?
            LIMIT 1
        ");

        $check->bind_param("si", $email, $user_id);
        $check->execute();

        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {

            $error = "That email address is already being used.";

            $check->close();

        } else {

            $check->close();

            $update = $conn->prepare("
                UPDATE users
                SET first_name = ?,
                    last_name = ?,
                    email = ?
                WHERE id = ?
            ");

            $update->bind_param(
                "sssi",
                $first_name,
                $last_name,
                $email,
                $user_id
            );

            if ($update->execute()) {

                $success = "Your profile has been updated successfully.";

                /* Update local user data */

                $user["first_name"] = $first_name;
                $user["last_name"]  = $last_name;
                $user["email"]      = $email;

                /* Update session name if your dashboard uses it */

                $_SESSION["first_name"] = $first_name;
                $_SESSION["last_name"]  = $last_name;

            } else {

                $error = "Unable to update your profile. Please try again.";
            }

            $update->close();
        }
    }
}


/* =========================================================
   CHANGE PASSWORD
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_password"])) {

    $current_password = $_POST["current_password"] ?? "";
    $new_password     = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (
        $current_password === "" ||
        $new_password === "" ||
        $confirm_password === ""
    ) {

        $error = "Please complete all password fields.";

    } elseif ($new_password !== $confirm_password) {

        $error = "New passwords do not match.";

    } elseif (strlen($new_password) < 6) {

        $error = "New password must be at least 6 characters.";

    } else {

        /*
         * Get current password.
         *
         * This assumes your users table stores the password
         * in a column named "password".
         */

        $passwordStmt = $conn->prepare("
            SELECT password
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        if ($passwordStmt) {

            $passwordStmt->bind_param("i", $user_id);
            $passwordStmt->execute();

            $passwordResult = $passwordStmt->get_result();

            if ($passwordResult->num_rows === 1) {

                $passwordRow = $passwordResult->fetch_assoc();

                if (password_verify($current_password, $passwordRow["password"])) {

                    $new_hash = password_hash(
                        $new_password,
                        PASSWORD_DEFAULT
                    );

                    $updatePassword = $conn->prepare("
                        UPDATE users
                        SET password = ?
                        WHERE id = ?
                    ");

                    $updatePassword->bind_param(
                        "si",
                        $new_hash,
                        $user_id
                    );

                    if ($updatePassword->execute()) {

                        $success = "Your password has been changed successfully.";

                    } else {

                        $error = "Unable to change your password.";
                    }

                    $updatePassword->close();

                } else {

                    $error = "Current password is incorrect.";
                }

            } else {

                $error = "Unable to find your account.";
            }

            $passwordStmt->close();

        } else {

            $error = "Password update is unavailable because your users table does not contain the expected password field.";
        }
    }
}


/* =========================================================
   INITIALS
========================================================= */

$firstInitial = strtoupper(
    substr($user["first_name"], 0, 1)
);

$lastInitial = strtoupper(
    substr($user["last_name"], 0, 1)
);

$initials = $firstInitial . $lastInitial;

$fullName = trim(
    $user["first_name"] . " " . $user["last_name"]
);

?>

<!DOCTYPE html>

<html lang="en">

<head>


<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>My Profile | Court22</title>

<link
    rel="stylesheet"
    href="css/profile.css"
>


</head>

<body>

<div class="profile-page">


<!-- =====================================================
     HEADER
====================================================== -->

<header class="site-header">

    <div class="logo">
        COURT<span>22</span>
    </div>

    <nav>

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="booking.php">
            Book a Court
        </a>

        <a href="profile.php" class="active">
            Profile
        </a>

        <a href="logout.php" class="logout-link">
            Logout
        </a>

    </nav>

</header>


<!-- =====================================================
     MAIN
====================================================== -->

<main class="profile-container">

    <!-- Page Heading -->

    <div class="profile-heading">

        <div>

            <h1>My Profile</h1>

            <p>
                Manage your Court22 account information.
            </p>

        </div>

    </div>


    <!-- =================================================
         ALERTS
    ================================================== -->

    <?php if (!empty($success)): ?>

        <div class="alert alert-success">
            <span class="alert-icon">✓</span>

            <span>
                <?php echo htmlspecialchars($success); ?>
            </span>
        </div>

    <?php endif; ?>


    <?php if (!empty($error)): ?>

        <div class="alert alert-error">
            <span class="alert-icon">!</span>

            <span>
                <?php echo htmlspecialchars($error); ?>
            </span>
        </div>

    <?php endif; ?>


    <!-- =================================================
         PROFILE HERO
    ================================================== -->

    <section class="profile-card profile-overview">

        <div class="avatar">

            <?php echo htmlspecialchars($initials); ?>

        </div>

        <div class="profile-info">

            <h2>
                <?php echo htmlspecialchars($fullName); ?>
            </h2>

            <p>
                <?php echo htmlspecialchars($user["email"]); ?>
            </p>

            <span class="member-badge">
                Court22 Member
            </span>

        </div>

    </section>


    <div class="profile-layout">

        <!-- =================================================
             PERSONAL INFORMATION
        ================================================== -->

        <section class="profile-card">

            <div class="card-header">

                <div>

                    <h2>
                        Personal Information
                    </h2>

                    <p>
                        Update your account details.
                    </p>

                </div>

                <div class="card-icon">
                    👤
                </div>

            </div>


            <form
                method="POST"
                action="profile.php"
                class="profile-form"
            >

                <div class="form-row">

                    <div class="form-group">

                        <label for="first_name">
                            First Name
                        </label>

                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            value="<?php echo htmlspecialchars($user["first_name"]); ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="last_name">
                            Last Name
                        </label>

                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            value="<?php echo htmlspecialchars($user["last_name"]); ?>"
                            required
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($user["email"]); ?>"
                        required
                    >

                    <small>
                        This email is used for your Court22 account.
                    </small>

                </div>


                <button
                    type="submit"
                    name="update_profile"
                    class="primary-btn"
                >
                    Save Changes
                </button>

            </form>

        </section>


        <!-- =================================================
             ACCOUNT INFORMATION
        ================================================== -->

        <section class="profile-card account-card">

            <div class="card-header">

                <div>

                    <h2>
                        Account Information
                    </h2>

                    <p>
                        Your Court22 account details.
                    </p>

                </div>

                <div class="card-icon">
                    ⚙
                </div>

            </div>


            <div class="account-list">

                <div class="account-item">

                    <span>
                        Account ID
                    </span>

                    <strong>
                        #<?php echo $user_id; ?>
                    </strong>

                </div>


                <div class="account-item">

                    <span>
                        Full Name
                    </span>

                    <strong>
                        <?php echo htmlspecialchars($fullName); ?>
                    </strong>

                </div>


                <div class="account-item">

                    <span>
                        Email
                    </span>

                    <strong class="email-value">
                        <?php echo htmlspecialchars($user["email"]); ?>
                    </strong>

                </div>


                <div class="account-item">

                    <span>
                        Account Status
                    </span>

                    <strong class="status-active">
                        Active
                    </strong>

                </div>

            </div>

        </section>

    </div>


    <!-- =================================================
         CHANGE PASSWORD
    ================================================== -->

    <section class="profile-card password-card">

        <div class="card-header">

            <div>

                <h2>
                    Change Password
                </h2>

                <p>
                    Keep your Court22 account secure by using a strong password.
                </p>

            </div>

            <div class="card-icon">
                🔒
            </div>

        </div>


        <form
            method="POST"
            action="profile.php"
            class="password-form"
            autocomplete="off"
        >

            <div class="form-row">

                <div class="form-group">

                    <label for="current_password">
                        Current Password
                    </label>

                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        placeholder="Enter current password"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="new_password">
                        New Password
                    </label>

                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        placeholder="Minimum 6 characters"
                        minlength="6"
                        required
                    >

                </div>

            </div>


            <div class="form-group password-confirm">

                <label for="confirm_password">
                    Confirm New Password
                </label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Re-enter your new password"
                    minlength="6"
                    required
                >

            </div>


            <button
                type="submit"
                name="change_password"
                class="secondary-btn"
            >
                Change Password
            </button>

        </form>

    </section>


    <!-- =================================================
         QUICK ACTIONS
    ================================================== -->

    <section class="quick-actions">

        <a
            href="booking.php"
            class="quick-action"
        >

            <div class="quick-icon">
                +
            </div>

            <div>

                <strong>
                    Book a Court
                </strong>

                <span>
                    Make a new reservation
                </span>

            </div>

        </a>


        <a
            href="dashboard.php"
            class="quick-action"
        >

            <div class="quick-icon">
                →
            </div>

            <div>

                <strong>
                    My Dashboard
                </strong>

                <span>
                    View your bookings
                </span>

            </div>

        </a>

    </section>

</main>


<!-- =====================================================
     FOOTER
====================================================== -->

<footer class="site-footer">

    <p>
        © <?php echo date("Y"); ?> Court22. All rights reserved.
    </p>

</footer>


</div>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const newPassword =
        document.getElementById("new_password");

    const confirmPassword =
        document.getElementById("confirm_password");


    function validatePasswords() {

        if (
            confirmPassword.value !== "" &&
            newPassword.value !== confirmPassword.value
        ) {

            confirmPassword.setCustomValidity(
                "Passwords do not match."
            );

        } else {

            confirmPassword.setCustomValidity("");
        }
    }


    newPassword.addEventListener(
        "input",
        validatePasswords
    );

    confirmPassword.addEventListener(
        "input",
        validatePasswords
    );

});

</script>

</body>

</html>
