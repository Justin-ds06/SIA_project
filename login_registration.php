<?php

session_start();
require_once "config.php";

if (isset($_POST['register'])) {

    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $checkEmail = $stmt->get_result();

    if ($checkEmail->num_rows > 0) {

        $_SESSION['register_error'] = 'Email is already registered';
        $_SESSION['active_form'] = 'register';

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssss",
            $first_name,
            $last_name,
            $email,
            $password
        );

        $stmt->execute();

        $_SESSION['active_form'] = 'login';
    }

    header("Location: index.php");
    exit();
}

if (isset($_POST['login'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];

            header("Location: dashboard.php");
            exit();

        } else {

            $_SESSION['login_error'] = 'Incorrect password';
            $_SESSION['active_form'] = 'login';
        }

    } else {

        $_SESSION['login_error'] = 'Email not found';
        $_SESSION['active_form'] = 'login';
    }

    header("Location: index.php");
    exit();
}

?>
