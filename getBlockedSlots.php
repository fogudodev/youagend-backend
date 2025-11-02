<?php
// Permitir CORS
header("Access-Control-Allow-Origin: *"); // ajustar se necessário
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Responder preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'db.php';

// Recebe ID do profissional logado
$professional_id = $_GET['professional_id'] ?? '';

if (!$professional_id) {
    echo json_encode(['success' => false, 'error' => 'ID do profissional não fornecido']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, professional_id, date, time, reason FROM unavailable_slots WHERE professional_id = ? ORDER BY date, time");
    $stmt->execute([$professional_id]);
    $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Garantir valores consistentes
    $slots = array_map(fn($s) => [
        'id' => (int)$s['id'],
        'professional_id' => $s['professional_id'],
        'date' => $s['date'],
        'time' => $s['time'] ?? null,
        'reason' => $s['reason'] ?? ''
    ], $slots);

    echo json_encode(['success' => true, 'slots' => $slots]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
