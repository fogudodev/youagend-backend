<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'db.php';

// Recebe dados do front
$input = json_decode(file_get_contents("php://input"), true);
$tipo = $input['tipo'] ?? '';
$user_id = $input['user_id'] ?? 0;

try {
    if ($tipo === 'admin_master') {
        // Pode ver todos
        $stmt = $pdo->query("SELECT id, name AS nome, email, telefone, tipo FROM professionals ORDER BY id DESC");
    } elseif ($tipo === 'admin_profissional') {
        // Pode ver apenas os criados por ele (seu salão)
        $stmt = $pdo->prepare("SELECT id, name AS nome, email, telefone, tipo 
                               FROM professionals 
                               WHERE criado_por = :id OR id = :id 
                               ORDER BY id DESC");
        $stmt->execute([':id' => $user_id]);
    } else {
        // Profissional: apenas ele mesmo
        $stmt = $pdo->prepare("SELECT id, name AS nome, email, telefone, tipo 
                               FROM professionals 
                               WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
    }

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);
} catch (Exception $e) {
    echo json_encode(["error" => "Erro ao buscar usuários: " . $e->getMessage()]);
}
