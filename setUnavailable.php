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

include '../db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['professional_id']) || !isset($data['date'])) {
    echo json_encode(["error" => "Dados incompletos"]);
    exit;
}

$professional_id = intval($data['professional_id']);
$date = $data['date'];
$time = isset($data['time']) && $data['time'] !== "" ? $data['time'] : null;
$reason = isset($data['reason']) ? $data['reason'] : null;

$stmt = $pdo->prepare("INSERT INTO unavailable_slots (professional_id, date, time, reason) VALUES (?, ?, ?, ?)");
$stmt->execute([$professional_id, $date, $time, $reason]);

echo json_encode(["success" => true]);
