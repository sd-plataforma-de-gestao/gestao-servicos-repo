<?php
include("./config/database.php");
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Endpoint para obter dados da dashboard
if (isset($_GET['action']) && $_GET['action'] === 'get_dashboard_data') {
    $data = [];

    // 1. Atendimentos Hoje
    $todayStart = date('Y-m-d 00:00:00');
    $todayEnd = date('Y-m-d 23:59:59');

    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM atendimentos 
        WHERE status_atendimento = 'Concluído' 
          AND criado_em BETWEEN ? AND ?
    ");
    $stmt->bind_param("ss", $todayStart, $todayEnd);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $data['atendimentos_hoje'] = (int)$row['total'];

    // 2. Consultas Crônicas (próximas 2h)
    // Como não temos agendamento real, vamos considerar "crônicos" como tipo_atendimento = 'Acompanhamento'
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM atendimentos 
        WHERE status_atendimento = 'Concluído' 
          AND tipo_atendimento = 'Acompanhamento'
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $data['consultas_cronicas'] = (int)$row['total'];

    // 3. Casos Agudos (esta semana)
    $startOfWeek = date('Y-m-d 00:00:00', strtotime('monday this week'));
    $endOfWeek = date('Y-m-d 23:59:59', strtotime('sunday this week'));

    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM atendimentos 
        WHERE status_atendimento = 'Concluído' 
          AND criado_em BETWEEN ? AND ?
    ");
    $stmt->bind_param("ss", $startOfWeek, $endOfWeek);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $data['casos_agudos'] = (int)$row['total'];

    // 4. Taxa de Adesão (exemplo: média da coluna taxa_adesao na tabela pacientes)
    $stmt = $conn->prepare("
        SELECT AVG(taxa_adesao) as media 
        FROM pacientes 
        WHERE status = 'ativo'
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $data['taxa_adesao'] = round($row['media'] ?? 0, 0); // Arredonda para inteiro

    // 5. Atividades Recentes (últimos 4 atendimentos)
    $stmt = $conn->prepare("
        SELECT 
            a.criado_em,
            p.nome AS paciente_nome,
            a.tipo_atendimento,
            a.status_atendimento
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
            'descricao' => "Consulta {$row['tipo_atendimento']} - Status: {$row['status_atendimento']}",
            'hora' => date('H:i', strtotime($row['criado_em'])),
            'status' => $row['status_atendimento'] === 'Concluído' ? 'completed' : 'pending'
        ];
    }
    $data['atividades_recentes'] = $atividades;

    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Se não for a requisição do dashboard, exibe uma mensagem de erro
http_response_code(400);
echo "Endpoint inválido.";
exit;