let chartsInstances = {};
let currentPeriod = 'semanal';

function initCharts(data) {
    console.log('initCharts chamada com dados:', data);
    criarGraficoAtendimentosPorTipo(data.atendimentos_por_tipo);
    criarGraficoAtendimentosPorPeriodo(data.atendimentos_por_periodo);
}

function loadTables(data) {
    console.log('loadTables chamada com dados:', data);
    preencherTabelaTopPacientes(data.top_pacientes);
    preencherTabelaAtendimentosRecentes(); 
}

function criarGraficoAtendimentosPorTipo(dados) {
    const ctx = document.getElementById('categorias-chart').getContext('2d');

    if (chartsInstances.categorias) {
        chartsInstances.categorias.destroy();
    }

    const labels = dados.map(item => item.tipo);
    const valores = dados.map(item => item.quantidade);
    const cores = ['#1a6d40', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6c757d'];

    console.log('Dados para gráfico de tipo:', labels, valores);

    chartsInstances.categorias = new Chart(ctx, {
        type: 'doughnut',
        data: { 
            labels: labels,
            datasets: [{
                data: valores,
                backgroundColor: cores,
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ' + context.parsed + ' atendimentos';
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
}

function criarGraficoAtendimentosPorPeriodo(dados) {
    const ctx = document.getElementById('vendas-chart').getContext('2d');

    if (chartsInstances.vendas) {
        chartsInstances.vendas.destroy();
    }

    console.log('Dados para gráfico de período:', dados);

    chartsInstances.vendas = new Chart(ctx, {
        type: 'line',
        data: { 
            labels: dados.labels,
            datasets: [{
                label: 'Atendimentos',
                data: dados.quantidade,
                borderColor: '#1a6d40',
                backgroundColor: 'rgba(26, 109, 64, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#1a6d40',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: '#1a6d40',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
}

function preencherTabelaTopPacientes(dados) {
    const tbody = document.querySelector('#top-pacientes-table tbody');
    if (!tbody) {
        console.error("Elemento 'top-pacientes-table' não encontrado.");
        return;
    }
    console.log('Dados para tabela Top Pacientes:', dados);

    tbody.innerHTML = '';
    dados.forEach(paciente => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${paciente.nome}</td>
            <td>${paciente.atendimentos}</td>
        `;
        tbody.appendChild(row);
    });
}

function preencherTabelaAtendimentosRecentes() {
    console.log('Carregando tabela de atendimentos recentes via API...');
    fetch('?action=get_recent_atendimentos')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro na rede: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(atendimentos => {
            const tbody = document.querySelector('#vendas-recentes-table tbody');
            if (!tbody) {
                console.error("Elemento 'vendas-recentes-table' não encontrado.");
                return;
            }
            console.log('Dados para tabela Atendimentos Recentes:', atendimentos);

            tbody.innerHTML = '';

            atendimentos.forEach(atendimento => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>#${atendimento.id}</td>
                    <td>${new Date(atendimento.criado_em).toLocaleString('pt-BR')}</td>
                    <td>${atendimento.paciente_nome}</td>
                    <td>${atendimento.tipo_atendimento}</td>
                    <td><span class="status-badge ${atendimento.status_atendimento.toLowerCase()}">${atendimento.status_atendimento}</span></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-outline-primary" onclick="visualizarAtendimento(${atendimento.id})">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="editarAtendimento(${atendimento.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Erro ao carregar atendimentos recentes:', error);
            const tbody = document.querySelector('#vendas-recentes-table tbody');
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="6">Erro ao carregar: ${error.message}</td></tr>`;
            }
        });
}

function carregarTratamentosContinuos() {
    console.log('Carregando tratamentos contínuos...');
    fetch('?action=get_tratamentos_continuos')
        .then(response => response.json())
        .then(data => {
            preencherTabelaTratamentos(data);
        })
        .catch(error => console.error('Erro ao carregar tratamentos contínuos:', error));
}

function preencherTabelaTratamentos(dados) {
    const tbody = document.querySelector('#tratamentos-table tbody');
    if (!tbody) {
        console.error("Elemento 'tratamentos-table' não encontrado.");
        return;
    }
    tbody.innerHTML = '';
    dados.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.paciente_nome}</td>
            <td>${item.medicamento_nome}</td>
            <td>${item.data_inicio_tratamento}</td>
            <td>${item.total_dispensacoes}</td>
        `;
        tbody.appendChild(row);
    });
}

function buscarDispensacoes(filtros) {
    console.log('Buscando dispensações com filtros:', filtros);
    const params = new URLSearchParams(filtros).toString();
    fetch(`?action=get_dispensacoes&${params}`)
        .then(response => response.json())
        .then(data => {
            preencherTabelaDispensacoes(data);
        })
        .catch(error => console.error('Erro ao buscar dispensações:', error));
}

function preencherTabelaDispensacoes(dados) {
    const tbody = document.querySelector('#dispensacoes-table tbody');
    if (!tbody) {
        console.error("Elemento 'dispensacoes-table' não encontrado.");
        return;
    }
    tbody.innerHTML = '';
    dados.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.atendimento_id}</td>
            <td>${item.data_atendimento}</td>
            <td>${item.paciente_nome}</td>
            <td>${item.medicamento_nome}</td>
            <td>${item.quantidade_dispensada}</td>
            <td>${item.tipo_atendimento}</td>
        `;
        tbody.appendChild(row);
    });
}

function buscarInteracoesPorPaciente(id) {
    console.log(`Buscando interações para o paciente ID: ${id}`);
    fetch(`?action=get_interacoes_paciente&id=${id}`)
        .then(response => response.json())
        .then(data => {
            exibirInteracoes(data);
        })
        .catch(error => console.error('Erro ao buscar interações:', error));
}

function exibirInteracoes(dados) {
    console.table(dados);
    alert(`Possíveis interações encontradas: ${dados.length}`);
}

document.querySelectorAll('[data-chart-type="vendas"]').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('[data-chart-type="vendas"]').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentPeriod = this.dataset.period;
    });
});

if (typeof mostrarNotificacao === 'undefined') {
    function mostrarNotificacao(mensagem, tipo = 'info') {
        console.log(`${tipo.toUpperCase()}: ${mensagem}`);
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${tipo === 'success' ? 'success' : tipo === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${mensagem}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 3000);
    }
}

if (typeof visualizarAtendimento === 'undefined') {
    function visualizarAtendimento(id) {
        mostrarNotificacao(`Visualizando atendimento #${id}`, 'info');
    }
}

if (typeof editarAtendimento === 'undefined') {
    function editarAtendimento(id) {
        mostrarNotificacao(`Editando atendimento #${id}`, 'info');
    }
}