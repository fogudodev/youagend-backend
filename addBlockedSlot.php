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
$professional_id = $data['professional_id'] ?? '';
$date = $data['date'] ?? '';
$time = $data['time'] ?? null;
$reason = $data['reason'] ?? '';

if (!$professional_id || !$date) {
    echo json_encode(['success'=>false, 'error'=>'Profissional e data são obrigatórios']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO unavailable_slots (professional_id, date, time, reason) VALUES (?, ?, ?, ?)");
$success = $stmt->execute([$professional_id, $date, $time, $reason]);

if ($success) {
    $id = $pdo->lastInsertId();
    echo json_encode(['success'=>true, 'slot'=>['id'=>$id,'professional_id'=>$professional_id,'date'=>$date,'time'=>$time,'reason'=>$reason]]);
} else {
    echo json_encode(['success'=>false, 'error'=>'Erro ao adicionar bloqueio']);
}
