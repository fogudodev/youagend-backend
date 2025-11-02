<?php
include 'db.php';

/// Permitir CORS
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

// === ENTRADA JSON ===
$input = json_decode(file_get_contents("php://input"), true);
$action = $input['action'] ?? null;

// Função utilitária para responder JSON
function respond($success, $message = '', $data = null)
{
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) $response['user'] = $data;
    echo json_encode($response);
    exit;
}

// ==========================================================
// LOGIN
// ==========================================================
if ($action === "login") {
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');

    if (!$email || !$password) {
        respond(false, "E-mail ou senha inválidos.");
    }

    try {
        $stmt = $pdo->prepare("
    SELECT id, name, email, tipo, plano, specialty, foto, password_hash
    FROM professionals
    WHERE email = ?
    LIMIT 1
");

        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica se usuário existe e senha é válida
        // LOGIN
        $hash = $user['password_hash'] ?? null;
        if ($user && $hash && password_verify($password, $hash)) {
            unset($user['password_hash']);
            respond(true, "Login bem-sucedido.", $user);
        } else {
            respond(false, "E-mail ou senha inválidos.");
        }
    } catch (PDOException $e) {
        respond(false, "Erro ao conectar ao servidor: " . $e->getMessage());
    }
}

// ==========================================================
// REGISTRO DE PROFISSIONAL
// ==========================================================
if ($action === "register") {
    $name = trim($input['name'] ?? '');
    $specialty = trim($input['specialty'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');
    $tipo = $input['tipo'] ?? 'profissional';
    $plano = $input['plano'] ?? 'gratuito';
    $criado_por = $input['criado_por'] ?? null;

    if (!$name || !$email || !$password) {
        respond(false, "Preencha todos os campos obrigatórios.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, "E-mail inválido.");
    }

    try {
        // Verifica e-mail duplicado
        $check = $pdo->prepare("SELECT COUNT(*) FROM professionals WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetchColumn() > 0) {
            respond(false, "E-mail já cadastrado.");
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("
            INSERT INTO professionals (name, specialty, email, password_hash, tipo, plano, criado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $specialty, $email, $hash, $tipo, $plano, $criado_por]);

        respond(true, "Profissional cadastrado com sucesso!");
    } catch (PDOException $e) {
        respond(false, "Erro ao cadastrar profissional.");
    }
}

// ==========================================================
// AÇÃO INVÁLIDA
// ==========================================================
respond(false, "Ação inválida.");
