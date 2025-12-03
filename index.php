<?php
session_start();
if (!isset($_SESSION['farmaceutico_id'])) {
    header("Location: /portal-repo-og/login.php");
    exit;
}
include(__DIR__ . '/config/database.php');

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Página inicial</title>
  <link rel="icon" href="/portal-repo-og/assets/favicon.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="/portal-repo-og/styles/global.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/header.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/sidebar.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/main.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/responsive.css">
</head>

<body>
  <div id="header-container"></div>

  <div id="main-content-wrapper">
    <div id="sidebar-container"></div>

    <div id="main-container"></div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/portal-repo-og/js/script.js"></script>

  <script>
  async function loadTemplate(templatePath, containerId) {
    try {
      const response = await fetch(templatePath);
      const html = await response.text();
      document.getElementById(containerId).innerHTML = html;

      if (containerId === 'header-container') {
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.getElementById('sidebar');
        if (menuToggle && sidebar) {
          menuToggle.removeEventListener('click', toggleSidebar);
          menuToggle.addEventListener('click', toggleSidebar);
        }
      }

      if (containerId === 'main-container') {
        carregarDashboard();
      }

      setTimeout(ativarSidebarAtual, 100);

    } catch (error) {
      console.error(`Erro ao carregar template ${templatePath}:`, error);
    }
  }

  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
      sidebar.classList.toggle('collapsed');
    }
  }

  async function carregarDashboard() {
    try {
      const response = await fetch('/portal-repo-og/dashboard.php?action=get_dashboard_data');
      const data = await response.json();

      const atendimentosHoje = document.getElementById('atendimentos-hoje');
      const consultasCronicas = document.getElementById('consultas-cronicas');
      const casosAgudos = document.getElementById('casos-agudos');
      const taxaAdesao = document.getElementById('taxa-adesao');

      if (atendimentosHoje) atendimentosHoje.textContent = data.atendimentos_hoje ?? '0';
      if (consultasCronicas) consultasCronicas.textContent = data.consultas_cronicas ?? '0';
      if (casosAgudos) casosAgudos.textContent = data.casos_agudos ?? '0';
      if (taxaAdesao) taxaAdesao.textContent = (data.taxa_adesao ?? 0) + '%';

      document.querySelectorAll('.stat-change').forEach(el => {
        el.textContent = '';
        el.className = 'stat-change';
      });

      const activityList = document.getElementById('activity-list');
      if (activityList) {
        if (data.atividades_recentes?.length > 0) {
          activityList.innerHTML = data.atividades_recentes.map(atividade => `
            <div class="activity-item">
              <div class="activity-icon">
                <i class="fas fa-user"></i>
              </div>
              <div class="activity-details">
                <h6 class="activity-name">${atividade.nome}</h6>
                <p class="activity-desc">${atividade.descricao}</p>
              </div>
              <div class="activity-meta">
                <span class="activity-time">${atividade.hora}</span>
                <span class="activity-status ${atividade.status}">${atividade.status === 'completed' ? 'Concluído' : 'Pendente'}</span>
              </div>
            </div>
          `).join('');
        } else {
          activityList.innerHTML = '<div class="text-center py-4">Nenhuma atividade recente.</div>';
        }
      }
    } catch (err) {
      console.error('Erro ao carregar dashboard:', err);
      const activityList = document.getElementById('activity-list');
      if (activityList) {
        activityList.innerHTML = '<div class="text-danger text-center">Erro ao carregar dados.</div>';
      }
    }
  }

  function ativarSidebarAtual() {
    const path = window.location.pathname;
    let paginaAtual = null;

    if (path === '/' || path === '/portal-repo-og/' || path.endsWith('/index.php')) {
      paginaAtual = 'inicio';
    } else if (path.includes('/atendimento.php')) {
      paginaAtual = 'atendimento';
    } else if (path.includes('/historico_atendimento.php')) {
      paginaAtual = 'historico';
    } else if (path.includes('/paciente.php')) {
      paginaAtual = 'pacientes';
    } else if (path.includes('/farmaceutico.php')) {
      paginaAtual = 'farmaceuticos';
    } else if (path.includes('/medicamento.php')) {
      paginaAtual = 'medicamentos';
    } else if (path.includes('/insights.php')) {
      paginaAtual = 'relatorios';
    } else if (path.includes('/unidade.php')) {
      paginaAtual = 'unidades';
    } else if (path.includes('/configuracoes.php')) {
      paginaAtual = 'configuracoes';
    }

    document.querySelectorAll('.sidebar-link').forEach(link => {
      link.classList.remove('active');
    });

    if (paginaAtual) {
      const linkAtivo = document.querySelector(`.sidebar-link[data-page="${paginaAtual}"]`);
      if (linkAtivo) {
        linkAtivo.classList.add('active');
      }
    }
  }

  document.addEventListener('DOMContentLoaded', async function() {
    await loadTemplate('/portal-repo-og/templates/header.php', 'header-container');
    await loadTemplate('/portal-repo-og/templates/sidebar.php', 'sidebar-container');
    await loadTemplate('/portal-repo-og/templates/main.php', 'main-container');
  });
</script>
</body>

</html>