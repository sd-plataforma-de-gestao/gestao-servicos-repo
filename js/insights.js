// Variáveis globais para armazenar instâncias dos gráficos
let chartsInstances = {};
let currentPeriod = 'semanal'; // Padrão para o novo gráfico de período

// Função para inicializar gráficos com dados recebidos do PHP
function initCharts(data) {
    criarGraficoAtendimentosPorTipo(data.atendimentos_por_tipo);
    criarGraficoAtendimentosPorPeriodo(data.atendimentos_por_periodo);
}

// Função para preencher tabelas com dados recebidos do PHP
function loadTables(data) {
    preencherTabelaTopPacientes(data.top_pacientes);
    preencherTabelaAtendimentosRecentes(); // Chamada para carregar do banco via PHP
}

function criarGraficoAtendimentosPorTipo(dados) {
    const ctx = document.getElementById('categorias-chart').getContext('2d');

    if (chartsInstances.categorias) {
        chartsInstances.categorias.destroy();
    }

    // Separar labels e valores
    const labels = dados.map(item => item.tipo);
    const valores = dados.map(item => item.quantidade);
    // Cores padrão para o gráfico de pizza
    const cores = ['#1a6d40', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6c757d'];

    chartsInstances.categorias = new Chart(ctx, {
        type: 'doughnut',
        data: { // ← CORREÇÃO: faltava "data"
            labels: labels,
            datasets: [{
                data: valores, // ← CORREÇÃO: era "valores" sozinho
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

    chartsInstances.vendas = new Chart(ctx, {
        type: 'line',
        data: { // ← CORREÇÃO: faltava "data"
            labels: dados.labels,
            datasets: [{
                label: 'Atendimentos',
                data: dados.quantidade, // ← CORREÇÃO: era "dados.quantidade" sozinho
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
    tbody.innerHTML = ''; // Limpa antes de preencher

    dados.forEach(paciente => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${paciente.nome}</td>
            <td>${paciente.atendimentos}</td>
        `;
        tbody.appendChild(row);
    });
}

// Função para preencher a tabela de atendimentos recentes
function preencherTabelaAtendimentosRecentes() {
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
            tbody.innerHTML = ''; // Limpa antes de preencher

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

// Eventos para troca de período no gráfico de atendimentos
document.querySelectorAll('[data-chart-type="vendas"]').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('[data-chart-type="vendas"]').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentPeriod = this.dataset.period;
    });
});

// Função para mostrar notificações (opcional, se não for definida no PHP)
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

// Funções para visualizar/editar atendimentos (opcional, se não for definida no PHP)
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