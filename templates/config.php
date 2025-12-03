<?php
session_start();

if (!isset($_SESSION['farmaceutico_id'])) {
    header("Location: /portal-repo-og/login.php");
    exit;
}

include(__DIR__ . '/../config/database.php');
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

$nomeSistemaFixo = "Vitally Sistemas";
$emailSuporteFixo = "miguelmcastell@hotmail.com.br";
$telefoneSuporteFixo = "(19) 97133-0883";
$geminiApiKey = ''; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Configurações do Sistema</title>
  <link rel="icon" href="/portal-repo-og/assets/favicon.png" type="image/png">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <link rel="stylesheet" href="/portal-repo-og/styles/global.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/header.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/sidebar.css">
  <link rel="stylesheet" href="/portal-repo-og/styles/config.css">
</head>

<body>
  <div id="header-container"></div>

  <div id="main-content-wrapper">
    <div id="sidebar-container"></div>
    <div id="main-container">
      <div class="page-header">
        <h2 class="page-title">Configurações do Sistema</h2>
        <p class="page-subtitle">Gerencie as informações básicas do seu sistema.</p>
      </div>

      <div class="card p-4 shadow-sm">
        <form id="formConfiguracaoBasica" method="POST" action="salvar_config.php">
          
          <div class="mb-3">
            <label for="nomeSistema" class="form-label">Nome do Sistema *</label>
            <input type="text" class="form-control" id="nomeSistema" name="nomeSistema" 
                   value="<?php echo htmlspecialchars($nomeSistemaFixo); ?>" readonly required>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="emailSuporte" class="form-label">E-mail de Suporte *</label>
              <input type="email" class="form-control" id="emailSuporte" name="emailSuporte" 
                     value="<?php echo htmlspecialchars($emailSuporteFixo); ?>" readonly required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="telefoneSuporte" class="form-label">Telefone de Contato</label>
              <input type="text" class="form-control" id="telefoneSuporte" name="telefoneSuporte" 
                     value="<?php echo htmlspecialchars($telefoneSuporteFixo); ?>" readonly placeholder="(00) 00000-0000">
            </div>
          </div>

          

          <h5 class="mt-4 mb-3">Integração Gemini API</h5>
          <div class="mb-3">
            <label for="geminiApiKey" class="form-label">Chave da API do Gemini</label>
            <div class="input-group">
                <input type="password" class="form-control" id="geminiApiKey" name="geminiApiKey" 
                       placeholder="Chave oculta ou vazia" readonly required>
                <button class="btn btn-outline-secondary" type="button" id="btnEditarApi">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
            <div class="form-text">Clique em Editar e digite sua senha para visualizar/modificar a chave.</div>
          </div>
          <div class="text-end">
            <button type="submit" class="btn btn-success">
              <i class="fa fa-save"></i> Salvar Configurações
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/portal-repo-og/js/script.js"></script>
  <script src="/portal-repo-og/js/sidebar.js"></script>
  <script>
    async function loadTemplate(templatePath, containerId) {
      try {
        const response = await fetch(templatePath);
        if (!response.ok) throw new Error(`Erro ${response.status}`);
        let html = await response.text();

        const basePath = '/portal-repo-og';
        html = html
          .replace(/src="(?!https?:|\/)([^"]+)"/g, `src="${basePath}/$1"`)
          .replace(/href="(?!https?:|\/)([^"]+)"/g, `href="${basePath}/$1"`);

        document.getElementById(containerId).innerHTML = html;
      } catch (error) {
        console.error(`Erro ao carregar ${templatePath}:`, error);
      }
    }

    document.addEventListener("DOMContentLoaded", async () => {
      await loadTemplate("/portal-repo-og/templates/header.php", "header-container");
      await loadTemplate("/portal-repo-og/templates/sidebar.php", "sidebar-container");

      if (typeof initializeSidebar === 'function') initializeSidebar();
      
      const btnEditarApi = document.getElementById('btnEditarApi');
      const geminiApiKeyInput = document.getElementById('geminiApiKey');
      let apiKeyLoaded = false;

      btnEditarApi.addEventListener('click', () => {
        Swal.fire({
          title: 'Confirmação de Segurança',
          html: '<p>Para editar a chave da API, digite sua senha de login:</p>',
          input: 'password',
          inputPlaceholder: 'Sua Senha',
          showCancelButton: true,
          confirmButtonText: 'Verificar e Carregar',
          cancelButtonText: 'Cancelar',
          showLoaderOnConfirm: true,
          preConfirm: (password) => {

            const farmaceuticoId = <?php echo isset($_SESSION['farmaceutico_id']) ? $_SESSION['farmaceutico_id'] : 'null'; ?>;
            
            if (!farmaceuticoId) {
                Swal.showValidationMessage('Erro: ID do farmacêutico não encontrado.');
                return false;
            }

            return fetch('/portal-repo-og/templates/check_password_and_get_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    id: farmaceuticoId, 
                    password: password 
                })
            })

            .then(response => {
              if (!response.ok) {
                throw new Error(response.statusText || 'Erro na comunicação com o servidor');
              }
              return response.json();
            })
            .then(data => {
              if (data.success) {
                return data.apiKey;
              } else {
                throw new Error(data.message || 'Senha incorreta ou erro de validação.');
              }
            })
            .catch(error => {
              Swal.showValidationMessage(`Falha na verificação: ${error.message}`);
              geminiApiKeyInput.value = ''; 
              geminiApiKeyInput.setAttribute('type', 'password');
              geminiApiKeyInput.setAttribute('readonly', 'true');
            });
          },
          allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
          if (result.isConfirmed) {
            geminiApiKeyInput.value = result.value;
            geminiApiKeyInput.removeAttribute('readonly');
            geminiApiKeyInput.setAttribute('type', 'text'); 
            apiKeyLoaded = true;

            Swal.fire({
              icon: 'success',
              title: 'Acesso Liberado!',
              text: 'A chave da API foi carregada e o campo está liberado para edição.',
              confirmButtonColor: '#1C5B40'
            });
          }
        });
      });
    });
  </script>
</body>
</html>