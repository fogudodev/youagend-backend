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

$professional_id = intval($_GET['professional_id'] ?? 0);
if (!$professional_id) { echo json_encode([]); exit; }

$stmt = $pdo->prepare("SELECT available_days FROM time_slots WHERE professional_id = ?");
$stmt->execute([$professional_id]);
$rows = $stmt->fetchAll();

$availableDays = [];
foreach($rows as $row) {
    $days = explode(',', $row['available_days']);
    $availableDays = array_merge($availableDays, $days);
}
$availableDays = array_map('intval', array_unique($availableDays));

$dates = [];
$today = new DateTime();
for ($i = 1; $i <= 21; $i++) {
    $date = (clone $today)->modify("+$i day");
    if (in_array((int)$date->format('w'), $availableDays)) {
        $dates[] = $date->format('Y-m-d');
    }
}
echo json_encode($dates);
?>
