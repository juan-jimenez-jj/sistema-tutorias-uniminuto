<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';

checkSession();
checkRole('estudiante');

header('Content-Type: application/json');

if (isset($_GET['docente_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Consulta optimizada usando la tabla docente_materia
    $query = "SELECT m.id, m.codigo, m.nombre 
              FROM materias m
              INNER JOIN docente_materia dm ON m.id = dm.materia_id
              WHERE dm.docente_id = :docente_id 
              AND m.estado = 'activa'
              ORDER BY m.codigo";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':docente_id', $_GET['docente_id']);
    $stmt->execute();
    
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($materias);
} else {
    echo json_encode([]);
}
?>