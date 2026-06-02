<?php
// php/api/familias.php

// Permitir que el navegador sepa que responderemos con un JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // Subimos un nivel para encontrar conexion.php
    require_once '../conexion.php';

    // Consultamos las familias registradas en la base de datos
    // Nota: Asegúrate de que los nombres de las columnas coincidan con tu tabla 'familias'
    $stmt = $pdo->query("SELECT id, nombre, lugares_asignados FROM familias ORDER BY nombre ASC");
    $familias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Respondemos con el formato exacto que tu index.html espera
    echo json_encode([
        'success' => true,
        'familias' => $familias
    ]);

} catch (Exception $e) {
    // Si algo falla con la base de datos, enviamos un error claro en formato JSON
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>