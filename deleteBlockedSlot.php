<?php
// Permitir CORS
header("Access-Control-Allow-Origin: *"); // ou restringir se quiser
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

// Responder requisição preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


// Responde ao preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') { http_response_code(405); exit; }

$id = $_GET['id'] ?? null;

if (!$id) { echo json_encode(['success'=>false, 'error'=>'ID inválido']); exit; }

$stmt = $pdo->prepare("DELETE FROM unavailable_slots WHERE id = ?");
$success = $stmt->execute([$id]);

echo json_encode(['success'=>$success]);
