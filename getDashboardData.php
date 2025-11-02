<?php
header("Access-Control-Allow-Origin: *"); // Ajuste em produção
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'db.php';

// Debug temporário (remover em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Verifica se o parâmetro user_id foi enviado
    if (!isset($_GET['user_id'])) {
        throw new Exception("Parâmetro 'user_id' é obrigatório");
    }

    $user_id = (int) $_GET['user_id'];

    // Verifica se a tabela bookings existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'bookings'");
    if ($stmt->rowCount() === 0) {
        throw new Exception("Tabela 'bookings' não existe!");
    }

    // Ganhos totais do mês para o profissional logado
    $stmt = $pdo->prepare("
        SELECT IFNULL(SUM(total_price),0) as total_ganhos 
        FROM bookings 
        WHERE professional_id = :user_id
        AND MONTH(date) = MONTH(CURDATE()) 
        AND YEAR(date) = YEAR(CURDATE())
    ");
    $stmt->execute(['user_id' => $user_id]);
    $ganhos = $stmt->fetchColumn();

    // Serviços confirmados do mês
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM bookings 
        WHERE professional_id = :user_id
        AND status = 'confirmado' 
        AND MONTH(date) = MONTH(CURDATE()) 
        AND YEAR(date) = YEAR(CURDATE())
    ");
    $stmt->execute(['user_id' => $user_id]);
    $servicos = $stmt->fetchColumn();

    // Cancelados do mês
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM bookings 
        WHERE professional_id = :user_id
        AND status = 'cancelado' 
        AND MONTH(date) = MONTH(CURDATE()) 
        AND YEAR(date) = YEAR(CURDATE())
    ");
    $stmt->execute(['user_id' => $user_id]);
    $cancelados = $stmt->fetchColumn();

    // Ganhos por mês (últimos 6 meses) do profissional
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(date, '%b') as mes, SUM(total_price) as ganhos 
        FROM bookings 
        WHERE professional_id = :user_id
        AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY YEAR(date), MONTH(date)
        ORDER BY date ASC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $grafico = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "ganhos" => (float)$ganhos,
        "servicos" => (int)$servicos,
        "cancelados" => (int)$cancelados,
        "grafico" => $grafico,
        "professionalId" => $user_id
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erro de banco de dados: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erro geral: " . $e->getMessage()]);
}
