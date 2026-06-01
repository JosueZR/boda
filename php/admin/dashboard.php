<?php
// ============================================================
//  DASHBOARD ADMIN — Panel de Familias y Confirmaciones
// ============================================================
session_start();

// Módulo para cerrar sesión de manera segura y redirigir al Index de la boda
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: ../../index.html");
    exit;
}

// Proteger: solo admins logueados
if (!isset($_SESSION['admin_logueado']) || $_SESSION['admin_logueado'] !== true) {
    header("Location: ../../public/admin/login.php");
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
    <link rel="stylesheet" href="../../public/css/dashboard.css">
</head>
<body>

<nav class="topbar">
    <div class="topbar-brand">
        <span class="topbar-ornament">◇</span>
        <div class="topbar-title">
            Panel de Admin
            <span> · Luis & Erendira</span>
        </div>
    </div>
    <div class="topbar-right">
        <a href="dashboard.php?action=logout" class="btn-logout">Salir →</a>
    </div>
</nav>

<div class="page-content">

    <?php if ($mensaje): ?>
        <div class="flash <?= $tipo_msg ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

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

</div>

<script>
// Animación de la barra de progreso
setTimeout(() => {
    document.getElementById('barraProgreso').style.width = '<?= $porcentaje ?>%';
}, 200);
</script>

</body>
</html>