<?php
// ============================================================
//  DASHBOARD ADMIN — Panel de Familias y Confirmaciones
// ============================================================
session_start();

// Proteger: solo admins logueados
if (!isset($_SESSION['admin_logueado']) || $_SESSION['admin_logueado'] !== true) {
    header('Location: login.php');
    exit;
}

// ── Intentar conexión a BD ──
$bdConectada = false;
$pdo = null;
try {
    require_once '../conexion.php';
    $bdConectada = true;
} catch (Exception $e) {
    // Sin BD: modo demo con datos en sesión
}

// ── DATOS EN SESIÓN (modo sin BD) ──
if (!isset($_SESSION['familias_demo'])) {
    $_SESSION['familias_demo'] = [
        ['id'=>1, 'nombre'=>'Familia García',    'lugares_asignados'=>4, 'confirmados'=>null, 'nota'=>null],
        ['id'=>2, 'nombre'=>'Familia Pérez',     'lugares_asignados'=>3, 'confirmados'=>null, 'nota'=>null],
        ['id'=>3, 'nombre'=>'Familia Rodríguez', 'lugares_asignados'=>5, 'confirmados'=>null, 'nota'=>null],
        ['id'=>4, 'nombre'=>'Familia López',     'lugares_asignados'=>2, 'confirmados'=>null, 'nota'=>null],
        ['id'=>5, 'nombre'=>'Familia Martínez',  'lugares_asignados'=>6, 'confirmados'=>null, 'nota'=>null],
    ];
    $_SESSION['next_id_demo'] = 6;
}

$mensaje = '';
$tipo_msg = '';

// ══════════════════════════════════════════
//  ACCIONES POST
// ══════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // ── AGREGAR FAMILIA ──
    if ($accion === 'agregar_familia') {
        $nombre  = trim($_POST['nombre_familia'] ?? '');
        $lugares = intval($_POST['lugares'] ?? 1);

        if ($nombre && $lugares >= 1) {
            if ($bdConectada) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO familias (nombre, lugares_asignados) VALUES (?, ?)");
                    $stmt->execute([$nombre, $lugares]);
                    $mensaje = "✅ Familia «{$nombre}» agregada correctamente.";
                    $tipo_msg = 'success';
                } catch(Exception $e) {
                    $mensaje = "❌ Error al guardar: " . $e->getMessage();
                    $tipo_msg = 'error';
                }
            } else {
                // Demo: guardar en sesión
                $id = $_SESSION['next_id_demo']++;
                $_SESSION['familias_demo'][] = [
                    'id' => $id,
                    'nombre' => $nombre,
                    'lugares_asignados' => $lugares,
                    'confirmados' => null,
                    'nota' => null,
                ];
                $mensaje = "✅ Familia «{$nombre}» agregada (modo demo, sin BD).";
                $tipo_msg = 'success';
            }
        } else {
            $mensaje = "⚠️ Por favor completa el nombre y los lugares correctamente.";
            $tipo_msg = 'warning';
        }
    }

    // ── ELIMINAR FAMILIA ──
    if ($accion === 'eliminar_familia') {
        $id = intval($_POST['familia_id'] ?? 0);
        if ($id > 0) {
            if ($bdConectada) {
                try {
                    $pdo->prepare("DELETE FROM familias WHERE id = ?")->execute([$id]);
                    $mensaje = "🗑️ Familia eliminada correctamente.";
                    $tipo_msg = 'success';
                } catch(Exception $e) {
                    $mensaje = "❌ Error al eliminar: " . $e->getMessage();
                    $tipo_msg = 'error';
                }
            } else {
                $_SESSION['familias_demo'] = array_values(
                    array_filter($_SESSION['familias_demo'], fn($f) => $f['id'] !== $id)
                );
                $mensaje = "🗑️ Familia eliminada (modo demo).";
                $tipo_msg = 'success';
            }
        }
    }

    // Redirigir para evitar reenvío del form
    $_SESSION['flash_msg']  = $mensaje;
    $_SESSION['flash_tipo'] = $tipo_msg;
    header('Location: dashboard.php');
    exit;
}

