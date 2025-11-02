<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'db.php';

$input = json_decode(file_get_contents("php://input"), true);
$id = $input['id'] ?? 0;

if (!$id) {
    echo json_encode(["success" => false, "message" => "ID inválido."]);
    exit;
}

try {
    // Inicia a transação
    $pdo->beginTransaction();

    // Tabelas que dependem de professionals
    $tabelasRelacionadas = [
        'services' => 'professional_id',
        'bookings' => 'professional_id',
        'time_slots' => 'professional_id',
        'unavailable_slots' => 'professional_id'
        // stablishments não entra aqui (só se quiser apagar também quando um profissional é excluído)
    ];

    // Exclui os registros relacionados primeiro
    foreach ($tabelasRelacionadas as $tabela => $coluna) {
        $stmt = $pdo->prepare("DELETE FROM $tabela WHERE $coluna = ?");
        $stmt->execute([$id]);
    }

    // Exclui o profissional principal
    $stmt = $pdo->prepare("DELETE FROM professionals WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        $pdo->commit();
        echo json_encode([
            "success" => true,
            "message" => "Profissional e todos os registros relacionados foram excluídos com sucesso."
        ]);
    } else {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "Profissional não encontrado."]);
    }

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        "success" => false,
        "message" => "Erro ao excluir: " . $e->getMessage()
    ]);
}
