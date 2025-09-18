document.addEventListener("DOMContentLoaded", () => {
  const unidadesGrid = document.getElementById("unidadesGrid");
  const totalUnidadesEl = document.getElementById("totalUnidades");
  const totalFarmaceuticosEl = document.getElementById("totalFarmaceuticos");
  const atendimentosHojeEl = document.getElementById("atendimentosHoje");

  const formUnidade = document.getElementById("formUnidade");
  const unidadeModal = document.getElementById("unidadeModal");
  const modalTitle = document.getElementById("unidadeModalLabel");

  const detalhesModal = document.getElementById("detalhesUnidadeModal");
  const detalhesCorpo = document.getElementById("detalhesCorpoUnidade");
  const btnEditarUnidade = document.getElementById("btnEditarUnidade");

  const unidadeId = document.getElementById("unidadeId");
  const nomeInput = document.getElementById("nomeUnidade");
  const cnpjInput = document.getElementById("cnpjUnidade");
  const telefoneInput = document.getElementById("telefoneUnidade");
  const enderecoInput = document.getElementById("enderecoUnidade");
  const farmaceuticoInput = document.getElementById("farmaceuticoResponsavel");
  const crfInput = document.getElementById("crfResponsavel");
  const horarioInput = document.getElementById("horarioFuncionamento");
  const statusInput = document.getElementById("statusUnidade");
  const observacoesInput = document.getElementById("observacoesUnidade");

    // Aqui "cadastrei" algumas empresas fictícias para visualização no front, 
    // não temos nada do banco ainda em relação as unidades que existem.
  let todasUnidades = [
    {
      id: 1,
      nome: "Farmácia Centro",
      cnpj: "12.345.678/0001-90",
      telefone: "(11) 3333-3333",
      endereco: "Rua Principal, 123 - Centro",
      farmaceuticoResponsavel: "Dr. Carlos Silva",
      crfResponsavel: "CRF-SP 12345",
      horarioFuncionamento: "08:00 - 18:00",
      status: "Ativa",
      observacoes: "Unidade principal da rede",
      farmaceuticos: 3,
      atendimentosHoje: 28
    },
    {
      id: 2,
      nome: "Farmácia Shopping",
      cnpj: "12.345.678/0002-71",
      telefone: "(11) 4444-4444",
      endereco: "Shopping Center, Loja 45",
      farmaceuticoResponsavel: "Dra. Ana Costa",
      crfResponsavel: "CRF-SP 67890",
      horarioFuncionamento: "10:00 - 22:00",
      status: "Ativa",
      observacoes: "Localizada no shopping principal",
      farmaceuticos: 2,
      atendimentosHoje: 15
    },
    {
      id: 3,
      nome: "Farmácia Bairro Norte",
      cnpj: "12.345.678/0003-52",
      telefone: "(11) 5555-5555",
      endereco: "Av. Norte, 567 - Bairro Norte",
      farmaceuticoResponsavel: "Dr. Pedro Santos",
      crfResponsavel: "CRF-SP 11111",
      horarioFuncionamento: "07:00 - 19:00",
      status: "Ativa",
      observacoes: "Atende região norte da cidade",
      farmaceuticos: 2,
      atendimentosHoje: 22
    }
    
  ];

  cnpjInput.addEventListener("input", function (e) {
    let v = e.target.value.replace(/\D/g, "");
    if (v.length <= 14) {
      v = v.replace(/^(\d{2})(\d)/, "$1.$2");
      v = v.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
      v = v.replace(/\.(\d{3})(\d)/, ".$1/$2");
      v = v.replace(/(\d{4})(\d)/, "$1-$2");
    }
    e.target.value = v;
  });

  telefoneInput.addEventListener("input", function (e) {
    let v = e.target.value.replace(/\D/g, "");
    let formatado = "";
    if (v.length > 0) formatado = "(";
    if (v.length >= 2) formatado += v.substring(0, 2) + ") ";
    if (v.length >= 6) formatado += v.substring(2, 6) + "-" + v.substring(6, 10);
    else formatado += v.substring(2);
    e.target.value = formatado;
  });

  function criarCardUnidade(unidade) {
    const statusClass = unidade.status.toLowerCase().replace(/\s+/g, "");
    
    const card = document.createElement("div");
    card.classList.add("unidade-card");
    card.dataset.id = unidade.id;
    card.innerHTML = `
      <div class="unidade-header">
        <h3 class="unidade-nome">${unidade.nome}</h3>
        <span class="unidade-status ${statusClass}">${unidade.status}</span>
      </div>
      
      <div class="unidade-info">
        <div class="unidade-info-item">
          <i class="fa fa-id-card"></i>
          <span>CNPJ: ${unidade.cnpj}</span>
        </div>
        <div class="unidade-info-item">
          <i class="fa fa-map-marker-alt"></i>
          <span>${unidade.endereco}</span>
        </div>
        <div class="unidade-info-item">
          <i class="fa fa-phone"></i>
          <span>${unidade.telefone}</span>
        </div>
        <div class="unidade-info-item">
          <i class="fa fa-clock"></i>
          <span>${unidade.horarioFuncionamento}</span>
        </div>
      </div>
      
      <div class="unidade-stats">
        <div class="unidade-stat">
          <span class="unidade-stat-number">${unidade.farmaceuticos}</span>
          <span class="unidade-stat-label">Farmacêuticos</span>
        </div>
        <div class="unidade-stat">
          <span class="unidade-stat-number">${unidade.atendimentosHoje}</span>
          <span class="unidade-stat-label">Atend. Hoje</span>
        </div>
      </div>
      
      <div class="unidade-actions">
        <button class="btn-unidade-action btn-editar">
          <i class="fa fa-edit"></i> Editar
        </button>
        <button class="btn-unidade-action primary btn-config">
          <i class="fa fa-cog"></i> Config
        </button>
      </div>
    `;

    unidadesGrid.appendChild(card);

    card.addEventListener("click", (e) => {
      if (!e.target.closest(".unidade-actions")) {
        abrirDetalhes(unidade.id);
      }
    });

    card.querySelector(".btn-editar").addEventListener("click", (e) => {
      e.stopPropagation();
      editarUnidade(unidade.id);
    });

    card.querySelector(".btn-config").addEventListener("click", (e) => {
      e.stopPropagation();
      configurarUnidade(unidade.id);
    });
  }

  function abrirDetalhes(id) {
    const unidade = todasUnidades.find(u => u.id === id);
    if (!unidade) return;

    detalhesCorpo.innerHTML = `
      <div class="row g-3">
        <div class="col-12">
          <h5><i class="fa fa-building"></i> ${unidade.nome}</h5>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>CNPJ</strong></label>
          <p class="form-control-plaintext">${unidade.cnpj}</p>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>Status</strong></label>
          <p class="form-control-plaintext">${unidade.status}</p>
        </div>
        <div class="col-12">
          <label class="form-label"><strong>Endereço</strong></label>
          <p class="form-control-plaintext">${unidade.endereco}</p>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>Telefone</strong></label>
          <p class="form-control-plaintext">${unidade.telefone}</p>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>Horário de Funcionamento</strong></label>
          <p class="form-control-plaintext">${unidade.horarioFuncionamento}</p>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>Farmacêutico Responsável</strong></label>
          <p class="form-control-plaintext">${unidade.farmaceuticoResponsavel || 'Não informado'}</p>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>CRF</strong></label>
          <p class="form-control-plaintext">${unidade.crfResponsavel || 'Não informado'}</p>
        </div>
        <div class="col-12">
          <label class="form-label"><strong>Observações</strong></label>
          <p class="form-control-plaintext">${unidade.observacoes || 'Nenhuma observação'}</p>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>Farmacêuticos</strong></label>
          <p class="form-control-plaintext">${unidade.farmaceuticos} profissionais</p>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>Atendimentos Hoje</strong></label>
          <p class="form-control-plaintext">${unidade.atendimentosHoje} atendimentos</p>
        </div>
      </div>
    `;

    detalhesModal.setAttribute("data-id", unidade.id);
    new bootstrap.Modal(detalhesModal).show();
  }


  function editarUnidade(id) {
    const unidade = todasUnidades.find(u => u.id === id);
    if (!unidade) return;

    unidadeId.value = unidade.id;
    nomeInput.value = unidade.nome;
    cnpjInput.value = unidade.cnpj;
    telefoneInput.value = unidade.telefone;
    enderecoInput.value = unidade.endereco;
    farmaceuticoInput.value = unidade.farmaceuticoResponsavel;
    crfInput.value = unidade.crfResponsavel;
    horarioInput.value = unidade.horarioFuncionamento;
    statusInput.value = unidade.status;
    observacoesInput.value = unidade.observacoes;

    modalTitle.textContent = "Editar Unidade";
    new bootstrap.Modal(unidadeModal).show();
  }

  function configurarUnidade(id) {
    const unidade = todasUnidades.find(u => u.id === id);
    if (!unidade) return;
    
    alert(`Configurações da ${unidade.nome}\n\nEsta funcionalidade será implementada em breve.`);
  }

  btnEditarUnidade.addEventListener("click", () => {
    const id = parseInt(detalhesModal.getAttribute("data-id"));
    bootstrap.Modal.getInstance(detalhesModal)?.hide();
    editarUnidade(id);
  });

  formUnidade.addEventListener("submit", function (e) {
    e.preventDefault();

    const id = unidadeId.value ? parseInt(unidadeId.value) : Date.now();
    const dados = {
      id,
      nome: nomeInput.value.trim(),
      cnpj: cnpjInput.value.trim(),
      telefone: telefoneInput.value.trim(),
      endereco: enderecoInput.value.trim(),
      farmaceuticoResponsavel: farmaceuticoInput.value.trim(),
      crfResponsavel: crfInput.value.trim(),
      horarioFuncionamento: horarioInput.value.trim(),
      status: statusInput.value,
      observacoes: observacoesInput.value.trim(),
      farmaceuticos: Math.floor(Math.random() * 5) + 1,
      atendimentosHoje: Math.floor(Math.random() * 50) + 1 
    };


    if (!dados.nome || !dados.cnpj || !dados.telefone || !dados.endereco) {
      alert("Preencha todos os campos obrigatórios.");
      return;
    }

    const index = todasUnidades.findIndex(u => u.id === id);
    if (index !== -1) {
      todasUnidades[index] = dados;
    } else {
      todasUnidades.push(dados);
    }

    renderizarUnidades();
    atualizarEstatisticas();
    formUnidade.reset();
    unidadeId.value = "";
    modalTitle.textContent = "Cadastrar Nova Unidade";
    bootstrap.Modal.getInstance(unidadeModal)?.hide();
  });

  document.querySelector("[data-bs-target='#unidadeModal']").addEventListener("click", () => {
    formUnidade.reset();
    unidadeId.value = "";
    modalTitle.textContent = "Cadastrar Nova Unidade";
  });

  function renderizarUnidades() {
    unidadesGrid.innerHTML = "";

    if (todasUnidades.length === 0) {
      unidadesGrid.innerHTML = `
        <div class="empty-state">
          <i class="fa fa-building"></i>
          <h4>Nenhuma unidade cadastrada</h4>
          <p>Cadastre a primeira unidade da sua rede farmacêutica.</p>
          <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#unidadeModal">
            <i class="fa fa-plus"></i> Cadastrar Primeira Unidade
          </button>
        </div>
      `;
    } else {
      todasUnidades.forEach(criarCardUnidade);
    }
  }

  function atualizarEstatisticas() {
    const totalUnidades = todasUnidades.length;
    const totalFarmaceuticos = todasUnidades.reduce((sum, u) => sum + u.farmaceuticos, 0);
    const totalAtendimentos = todasUnidades.reduce((sum, u) => sum + u.atendimentosHoje, 0);

    totalUnidadesEl.textContent = totalUnidades;
    totalFarmaceuticosEl.textContent = totalFarmaceuticos;
    atendimentosHojeEl.textContent = totalAtendimentos;
  }

  renderizarUnidades();
  atualizarEstatisticas();
});

