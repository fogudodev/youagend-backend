<?php
// Permitir CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Responder requisição preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// -------------------------------
// Conexão com o banco
// -------------------------------
include 'db.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo json_encode(["error" => "Erro na conexão com o banco", "details" => $e->getMessage()]);
    exit;
}

// -------------------------------
// Recebe dados da requisição
// -------------------------------
$data = json_decode(file_get_contents("php://input"), true);

$professional_id = $data['professional_id'] ?? null;
$date = $data['date'] ?? null;
$time = $data['time'] ?? null;
$name = $data['name'] ?? null;
$phone = $data['phone'] ?? null;
$services = $data['services'] ?? null; // array de serviços {name, price}
$totalPrice = $data['totalPrice'] ?? null;

// -------------------------------
// Validação dos parâmetros obrigatórios
// -------------------------------
if (!$professional_id || !$date || !$time || !$name || !$phone || !$services || !is_array($services) || count($services) === 0 || !$totalPrice) {
    echo json_encode(["error" => "Parâmetros inválidos ou incompletos"]);
    exit;
}

// Normaliza formatos
$date = date('Y-m-d', strtotime($date));
$time = substr($time, 0, 5);

// -------------------------------
// Verifica se o profissional existe
// -------------------------------
$stmt = $pdo->prepare("SELECT COUNT(*) FROM professionals WHERE id = ?");
$stmt->execute([$professional_id]);
if ($stmt->fetchColumn() == 0) {
    echo json_encode(["error" => "Profissional não encontrado"]);
    exit;
}

// -------------------------------
// Verifica se já existe agendamento nesse horário
// -------------------------------
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE professional_id = ? AND date = ? AND time = ?");
$stmt->execute([$professional_id, $date, $time]);
if ($stmt->fetchColumn() > 0) {
    echo json_encode(["error" => "Não é possível realizar o agendamento. Horário já ocupado."]);
    exit;
}

// -------------------------------
// Insere novo agendamento
// -------------------------------
try {
    // Converte array de serviços em JSON
    $services_json = json_encode($services);

    $stmt = $pdo->prepare("
        INSERT INTO bookings (professional_id, date, time, customer_name, customer_phone, services, total_price)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $ok = $stmt->execute([$professional_id, $date, $time, $name, $phone, $services_json, $totalPrice]);

    if ($ok) {
        echo json_encode(["success" => true, "message" => "Agendamento realizado com sucesso."]);
    } else {
        $errorInfo = $stmt->errorInfo();
        echo json_encode(["error" => "Erro ao salvar agendamento", "details" => $errorInfo]);
    }
} catch (Exception $e) {
    echo json_encode(["error" => "Erro ao salvar agendamento", "details" => $e->getMessage()]);
}
?>
