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

$professional_id = $_GET['professional_id'] ?? null;
$date = $_GET['date'] ?? null;

if (!$professional_id || !$date) {
    echo json_encode([]);
    exit;
}

$date = date('Y-m-d', strtotime($date));
$dayOfWeek = date('w', strtotime($date)); // 0 = domingo ... 6 = sábado

// 🔹 Busca horários cadastrados no banco para esse profissional
$stmt = $pdo->prepare("SELECT time, available_days FROM time_slots WHERE professional_id = ?");
$stmt->execute([$professional_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Filtra apenas horários compatíveis com o dia da semana
$slots = [];
foreach ($rows as $row) {
    $availableDays = explode(',', $row['available_days']);
    if (in_array($dayOfWeek, array_map('intval', $availableDays))) {
        $slots[] = substr($row['time'], 0, 5); // garante formato HH:MM
    }
}

// 🔹 Busca horários já agendados
$stmt = $pdo->prepare("SELECT time FROM bookings WHERE professional_id = ? AND date = ?");
$stmt->execute([$professional_id, $date]);
$booked = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 🔹 Monta resposta
$result = [];
foreach ($slots as $slot) {
    $result[] = [
        "time" => $slot,
        "available" => !in_array($slot, $booked)
    ];
}

// 🔹 Checa se o dia inteiro foi bloqueado em unavailable_slots
$stmt = $pdo->prepare("SELECT COUNT(*) FROM unavailable_slots WHERE professional_id = ? AND date = ? AND time IS NULL");
$stmt->execute([$professional_id, $date]);
$dayBlocked = $stmt->fetchColumn() > 0;

if ($dayBlocked) {
    foreach ($result as &$r) {
        $r['available'] = false;
    }
}

echo json_encode($result);
