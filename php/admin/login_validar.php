<?php
// ============================================================
//  PROCESO DE LOGIN - Consulta Exclusiva a Base de Datos
// ============================================================
session_start();

// Si ya está logueado, redirigir directamente al dashboard
if (isset($_SESSION['admin_logueado']) && $_SESSION['admin_logueado'] === true) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($password)) {
        try {
            // Incluye el archivo de conexión que está una carpeta arriba
            require_once '../conexion.php';
            
            // Consultar el usuario administrador por email
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar la existencia del usuario y validar el hash de la contraseña de boda.sql
            if ($usuario && password_verify($password, $usuario['password_hash'])) {
                $_SESSION['admin_logueado'] = true;
                $_SESSION['admin_nombre']   = $usuario['nombre'];
                
                header('Location: dashboard.php');
                exit;
            } else {
                // Si las credenciales no coinciden en la base de datos, regresa con error
                header('Location: login.php?error=1');
                exit;
            }
        } catch (Exception $e) {
            // En caso de un fallo crítico de conexión a la Base de Datos
            die("Error crítico del sistema: " . $e->getMessage());
        }
    } else {
        header('Location: login.php?error=empty');
        exit;
    }
} else {
    // Si intentan entrar al archivo por GET, se les deniega el acceso
    header('Location: login.php');
    exit;
}
?>