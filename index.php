<?php
//include_once __DIR__ . '/../config/auth.php';

//if (!Auth::isAuthenticated()) {
//header("Location: /portal-repo-og/templates/login.php");
//exit();
//}
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

        // ✅ Após carregar o HEADER, vincule o evento do menu
        if (containerId === 'header-container') {
          const menuToggle = document.getElementById('menu-toggle');
          const sidebar = document.getElementById('sidebar');
          if (menuToggle && sidebar) {
            // Remove evento anterior (evita duplicação)
            menuToggle.removeEventListener('click', toggleSidebar);
            menuToggle.addEventListener('click', toggleSidebar);
          }
        }
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

        document.getElementById('atendimentos-hoje').textContent = data.atendimentos_hoje ?? '0';
        document.getElementById('consultas-cronicas').textContent = data.consultas_cronicas ?? '0';
        document.getElementById('casos-agudos').textContent = data.casos_agudos ?? '0';
        document.getElementById('taxa-adesao').textContent = (data.taxa_adesao ?? 0) + '%';

        const activityList = document.getElementById('activity-list');
        if (!activityList) return;

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
      } catch (err) {
        console.error('Erro ao carregar dashboard:', err);
        const activityList = document.getElementById('activity-list');
        if (activityList) {
          activityList.innerHTML = '<div class="text-danger text-center">Erro ao carregar dados.</div>';
        }
      }
    }

    document.addEventListener('DOMContentLoaded', async function() {
      await loadTemplate('/portal-repo-og/templates/header.php', 'header-container');
      await loadTemplate('/portal-repo-og/templates/sidebar.php', 'sidebar-container');
      await loadTemplate('/portal-repo-og/templates/main.php', 'main-container');

      setTimeout(carregarDashboard, 100);
    });

    // Após carregar o header.php
const menuToggle = document.getElementById('menu-toggle');
const sidebar = document.getElementById('sidebar');
if (menuToggle && sidebar) {
  menuToggle.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
  });
}
  </script>
</body>

</html>