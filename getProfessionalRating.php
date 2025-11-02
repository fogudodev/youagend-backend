<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");
include 'db.php';

$userId = $_GET['user_id'] ?? null; // ID do profissional logado

if (!$userId) {
    echo json_encode(["error" => "Usuário não informado"]);
    exit;
}

// Exemplo simples: buscar dashboard
$stmt = $pdo->prepare("
    SELECT 
        SUM(ganhos) as ganhos,
        COUNT(*) as servicos,
        SUM(CASE WHEN status='cancelado' THEN 1 ELSE 0 END) as cancelados
    FROM bookings
    WHERE professional_id = :user_id
");
$stmt->execute([":user_id" => $userId]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

$response = [
    "ganhos" => floatval($data['ganhos'] ?? 0),
    "servicos" => intval($data['servicos'] ?? 0),
    "cancelados" => intval($data['cancelados'] ?? 0),
    "profissionalId" => intval($userId), // ✅ Isso é importante para o fetch de avaliação
    "grafico" => [] // se você tiver dados para gráfico
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
