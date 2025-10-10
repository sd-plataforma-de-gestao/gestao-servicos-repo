document.addEventListener("DOMContentLoaded", () => {

  // =================================================================
  // 1. ELEMENTOS DO DOM
  // =================================================================
  
  const formConfiguracao = document.getElementById("formConfiguracao");
  const btnSalvar = document.querySelector(".btn-salvar-config");
  
  // Abas
  const tabButtons = document.querySelectorAll(".config-tab-button");
  const tabPanes = document.querySelectorAll(".config-tab-pane");
  
  // Campos Geral
  const nomeGrupoInput = document.getElementById("nomeGrupo");
  const emailContatoInput = document.getElementById("emailContato");
  const telefoneContatoInput = document.getElementById("telefoneContato");
  
  // Campos Farmácia
  const crfResponsavelInput = document.getElementById("crfResponsavel");
  const cnpjFarmaciaInput = document.getElementById("cnpjFarmacia");
  const enderecoFarmaciaInput = document.getElementById("enderecoFarmacia");
  const horarioFuncionamentoInput = document.getElementById("horarioFuncionamento");
  
  // Campos Usuários
  const nomeUsuarioInput = document.getElementById("nomeUsuario");
  const emailUsuarioInput = document.getElementById("emailUsuario");
  const funcaoUsuarioInput = document.getElementById("funcaoUsuario");
  const btnAdicionarUsuario = document.getElementById("btnAdicionarUsuario");
  const btnGerenciarUsuarios = document.getElementById("btnGerenciarUsuarios");
  const usuariosList = document.getElementById("usuariosList");
  
  // Campos Tema
  const temaOptions = document.querySelectorAll(".tema-option");
  const corPrimariaInput = document.getElementById("corPrimaria");
  const corPreview = document.getElementById("corPreview");
  const btnAplicarTema = document.getElementById("btnAplicarTema");

  // =================================================================
  // 2. FORMATAÇÃO DE CAMPOS (Reutilizando do unidade.js)
  // =================================================================

  if (telefoneContatoInput) {
    telefoneContatoInput.addEventListener("input", function (e) {
      let v = e.target.value.replace(/\D/g, "");
      let formatado = "";
      if (v.length > 0) formatado = "(";
      if (v.length >= 2) formatado += v.substring(0, 2) + ") ";
      if (v.length >= 6) formatado += v.substring(2, 6) + "-" + v.substring(6, 10);
      else formatado += v.substring(2);
      e.target.value = formatado;
    });
  }

  if (cnpjFarmaciaInput) {
    cnpjFarmaciaInput.addEventListener("input", function (e) {
      let v = e.target.value.replace(/\D/g, "");
      if (v.length <= 14) {
        v = v.replace(/^(\d{2})(\d)/, "$1.$2");
        v = v.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
        v = v.replace(/\.(\d{3})(\d)/, ".$1/$2");
        v = v.replace(/(\d{4})(\d)/, "$1-$2");
      }
      e.target.value = v;
    });
  }

  // =================================================================
  // 3. NAVEGAÇÃO ENTRE ABAS
  // =================================================================

  tabButtons.forEach(button => {
    button.addEventListener("click", () => {
      const targetTab = button.getAttribute("data-tab");
      
      // Remove active de todos os botões e panes
      tabButtons.forEach(btn => btn.classList.remove("active"));
      tabPanes.forEach(pane => pane.classList.remove("active"));
      
      // Adiciona active ao botão clicado e ao pane correspondente
      button.classList.add("active");
      document.getElementById(`tab-${targetTab}`).classList.add("active");
    });
  });

  // =================================================================
  // 4. GERENCIAMENTO DE USUÁRIOS
  // =================================================================

  let usuariosCadastrados = [
    {
      id: 1,
      nome: "João Silva",
      email: "joao@farmacia.com",
      funcao: "admin"
    },
    {
      id: 2,
      nome: "Maria Santos",
      email: "maria@farmacia.com",
      funcao: "farmaceutico"
    },
    {
      id: 3,
      nome: "Pedro Costa",
      email: "pedro@farmacia.com",
      funcao: "atendente"
    }
  ];

  function renderizarUsuarios() {
    usuariosList.innerHTML = "";
    
    if (usuariosCadastrados.length === 0) {
      usuariosList.innerHTML = `
        <div class="text-center text-muted py-3">
          <i class="fa fa-users fa-2x mb-2"></i>
          <p>Nenhum usuário cadastrado</p>
        </div>
      `;
      return;
    }

    usuariosCadastrados.forEach(usuario => {
      const usuarioItem = document.createElement("div");
      usuarioItem.classList.add("usuario-item");
      usuarioItem.innerHTML = `
        <div class="usuario-info">
          <h6>${usuario.nome}</h6>
          <small>${usuario.email}</small>
        </div>
        <span class="usuario-funcao ${usuario.funcao}">${usuario.funcao}</span>
      `;
      usuariosList.appendChild(usuarioItem);
    });
  }

  btnAdicionarUsuario.addEventListener("click", () => {
    const nome = nomeUsuarioInput.value.trim();
    const email = emailUsuarioInput.value.trim();
    const funcao = funcaoUsuarioInput.value;

    if (!nome || !email || !funcao) {
      alert("Preencha todos os campos para adicionar um usuário.");
      return;
    }

    // Verificar se o email já existe
    if (usuariosCadastrados.some(u => u.email === email)) {
      alert("Este e-mail já está cadastrado.");
      return;
    }

    const novoUsuario = {
      id: Date.now(),
      nome,
      email,
      funcao
    };

    usuariosCadastrados.push(novoUsuario);
    renderizarUsuarios();

    // Limpar campos
    nomeUsuarioInput.value = "";
    emailUsuarioInput.value = "";
    funcaoUsuarioInput.value = "";

    console.log("Novo usuário adicionado:", novoUsuario);
  });

  btnGerenciarUsuarios.addEventListener("click", () => {
    alert("Funcionalidade 'Gerenciar Usuários' será implementada em breve.\n\nAqui você poderá editar, excluir e gerenciar permissões dos usuários.");
  });

  // =================================================================
  // 5. LÓGICA DE TEMA
  // =================================================================

  let temaAtual = "claro";
  let corPrimariaAtual = "#1a6d40";

  temaOptions.forEach(option => {
    option.addEventListener("click", () => {
      temaOptions.forEach(opt => opt.classList.remove("selected"));
      option.classList.add("selected");
      temaAtual = option.getAttribute("data-tema");
      console.log("Tema selecionado:", temaAtual);
    });
  });

  corPrimariaInput.addEventListener("input", (e) => {
    corPrimariaAtual = e.target.value;
    corPreview.style.backgroundColor = corPrimariaAtual;
  });

  btnAplicarTema.addEventListener("click", () => {
    console.log("Aplicando tema:", { tema: temaAtual, cor: corPrimariaAtual });
    
    // Aqui você aplicaria o tema ao sistema
    // Por exemplo, alterando variáveis CSS ou classes no body
    document.documentElement.style.setProperty('--primary-color', corPrimariaAtual);
    
    // Simulação de aplicação do tema escuro/claro
    if (temaAtual === "escuro") {
      document.body.classList.add("tema-escuro");
    } else {
      document.body.classList.remove("tema-escuro");
    }
    
    alert(`Tema ${temaAtual} aplicado com a cor ${corPrimariaAtual}!`);
  });

  // =================================================================
  // 6. CARREGAR CONFIGURAÇÕES (Simulação)
  // =================================================================

  function carregarConfiguracoes() {
    // Simulação de dados carregados do PHP/BD
    const configSimulada = {
      // Geral
      nomeGrupo: "SD Plataforma de Gestão",
      emailContato: "contato@sdplataforma.com.br",
      telefoneContato: "(11) 9999-8888",
      
      // Farmácia (dados da unidade logada)
      crfResponsavel: "CRF-SP 54321",
      cnpjFarmacia: "12.345.678/0001-90",
      enderecoFarmacia: "Rua das Farmácias, 123 - Centro, São Paulo - SP",
      horarioFuncionamento: "08:00 - 18:00",
      
      // Tema
      tema: "claro",
      corPrimaria: "#1a6d40"
    };

    // Preencher campos Geral
    if (nomeGrupoInput) nomeGrupoInput.value = configSimulada.nomeGrupo || "";
    if (emailContatoInput) emailContatoInput.value = configSimulada.emailContato || "";
    if (telefoneContatoInput) telefoneContatoInput.value = configSimulada.telefoneContato || "";
    
    // Preencher campos Farmácia
    if (crfResponsavelInput) crfResponsavelInput.value = configSimulada.crfResponsavel || "";
    if (cnpjFarmaciaInput) cnpjFarmaciaInput.value = configSimulada.cnpjFarmacia || "";
    if (enderecoFarmaciaInput) enderecoFarmaciaInput.value = configSimulada.enderecoFarmacia || "";
    if (horarioFuncionamentoInput) horarioFuncionamentoInput.value = configSimulada.horarioFuncionamento || "";
    
    // Aplicar tema
    temaAtual = configSimulada.tema || "claro";
    corPrimariaAtual = configSimulada.corPrimaria || "#1a6d40";
    
    if (corPrimariaInput) corPrimariaInput.value = corPrimariaAtual;
    if (corPreview) corPreview.style.backgroundColor = corPrimariaAtual;
    
    // Selecionar tema correto
    temaOptions.forEach(option => {
      if (option.getAttribute("data-tema") === temaAtual) {
        option.classList.add("selected");
      } else {
        option.classList.remove("selected");
      }
    });
  }

  // =================================================================
  // 7. SALVAR CONFIGURAÇÕES (Integração com PHP)
  // =================================================================

  formConfiguracao.addEventListener("submit", function (e) {
    e.preventDefault();

    // 1. Coletar todos os dados do formulário
    const dadosConfiguracao = {
      // Geral
      nomeGrupo: nomeGrupoInput?.value.trim() || "",
      emailContato: emailContatoInput?.value.trim() || "",
      telefoneContato: telefoneContatoInput?.value.trim() || "",
      
      // Farmácia
      crfResponsavel: crfResponsavelInput?.value.trim() || "",
      cnpjFarmacia: cnpjFarmaciaInput?.value.trim() || "",
      enderecoFarmacia: enderecoFarmaciaInput?.value.trim() || "",
      horarioFuncionamento: horarioFuncionamentoInput?.value.trim() || "",
      
      // Usuários
      usuarios: usuariosCadastrados,
      
      // Tema
      tema: temaAtual,
      corPrimaria: corPrimariaAtual
    };

    // 2. Validação básica
    if (!dadosConfiguracao.nomeGrupo || !dadosConfiguracao.emailContato) {
      alert("Por favor, preencha os campos obrigatórios (Nome do Grupo e E-mail de Contato).");
      return;
    }

    if (!dadosConfiguracao.crfResponsavel || !dadosConfiguracao.cnpjFarmacia || !dadosConfiguracao.enderecoFarmacia) {
      alert("Por favor, preencha os campos obrigatórios da Farmácia.");
      return;
    }

    // 3. Simulação de envio para o PHP
    console.log("Dados prontos para envio ao servidor (PHP/BD):", dadosConfiguracao);
    
    // Desabilitar botão e mostrar loading
    btnSalvar.disabled = true;
    btnSalvar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvando...';

    // Aqui você faria a requisição AJAX/Fetch para o seu script PHP
    /*
    fetch('salvar_configuracoes.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(dadosConfiguracao),
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert("Configurações salvas com sucesso!");
      } else {
        alert("Erro ao salvar configurações: " + data.message);
      }
    })
    .catch(error => {
      console.error('Erro na requisição:', error);
      alert("Ocorreu um erro de comunicação com o servidor.");
    })
    .finally(() => {
      // Reabilitar botão e restaurar texto
      btnSalvar.disabled = false;
      btnSalvar.innerHTML = '<i class="fa fa-save"></i> Salvar Configurações';
    });
    */

    // Simulação de sucesso após 2 segundos
    setTimeout(() => {
      alert("Configurações salvas com sucesso! (Simulação)");
      btnSalvar.disabled = false;
      btnSalvar.innerHTML = '<i class="fa fa-save"></i> Salvar Configurações';
    }, 2000);
  });

  // =================================================================
  // 8. INICIALIZAÇÃO
  // =================================================================

  // Carregar configurações e renderizar usuários ao iniciar
  carregarConfiguracoes();
  renderizarUsuarios();
});
