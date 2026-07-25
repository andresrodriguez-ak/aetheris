<?php
session_start();
include '../config/db_config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Todos los campos son obligatorios.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = strtolower($user['role']);
                header("Location: index.php");
                exit();
            } else {
                $error = 'Credenciales incorrectas.';
            }
        } else {
            $error = 'Usuario no encontrado.';
        }
    }
}

$signup_error    = $_SESSION['signup_error']    ?? '';
$signup_username = $_SESSION['signup_username'] ?? '';
$signup_email    = $_SESSION['signup_email']    ?? '';
$signup_success  = $_SESSION['signup_success']  ?? '';
unset($_SESSION['signup_error'], $_SESSION['signup_username'], $_SESSION['signup_email'], $_SESSION['signup_success']);

$show_signup = (isset($_GET['r']) && $_GET['r'] === 'signup') || !empty($signup_error);

$page_title   = "Iniciar Sesión";
$accent_color = "general";
$page_css     = ['auth.css'];

require_once '../src/includes/header.php';
?>
</div></div>

<main class="auth-main">
    <div class="auth-box">

        <div class="auth-panel">
            <div class="panel-text" id="panelLogin">
                <h2>BIENVENIDO DE VUELTA</h2>
                <p>Accede a tu cuenta para continuar disfrutando de tus lecturas favoritas.</p>
            </div>
            <div class="panel-text hidden" id="panelSignup">
                <h2>BIENVENIDO</h2>
                <p>Lugar donde puedes leer tus lecturas favoritas!</p>
            </div>
        </div>

        <div class="auth-slider">
            <div class="auth-track" id="authTrack">

                <div class="auth-form">
                    <h2>Iniciar Sesión</h2>
                    <?php if ($signup_success): ?>
                        <div class="message success"><?php echo htmlspecialchars($signup_success); ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form action="login.php" method="post">
                        <input type="text" name="username" placeholder="Nombre de usuario"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                        <input type="password" name="password" placeholder="Contraseña" required>
                        <button type="submit">Ingresar</button>
                    </form>
                    <p class="auth-switch">¿No tienes cuenta? <a onclick="showSignup(event)">Regístrate aquí</a></p>
                </div>

                <div class="auth-form">
                    <h2>Registrarse</h2>
                    <?php if ($signup_error): ?>
                        <div class="message error"><?php echo htmlspecialchars($signup_error); ?></div>
                    <?php endif; ?>
                    <form action="signup.php" method="post">
                        <input type="text"  name="username" placeholder="Nombre de usuario"
                               value="<?php echo htmlspecialchars($signup_username); ?>" required>
                        <input type="email" name="email"    placeholder="Correo electrónico"
                               value="<?php echo htmlspecialchars($signup_email); ?>" required>
                        <input type="password" name="password" placeholder="Contraseña (mínimo 8 caracteres)" minlength="8" required>
                        <button type="submit">Registrarse</button>
                    </form>
                    <p class="auth-switch">¿Ya tienes cuenta? <a onclick="showLogin(event)">Inicia sesión</a></p>
                </div>

            </div>
        </div>

    </div>
</main>

<div style="display:none"><div>
<?php require_once '../src/includes/footer.php'; ?>

<script src="js/auth.js"></script>
<?php if ($show_signup): ?>
<script>showSignup();</script>
<?php endif; ?>