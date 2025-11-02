<?php
include 'db.php'; // conexão PDO ou mysqli

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['id'] ?? null;
    $nome = $_POST['name'] ?? '';
    $telefone = $_POST['telefone'] ?? '';

    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'ID do usuário não informado']);
        exit;
    }

    // Upload de foto
    $fotoPath = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $fotoPath = 'uploads/' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], $fotoPath);
    }

    // Update no banco
    $sql = "UPDATE profissionals SET name = ?, telefone = ?" . ($fotoPath ? ", foto = ?" : "") . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($fotoPath) {
        $stmt->bind_param("sssi", $nome, $telefone, $fotoPath, $userId);
    } else {
        $stmt->bind_param("ssi", $nome, $telefone, $userId);
    }
    $stmt->execute();

    echo json_encode(['success' => true, 'foto' => $fotoPath]);
}
?>
