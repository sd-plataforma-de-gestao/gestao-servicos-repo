// Banco de Dados que eu adicionei pro front da tela de insights, depois temos que conectar com nosso banco
const dadosFicticios = {
    metricas: {
        receitaTotal: 89500.75,
        receitaVariacao: 15.2,
        totalAtendimentos: 1847,
        atendimentosVariacao: 12.8,
        novosPacientes: 156,
        pacientesVariacao: -3.5,
        taxaAdesao: 87.3,
        adesaoVariacao: 2.1
    },
    
    atendimentosPorPeriodo: {
        diario: {
            labels: ['01/12', '02/12', '03/12', '04/12', '05/12', '06/12', '07/12', '08/12', '09/12', '10/12'],
            atendimentos: [78, 92, 65, 103, 87, 71, 95, 108, 82, 89],
            receita: [7800, 9200, 6500, 10300, 8700, 7100, 9500, 10800, 8200, 8900]
        },
        semanal: {
            labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4'],
            atendimentos: [520, 485, 610, 432],
            receita: [52000, 48500, 61000, 43200]
        },
        mensal: {
            labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
            atendimentos: [1850, 1720, 2100, 1650, 2250, 2080, 1890, 1950, 2150, 1847, 2200, 1780],
            receita: [85000, 78000, 95000, 72000, 105000, 89500, 82000, 87000, 98000, 89500, 102000, 81000]
        }
    },
    
    atendimentosPorTipo: {
        labels: ['Crônicos', 'Agudos', 'Preventivos', 'Emergência', 'Consultas', 'Outros'],
        valores: [42, 28, 15, 8, 5, 2],
        cores: ['#1a6d40', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6c757d']
    },
    
    funnelPacientes: {
        labels: ['Cadastrados', 'Primeira Consulta', 'Tratamento', 'Acompanhamento', 'Alta'],
        valores: [2500, 1800, 1200, 850, 420],
        cores: ['#dc3545', '#fd7e14', '#ffc107', '#28a745', '#1a6d40']
    },
    
    receitaCustos: {
        labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
        receita: [85000, 78000, 95000, 72000, 105000, 89500],
        custos: [45000, 42000, 52000, 38000, 58000, 48000]
    },
    
    topMedicamentos: [
        { id: 1, nome: 'Paracetamol 500mg', dispensacoes: 245, receita: 12250, variacao: 18.5 },
        { id: 2, nome: 'Losartana 50mg', dispensacoes: 189, receita: 18900, variacao: -2.1 },
        { id: 3, nome: 'Metformina 850mg', dispensacoes: 167, receita: 16700, variacao: 12.7 },
        { id: 4, nome: 'Omeprazol 20mg', dispensacoes: 234, receita: 11700, variacao: 25.3 },
        { id: 5, nome: 'Sinvastatina 20mg', dispensacoes: 145, receita: 14500, variacao: -5.8 },
        { id: 6, nome: 'Captopril 25mg', dispensacoes: 123, receita: 6150, variacao: 8.1 },
        { id: 7, nome: 'Glibenclamida 5mg', dispensacoes: 98, receita: 4900, variacao: 15.4 },
        { id: 8, nome: 'Atenolol 50mg', dispensacoes: 87, receita: 8700, variacao: -3.9 },
        { id: 9, nome: 'Hidroclorotiazida 25mg', dispensacoes: 156, receita: 7800, variacao: 22.7 },
        { id: 10, nome: 'Dipirona 500mg', dispensacoes: 289, receita: 8670, variacao: 11.3 }
    ],
    
    topPacientes: [
        { id: 1, nome: 'Maria Silva Santos', atendimentos: 23, totalGasto: 2850, ultimoAtendimento: '2024-12-08' },
        { id: 2, nome: 'João Carlos Oliveira', atendimentos: 18, totalGasto: 2340, ultimoAtendimento: '2024-12-07' },
        { id: 3, nome: 'Ana Paula Costa', atendimentos: 15, totalGasto: 1950, ultimoAtendimento: '2024-12-06' },
        { id: 4, nome: 'Pedro Henrique Lima', atendimentos: 21, totalGasto: 2730, ultimoAtendimento: '2024-12-09' },
        { id: 5, nome: 'Lucia Fernanda Souza', atendimentos: 12, totalGasto: 1560, ultimoAtendimento: '2024-12-05' },
        { id: 6, nome: 'Carlos Eduardo Santos', atendimentos: 19, totalGasto: 2470, ultimoAtendimento: '2024-12-08' },
        { id: 7, nome: 'Fernanda Rodrigues', atendimentos: 14, totalGasto: 1820, ultimoAtendimento: '2024-12-04' },
        { id: 8, nome: 'Roberto Alves', atendimentos: 16, totalGasto: 2080, ultimoAtendimento: '2024-12-07' },
        { id: 9, nome: 'Juliana Pereira', atendimentos: 11, totalGasto: 1430, ultimoAtendimento: '2024-12-03' },
        { id: 10, nome: 'Ricardo Ferreira', atendimentos: 13, totalGasto: 1690, ultimoAtendimento: '2024-12-06' }
    ],
    
    atendimentosRecentes: [
        { id: 1001, data: '2024-12-10 14:30', paciente: 'Maria Silva Santos', tipo: 'Crônico', medicamento: 'Losartana 50mg', valor: 125, status: 'concluido' },
        { id: 1002, data: '2024-12-10 13:45', paciente: 'João Carlos Oliveira', tipo: 'Agudo', medicamento: 'Paracetamol 500mg', valor: 85, status: 'processando' },
        { id: 1003, data: '2024-12-10 12:20', paciente: 'Ana Paula Costa', tipo: 'Preventivo', medicamento: 'Metformina 850mg', valor: 95, status: 'pendente' },
        { id: 1004, data: '2024-12-10 11:15', paciente: 'Pedro Henrique Lima', tipo: 'Crônico', medicamento: 'Sinvastatina 20mg', valor: 110, status: 'concluido' },
        { id: 1005, data: '2024-12-10 10:30', paciente: 'Lucia Fernanda Souza', tipo: 'Agudo', medicamento: 'Omeprazol 20mg', valor: 75, status: 'cancelado' },
        { id: 1006, data: '2024-12-10 09:45', paciente: 'Carlos Eduardo Santos', tipo: 'Crônico', medicamento: 'Captopril 25mg', valor: 65, status: 'concluido' },
        { id: 1007, data: '2024-12-10 08:20', paciente: 'Fernanda Rodrigues', tipo: 'Preventivo', medicamento: 'Glibenclamida 5mg', valor: 55, status: 'processando' },
        { id: 1008, data: '2024-12-09 17:30', paciente: 'Roberto Alves', tipo: 'Agudo', medicamento: 'Atenolol 50mg', valor: 90, status: 'concluido' },
        { id: 1009, data: '2024-12-09 16:15', paciente: 'Juliana Pereira', tipo: 'Crônico', medicamento: 'Hidroclorotiazida 25mg', valor: 45, status: 'pendente' },
        { id: 1010, data: '2024-12-09 15:45', paciente: 'Ricardo Ferreira', tipo: 'Agudo', medicamento: 'Dipirona 500mg', valor: 35, status: 'concluido' }
    ]
};

let chartsInstances = {};
let currentPeriod = 'diario';

document.addEventListener('DOMContentLoaded', function() {
    carregarHeaderESidebar();
    inicializarPagina();
});

async function carregarHeaderESidebar() {
    try {
        const headerResponse = await fetch('header.html');
        const headerHtml = await headerResponse.text();
        document.getElementById('header-placeholder').innerHTML = headerHtml;
        
        const sidebarResponse = await fetch('sidebar.html');
        const sidebarHtml = await sidebarResponse.text();
        document.getElementById('sidebar-placeholder').innerHTML = sidebarHtml;
        
        inicializarHeaderESidebar();
        
    } catch (error) {
        console.error('Erro ao carregar header e sidebar:', error);
        criarHeaderESidebarBasicos();
    }
}

function inicializarHeaderESidebar() {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.main-sidebar');
    const body = document.body;
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            body.classList.toggle('sidebar-collapsed');
        });
    }
    
    document.querySelectorAll('.nav-item.has-submenu > .nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.parentElement;
            parent.classList.toggle('open');
        });
    });
    
    const searchInput = document.querySelector('.search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            // Implementar lógica de pesquisa
            console.log('Pesquisando:', this.value);
        });
    }
}

