<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include 'db.php';

$professional_id = $_GET['professional_id'] ?? null;
if (!$professional_id) {
    echo json_encode([]);
    exit;
}

// Busca serviços do profissional
$stmt = $pdo->prepare("SELECT id, name, price FROM services WHERE professional_id = ?");
$stmt->execute([$professional_id]);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($services);
?>
