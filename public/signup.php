<?php
session_start();
include '../config/db_config.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del email no es válido.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $error = 'El nombre de usuario o email ya están registrados.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            $stmt->bind_param("sss", $username, $email, $hashed);

            if ($stmt->execute()) {
                $_SESSION['signup_success'] = 'Registro exitoso. Ya podés iniciar sesión.';
                header("Location: login.php");
                exit();
            } else {
                $error = 'Error al registrar el usuario. Intenta nuevamente.';
            }
        }
    }

    if ($error) {
        $_SESSION['signup_error']    = $error;
        $_SESSION['signup_username'] = $username;
        $_SESSION['signup_email']    = $email;
    }
}

header("Location: login.php?r=signup");
exit();