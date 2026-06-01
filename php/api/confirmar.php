<?php
// ============================================================
//  API: Guardar confirmación de asistencia
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$familia_id = intval($input['familia_id'] ?? 0);
$personas   = intval($input['personas'] ?? 0);
$nota       = trim($input['nota'] ?? '');

if ($familia_id <= 0 || $personas < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

try {
    require_once '../conexion.php';

    // Verificar que la familia existe y el límite
    $fam = $pdo->prepare("SELECT id, nombre, lugares_asignados FROM familias WHERE id = ?");
    $fam->execute([$familia_id]);
    $familia = $fam->fetch(PDO::FETCH_ASSOC);

    if (!$familia) {
        throw new Exception('Familia no encontrada');
    }

    if ($personas > $familia['lugares_asignados']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Supera el límite de lugares asignados']);
        exit;
    }

    // Insertar o actualizar confirmación
    $stmt = $pdo->prepare("
        INSERT INTO confirmaciones (familia_id, personas_confirmadas, nota)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE personas_confirmadas = VALUES(personas_confirmadas), nota = VALUES(nota)
    ");
    $stmt->execute([$familia_id, $personas, $nota]);

    echo json_encode([
        'success' => true,
        'message' => "Confirmación de {$familia['nombre']} guardada correctamente.",
        'datos'   => ['familia' => $familia['nombre'], 'personas' => $personas]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al guardar: ' . $e->getMessage()]);
}
?>