function criarHeaderESidebarBasicos() {
    const headerPlaceholder = document.getElementById('header-placeholder');
    headerPlaceholder.innerHTML = `
        <header class="main-header" style="height: 70px; background: white; border-bottom: 1px solid #e0e0e0; display: flex; align-items: center; padding: 0 20px;">
            <h1 style="color: #1a6d40; margin: 0;">FarmaSystem - Relatórios</h1>
        </header>
    `;
    
    const sidebarPlaceholder = document.getElementById('sidebar-placeholder');
    sidebarPlaceholder.innerHTML = `
        <aside class="main-sidebar" style="width: 250px; height: calc(100vh - 70px); background: white; border-right: 1px solid #e0e0e0; padding: 20px;">
            <nav>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 10px;"><a href="#" style="color: #1a6d40; text-decoration: none;">📊 Relatórios</a></li>
                    <li style="margin-bottom: 10px;"><a href="#" style="color: #666; text-decoration: none;">👥 Pacientes</a></li>
                    <li style="margin-bottom: 10px;"><a href="#" style="color: #666; text-decoration: none;">💊 Medicamentos</a></li>
                    <li style="margin-bottom: 10px;"><a href="#" style="color: #666; text-decoration: none;">🩺 Atendimentos</a></li>
                </ul>
            </nav>
        </aside>
    `;
}

