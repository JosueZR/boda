<?php
// ============================================================
//  VALIDACIÓN INTEGRADA - Con redirección exacta al Backend
// ============================================================
session_start();

// Si ya inició sesión previamente, lo mandamos al dashboard en php/admin/
if (isset($_SESSION['admin_logueado']) && $_SESSION['admin_logueado'] === true) {
    header('Location: ../../php/admin/dashboard.php'); 
    exit;
}

$error_msg = ""; // Variable para guardar el texto del error

// Procesar el formulario cuando se hace clic en el botón
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error_msg = "Por favor, introduce todos los campos.";
    } else {
        try {
            // Buscamos el archivo de conexión subiendo dos niveles
            if (file_exists('../../php/conexion.php')) {
                require_once '../../php/conexion.php';
                
                // Consulta a la base de datos
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verificar si existe el usuario y coincide la contraseña
                if ($usuario && password_verify($password, $usuario['password_hash'])) {
                    $_SESSION['admin_logueado'] = true;
                    $_SESSION['admin_nombre']   = $usuario['nombre'];
                    
                    // REDIRECCIÓN CORREGIDA: Sube dos niveles desde public/admin y entra a php/admin/
                    header('Location: ../../php/admin/dashboard.php');
                    exit;
                } else {
                    $error_msg = "El correo o la contraseña son incorrectos.";
                }
            } else {
                $error_msg = "Error del sistema: No se encontró el archivo de conexión.";
            }
        } catch (Exception $e) {
            $error_msg = "Error en la Base de Datos: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin · Luis & Erendira</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>

    <div class="bg-ornaments">
        <span style="top:10%; left:8%; animation-delay:0s">✦</span>
        <span style="top:80%; left:5%; animation-delay:2s">◇</span>
        <span style="top:20%; right:8%; animation-delay:1s">✧</span>
        <span style="top:70%; right:6%; animation-delay:3s">✦</span>
        <span style="top:50%; left:50%; animation-delay:1.5s">◇</span>
    </div>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-ornament">✦ ◇ ✦</div>
            <h1>Panel de Administración</h1>
            <p class="subtitulo">Luis & Erendira · 2026</p>

            <?php if (!empty($error_msg)): ?>
                <div class="error-msg">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="admin@boda.com"
                        required
                        autocomplete="email"
                    >
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                </div>
                <button type="submit" class="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i> Ingresar al Panel
                </button>
            </form>

            <a href="../../index.html" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Volver a la página de la boda
            </a>
        </div>
    </div>

</body>
</html>