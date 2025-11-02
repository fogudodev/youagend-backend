<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Permitir CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'db.php'; // assume $pdo vindo daqui

// Assegura exceptions
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Função geradora slug curto
function generateSlug($name) {
    $base = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
    $rand = substr(bin2hex(random_bytes(3)), 0, 6);
    return rtrim("$base-$rand", '-');
}

// JSON
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "JSON inválido."]);
    exit;
}

// Campos
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$specialty = trim($input['specialty'] ?? '');
$telefone = trim($input['telefone'] ?? '');
$tipo = 'profissional';
$plano = 'gratuito';

// Serviços e horários
$services = is_array($input['services'] ?? []) ? $input['services'] : [];
$time_slots = is_array($input['time_slots'] ?? []) ? $input['time_slots'] : [];

// Validações
if (!$name || !$email || !$password) {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "Preencha: name, email, password"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "E-mail inválido."]);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "A senha deve ter ao menos 6 caracteres."]);
    exit;
}

// Telefone
if ($telefone !== '') {
    $digits = preg_replace('/\D+/', '', $telefone);
    if (strlen($digits) < 8 || strlen($digits) > 15) {
        http_response_code(422);
        echo json_encode(["success" => false, "message" => "Telefone inválido."]);
        exit;
    }
    $telefone = $digits;
}

// Limpeza Serviços
$cleanServices = [];
foreach ($services as $srv) {
    if (!is_array($srv)) continue;
    $sName = trim($srv['name'] ?? '');
    if ($sName === '') continue;
    $sPrice = isset($srv['price']) ? floatval($srv['price']) : 0.0;
    $sDuration = isset($srv['duration']) ? intval($srv['duration']) : 0;
    $cleanServices[] = ['name' => $sName, 'price' => $sPrice, 'duration' => $sDuration];
}

// Verifica se tem pelo menos 1 serviço
if (empty($cleanServices)) {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "É obrigatório enviar pelo menos 1 serviço."]);
    exit;
}

// Pega o primeiro serviço para os campos obrigatórios da tabela professionals
$firstService = $cleanServices[0];

// Mapeamento dias
$dayMap = [
    "Segunda" => 1,
    "Terça"   => 2,
    "Quarta"  => 3,
    "Quinta"  => 4,
    "Sexta"   => 5,
    "Sábado"  => 6,
    "Domingo" => 7
];

// Limpeza horários
$cleanTimeSlots = [];
foreach ($time_slots as $slot) {
    if (!is_array($slot)) continue;
    $time = trim($slot['time'] ?? '');
    $days = $slot['available_days'] ?? [];

    if (is_string($days)) {
        $daysArr = array_filter(array_map('trim', explode(',', $days)));
    } elseif (is_array($days)) {
        $daysArr = array_filter(array_map('trim', $days));
    } else {
        $daysArr = [];
    }

    $daysNum = [];
    foreach ($daysArr as $d) {
        if (isset($dayMap[$d])) $daysNum[] = $dayMap[$d];
    }

    if ($time === '' || empty($daysNum)) continue;

    $cleanTimeSlots[] = [
        'time' => $time,
        'available_days' => implode(',', $daysNum)
    ];
}

// Verifica duplicidade email
$check = $pdo->prepare("SELECT COUNT(*) FROM professionals WHERE email = ?");
$check->execute([$email]);
if ($check->fetchColumn() > 0) {
    http_response_code(409);
    echo json_encode(["success" => false, "message" => "E-mail já cadastrado."]);
    exit;
}

// Preparação
$hash = password_hash($password, PASSWORD_BCRYPT);
$slug = generateSlug($name);
$trial_inicio = date('Y-m-d H:i:s');
$trial_fim = date('Y-m-d H:i:s', strtotime('+15 days'));

try {
    $pdo->beginTransaction();

    // INSERT principal incluindo service e price obrigatórios
    $stmt = $pdo->prepare("
        INSERT INTO professionals 
        (name, specialty, email, password_hash, tipo, plano, trial_inicio, trial_fim, telefone, slug, service, price)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $name,
        $specialty,
        $email,
        $hash,
        $tipo,
        $plano,
        $trial_inicio,
        $trial_fim,
        $telefone,
        $slug,
        $firstService['name'],
        $firstService['price']
    ]);
    $professional_id = $pdo->lastInsertId();

    // Inserção de todos os serviços na tabela services
    $serviceStmt = $pdo->prepare("INSERT INTO services (professional_id, name, price, duration) VALUES (?, ?, ?, ?)");
    foreach ($cleanServices as $srv) {
        $serviceStmt->execute([$professional_id, $srv['name'], $srv['price'], $srv['duration']]);
    }

    // Inserção dos horários
    $timeStmt = $pdo->prepare("INSERT INTO time_slots (professional_id, time, available_days) VALUES (?, ?, ?)");
    foreach ($cleanTimeSlots as $slot) {
        $timeStmt->execute([$professional_id, $slot['time'], $slot['available_days']]);
    }

    $pdo->commit();

    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Profissional cadastrado com sucesso!",
        "professional_id" => $professional_id,
        "slug" => $slug,
        "url" => "https://youagend.com/$slug"
    ]);
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Erro ao cadastrar profissional: " . $e->getMessage() . " | Payload: " . substr($raw, 0, 2000));
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro interno ao cadastrar."]);
    exit;
}
