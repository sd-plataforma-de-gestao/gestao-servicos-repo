// DADOS FICTÍCIOS, ADICONADO PARA SIMULAR A CONEXÃO COM O BANCO DE DADOS
const mockData = {
    atendimentos: [
        { id: 101, paciente: 'Maria Souza', farmaceutico: 'Dr. André', data: '2025-09-01', tipo: 'Crônico', status: 'Concluído' },
        { id: 102, paciente: 'João Lima', farmaceutico: 'Dra. Carolina', data: '2025-09-02', tipo: 'Agudo', status: 'Pendente' },
        { id: 103, paciente: 'Ana Paula', farmaceutico: 'Dr. André', data: '2025-09-03', tipo: 'Crônico', status: 'Concluído' },
        { id: 104, paciente: 'Carlos Silva', farmaceutico: 'Dra. Luana', data: '2025-09-04', tipo: 'Agudo', status: 'Cancelado' },
        { id: 105, paciente: 'Fernanda Gomes', farmaceutico: 'Dra. Luana', data: '2025-09-05', tipo: 'Crônico', status: 'Concluído' },
        { id: 106, paciente: 'Roberto Dias', farmaceutico: 'Dr. André', data: '2025-09-06', tipo: 'Agudo', status: 'Concluído' },
        { id: 107, paciente: 'Patrícia Cruz', farmaceutico: 'Dra. Luana', data: '2025-09-07', tipo: 'Agudo', status: 'Concluído' },
        { id: 108, paciente: 'Gustavo Ribeiro', farmaceutico: 'Dr. André', data: '2025-09-08', tipo: 'Crônico', status: 'Pendente' },
    ],
    pacientes: 120,
    medicamentos: [
        { nome: 'Amoxicilina', dispensados: 50 },
        { nome: 'Ibuprofeno', dispensados: 85 },
        { nome: 'Sinvastatina', dispensados: 40 },
        { nome: 'Loratadina', dispensados: 65 },
        { nome: 'Metformina', dispensados: 30 },
        { nome: 'Dipirona', dispensados: 90 },
    ],
};

// ======================================================================
// Funções para preencher os cards, gráficos e tabela
// ======================================================================

function preencherCardsInsights() {
    const { atendimentos, pacientes, medicamentos } = mockData;
    const atendimentosConcluidos = atendimentos.filter(a => a.status === 'Concluído' || a.status === 'Concluido').length;
    const atendimentosCronicos = atendimentos.filter(a => (a.status === 'Concluído' || a.status === 'Concluido') && a.tipo === 'Crônico').length;
    const totalMedicamentosDispensados = medicamentos.reduce((sum, med) => sum + med.dispensados, 0);

    document.getElementById('atendimentos-mes').textContent = atendimentosConcluidos;
    document.getElementById('pacientes-ativos').textContent = pacientes;
    document.getElementById('atendimentos-cronicos').textContent = atendimentosCronicos;
    document.getElementById('medicamentos-dispensados').textContent = totalMedicamentosDispensados;
}

function criarGraficoAtendimentosPorTipo() {
    const atendimentosPorTipo = mockData.atendimentos.reduce((acc, atendimento) => {
        acc[atendimento.tipo] = (acc[atendimento.tipo] || 0) + 1;
        return acc;
    }, {});
    const tipos = Object.keys(atendimentosPorTipo);
    const quantidades = Object.values(atendimentosPorTipo);
    const ctx = document.getElementById('atendimentosPorTipo').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: tipos,
            datasets: [{
                label: 'Atendimentos por Tipo',
                data: quantidades,
                backgroundColor: ['#1a6d40', '#ffc107', '#dc3545', '#0d6efd'],
                hoverOffset: 4
            }]
        },
        options: { responsive: true }
    });
}

function criarGraficoTopMedicamentos() {
    const topMedicamentos = mockData.medicamentos.sort((a, b) => b.dispensados - a.dispensados).slice(0, 5);
    const nomes = topMedicamentos.map(m => m.nome);
    const dispensados = topMedicamentos.map(m => m.dispensados);
    const ctx = document.getElementById('topMedicamentos').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: nomes,
            datasets: [{
                label: 'Unidades Dispensadas',
                data: dispensados,
                backgroundColor: 'rgba(26, 109, 64, 0.7)',
                borderColor: 'rgba(26, 109, 64, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, title: { display: true, text: 'Quantidade' } } }
        }
    });
}

function renderizarTabelaRelatorio() {
    const tbody = document.getElementById('relatorio-atendimentos-body');
    tbody.innerHTML = '';
    mockData.atendimentos.forEach(atendimento => {
        const row = document.createElement('tr');
        const statusClass = atendimento.status.toLowerCase().replace(' ', '-').normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        row.innerHTML = `
            <td>${atendimento.id}</td>
            <td>${atendimento.paciente}</td>
            <td>${atendimento.farmaceutico}</td>
            <td>${new Date(atendimento.data).toLocaleDateString('pt-BR')}</td>
            <td>${atendimento.tipo}</td>
            <td><span class="status-badge status-${statusClass}">${atendimento.status}</span></td>
        `;
        tbody.appendChild(row);
    });
}


// ======================================================================
// Função de inicialização principal da página de relatórios
// ======================================================================
function initializeRelatorios() {
    // Estas funções agora são chamadas por uma função principal
    // que só roda depois que o HTML está pronto.
    console.log("Inicializando Relatórios...");
    preencherCardsInsights();
    criarGraficoAtendimentosPorTipo();
    criarGraficoTopMedicamentos();
    renderizarTabelaRelatorio();
    console.log("Relatórios inicializados com sucesso.");
}