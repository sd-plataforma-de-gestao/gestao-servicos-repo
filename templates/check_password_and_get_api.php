<?php
session_start();
header('Content-Type: application/json');

include(__DIR__ . '/../config/database.php'); 

if (!isset($conn)) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados.']);
    exit;
}

$farmaceuticoId = $_SESSION['farmaceutico_id'] ?? null;
if (!$farmaceuticoId) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));
$password = $data->password ?? null;

if (!$password) {
    echo json_encode(['success' => false, 'message' => 'Digite a senha para continuar.']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT senha, api_key_gemini FROM farmaceuticos WHERE id = ?");
    $stmt->bind_param("i", $farmaceuticoId); 
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if ($user && $password === $user['senha']) {
        
        $apiKey = $user['api_key_gemini'] ?? ''; 
        
        echo json_encode([
            'success' => true, 
            'apiKey' => $apiKey
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Senha de login incorreta.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro de processamento no servidor.']);
}

$conn->close();
?>