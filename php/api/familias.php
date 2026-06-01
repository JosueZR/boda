<?php
// ============================================================
//  API: Devuelve la lista de familias en JSON
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    require_once '../conexion.php';
    $familias = $pdo->query("SELECT id, nombre, lugares_asignados FROM familias ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'familias' => $familias]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Sin conexión a BD', 'familias' => []]);
}
?>
