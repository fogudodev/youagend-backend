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

// Ganhos totais do mês
$stmt = $pdo->query("SELECT IFNULL(SUM(price),0) as total_ganhos 
                     FROM bookings 
                     WHERE MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())");
$ganhos = $stmt->fetchColumn();

// Serviços realizados
$stmt = $pdo->query("SELECT COUNT(*) FROM bookings 
                     WHERE status = 'confirmado' 
                     AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())");
$servicos = $stmt->fetchColumn();

// Cancelados
$stmt = $pdo->query("SELECT COUNT(*) FROM bookings 
                     WHERE status = 'cancelado' 
                     AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())");
$cancelados = $stmt->fetchColumn();

// Ganhos por mês (últimos 6 meses)
$stmt = $pdo->query("SELECT DATE_FORMAT(date, '%b') as mes, SUM(price) as ganhos 
                     FROM bookings 
                     WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                     GROUP BY YEAR(date), MONTH(date)
                     ORDER BY date ASC");
$grafico = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "ganhos" => $ganhos,
    "servicos" => $servicos,
    "cancelados" => $cancelados,
    "grafico" => $grafico
]);

/* SUGESTÃO: substitua chamadas como $pdo->query($sql) por:
$stmt = $pdo->prepare($sql);
$stmt->execute([$param1, $param2]);
*/
