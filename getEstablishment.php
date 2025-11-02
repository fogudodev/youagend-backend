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

$slug = $_GET['slug'] ?? null;

if (!$slug) {
    echo json_encode(["error" => "Slug não informado."]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM establishments WHERE slug = ?");
    $stmt->execute([$slug]);
    $estab = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$estab) {
        echo json_encode(["error" => "Estabelecimento não encontrado."]);
        exit;
    }

    // Busca profissionais associados
    $stmt2 = $pdo->prepare("SELECT id, name, specialty, tipo, plano, trial_fim FROM professionals WHERE id = ?");
    $stmt2->execute([$estab['professional_id']]);
    $estab['professionals'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($estab);

} catch (PDOException $e) {
    echo json_encode(["error" => "Erro ao buscar dados: " . $e->getMessage()]);
}
?>