function inicializarPagina() {
    mostrarLoading(true);
    
    setTimeout(() => {
        atualizarMetricas();
        inicializarGraficos();
        preencherTabelas();
        configurarEventListeners();
        configurarFiltros();
        mostrarLoading(false);
        
        document.querySelectorAll('.card').forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('fade-in');
            }, index * 100);
        });
    }, 1500);
}


function atualizarMetricas() {
    const metricas = dadosFicticios.metricas;
    
    document.getElementById('receita-total').textContent = formatarMoeda(metricas.receitaTotal);
    document.getElementById('receita-variacao').textContent = `${metricas.receitaVariacao > 0 ? '+' : ''}${metricas.receitaVariacao}%`;
    
    document.getElementById('total-vendas').textContent = formatarNumero(metricas.totalAtendimentos);
    document.getElementById('vendas-variacao').textContent = `${metricas.atendimentosVariacao > 0 ? '+' : ''}${metricas.atendimentosVariacao}%`;
    
    document.getElementById('novos-clientes').textContent = formatarNumero(metricas.novosPacientes);
    document.getElementById('clientes-variacao').textContent = `${metricas.pacientesVariacao > 0 ? '+' : ''}${metricas.pacientesVariacao}%`;
    
    document.getElementById('taxa-conversao').textContent = `${metricas.taxaAdesao}%`;
    document.getElementById('conversao-variacao').textContent = `${metricas.adesaoVariacao > 0 ? '+' : ''}${metricas.adesaoVariacao}%`;
    
    atualizarClassesVariacao();
}

function atualizarClassesVariacao() {
    const metricas = dadosFicticios.metricas;
    
    const variacoes = [
        { elemento: 'receita-variacao', valor: metricas.receitaVariacao },
        { elemento: 'vendas-variacao', valor: metricas.atendimentosVariacao },
        { elemento: 'clientes-variacao', valor: metricas.pacientesVariacao },
        { elemento: 'conversao-variacao', valor: metricas.adesaoVariacao }
    ];
    
    variacoes.forEach(item => {
        const elemento = document.getElementById(item.elemento);
        const parent = elemento.closest('small');
        
        parent.className = parent.className.replace(/text-(success|danger|secondary)/, '');
        
        if (item.valor > 0) {
            parent.classList.add('text-success');
            parent.querySelector('i').className = 'fas fa-arrow-up';
        } else if (item.valor < 0) {
            parent.classList.add('text-danger');
            parent.querySelector('i').className = 'fas fa-arrow-down';
        } else {
            parent.classList.add('text-secondary');
            parent.querySelector('i').className = 'fas fa-minus';
        }
    });
}

function inicializarGraficos() {
    criarGraficoAtendimentos();
    criarGraficoTiposAtendimento();
    criarGraficoFunnelPacientes();
    criarGraficoReceitaCustos();
}

