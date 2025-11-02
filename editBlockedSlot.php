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

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;
$reason = $data['reason'] ?? '';

if (!$id) { echo json_encode(['success'=>false, 'error'=>'ID inválido']); exit; }

$stmt = $pdo->prepare("UPDATE unavailable_slots SET reason = ? WHERE id = ?");
$success = $stmt->execute([$reason, $id]);

echo json_encode(['success'=>$success]);
