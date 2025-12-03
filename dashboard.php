<?php
include("./config/database.php");
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (isset($_GET['action']) && $_GET['action'] === 'get_dashboard_data') {
    $data = [];

    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM atendimentos 
        WHERE status_atendimento = 'Concluído' 
          AND DATE(criado_em) = CURDATE()
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $data['atendimentos_hoje'] = (int)($row['total'] ?? 0);

    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM atendimentos a
        JOIN pacientes p ON a.paciente_id = p.id
        WHERE a.status_atendimento = 'Concluído'
          AND p.tipo_paciente = 'cronico'
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $data['consultas_cronicas'] = (int)($row['total'] ?? 0);

    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM atendimentos a
        JOIN pacientes p ON a.paciente_id = p.id
        WHERE a.status_atendimento = 'Concluído'
          AND p.tipo_paciente = 'agudo'
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $data['casos_agudos'] = (int)($row['total'] ?? 0);

    $stmt = $conn->prepare("
        SELECT AVG(taxa_adesao) as media 
        FROM pacientes 
        WHERE status = 'ativo'
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $data['taxa_adesao'] = round($row['media'] ?? 0, 0);

    $stmt = $conn->prepare("
        SELECT 
            a.criado_em,
            p.nome AS paciente_nome,
            a.tipo_atendimento,
            a.status_atendimento,
            p.tipo_paciente
        FROM atendimentos a
        JOIN pacientes p ON a.paciente_id = p.id
        ORDER BY a.criado_em DESC
        LIMIT 4
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    $atividades = [];
    while ($row = $result->fetch_assoc()) {
        $atividades[] = [
            'nome' => $row['paciente_nome'],
            'descricao' => "{$row['tipo_atendimento']} ({$row['tipo_paciente']})",
            'hora' => date('H:i', strtotime($row['criado_em'])),
            'status' => $row['status_atendimento'] === 'Concluído' ? 'completed' : 'pending'
        ];
    }
    $data['atividades_recentes'] = $atividades;

    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo "Endpoint inválido.";
exit;