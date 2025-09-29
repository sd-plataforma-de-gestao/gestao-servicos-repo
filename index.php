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
  <title>Dashboard</title>
  <link rel="icon" href="/assets/favicon.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="styles/global.css">
  <link rel="stylesheet" href="styles/header.css">
  <link rel="stylesheet" href="styles/sidebar.css">
  <link rel="stylesheet" href="styles/main.css">
  <link rel="stylesheet" href="styles/responsive.css">
</head>
<body>
    <div id="header-container"></div>
    
    <div id="main-content-wrapper">
      <div id="sidebar-container"></div>
    
      <div id="main-container"></div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
    
    <script>
      async function loadTemplate(templatePath, containerId) {
        try {
          const response = await fetch(templatePath);
          const html = await response.text();
          document.getElementById(containerId).innerHTML = html;
        } catch (error) {
          console.error(`Erro ao carregar template ${templatePath}:`, error);
        }   
      }
    
      document.addEventListener('DOMContentLoaded', async function() {
        await loadTemplate('templates/header.php', 'header-container');
        await loadTemplate('templates/sidebar.php', 'sidebar-container');
        await loadTemplate('templates/main.php', 'main-container');
      });
    </script>
</body>
</html>