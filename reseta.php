<?php
include 'db.php';

// Configura aqui o e-mail do usuário e a nova senha
$email = 'luanamara@youagend.com';      // usuário que terá a senha resetada
$novaSenha = '123456';           // nova senha

if (!$email || !$novaSenha) {
    echo json_encode(['success' => false, 'message' => 'E-mail e nova senha obrigatórios.']);
    exit;
}

try {
    // Gera o hash seguro da nova senha
    $hash = password_hash($novaSenha, PASSWORD_BCRYPT);

    // Atualiza a senha no banco
    $stmt = $pdo->prepare("UPDATE professionals SET password_hash = ? WHERE email = ?");
    $stmt->execute([$hash, $email]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Senha atualizada com sucesso!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar senha: ' . $e->getMessage()]);
}
