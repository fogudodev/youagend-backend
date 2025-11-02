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

$slug = $_GET['slug'] ?? '';

if (!$slug) {
    echo json_encode(["success" => false, "message" => "Slug não informado"]);
    exit;
}

// Busca na tabela establishments pelo slug
$stmt = $pdo->prepare("
    SELECT e.id as establishment_id, e.name as establishment_name, e.slug, p.id as professional_id, 
           p.name as professional_name, p.specialty, p.tipo
    FROM establishments e
    LEFT JOIN professionals p ON e.professional_id = p.id
    WHERE e.slug = ?
");
$stmt->execute([$slug]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    echo json_encode(["success" => false, "message" => "Profissional ou estabelecimento não encontrado"]);
    exit;
}

echo json_encode([
    "success" => true,
    "data" => [
        "establishment_id" => $result['establishment_id'],
        "establishment_name" => $result['establishment_name'],
        "professional_id" => $result['professional_id'],
        "name" => $result['professional_name'],
        "specialty" => $result['specialty'],
        "tipo" => $result['tipo']
    ]
]);
