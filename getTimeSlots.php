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
include 'db.php'; // seu arquivo de conexão PDO

$professional_id = $_GET['professional_id'] ?? '';
$date = $_GET['date'] ?? '';

if (!$professional_id || !$date) {
    echo json_encode([]);
    exit;
}

// Normaliza formatos
$date = date('Y-m-d', strtotime($date));

// buscar horários bloqueados
$stmt = $pdo->prepare("SELECT time FROM unavailable_slots WHERE professional_id = ? AND date = ?");
$stmt->execute([$professional_id, $date]);
$blocked_times = $stmt->fetchAll(PDO::FETCH_COLUMN);

// buscar horários já agendados
$stmt = $pdo->prepare("SELECT time FROM bookings WHERE professional_id = ? AND date = ?");
$stmt->execute([$professional_id, $date]);
$booked_times = $stmt->fetchAll(PDO::FETCH_COLUMN);

// horários padrão da profissional
$slotsStmt = $pdo->prepare("SELECT time FROM time_slots WHERE professional_id = ?");
$slotsStmt->execute([$professional_id]);
$slots = $slotsStmt->fetchAll(PDO::FETCH_COLUMN);

// combina bloqueados e agendados
$unavailable = array_merge($blocked_times, $booked_times);

$result = [];
foreach ($slots as $slot) {
    $result[] = [
        "time" => $slot,
        "available" => !in_array($slot, $unavailable)
    ];
}

// verifica se o dia todo está bloqueado (time IS NULL significa dia completo indisponível)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM unavailable_slots WHERE professional_id = ? AND date = ? AND time IS NULL");
$stmt->execute([$professional_id, $date]);
$dayBlocked = $stmt->fetchColumn() > 0;

if ($dayBlocked) {
    foreach ($result as &$r) {
        $r['available'] = false;
    }
}

// retornar resultado
echo json_encode($result);
