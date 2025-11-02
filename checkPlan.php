<?php
include 'db.php';
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

$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["error" => "ID do usuário não informado."]);
    exit;
}

$stmt = $pdo->prepare("SELECT plano, trial_fim, ativo FROM professionals WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["error" => "Usuário não encontrado."]);
    exit;
}

if (!$user['ativo']) {
    echo json_encode(["error" => "Conta inativa."]);
    exit;
}

if ($user['plano'] === 'trial' && strtotime($user['trial_fim']) < time()) {
    echo json_encode(["error" => "Seu período gratuito expirou. Adquira um plano para continuar."]);
    exit;
}

// Se passou por todos os testes, continua normalmente
echo json_encode(["success" => true, "message" => "Acesso liberado."]);
?>
