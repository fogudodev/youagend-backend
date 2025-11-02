<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'db.php';

// Recebe JSON
$data = json_decode(file_get_contents("php://input"), true);

// DEBUG: salva o que chega do frontend
file_put_contents("debug.log", date('Y-m-d H:i:s') . " - " . print_r($data, true) . PHP_EOL, FILE_APPEND);

$user_id = isset($data['user_id']) ? (int)$data['user_id'] : null;
$user_type = $data['user_type'] ?? 'profissional';
$booking_id = isset($data['booking_id']) ? (int)$data['booking_id'] : null;
$new_status = $data['status'] ?? null;

if (!$user_id || !$booking_id || !$new_status) {
    echo json_encode(["error" => "Parâmetros obrigatórios ausentes"]);
    exit;
}

try {
    // Busca agendamento e profissional
    $stmt = $pdo->prepare("
        SELECT b.*, p.id AS professional_id, p.salon_owner_id
        FROM bookings b
        JOIN professionals p ON b.professional_id = p.id
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(["error" => "Agendamento não encontrado"]);
        exit;
    }

    // Controle de permissão
    if ($user_type === 'admin_profissional') {
        if ($booking['salon_owner_id'] != $user_id) {
            echo json_encode(["error" => "Você não tem permissão para alterar este agendamento"]);
            exit;
        }
    } elseif ($user_type === 'profissional') {
        if ($booking['professional_id'] != $user_id) {
            echo json_encode(["error" => "Você só pode alterar seus próprios agendamentos"]);
            exit;
        }
    } else {
        echo json_encode(["error" => "Tipo de usuário não autorizado"]);
        exit;
    }

    // Atualiza status
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $booking_id]);

    echo json_encode(["success" => true, "message" => "Status atualizado com sucesso"]);

} catch (Exception $e) {
    echo json_encode([
        "error" => "Erro ao atualizar status",
        "details" => $e->getMessage()
    ]);
}
?>
