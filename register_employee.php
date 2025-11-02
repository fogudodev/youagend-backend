<?php
include 'db.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

$input = json_decode(file_get_contents("php://input"), true);

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');
$specialty = trim($input['specialty'] ?? '');
$telefone = trim($input['telefone'] ?? '');
$admin_id = intval($input['admin_id'] ?? 0); // id do dono do salão

if (!$name || !$email || !$password || !$admin_id) {
    echo json_encode(["success" => false, "message" => "Campos obrigatórios faltando."]);
    exit;
}

// verifica se admin existe e pega establishment
$stmt = $pdo->prepare("SELECT e.id FROM establishments e WHERE e.professional_id = ?");
$stmt->execute([$admin_id]);
$estab = $stmt->fetch();

if (!$estab) {
    echo json_encode(["success" => false, "message" => "Salão não encontrado para este administrador."]);
    exit;
}

$establishment_id = $estab['id'];

// Verifica se o e-mail já existe
$check = $pdo->prepare("SELECT COUNT(*) FROM professionals WHERE email = ?");
$check->execute([$email]);
if ($check->fetchColumn() > 0) {
    echo json_encode(["success" => false, "message" => "E-mail já cadastrado."]);
    exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare("
        INSERT INTO professionals (name, specialty, email, password_hash, tipo, telefone, plano, establishment_id)
        VALUES (?, ?, ?, ?, 'funcionario', ?, 'vinculado', ?)
    ");
    $stmt->execute([$name, $specialty, $email, $hash, $telefone, $establishment_id]);

    echo json_encode(["success" => true, "message" => "Funcionário cadastrado com sucesso!"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Erro ao cadastrar funcionário: " . $e->getMessage()]);
}
?>
