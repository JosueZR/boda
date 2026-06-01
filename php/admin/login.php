<?php
// ============================================================
//  LOGIN ADMIN - Credenciales predeterminadas hardcodeadas
//  Usuario: admin@boda.com  /  Contraseña: boda2026
// ============================================================
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['admin_logueado']) && $_SESSION['admin_logueado'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Credenciales predeterminadas (hardcodeadas)
    $EMAIL_ADMIN    = 'admin@boda.com';
    $PASSWORD_ADMIN = 'boda2026';

    if ($email === $EMAIL_ADMIN && $password === $PASSWORD_ADMIN) {
        $_SESSION['admin_logueado'] = true;
        $_SESSION['admin_nombre']   = 'Administrador';
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'El correo o la contraseña son incorrectos.';
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
    <style>
        :root {
            --crema: #faf6f0;
            --dorado: #c9a96e;
            --dorado-oscuro: #a07840;
            --cafe: #3b2f24;
            --texto: #4a3f35;
            --texto-suave: #8a7a6a;
            --blanco: #ffffff;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #faf6f0 0%, #f0e8d8 50%, #e8d9c0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: radial-gradient(ellipse 70% 70% at 80% 80%, rgba(201,169,110,0.12) 0%, transparent 70%),
                        radial-gradient(ellipse 60% 60% at 20% 20%, rgba(201,169,110,0.1) 0%, transparent 70%);
            pointer-events: none;
        }
        /* Partículas decorativas */
        .bg-ornaments {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .bg-ornaments span {
            position: absolute;
            color: rgba(201,169,110,0.15);
            font-size: 1.5rem;
            animation: floatOrn 8s ease-in-out infinite;
        }
        @keyframes floatOrn {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(15deg); }
        }
        /* Card de login */
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
        }
        .login-card {
            background: var(--blanco);
            border-radius: 16px;
            padding: 3rem 2.5rem;
            box-shadow: 0 20px 60px rgba(59,47,36,0.12), 0 4px 20px rgba(201,169,110,0.1);
            border: 1px solid rgba(201,169,110,0.2);
            text-align: center;
            animation: slideUp 0.8s ease both;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .login-ornament {
            color: var(--dorado);
            font-size: 1.3rem;
            letter-spacing: 5px;
            margin-bottom: 1rem;
            opacity: 0.7;
        }
        .login-card h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.7rem;
            color: var(--cafe);
            margin-bottom: 0.3rem;
        }
        .login-card .subtitulo {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            color: var(--texto-suave);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .credenciales-hint {
            background: linear-gradient(135deg, var(--crema), #f5efe3);
            border: 1px solid rgba(201,169,110,0.3);
            border-radius: 8px;
            padding: 0.8rem 1rem;
            margin: 1.2rem 0 2rem;
            text-align: left;
        }
        .credenciales-hint p {
            font-size: 0.78rem;
            color: var(--texto-suave);
            margin-bottom: 0.2rem;
        }
        .credenciales-hint strong { color: var(--cafe); }
        .form-group {
            text-align: left;
            margin-bottom: 1.2rem;
        }
        .form-group label {
            display: block;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--texto-suave);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1.5px solid #dcd3c7;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: 'Poppins', sans-serif;
            color: var(--texto);
            background: #fdfdfd;
            outline: none;
            transition: all 0.3s;
        }
        .form-group input:focus {
            border-color: var(--dorado);
            box-shadow: 0 0 0 3px rgba(201,169,110,0.15);
            background: var(--blanco);
        }
        .btn-login {
            width: 100%;
            padding: 0.95rem;
            background: var(--cafe);
            color: var(--crema);
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: 'Poppins', sans-serif;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 0.5rem;
        }
        .btn-login:hover {
            background: #5c4a35;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59,47,36,0.2);
        }
        .btn-login:active { transform: translateY(0); }
        .error-msg {
            background: #fdf2f2;
            border: 1px solid #f0c0c0;
            color: #c0392b;
            padding: 0.7rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1.2rem;
            text-align: left;
        }
        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: var(--texto-suave);
            font-size: 0.8rem;
            text-decoration: none;
            letter-spacing: 0.5px;
            transition: color 0.3s;
        }
        .back-link:hover { color: var(--dorado); }
    </style>
</head>
<body>

    <!-- Ornamentos de fondo -->
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

            <!-- Sugerencia de credenciales -->
            <div class="credenciales-hint">
                <p>📧 <strong>admin@boda.com</strong></p>
                <p>🔑 <strong>boda2026</strong></p>
            </div>

            <?php if ($error): ?>
                <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="admin@boda.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
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
                    Ingresar al Panel
                </button>
            </form>

            <a href="../../index.html" class="back-link">← Volver a la página de la boda</a>
        </div>
    </div>

</body>
</html>