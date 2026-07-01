<?php
session_start();
include '../config/db_config.php';

$error   = '';
$success = '';

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
                $success = 'Registro exitoso. Redirigiendo...';
                header("refresh:2;url=login.php");
            } else {
                $error = 'Error al registrar el usuario. Intenta nuevamente.';
            }
        }
    }

    if ($error) {
        $_SESSION['signup_error']    = $error;
        $_SESSION['signup_username'] = $username;
        $_SESSION['signup_email']    = $email;
        header("Location: login.php?r=signup");
        exit();
    }
} else {
    header("Location: login.php?r=signup");
    exit();
}

$page_title   = "Registro";
$accent_color = "general";
$page_css     = ['auth.css'];
require_once '../src/includes/header.php';
?>
</div></div>
<main class="auth-main">
    <div class="auth-box">
        <div class="auth-panel">
            <h2>BIENVENIDO</h2>
            <p>Lugar donde puedes leer tus lecturas favoritas!</p>
        </div>
        <div class="auth-slider">
            <div class="auth-track">
                <div class="auth-form">
                    <h2>Registrarse</h2>
                    <?php if ($success): ?>
                        <div class="message success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
<div style="display:none"><div>
<?php require_once '../src/includes/footer.php'; ?>