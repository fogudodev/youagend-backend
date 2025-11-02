<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$professional_id = $_GET['professional_id'] ?? null;
$date = $_GET['date'] ?? null;
$status = $_GET['status'] ?? null;

if (!$professional_id) {
    echo json_encode(["error" => "Profissional não informado"]);
    exit;
}

// 🔹 Adicionamos a coluna `service` na seleção
$query = "
    SELECT id, professional_id, date, time, services, customer_name, customer_phone, customer_email, status, created_at
    FROM bookings
    WHERE professional_id = :professional_id
";

$params = [":professional_id" => $professional_id];

if ($date) {
    $query .= " AND date = :date";
    $params[":date"] = $date;
}

if ($status) {
    $query .= " AND status = :status";
    $params[":status"] = $status;
}

$query .= " ORDER BY date ASC, time ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Incluímos o campo `service` no JSON
$formatted = array_map(function ($b) {
    $services = $b["services"] ?? "[]"; // pode ser string JSON ou vazio

    // Decodifica JSON para array
    $servicesArray = json_decode($services, true);

    // Se não for array, transforma em array vazio
    if (!is_array($servicesArray)) $servicesArray = [];

    // Agora extrai apenas os nomes
    $serviceNames = array_map(function($s) {
        return $s['name'] ?? '';
    }, $servicesArray);

    return [
        "id" => $b["id"],
        "professionalId" => (int)$b["professional_id"],
        "date" => $b["date"],
        "time" => $b["time"],
        "service" => $serviceNames, // agora só nomes
        "customer" => [
            "name" => $b["customer_name"],
            "phone" => $b["customer_phone"] ?? "",
            "email" => $b["customer_email"] ?? ""
        ],
        "status" => $b["status"],
        "createdAt" => $b["created_at"]
    ];
}, $bookings);

echo json_encode($formatted, JSON_UNESCAPED_UNICODE);

