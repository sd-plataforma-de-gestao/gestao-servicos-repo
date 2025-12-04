
    function initializeReportPage() {
        if (typeof Chart === 'undefined' || Chart === null) {
            console.error('Chart.js não está disponível');
            return;
        }

        if (document.readyState !== 'loading' && document.querySelector('#reportType')) {
            loadRelatorioFunctions();
        } else {
            document.addEventListener('DOMContentLoaded', loadRelatorioFunctions);
        }
    }

    function loadRelatorioFunctions() {
        const apiBase = location.pathname + '?action=fetch';
        let mainChart = null;

        const reportTypeSelect = document.getElementById('reportType');
        const refreshBtn = document.getElementById('refreshBtn');

        if (reportTypeSelect) {
            reportTypeSelect.addEventListener('change', function(){
                loadReport(this.value);
            });
        }

        if (refreshBtn) {
            refreshBtn.addEventListener('click', function(){
                const type = document.getElementById('reportType')?.value || 'kpis_over_time';
                loadReport(type);
            });
        }

        setTimeout(() => {
            loadReport('kpis_over_time');
        }, 100);

        function loadReport(type) {
            if (!type) {
                const titleEl = document.getElementById('card-title');
                if (titleEl) titleEl.innerText = 'Selecione um relatório';
                
                const tableWrapper = document.getElementById('tableWrapper');
                if (tableWrapper) tableWrapper.innerHTML = '';
                
                if (mainChart) { 
                    mainChart.destroy(); 
                    mainChart = null; 
                }
                return;
            }

            const titleEl = document.getElementById('card-title');
            if (titleEl) {
                const titles = {
                    'kpis_over_time':'KPIs (Evolução)',
                    'top_medicamentos':'Top Medicamentos',
                    'estoque_critico':'Estoque Crítico',
                    'tratamentos_continuos':'Tratamentos Contínuos',
                    'dispensacoes':'Histórico de Dispensações',
                    'interacoes_paciente':'Interações Medicamentosas',
                    'produtividade_farmaceuticos':'Produtividade por Farmacêutico',
                    'unidades_desempenho':'Desempenho por Unidade',
                    'receita_por_medicamento':'Receita por Medicamento',
                    'predictive_atendimentos':'Previsão: Atendimentos (30 dias)',
                    'predictive_adesao':'Previsão: Taxa de Adesão (30 dias)'
                };
                titleEl.innerText = titles[type] || 'Relatório';
            }

            const params = new URLSearchParams();
            params.set('type', type);
            params.set('limit', document.getElementById('limit')?.value || 10);
            params.set('threshold', document.getElementById('threshold')?.value || 5);

            fetch(apiBase + '&' + params.toString())
                .then(r => r.json())
                .then(resp => {
                    if (!resp || resp.status !== 'ok') {
                        const tableWrapper = document.getElementById('tableWrapper');
                        if (tableWrapper) tableWrapper.innerHTML = '<div class="text-muted">Erro ao carregar relatório.</div>';
                        return;
                    }
                    renderReport(type, resp.data);
                })
                .catch(err => {
                    console.error(err);
                    const tableWrapper = document.getElementById('tableWrapper');
                    if (tableWrapper) tableWrapper.innerHTML = '<div class="text-muted">Erro ao carregar relatório.</div>';
                });
        }

        function renderReport(type, data) {
            const tableWrapper = document.getElementById('tableWrapper');
            if (tableWrapper) tableWrapper.innerHTML = '';
            
            if (mainChart) { 
                mainChart.destroy(); 
                mainChart = null; 
            }

            switch(type) {
                case 'kpis_over_time':
                    if (!data.length) {
                        if (tableWrapper) tableWrapper.innerHTML = '<div class="text-muted">Nenhum dado encontrado.</div>';
                        return;
                    }
                    const labels = data.map(r => r.data);
                    const total = data.map(r => r.total_atendimentos);
                    const cron = data.map(r => r.atendimentos_cronicos);
                    const agud = data.map(r => r.atendimentos_agudos);
                    const ades = data.map(r => r.taxa_adesao_media);

                    const ctx = document.getElementById('mainChart')?.getContext('2d');
                    if (ctx) {
                        mainChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [
                                    { label: 'Total Atendimentos', data: total, fill: true, tension:0.2 },
                                    { label: 'Crônicos', data: cron, fill: true, tension:0.2 },
                                    { label: 'Agudos', data: agud, fill: true, tension:0.2 }
                                ]
                            },
                            options: {
                                maintainAspectRatio:false,
                                plugins: { legend: { position:'top' } }
                            }
                        });
                    }

                    const rows = data.slice(-10).reverse().map(r => `<tr><td>${r.data}</td><td>${r.total_atendimentos}</td><td>${r.atendimentos_cronicos}</td><td>${r.atendimentos_agudos}</td><td>${r.taxa_adesao_media}%</td></tr>`).join('');
                    if (tableWrapper) tableWrapper.innerHTML = `<table class="table"><thead><tr><th>Data</th><th>Total</th><th>Crônicos</th><th>Agudos</th><th>Taxa Adesão</th></tr></thead><tbody>${rows}</tbody></table>`;
                    updateKPIs(total.slice(-1)[0] || '—', ades.slice(-1)[0] ? ades.slice(-1)[0] + '%' : '—');
                    updateRecentEvents();
                    break;

                case 'top_medicamentos':
                    if (!data.length) { 
                        if (tableWrapper) tableWrapper.innerHTML = '<div class="text-muted">Nenhum dado encontrado.</div>'; 
                        return; 
                    }
                    const labelsTop = data.map(r=>r.medicamento_nome);
                    const totalsTop = data.map(r=>r.total_dispensado);
                    const ctx2 = document.getElementById('mainChart')?.getContext('2d');
                    if (ctx2) {
                        mainChart = new Chart(ctx2, {
                            type:'bar',
                            data:{ labels: labelsTop, datasets:[{ label:'Total dispensado', data: totalsTop }]},
                            options:{ indexAxis:'y', maintainAspectRatio:false }
                        });
                    }
                    const rowsTop = data.map(r=>`<tr><td>${escapeHtml(r.medicamento_nome)}</td><td>${r.total_dispensado}</td></tr>`).join('');
                    if (tableWrapper) tableWrapper.innerHTML = `<table class="table"><thead><tr><th>Medicamento</th><th>Total</th></tr></thead><tbody>${rowsTop}</tbody></table>`;
                    break;

                case 'estoque_critico':
                    if (!data.length) { 
                        if (tableWrapper) tableWrapper.innerHTML = '<div class="text-muted">Nenhum medicamento em estoque crítico.</div>'; 
                        return; 
                    }
                    const labelsEst = data.map(r=>r.medicamento_nome);
                    const qEst = data.map(r=>r.quantidade);
                    const ctx3 = document.getElementById('mainChart')?.getContext('2d');
                    if (ctx3) {
                        mainChart = new Chart(ctx3, {
                            type:'bar',
                            data:{ labels: labelsEst, datasets:[{ label:'Quantidade', data: qEst }]},
                            options:{ maintainAspectRatio:false, plugins:{ legend:{display:false}}}
                        });
                    }
                    const rowsEst = data.map(r=>`<tr><td>${escapeHtml(r.medicamento_nome)}</td><td>${r.quantidade}</td><td>${r.data_validade}</td></tr>`).join('');
                    if (tableWrapper) tableWrapper.innerHTML = `<table class="table"><thead><tr><th>Medicamento</th><th>Quantidade</th><th>Validade</th></tr></thead><tbody>${rowsEst}</tbody></table>`;
                    break;

                case 'tratamentos_continuos':
                    if (!data.length) { 
                        if (tableWrapper) tableWrapper.innerHTML = '<div class="text-muted">Nenhum dado encontrado.</div>'; 
                        return; 
                    }
                    const rowsTC = data.map(r=>`<tr><td>${escapeHtml(r.paciente_nome)}</td><td>${escapeHtml(r.medicamento_nome)}</td><td>${r.data_inicio_tratamento}</td><td>${r.total_dispensacoes}</td></tr>`).join('');
                    if (tableWrapper) tableWrapper.innerHTML = `<table class="table"><thead><tr><th>Paciente</th><th>Medicamento</th><th>Início</th><th>Dispensações</th></tr></thead><tbody>${rowsTC}</tbody></table>`;
                    const labelsTC = data.map(r=>r.paciente_nome + ' — ' + r.medicamento_nome);
                    const valuesTC = data.map(r=>r.total_dispensacoes);
                    const ctx4 = document.getElementById('mainChart')?.getContext('2d');
                    if (ctx4) {
                        mainChart = new Chart(ctx4, { type:'bar', data:{ labels:labelsTC, datasets:[{label:'Dispensações', data:valuesTC}]}, options:{maintainAspectRatio:false} });
                    }
                    break;

                case 'dispensacoes':
                    if (!data.length) { 
                        if (tableWrapper) tableWrapper.innerHTML = '<div class="text-muted">Nenhum dado.</div>'; 
                        return; 
                    }
                    const rowsD = data.map(r=>`<tr><td>#${r.atendimento_id}</td><td>${r.data_atendimento}</td><td>${escapeHtml(r.paciente_nome)}</td><td>${escapeHtml(r.medicamento_nome)}</td><td>${r.quantidade_dispensada}</td><td>${r.tipo_atendimento}</td></tr>`).join('');
                    if (tableWrapper) tableWrapper.innerHTML = `<table class="table"><thead><tr><th>ID</th><th>Data</th><th>Paciente</th><th>Medicamento</th><th>Qtd</th><th>Tipo</th></tr></thead><tbody>${rowsD}</tbody></table>`;
                    const byDate = {};
                    data.forEach(r => {
                        const d = r.data_atendimento.split(' ')[0];
                        byDate[d] = (byDate[d] || 0) + 1;
                    });
                    const labelsD = Object.keys(byDate).slice(-20);
                    const valsD = labelsD.map(l=>byDate[l]);
                    const ctx5 = document.getElementById('mainChart')?.getContext('2d');
                    if (ctx5) {
                        mainChart = new Chart(ctx5, { type:'line', data:{ labels:labelsD, datasets:[{label:'Dispensações', data:valsD}]}, options:{maintainAspectRatio:false} });
                    }
                    break;

                case 'interacoes_paciente':
                    if (!data.length) { 
                        if (tableWrapper) tableWrapper.innerHTML = '<div class="text-muted">Nenhum dado.</div>'; 
                        return; 
                    }
                    const rowsI = data.map(r=>`<tr><td>${escapeHtml(r.medicamento1)}</td><td>${escapeHtml(r.medicamento2)}</td><td>${escapeHtml(r.descricao)}</td></tr>`).join('');
                    if (tableWrapper) tableWrapper.innerHTML = `<table class="table"><thead><tr><th>Medicamento 1</th><th>Medicamento 2</th><th>Descrição</th></tr></thead><tbody>${rowsI}</tbody></table>`;
                    const mainChartCtx = document.getElementById('mainChart')?.getContext('2d');
                    if (mainChartCtx) mainChartCtx.clearRect(0,0,400,200);
                    break;

                case 'produtividade_farmaceuticos':
                    if (!data.length) { 
                        if (tableWrapper) tableWrapper.innerHTML = '<div class="text-muted">Nenhum dado.</div>'; 
                        return; 
                    }
                    const labelsF = data.map(r=>r.farmaceutico_nome);
                    const valsF = data.map(r=>r.total_atendimentos);
                    const ctx6 = document.getElementById('mainChart')?.getContext('2d');
                    if (ctx6) {
                        mainChart = new Chart(ctx6, { type:'bar', data:{ labels:labelsF, datasets:[{label:'Atendimentos', data:valsF}]}, options:{indexAxis:'y', maintainAspectRatio:false} });
                    }
                    const rowsF = data.map(r=>`<tr><td>${escapeHtml(r.farmaceutico_nome)}</td><td>${r.total_atendimentos}</td><td>${r.total_cancelados}</td></tr>`).join('');
                    if (tableWrapper) tableWrapper.innerHTML = `<table class="table"><thead><tr><th>Farmacêutico</th><th>Atendimentos</th><th>Cancelados</th></tr></thead><tbody>${rowsF}</tbody></table>`;
                    break;

                case 'unidades_desempenho':
                    if (!data.length) { 
                        if (tableWrapper) tableWrapper.innerHTML = '<div class="text-muted">Nenhum dado.</div>'; 
                        return; 
                    }
                    const labelsU = data.map(r=>r.unidade_nome);
                    const valsU = data.map(r=>r.total_atendimentos);
                    const ctx7 = document.getElementById('mainChart')?.getContext('2d');
                    if (ctx7) {
                        mainChart = new Chart(ctx7, { type:'bar', data:{ labels:labelsU, datasets:[{label:'Atendimentos', data:valsU}]}, options:{indexAxis:'y', maintainAspectRatio:false} });
                    }
                    const rowsU = data.map(r=>`<tr><td>${escapeHtml(r.unidade_nome)}</td><td>${r.status}</td><td>${r.total_atendimentos}</td></tr>`).join('');
                    if (tableWrapper) tableWrapper.innerHTML = `<table class="table"><thead><tr><th>Unidade</th><th>Status</th><th>Atendimentos</th></tr></thead><tbody>${rowsU}</tbody></table>`;
                    break;

                case 'receita_por_medicamento':
                    if (!data.length) { 
                        if (tableWrapper) tableWrapper.innerHTML = '<div class="text-muted">Nenhum dado.</div>'; 
                        return; 
                    }
                    const labelsR = data.map(r=>r.medicamento_nome);
                    const valsR = data.map(r=>Number(r.receita.toFixed(2)));
                    const ctx8 = document.getElementById('mainChart')?.getContext('2d');
                    if (ctx8) {
                        mainChart = new Chart(ctx8, { type:'bar', data:{ labels:labelsR, datasets:[{label:'Receita (R$)', data:valsR}]}, options:{indexAxis:'y', maintainAspectRatio:false} });
                    }
                    const rowsR = data.map(r=>`<tr><td>${escapeHtml(r.medicamento_nome)}</td><td>R$ ${Number(r.receita).toFixed(2)}</td></tr>`).join('');
                    if (tableWrapper) tableWrapper.innerHTML = `<table class="table"><thead><tr><th>Medicamento</th><th>Receita</th></tr></thead><tbody>${rowsR}</tbody></table>`;
                    break;

                case 'predictive_atendimentos':
                case 'predictive_adesao':
                    if (data.error) { 
                        if (tableWrapper) tableWrapper.innerHTML = `<div class="text-muted">${data.error}</div>`; 
                        return; 
                    }
                    const labelsP = data.labels;
                    const valsP = data.predictions;
                    const ctx9 = document.getElementById('mainChart')?.getContext('2d');
                    if (ctx9) {
                        mainChart = new Chart(ctx9, { type:'line', data:{ labels:labelsP, datasets:[{label:'Previsão', data:valsP, tension:0.3}]}, options:{maintainAspectRatio:false} });
                    }
                    const rowsP = labelsP.map((lab, i) => `<tr><td>${lab}</td><td>${valsP[i]}</td></tr>`).join('');
                    if (tableWrapper) tableWrapper.innerHTML = `<table class="table"><thead><tr><th>Data</th><th>Previsão</th></tr></thead><tbody>${rowsP}</tbody></table>`;
                    break;

                default:
                    if (tableWrapper) tableWrapper.innerHTML = '<div class="text-muted">Relatório não implementado.</div>';
            }
        }

        function escapeHtml(s) {
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '<')
                .replace(/>/g, '>')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function updateKPIs(atendimentos, adesao) {
            const kpiAtend = document.getElementById('kpi_atendimentos');
            const kpiAdesao = document.getElementById('kpi_adesao');
            if (kpiAtend) kpiAtend.innerText = atendimentos;
            if (kpiAdesao) kpiAdesao.innerText = adesao;
            
            fetch(apiBase + '&type=kpis_over_time&limit=1')
                .then(r=>r.json()).then(resp=>{
                    const kpiPacientes = document.getElementById('kpi_pacientes');
                    if (kpiPacientes) kpiPacientes.innerText = '—';
                }).catch(()=>{
                    const kpiPacientes = document.getElementById('kpi_pacientes');
                    if (kpiPacientes) kpiPacientes.innerText = '—';
                });
        }

        function updateRecentEvents() {
            fetch(apiBase + '&type=dispensacoes&limit=5').then(r=>r.json()).then(resp=>{
                const arr = resp.data || [];
                const recentEvents = document.getElementById('recentEvents');
                
                if (!arr.length) { 
                    if (recentEvents) recentEvents.innerHTML = '<div class="text-muted">Nenhum evento recente.</div>'; 
                    return; 
                }
                
                const html = '<ul style="padding-left:16px;margin:0;">' + arr.slice(0,5).map(a=>`<li>#${a.atendimento_id} — ${escapeHtml(a.paciente_nome)}: ${escapeHtml(a.medicamento_nome)} (${a.quantidade_dispensada})</li>`).join('') + '</ul>';
                if (recentEvents) recentEvents.innerHTML = html;
            }).catch(()=>{
                const recentEvents = document.getElementById('recentEvents');
                if (recentEvents) recentEvents.innerHTML = '<div class="text-muted">Erro ao carregar.</div>';
            });
        }
    }

    if (typeof Chart !== 'undefined' && Chart !== null) {
        initializeReportPage();
    } else {
        setTimeout(initializeReportPage, 200);
    }
