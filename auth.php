<?php
include 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// --- Resposta automática para requisição OPTIONS (CORS) ---
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$action = $input['action'] ?? null;

// ========================== LOGIN ==========================
if ($action === "login") {
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');

    if (!$email || !$password) {
        echo json_encode(["success" => false, "message" => "E-mail e senha são obrigatórios."]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM professionals WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['senha_hash'])) {
        unset($user['senha_hash']); // Remove o hash antes de enviar
        echo json_encode([
            "success" => true,
            "message" => "Login realizado com sucesso!",
            "user" => $user
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "E-mail ou senha inválidos."
        ]);
    }
    exit;
}

// ------------------ CADASTRO HIERÁRQUICO ------------------
if ($action === "register") {
    $nome = $input['name'] ?? '';
    $especialty = $input['specialty'] ?? '';
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $criador_id = $input['created_by'] ?? null;

    if (!$nome || !$email || !$password || !$criador_id) {
        echo json_encode(["success" => false, "message" => "Campos obrigatórios ausentes"]);
        exit;
    }

    // Buscar tipo do criador
    $stmt = $pdo->prepare("SELECT tipo FROM professionals WHERE id = ?");
    $stmt->execute([$criador_id]);
    $criador = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$criador) {
        echo json_encode(["success" => false, "message" => "Criador não encontrado"]);
        exit;
    }

    // Define tipo de usuário permitido
    $novoTipo = 'profissional';
    if ($criador['tipo'] === 'admin_master') $novoTipo = 'admin_profissional';
    if ($criador['tipo'] === 'admin_profissional') $novoTipo = 'profissional';

    $hash = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO professionals (name, specialty, email, password_hash, tipo, criado_por)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nome, $especialty, $email, $hash, $novoTipo, $criador_id]);
        echo json_encode(["success" => true, "message" => "Usuário cadastrado com sucesso!", "tipo" => $novoTipo]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Erro: " . $e->getMessage()]);
    }
    exit;
}


// ===================== CADASTRO DE PROFISSIONAL =====================
if ($action === "register") {
    $nome = trim($input['name'] ?? '');
    $especialty = trim($input['specialty'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');
    $tipo = $input['tipo'] ?? 'profissional'; // valores possíveis: admin_master, admin_profissional, profissional
    $criado_por = $input['criado_por'] ?? null; // quem criou (id do admin)

    if (!$nome || !$email || !$password) {
        echo json_encode(["success" => false, "message" => "Campos obrigatórios ausentes."]);
        exit;
    }

    // Verifica se já existe e-mail cadastrado
    $check = $pdo->prepare("SELECT COUNT(*) FROM professionals WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(["success" => false, "message" => "E-mail já está cadastrado."]);
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO professionals (nome, specialty, email, senha_hash, tipo, criado_por)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nome, $especialty, $email, $hash, $tipo, $criado_por]);

        echo json_encode(["success" => true, "message" => "Profissional cadastrado com sucesso!"]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Erro ao cadastrar: " . $e->getMessage()]);
    }
    exit;
}

// ===================== AÇÃO DESCONHECIDA =====================
echo json_encode(["success" => false, "message" => "Ação inválida."]);
