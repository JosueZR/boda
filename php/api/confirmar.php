<?php
// php/api/confirmar.php

// Permitir que el navegador sepa que responderemos con un JSON
header('Content-Type: application/json; charset=utf-8');

// 1. LEER EL CUERPO DE LA PETICIÓN JSON
$json = file_get_contents('php://input');
$body = json_decode($json, true);

// 2. EXTRAER Y SANITIZAR LOS DATOS
$familia_id = (int)($body['familia_id'] ?? 0);
$personas   = (int)($body['personas']   ?? 1);
$nota       = trim($body['nota']        ?? '');
 
if ($familia_id <= 0 || $personas < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}
 
try {
    // 3. REUTILIZAR TU CONEXIÓN EXISTENTE
    require_once '../conexion.php'; // Esto ya te da acceso a la variable $pdo
 
    // Verificar que la familia existe y obtener el límite
    $stmt = $pdo->prepare("SELECT lugares_asignados FROM familias WHERE id = ?");
    $stmt->execute([$familia_id]);
    $familia = $stmt->fetch(PDO::FETCH_ASSOC); // Aseguramos el fetch asociativo
 
    if (!$familia) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Familia no encontrada']);
        exit;
    }
 
    if ($personas > $familia['lugares_asignados']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Supera el límite de lugares']);
        exit;
    }
 
    // 4. INSERT O UPDATE EN LA BASE DE DATOS
    $stmt = $pdo->prepare("
        INSERT INTO confirmaciones (familia_id, personas_confirmadas, nota)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
            personas_confirmadas = VALUES(personas_confirmadas),
            nota = VALUES(nota),
            fecha_confirmacion = CURRENT_TIMESTAMP()
    ");
    $stmt->execute([$familia_id, $personas, $nota ?: null]);
 
    echo json_encode(['success' => true]);
 
} catch (Exception $e) { // Captura tanto PDOException como otros errores
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>