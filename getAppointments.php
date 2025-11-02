<?php
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



include 'db.php';

// Recebe dados de autenticação
$user_id = $_GET['user_id'] ?? null;         // ID do usuário logado
$user_type = $_GET['user_type'] ?? 'profissional'; // admin_master | admin_profissional | profissional

$filter_date = $_GET['date'] ?? null;
$filter_service = $_GET['service'] ?? null;

try {
    if ($user_type === 'admin_master') {
        // Vê todos os agendamentos
        $sql = "SELECT b.*, p.name AS professional_name FROM bookings b
                JOIN professionals p ON b.professional_id = p.id
                WHERE 1=1";
        $params = [];

    } elseif ($user_type === 'admin_profissional') {
        // Vê todos os agendamentos dos profissionais do salão do dono
        // Supondo que a tabela professionals tem um campo 'salon_owner_id' referenciando o admin_profissional
        $sql = "SELECT b.*, p.name AS professional_name FROM bookings b
                JOIN professionals p ON b.professional_id = p.id
                WHERE p.salon_owner_id = ?";
        $params = [$user_id];

    } else {
        // profissional vê só os seus agendamentos
        $sql = "SELECT b.*, p.name AS professional_name FROM bookings b
                JOIN professionals p ON b.professional_id = p.id
                WHERE professional_id = ?";
        $params = [$user_id];
    }

    // Filtros opcionais
    if ($filter_date) {
        $sql .= " AND date = ?";
        $params[] = $filter_date;
    }
    if ($filter_service) {
        $sql .= " AND service LIKE ?";
        $params[] = "%$filter_service%";
    }

    $sql .= " ORDER BY date, time";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($bookings);

} catch (Exception $e) {
    echo json_encode(["error" => "Erro ao buscar agendamentos", "details" => $e->getMessage()]);
}
?>
