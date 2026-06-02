<?php
// ============================================================
//  API: Leer y guardar configuración de la boda (fecha, etc.)
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// ── Fallback si no hay BD ──
$FECHA_DEFAULT = '2026-02-14T16:00:00';

// ── GET: devolver configuración ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        require_once '../conexion.php';

        $stmt = $pdo->query("SELECT clave, valor FROM configuracion");
        $config = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $config[$row['clave']] = $row['valor'];
        }

        echo json_encode([
            'success'    => true,
            'fecha_boda' => $config['fecha_boda'] ?? $FECHA_DEFAULT,
            'texto_fecha'=> $config['texto_fecha'] ?? 'Sábado · 14 de Febrero · 2026',
            'fecha_limite_rsvp' => $config['fecha_limite_rsvp'] ?? '10 de Enero · 2026',
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success'    => false,
            'fecha_boda' => $FECHA_DEFAULT,
            'texto_fecha'=> 'Sábado · 14 de Febrero · 2026',
            'fecha_limite_rsvp' => '10 de Enero · 2026',
        ]);
    }
    exit;
}

// ── POST: guardar configuración (solo desde admin con sesión) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    if (!isset($_SESSION['admin_logueado']) || $_SESSION['admin_logueado'] !== true) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $fecha_boda        = trim($input['fecha_boda']        ?? '');
    $texto_fecha       = trim($input['texto_fecha']       ?? '');
    $fecha_limite_rsvp = trim($input['fecha_limite_rsvp'] ?? '');

    if (empty($fecha_boda)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'La fecha de la boda es requerida']);
        exit;
    }

    try {
        require_once '../conexion.php';

        // Crear tabla si no existe
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS configuracion (
                clave VARCHAR(50) PRIMARY KEY,
                valor TEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        // Guardar cada clave
        $stmt = $pdo->prepare("
            INSERT INTO configuracion (clave, valor)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()
        ");

        $stmt->execute(['fecha_boda',        $fecha_boda]);
        $stmt->execute(['texto_fecha',        $texto_fecha]);
        $stmt->execute(['fecha_limite_rsvp',  $fecha_limite_rsvp]);

        echo json_encode([
            'success' => true,
            'message' => 'Configuración guardada correctamente.',
            'fecha_boda' => $fecha_boda,
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error al guardar: ' . $e->getMessage()]);
    }
    exit;
}
?>