// Recuperar flash message
if (isset($_SESSION['flash_msg'])) {
    $mensaje  = $_SESSION['flash_msg'];
    $tipo_msg = $_SESSION['flash_tipo'];
    unset($_SESSION['flash_msg'], $_SESSION['flash_tipo']);
}

// ══════════════════════════════════════════
//  CARGAR DATOS
// ══════════════════════════════════════════
$familias = [];
$total_invitados  = 0;
$total_confirmados = 0;
$total_pendientes  = 0;

if ($bdConectada) {
    try {
        // Subquery para traer solo la confirmación más reciente por familia
        $rows = $pdo->query("
            SELECT f.id, f.nombre, f.lugares_asignados,
                   c.personas_confirmadas AS confirmados,
                   c.nota,
                   c.fecha_confirmacion
            FROM familias f
            LEFT JOIN (
                SELECT familia_id,
                       personas_confirmadas,
                       nota,
                       fecha_confirmacion
                FROM confirmaciones c1
                WHERE fecha_confirmacion = (
                    SELECT MAX(fecha_confirmacion)
                    FROM confirmaciones c2
                    WHERE c2.familia_id = c1.familia_id
                )
            ) c ON c.familia_id = f.id
            ORDER BY f.nombre ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
        $familias = $rows;
    } catch(Exception $e) {
        $familias = $_SESSION['familias_demo'];
    }
} else {
    $familias = $_SESSION['familias_demo'];
}

foreach ($familias as $f) {
    $total_invitados += $f['lugares_asignados'];
    if ($f['confirmados'] !== null) {
        $total_confirmados += $f['confirmados'];
    } else {
        $total_pendientes++;
    }
}
$porcentaje = $total_invitados > 0 ? round(($total_confirmados / $total_invitados) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin · Luis & Erendira</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* ============================================================
           ADMIN DASHBOARD STYLES
           ============================================================ */
        :root {
            --crema:        #faf6f0;
            --crema-oscura: #f0e8d8;
            --dorado:       #c9a96e;
            --dorado-claro: #e8d5b0;
            --dorado-oscuro:#a07840;
            --cafe:         #3b2f24;
            --cafe-medio:   #5c4a35;
            --texto:        #4a3f35;
            --texto-suave:  #8a7a6a;
            --blanco:       #ffffff;
            --verde:        #3a8a5a;
            --rojo:         #c0392b;
            --sombra:       rgba(59,47,36,0.08);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            background: var(--crema);
            font-family: 'Poppins', sans-serif;
            color: var(--texto);
            min-height: 100vh;
        }

        /* ── SIDEBAR / NAVBAR ── */
        .topbar {
            background: var(--cafe);
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
            box-shadow: 0 2px 20px rgba(59,47,36,0.2);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        .topbar-ornament { color: var(--dorado); font-size: 1.2rem; }
        .topbar-title {
            font-family: 'Playfair Display', serif;
            color: var(--dorado-claro);
            font-size: 1.1rem;
        }
        .topbar-title span { color: rgba(232,213,176,0.5); font-style: italic; font-size: 0.85rem; }
        .topbar-right { display: flex; align-items: center; gap: 1rem; }
        .badge-bd {
            font-size: 0.72rem;
            padding: 0.3rem 0.7rem;
            border-radius: 20px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .badge-bd.conectado { background: rgba(58,138,90,0.2); color: #7dd4a3; border: 1px solid rgba(58,138,90,0.3); }
        .badge-bd.demo { background: rgba(201,169,110,0.15); color: var(--dorado); border: 1px solid rgba(201,169,110,0.3); }
        .btn-logout {
            color: rgba(232,213,176,0.6);
            text-decoration: none;
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border: 1px solid rgba(232,213,176,0.2);
            border-radius: 6px;
            transition: all 0.3s;
        }
        .btn-logout:hover { color: var(--dorado-claro); border-color: rgba(201,169,110,0.5); }

        /* ── CONTENIDO PRINCIPAL ── */
        .page-content {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem;
        }

        /* ── FLASH MESSAGE ── */
        .flash {
            padding: 0.9rem 1.2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: slideDown 0.4s ease;
        }
        .flash.success { background: #f0faf4; border: 1px solid #9ecca8; color: #2d7a45; }
        .flash.error   { background: #fdf2f2; border: 1px solid #f0c0c0; color: #c0392b; }
        .flash.warning { background: #fefcf0; border: 1px solid #e8d5a0; color: #7a6030; }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── ESTADÍSTICAS ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2.5rem;
        }
        .stat-card {
            background: var(--blanco);
            border: 1px solid rgba(201,169,110,0.2);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 20px var(--sombra);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px var(--sombra);
        }
        .stat-emoji { font-size: 2rem; margin-bottom: 0.5rem; }
        .stat-num {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--cafe);
            line-height: 1;
        }
        .stat-label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--texto-suave);
            margin-top: 0.3rem;
        }

        /* BARRA DE PROGRESO */
        .progreso-section {
            background: var(--blanco);
            border: 1px solid rgba(201,169,110,0.2);
            border-radius: 12px;
            padding: 1.5rem 2rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 4px 20px var(--sombra);
        }
        .progreso-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.8rem;
        }
        .progreso-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            color: var(--cafe);
        }
        .progreso-pct {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            color: var(--dorado-oscuro);
        }
        .progress-bar-bg {
            background: var(--crema-oscura);
            border-radius: 100px;
            height: 12px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            border-radius: 100px;
            background: linear-gradient(to right, var(--dorado), var(--dorado-oscuro));
            transition: width 1s ease;
        }

        /* ── SECCIONES ── */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            color: var(--cafe);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* ── FORMULARIO AGREGAR FAMILIA ── */
        .form-agregar {
            background: var(--blanco);
            border: 1px solid rgba(201,169,110,0.2);
            border-radius: 12px;
            padding: 1.8rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 4px 20px var(--sombra);
        }
        .form-agregar-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            color: var(--cafe);
            margin-bottom: 1.2rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid rgba(201,169,110,0.2);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 0.8rem;
            align-items: end;
        }
        .form-campo label {
            display: block;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--texto-suave);
            margin-bottom: 0.4rem;
            font-weight: 500;
        }
        .form-campo input {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1.5px solid #dcd3c7;
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
            color: var(--texto);
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-campo input:focus {
            border-color: var(--dorado);
            box-shadow: 0 0 0 3px rgba(201,169,110,0.15);
        }
        .btn-agregar {
            padding: 0.7rem 1.5rem;
            background: var(--cafe);
            color: var(--crema);
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
            letter-spacing: 0.5px;
        }
        .btn-agregar:hover {
            background: var(--cafe-medio);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(59,47,36,0.2);
        }

        /* ── TABLA DE FAMILIAS ── */
        .tabla-wrapper {
            background: var(--blanco);
            border: 1px solid rgba(201,169,110,0.2);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px var(--sombra);
        }
        .tabla-familias {
            width: 100%;
            border-collapse: collapse;
        }
        .tabla-familias thead {
            background: var(--cafe);
        }
        .tabla-familias thead th {
            padding: 1rem 1.2rem;
            text-align: left;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--dorado-claro);
            font-weight: 500;
        }
        .tabla-familias tbody tr {
            border-bottom: 1px solid rgba(201,169,110,0.1);
            transition: background 0.2s;
        }
        .tabla-familias tbody tr:last-child { border-bottom: none; }
        .tabla-familias tbody tr:hover { background: var(--crema); }
        .tabla-familias td {
            padding: 1rem 1.2rem;
            font-size: 0.9rem;
            vertical-align: middle;
        }
        .nombre-familia {
            font-family: 'Playfair Display', serif;
            font-size: 1rem;
            color: var(--cafe);
        }
        .badge-confirmado {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.82rem;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
        }
        .badge-confirmado.si {
            background: #f0faf4;
            color: #2d7a45;
            border: 1px solid #9ecca8;
        }
        .badge-confirmado.pendiente {
            background: #fefcf0;
            color: #7a6030;
            border: 1px solid #e8d5a0;
        }
        .badge-confirmado.no {
            background: #fdf2f2;
            color: #c0392b;
            border: 1px solid #f0c0c0;
        }
        .progress-mini-bg {
            background: var(--crema-oscura);
            border-radius: 100px;
            height: 8px;
            width: 100px;
            overflow: hidden;
            display: inline-block;
        }
        .progress-mini-fill {
            height: 100%;
            border-radius: 100px;
            background: linear-gradient(to right, var(--dorado), var(--dorado-oscuro));
        }
        .btn-eliminar {
            background: none;
            border: 1px solid #f0c0c0;
            color: #c0392b;
            padding: 0.3rem 0.7rem;
            border-radius: 6px;
            font-size: 0.78rem;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Poppins', sans-serif;
        }
        .btn-eliminar:hover {
            background: #fdf2f2;
            border-color: #c0392b;
        }
        .sin-datos {
            text-align: center;
            padding: 3rem;
            color: var(--texto-suave);
            font-size: 0.9rem;
        }
        .sin-datos .sin-icon { font-size: 2.5rem; margin-bottom: 0.8rem; }

        /* ── RESPONSIVE ── */
        @media (max-width: 700px) {
            .form-row { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .topbar { padding: 0 1rem; }
            .topbar-title span { display: none; }
            .tabla-familias thead th:nth-child(4),
            .tabla-familias td:nth-child(4) { display: none; }
        }
    </style>
</head>
<body>

<!-- TOP BAR -->
<nav class="topbar">
    <div class="topbar-brand">
        <span class="topbar-ornament">◇</span>
        <div class="topbar-title">
            Panel de Admin
            <span> · Luis & Erendira</span>
        </div>
    </div>
    <div class="topbar-right">
        <?php if ($bdConectada): ?>
            <span class="badge-bd conectado">🟢 BD Conectada</span>
        <?php else: ?>
            <span class="badge-bd demo">⚡ Modo Demo</span>
        <?php endif; ?>
        <a href="logout.php" class="btn-logout">Salir →</a>
    </div>
</nav>

<div class="page-content">

    <!-- FLASH -->
    <?php if ($mensaje): ?>
        <div class="flash <?= $tipo_msg ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <!-- ESTADÍSTICAS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-emoji">👨‍👩‍👧‍👦</div>
            <div class="stat-num"><?= count($familias) ?></div>
            <div class="stat-label">Familias Invitadas</div>
        </div>
        <div class="stat-card">
            <div class="stat-emoji">🎟️</div>
            <div class="stat-num"><?= $total_invitados ?></div>
            <div class="stat-label">Lugares Reservados</div>
        </div>
        <div class="stat-card">
            <div class="stat-emoji">✅</div>
            <div class="stat-num"><?= $total_confirmados ?></div>
            <div class="stat-label">Confirmados</div>
        </div>
        <div class="stat-card">
            <div class="stat-emoji">⏳</div>
            <div class="stat-num"><?= $total_pendientes ?></div>
            <div class="stat-label">Pendientes</div>
        </div>
    </div>

    <!-- BARRA DE PROGRESO -->
    <div class="progreso-section">
        <div class="progreso-header">
            <span class="progreso-title">📊 Progreso de Confirmaciones</span>
            <span class="progreso-pct"><?= $porcentaje ?>%</span>
        </div>
        <div class="progress-bar-bg">
            <div class="progress-bar-fill" id="barraProgreso" style="width: 0%"></div>
        </div>
        <p style="font-size:0.8rem; color:var(--texto-suave); margin-top:0.6rem;">
            <?= $total_confirmados ?> de <?= $total_invitados ?> lugares confirmados
        </p>
    </div>

    <!-- FORMULARIO AGREGAR FAMILIA -->
    <div class="form-agregar">
        <h2 class="form-agregar-title">➕ Agregar Nueva Familia</h2>
        <form method="POST" action="" id="formAgregar">
            <input type="hidden" name="accion" value="agregar_familia">
            <div class="form-row">
                <div class="form-campo">
                    <label for="nombre_familia">Nombre de la familia</label>
                    <input
                        type="text"
                        id="nombre_familia"
                        name="nombre_familia"
                        placeholder="Ej: Familia Hernández"
                        required
                        maxlength="100"
                    >
                </div>
                <div class="form-campo">
                    <label for="lugares">Lugares asignados</label>
                    <input
                        type="number"
                        id="lugares"
                        name="lugares"
                        min="1"
                        max="20"
                        value="1"
                        required
                        style="width:90px"
                    >
                </div>
                <div class="form-campo">
                    <label style="opacity:0">·</label>
                    <button type="submit" class="btn-agregar">+ Agregar Familia</button>
                </div>
            </div>
        </form>
    </div>

    <!-- TABLA DE FAMILIAS -->
    <div class="section-header">
        <h2 class="section-title">👨‍👩‍👧 Lista de Familias Invitadas</h2>
        <span style="font-size:0.8rem; color:var(--texto-suave);">
            <?= count($familias) ?> familia<?= count($familias) !== 1 ? 's' : '' ?> registrada<?= count($familias) !== 1 ? 's' : '' ?>
        </span>
    </div>

    <div class="tabla-wrapper">
        <?php if (empty($familias)): ?>
            <div class="sin-datos">
                <div class="sin-icon">📋</div>
                <p>No hay familias registradas aún.</p>
                <p style="margin-top:0.3rem; font-size:0.8rem;">Agrega la primera familia usando el formulario de arriba.</p>
            </div>
        <?php else: ?>
        <table class="tabla-familias">
            <thead>
                <tr>
                    <th>Familia</th>
                    <th>Lugares</th>
                    <th>Confirmados</th>
                    <th>Estado</th>
                    <th>Nota</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($familias as $f):
                    $confirmados = $f['confirmados'];
                    $lugares     = $f['lugares_asignados'];
                    $pct_mini    = $confirmados !== null ? round(($confirmados / $lugares) * 100) : 0;
                ?>
                <tr>
                    <td><span class="nombre-familia"><?= htmlspecialchars($f['nombre']) ?></span></td>
                    <td>
                        <strong><?= $lugares ?></strong>
                        <span style="color:var(--texto-suave); font-size:0.8rem;"> lugar<?= $lugares !== 1 ? 'es' : '' ?></span>
                    </td>
                    <td>
                        <?php if ($confirmados !== null): ?>
                            <strong><?= $confirmados ?></strong> / <?= $lugares ?>
                            <div class="progress-mini-bg" style="margin-top:4px;">
                                <div class="progress-mini-fill" style="width:<?= $pct_mini ?>%"></div>
                            </div>
                        <?php else: ?>
                            <span style="color:var(--texto-suave); font-size:0.85rem;">Sin confirmar</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($confirmados === null): ?>
                            <span class="badge-confirmado pendiente">⏳ Pendiente</span>
                        <?php elseif ($confirmados > 0): ?>
                            <span class="badge-confirmado si">✅ Confirmado</span>
                        <?php else: ?>
                            <span class="badge-confirmado no">❌ No asiste</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:0.82rem; color:var(--texto-suave); max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <?= $f['nota'] ? htmlspecialchars($f['nota']) : '—' ?>
                    </td>
                    <td>
                        <form method="POST" action="" onsubmit="return confirm('¿Eliminar a <?= htmlspecialchars(addslashes($f['nombre'])) ?>?')">
                            <input type="hidden" name="accion" value="eliminar_familia">
                            <input type="hidden" name="familia_id" value="<?= $f['id'] ?>">
                            <button type="submit" class="btn-eliminar">🗑️ Eliminar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div><!-- /page-content -->

<script>
// Animación barra de progreso al cargar
setTimeout(() => {
    document.getElementById('barraProgreso').style.width = '<?= $porcentaje ?>%';
}, 200);

// Confirmar eliminación con estilo
</script>

</body>
</html>
