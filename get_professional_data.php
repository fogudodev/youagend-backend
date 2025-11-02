<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
  http_response_code(200);
  exit();
}

include 'db.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
  echo json_encode(["success" => false, "message" => "Slug não fornecido"]);
  exit;
}

try {
  $stmt = $pdo->prepare("
    SELECT e.id AS establishment_id, e.name AS establishment_name, 
           p.id AS professional_id, p.name, p.telefone, p.specialty, p.email
    FROM establishments e
    JOIN professionals p ON e.professional_id = p.id
    WHERE e.slug = ?
  ");
  $stmt->execute([$slug]);
  $data = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$data) {
    echo json_encode(["success" => false, "message" => "Profissional não encontrado"]);
    exit;
  }

  // Serviços
  $stmt2 = $pdo->prepare("SELECT id, name, price, duration FROM services WHERE rel_professional_id = ?");
  $stmt2->execute([$data['professional_id']]);
  $services = $stmt2->fetchAll(PDO::FETCH_ASSOC);

  // Dias e horários
  $stmt3 = $pdo->prepare("SELECT id, dia, horario FROM available_days WHERE rel_professional_id = ?");
  $stmt3->execute([$data['professional_id']]);
  $available_days = $stmt3->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    "success" => true,
    "professional" => $data,
    "services" => $services,
    "available_days" => $available_days
  ]);
} catch (PDOException $e) {
  echo json_encode(["success" => false, "message" => "Erro: " . $e->getMessage()]);
}
?>
