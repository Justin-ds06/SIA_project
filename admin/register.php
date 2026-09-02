<?php

session_start();

require_once __DIR__ . "/config.php";

/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$error = "";
$success = "";

$first_name = "";
$last_name = "";
$email = "";


/*
|--------------------------------------------------------------------------
| IF ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/

if (isset($_SESSION["user_id"]) && !empty($_SESSION["user_id"])) {

    header("Location: dashboard.php");
    exit();

}


/*
|--------------------------------------------------------------------------
| REGISTRATION
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST["first_name"] ?? "");
    $last_name = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($first_name === "") {

        $error = "Please enter your first name.";

    } elseif ($last_name === "") {

        $error = "Please enter your last name.";

    } elseif ($email === "") {

        $error = "Please enter your email address.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif ($password === "") {

        $error = "Please enter a password.";

    } elseif (strlen($password) < 6) {

        $error = "Password must be at least 6 characters.";

    } elseif ($confirm_password === "") {

        $error = "Please confirm your password.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    }


    /*
    |--------------------------------------------------------------------------
    | CHECK EMAIL
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        $stmt = $conn->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        if (!$stmt) {

            $error = "Database error: " . $conn->error;

        } else {

            $stmt->bind_param("s", $email);

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows > 0) {

                $error = "An account with this email already exists.";

            }

            $stmt->close();

        }

    }


    /*
    |--------------------------------------------------------------------------
    | CREATE ACCOUNT
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        /*
         * NEVER store plain-text passwords.
         */
        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );


        $stmt = $conn->prepare("
            INSERT INTO users
            (
                first_name,
                last_name,
                email,
                password
            )
            VALUES
            (?, ?, ?, ?)
        ");


        if (!$stmt) {

            $error = "Database error: " . $conn->error;

        } else {

            $stmt->bind_param(
                "ssss",
                $first_name,
                $last_name,
                $email,
                $hashed_password
            );


            if ($stmt->execute()) {

                $success =
                    "Account created successfully! You can now log in.";

                /*
                 * Clear password fields.
                 */
                $first_name = "";
                $last_name = "";
                $email = "";

            } else {

                $error =
                    "Unable to create your account: " .
                    $stmt->error;

            }


            $stmt->close();

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

    <title>Court22 - Create Account</title>

    <link
        rel="stylesheet"
        href="admin/css/admin.css"
    >

</head>


<body>


<div class="register-page">


    <!-- =====================================================
         LEFT SIDE
    ====================================================== -->

    <div class="register-brand">

        <div class="brand-overlay">

            <a
                href="index.php"
                class="brand-logo"
            >
                COURT<span>22</span>
            </a>


            <div class="brand-content">

                <p class="brand-label">
                    COURT BOOKING SYSTEM
                </p>

                <h1>
                    Play.
                    <br>
                    Book.
                    <br>
                    <span>Enjoy.</span>
                </h1>

                <p class="brand-description">
                    Create your Court22 account and book your
                    favorite court with ease.
                </p>

            </div>


            <div class="brand-footer">
                © <?= date("Y") ?> Court22. All rights reserved.
            </div>

        </div>

    </div>


    <!-- =====================================================
         RIGHT SIDE
    ====================================================== -->

    <div class="register-container">


        <div class="register-card">


            <div class="mobile-logo">

                <a href="index.php">
                    COURT<span>22</span>
                </a>

            </div>


            <div class="register-heading">

                <p class="small-title">
                    WELCOME TO COURT22
                </p>

                <h2>
                    Create your account
                </h2>

                <p>
                    Sign up to start booking your court.
                </p>

            </div>


            <!-- =================================================
                 ERROR
            ================================================== -->

            <?php if ($error !== ""): ?>

                <div class="alert error">

                    <span class="alert-icon">!</span>

                    <div>
                        <?= htmlspecialchars($error) ?>
                    </div>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 SUCCESS
            ================================================== -->

            <?php if ($success !== ""): ?>

                <div class="alert success">

                    <span class="alert-icon">✓</span>

                    <div>
                        <?= htmlspecialchars($success) ?>
                    </div>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 FORM
            ================================================== -->

            <form
                method="POST"
                action=""
                id="registerForm"
                autocomplete="off"
            >


                <!-- NAME -->

                <div class="name-grid">


                    <div class="form-group">

                        <label for="first_name">
                            First Name
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">
                                👤
                            </span>

                            <input
                                type="text"
                                id="first_name"
                                name="first_name"
                                placeholder="First name"
                                value="<?= htmlspecialchars($first_name) ?>"
                                maxlength="100"
                                autocomplete="given-name"
                                required
                            >

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="last_name">
                            Last Name
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">
                                👤
                            </span>

                            <input
                                type="text"
                                id="last_name"
                                name="last_name"
                                placeholder="Last name"
                                value="<?= htmlspecialchars($last_name) ?>"
                                maxlength="100"
                                autocomplete="family-name"
                                required
                            >

                        </div>

                    </div>


                </div>


                <!-- EMAIL -->

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">
                            @
                        </span>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter your email"
                            value="<?= htmlspecialchars($email) ?>"
                            maxlength="150"
                            autocomplete="email"
                            required
                        >

                    </div>

                </div>


                <!-- PASSWORD -->

                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">
                            •
                        </span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Create a password"
                            minlength="6"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-target="password"
                            aria-label="Show password"
                        >
                            Show
                        </button>

                    </div>


                    <div class="password-strength">

                        <div class="strength-bar">

                            <span id="strengthBar"></span>

                        </div>

                        <span id="strengthText">
                            At least 6 characters
                        </span>

                    </div>

                </div>


                <!-- CONFIRM PASSWORD -->

                <div class="form-group">

                    <label for="confirm_password">
                        Confirm Password
                    </label>

                    <div class="input-wrapper">

                        <span class="input-icon">
                            •
                        </span>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Confirm your password"
                            minlength="6"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-target="confirm_password"
                            aria-label="Show password"
                        >
                            Show
                        </button>

                    </div>

                    <div
                        class="password-match"
                        id="passwordMatch"
                    ></div>

                </div>


                <!-- TERMS -->

                <label class="terms">

                    <input
                        type="checkbox"
                        id="terms"
                        name="terms"
                        required
                    >

                    <span>
                        I agree to the Court22
                        <a href="#" onclick="return false;">
                            Terms & Conditions
                        </a>
                        and
                        <a href="#" onclick="return false;">
                            Privacy Policy
                        </a>.
                    </span>

                </label>


                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="register-button"
                    id="registerButton"
                >

                    Create Account

                    <span>
                        →
                    </span>

                </button>


            </form>


            <!-- =================================================
                 LOGIN
            ================================================== -->

            <div class="login-link">

                Already have an account?

                <a href="login.php">
                    Log in
                </a>

            </div>


        </div>

    </div>

</div>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        /* =====================================================
           PASSWORD TOGGLE
        ====================================================== */

        const toggleButtons =
            document.querySelectorAll(
                ".password-toggle"
            );


        toggleButtons.forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const targetId =
                            button.dataset.target;

                        const input =
                            document.getElementById(
                                targetId
                            );


                        if (
                            input.type ===
                            "password"
                        ) {

                            input.type =
                                "text";

                            button.textContent =
                                "Hide";

                        } else {

                            input.type =
                                "password";

                            button.textContent =
                                "Show";

                        }

                    }
                );

            }
        );


        /* =====================================================
           PASSWORD STRENGTH
        ====================================================== */

        const password =
            document.getElementById(
                "password"
            );

        const strengthBar =
            document.getElementById(
                "strengthBar"
            );

        const strengthText =
            document.getElementById(
                "strengthText"
            );


        password.addEventListener(
            "input",
            function () {

                const value =
                    password.value;


                let strength = 0;


                if (value.length >= 6) {
                    strength++;
                }

                if (value.length >= 10) {
                    strength++;
                }

                if (/[A-Z]/.test(value)) {
                    strength++;
                }

                if (/[0-9]/.test(value)) {
                    strength++;
                }

                if (/[^A-Za-z0-9]/.test(value)) {
                    strength++;
                }


                strengthBar.className = "";


                if (value.length === 0) {

                    strengthBar.style.width =
                        "0%";

                    strengthText.textContent =
                        "At least 6 characters";

                } else if (strength <= 1) {

                    strengthBar.style.width =
                        "25%";

                    strengthBar.classList.add(
                        "weak"
                    );

                    strengthText.textContent =
                        "Weak password";

                } else if (strength <= 3) {

                    strengthBar.style.width =
                        "55%";

                    strengthBar.classList.add(
                        "medium"
                    );

                    strengthText.textContent =
                        "Medium password";

                } else {

                    strengthBar.style.width =
                        "100%";

                    strengthBar.classList.add(
                        "strong"
                    );

                    strengthText.textContent =
                        "Strong password";

                }

            }
        );


        /* =====================================================
           PASSWORD MATCH
        ====================================================== */

        const confirmPassword =
            document.getElementById(
                "confirm_password"
            );

        const passwordMatch =
            document.getElementById(
                "passwordMatch"
            );


        function checkPasswordMatch() {

            if (
                confirmPassword.value === ""
            ) {

                passwordMatch.textContent =
                    "";

                passwordMatch.className =
                    "password-match";

                return;

            }


            if (
                password.value ===
                confirmPassword.value
            ) {

                passwordMatch.textContent =
                    "✓ Passwords match";

                passwordMatch.className =
                    "password-match match";

            } else {

                passwordMatch.textContent =
                    "Passwords do not match";

                passwordMatch.className =
                    "password-match no-match";

            }

        }


        confirmPassword.addEventListener(
            "input",
            checkPasswordMatch
        );


        password.addEventListener(
            "input",
            checkPasswordMatch
        );


        /* =====================================================
           FORM SUBMIT
        ====================================================== */

        const form =
            document.getElementById(
                "registerForm"
            );

        const registerButton =
            document.getElementById(
                "registerButton"
            );


        form.addEventListener(
            "submit",
            function (event) {

                if (
                    password.value !==
                    confirmPassword.value
                ) {

                    event.preventDefault();

                    alert(
                        "Passwords do not match."
                    );

                    confirmPassword.focus();

                    return;

                }


                if (
                    password.value.length < 6
                ) {

                    event.preventDefault();

                    alert(
                        "Password must be at least 6 characters."
                    );

                    password.focus();

                    return;

                }


                registerButton.disabled =
                    true;

                registerButton.innerHTML =
                    "Creating Account...";

            }
        );


    }
);

</script>

</body>

</html>