function criarGraficoAtendimentos() {
    const ctx = document.getElementById('vendas-chart').getContext('2d');
    const dados = dadosFicticios.atendimentosPorPeriodo[currentPeriod];
    
    if (chartsInstances.vendas) {
        chartsInstances.vendas.destroy();
    }
    
    chartsInstances.vendas = new Chart(ctx, {
        type: 'line',
        data: {
            labels: dados.labels,
            datasets: [{
                label: 'Atendimentos',
                data: dados.atendimentos,
                borderColor: '#1a6d40',
                backgroundColor: 'rgba(26, 109, 64, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#1a6d40',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6
            }, {
                label: 'Receita (R$ mil)',
                data: dados.receita.map(valor => valor / 1000),
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderWidth: 3,
                fill: false,
                tension: 0.4,
                pointBackgroundColor: '#28a745',
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
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
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

function criarGraficoTiposAtendimento() {
    const ctx = document.getElementById('categorias-chart').getContext('2d');
    const dados = dadosFicticios.atendimentosPorTipo;
    
    if (chartsInstances.categorias) {
        chartsInstances.categorias.destroy();
    }
    
    chartsInstances.categorias = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: dados.labels,
            datasets: [{
                data: dados.valores,
                backgroundColor: dados.cores,
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
                            return context.label + ': ' + context.parsed + '%';
                        }
                    }
                }
            }
        }
    });
}

function criarGraficoFunnelPacientes() {
    const ctx = document.getElementById('funil-chart').getContext('2d');
    const dados = dadosFicticios.funnelPacientes;
    
    if (chartsInstances.funil) {
        chartsInstances.funil.destroy();
    }
    
    chartsInstances.funil = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dados.labels,
            datasets: [{
                label: 'Pacientes',
                data: dados.valores,
                backgroundColor: dados.cores,
                borderWidth: 1,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + formatarNumero(context.parsed.x) + ' pacientes';
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                y: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function criarGraficoReceitaCustos() {
    const ctx = document.getElementById('receita-custos-chart').getContext('2d');
    const dados = dadosFicticios.receitaCustos;
    
    if (chartsInstances.receitaCustos) {
        chartsInstances.receitaCustos.destroy();
    }
    
    chartsInstances.receitaCustos = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dados.labels,
            datasets: [{
                label: 'Receita',
                data: dados.receita,
                backgroundColor: 'rgba(26, 109, 64, 0.8)',
                borderColor: '#1a6d40',
                borderWidth: 1
            }, {
                label: 'Custos',
                data: dados.custos,
                backgroundColor: 'rgba(220, 53, 69, 0.8)',
                borderColor: '#dc3545',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + formatarMoeda(context.parsed.y);
                        }
                    }
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
                    },
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + (value / 1000) + 'k';
                        }
                    }
                }
            }
        }
    });
}

function preencherTabelas() {
    preencherTabelaMedicamentos();
    preencherTabelaPacientes();
    preencherTabelaAtendimentosRecentes();
}

