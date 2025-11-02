<?php
include 'db.php';

$slug = $_GET['slug'] ?? '';

if (!$slug) {
    echo "Slug não informado!";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM professionals WHERE slug = ?");
$stmt->execute([$slug]);
$prof = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prof) {
    http_response_code(404);
    echo "Profissional não encontrado!";
    exit;
}

// Aqui você exibe os dados
echo "<h1>{$prof['name']}</h1>";
echo "<p>Email: {$prof['email']}</p>";
echo "<p>Telefone: {$prof['telefone']}</p>";
echo "<p>Especialidade: {$prof['specialty']}</p>";