function preencherTabelaMedicamentos() {
    const tbody = document.querySelector('#top-produtos-table tbody');
    tbody.innerHTML = '';
    
    dadosFicticios.topMedicamentos.forEach(medicamento => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <div class="icon-circle bg-primary text-white me-2" style="width: 30px; height: 30px; font-size: 12px;">
                        <i class="fas fa-pills"></i>
                    </div>
                    <span>${medicamento.nome}</span>
                </div>
            </td>
            <td>${formatarNumero(medicamento.dispensacoes)}</td>
            <td>${formatarMoeda(medicamento.receita)}</td>
            <td>
                <span class="${medicamento.variacao >= 0 ? 'variacao-positiva' : 'variacao-negativa'}">
                    <i class="fas fa-arrow-${medicamento.variacao >= 0 ? 'up' : 'down'}"></i>
                    ${medicamento.variacao >= 0 ? '+' : ''}${medicamento.variacao}%
                </span>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function preencherTabelaPacientes() {
    const tbody = document.querySelector('#top-clientes-table tbody');
    tbody.innerHTML = '';
    
    dadosFicticios.topPacientes.forEach(paciente => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <div class="icon-circle bg-success text-white me-2" style="width: 30px; height: 30px; font-size: 12px;">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <span>${paciente.nome}</span>
                </div>
            </td>
            <td>${formatarNumero(paciente.atendimentos)}</td>
            <td>${formatarMoeda(paciente.totalGasto)}</td>
            <td>${formatarData(paciente.ultimoAtendimento)}</td>
        `;
        tbody.appendChild(row);
    });
}

function preencherTabelaAtendimentosRecentes() {
    const tbody = document.querySelector('#vendas-recentes-table tbody');
    tbody.innerHTML = '';
    
    dadosFicticios.atendimentosRecentes.forEach(atendimento => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>#${atendimento.id}</td>
            <td>${formatarDataHora(atendimento.data)}</td>
            <td>${atendimento.paciente}</td>
            <td>${atendimento.medicamento}</td>
            <td>${atendimento.tipo}</td>
            <td>${formatarMoeda(atendimento.valor)}</td>
            <td>
                <span class="status-badge ${atendimento.status}">
                    ${obterTextoStatus(atendimento.status)}
                </span>
            </td>
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
}


function configurarEventListeners() {
    document.querySelectorAll('[data-chart-type="vendas"]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-chart-type="vendas"]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            currentPeriod = this.dataset.period;
            criarGraficoAtendimentos();
        });
    });
    
    document.getElementById('aplicar-filtros').addEventListener('click', aplicarFiltros);
    document.getElementById('limpar-filtros').addEventListener('click', limparFiltros);
    document.getElementById('exportar-relatorio').addEventListener('click', exportarRelatorio);
    
    document.getElementById('refresh-vendas').addEventListener('click', function() {
        mostrarLoading(true);
        setTimeout(() => {
            preencherTabelaAtendimentosRecentes();
            mostrarLoading(false);
            mostrarNotificacao('Dados atualizados com sucesso!', 'success');
        }, 1000);
    });
    
    document.getElementById('ver-todos-produtos').addEventListener('click', () => {
        mostrarNotificacao('Redirecionando para página de medicamentos...', 'info');
    });
    
    document.getElementById('ver-todos-clientes').addEventListener('click', () => {
        mostrarNotificacao('Redirecionando para página de pacientes...', 'info');
    });
    
    document.getElementById('ver-todas-vendas').addEventListener('click', () => {
        mostrarNotificacao('Redirecionando para página de atendimentos...', 'info');
    });
}

function configurarFiltros() {
    const hoje = new Date();
    const trintaDiasAtras = new Date(hoje.getTime() - (30 * 24 * 60 * 60 * 1000));
    
    document.getElementById('data-fim').value = hoje.toISOString().split('T')[0];
    document.getElementById('data-inicio').value = trintaDiasAtras.toISOString().split('T')[0];
}

function aplicarFiltros() {
    mostrarLoading(true);
    
    const filtros = {
        periodo: document.getElementById('periodo-select').value,
        categoria: document.getElementById('categoria-select').value,
        dataInicio: document.getElementById('data-inicio').value,
        dataFim: document.getElementById('data-fim').value
    };
    
    console.log('Aplicando filtros:', filtros);
    
    setTimeout(() => {
        atualizarMetricas();
        inicializarGraficos();
        preencherTabelas();
        mostrarLoading(false);
        mostrarNotificacao('Filtros aplicados com sucesso!', 'success');
    }, 1500);
}

function limparFiltros() {
    document.getElementById('periodo-select').value = '30';
    document.getElementById('categoria-select').value = 'todas';
    document.getElementById('data-inicio').value = '';
    document.getElementById('data-fim').value = '';
    
    configurarFiltros();
    mostrarNotificacao('Filtros limpos!', 'info');
}

function exportarRelatorio() {
    mostrarLoading(true);
    
    setTimeout(() => {
        mostrarLoading(false);
        mostrarNotificacao('Relatório exportado com sucesso!', 'success');
        
        console.log('Exportando relatório...');
    }, 2000);
}

function visualizarAtendimento(id) {
    mostrarNotificacao(`Visualizando atendimento #${id}`, 'info');
}

function editarAtendimento(id) {
    mostrarNotificacao(`Editando atendimento #${id}`, 'info');
}

function formatarMoeda(valor) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor);
}

function formatarNumero(numero) {
    return new Intl.NumberFormat('pt-BR').format(numero);
}

function formatarData(data) {
    return new Date(data).toLocaleDateString('pt-BR');
}

function formatarDataHora(dataHora) {
    return new Date(dataHora).toLocaleString('pt-BR');
}

function obterTextoStatus(status) {
    const statusTextos = {
        'pendente': 'Pendente',
        'processando': 'Processando',
        'concluido': 'Concluído',
        'cancelado': 'Cancelado'
    };
    return statusTextos[status] || status;
}

function mostrarLoading(mostrar) {
    const overlay = document.getElementById('loading-overlay');
    if (mostrar) {
        overlay.classList.remove('d-none');
    } else {
        overlay.classList.add('d-none');
    }
}

function mostrarNotificacao(mensagem, tipo = 'info') {
    const notificacao = document.createElement('div');
    notificacao.className = `alert alert-${tipo === 'success' ? 'success' : tipo === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
    notificacao.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notificacao.innerHTML = `
        ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notificacao);
    
    setTimeout(() => {
        if (notificacao.parentNode) {
            notificacao.remove();
        }
    }, 3000);
}

async function carregarDadosDoServidor() {
    try {
        return dadosFicticios;
    } catch (error) {
        console.error('Erro ao carregar dados:', error);
        mostrarNotificacao('Erro ao carregar dados do servidor', 'error');
        return dadosFicticios; // Fallback para dados fictícios
    }
}

async function salvarFiltrosUsuario(filtros) {
    try {
        console.log('Filtros salvos:', filtros);
    } catch (error) {
        console.error('Erro ao salvar filtros:', error);
    }
}

async function exportarDados(formato = 'excel') {
    try {
        console.log(`Exportando dados em formato ${formato}`);
    } catch (error) {
        console.error('Erro ao exportar dados:', error);
        mostrarNotificacao('Erro ao exportar dados', 'error');
    }
}

setInterval(() => {
    if (!document.hidden) {
        console.log('Atualizando dados automaticamente...');
    }
}, 5 * 60 * 1000);

