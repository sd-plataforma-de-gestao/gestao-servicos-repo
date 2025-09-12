document.addEventListener("DOMContentLoaded", () => {
  // ===== REFERÊNCIAS AO DOM =====
  const listaMedicamentos = document.getElementById("lista-medicamentos");
  const inputBusca = document.getElementById("buscaMedicamento");
  const selectFiltro = document.getElementById("filtroStatus");

  // MODAL DE EDIÇÃO
  const formMedicamento = document.getElementById("formMedicamento");
  const medicamentoModal = document.getElementById("medicamentoModal");
  const modalTitle = document.getElementById("medicamentoModalLabel");

  // MODAL DE VISUALIZAR OS DADOS DOS MEDICAMENTOS CADASTRADOS
  const detalhesModal = document.getElementById("detalhesMedicamentoModal");
  const detalhesCorpo = document.getElementById("detalhesCorpo");
  const btnEditarDoDetalhe = document.getElementById("btnEditarDoDetalhe");

  // CAMPOS FORMULÁRIO DE EDIÇÃO
  const medicamentoId = document.getElementById("medicamentoId");
  const nomeInput = document.getElementById("nomeMedicamento");
  const principioAtivoInput = document.getElementById("principioAtivo");
  const dosagemInput = document.getElementById("dosagem");
  const fabricanteInput = document.getElementById("fabricante");
  const tipoInput = document.getElementById("tipoMedicamento");
  const numeroLoteInput = document.getElementById("numeroLote");
  const dataValidadeInput = document.getElementById("dataValidade");
  const quantidadeEstoqueInput = document.getElementById("quantidadeEstoque");
  const precoUnitarioInput = document.getElementById("precoUnitario");
  const descricaoInput = document.getElementById("descricaoMedicamento");
  const requerReceitaInput = document.getElementById("requerReceita");
  const condicaoArmazenamentoInput = document.getElementById("condicaoArmazenamento");

  // ===== DADOS DOS MEDICAMENTOS (PRÉ CADASTRADOS PARA EXEMPLO) =====
  let todosMedicamentos = [
    {
      id: 1,
      nome: "Paracetamol",
      principioAtivo: "Paracetamol",
      dosagem: "500mg",
      fabricante: "EMS",
      tipo: "Comprimido",
      numeroLote: "ABC123",
      dataValidade: "2025-12-15",
      quantidadeEstoque: 150,
      precoUnitario: 0.25,
      descricao: "Analgésico e antitérmico",
      requerReceita: "Não",
      condicaoArmazenamento: "Temperatura Ambiente",
      status: "disponivel"
    },
    {
      id: 2,
      nome: "Ibuprofeno",
      principioAtivo: "Ibuprofeno",
      dosagem: "600mg",
      fabricante: "Medley",
      tipo: "Comprimido",
      numeroLote: "XYZ789",
      dataValidade: "2024-08-20",
      quantidadeEstoque: 25,
      precoUnitario: 0.45,
      descricao: "Anti-inflamatório não esteroidal",
      requerReceita: "Não",
      condicaoArmazenamento: "Temperatura Ambiente",
      status: "baixo"
    },
    {
      id: 3,
      nome: "Dipirona",
      principioAtivo: "Dipirona Sódica",
      dosagem: "500mg",
      fabricante: "Sanofi",
      tipo: "Comprimido",
      numeroLote: "DEF456",
      dataValidade: "2026-03-10",
      quantidadeEstoque: 0,
      precoUnitario: 0.18,
      descricao: "Analgésico e antitérmico",
      requerReceita: "Não",
      condicaoArmazenamento: "Temperatura Ambiente",
      status: "esgotado"
    }
  ];

  // ===== FORMATAÇÃO DE CAMPOS =====
  precoUnitarioInput.addEventListener("input", function (e) {
    let value = e.target.value.replace(/[^\d.,]/g, '');
    e.target.value = value;
  });

  // ===== CRIAR CARD DO MEDICAMENTO =====
  function criarCard(medicamento) {
    const statusNorm = (medicamento.status || "")
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase()
      .replace(/\s+/g, "");

    const dataValidade = new Date(medicamento.dataValidade);
    const hoje = new Date();
    const diasParaVencer = Math.ceil((dataValidade - hoje) / (1000 * 60 * 60 * 24));
    
    let statusValidade = '';
    if (diasParaVencer < 0) {
      statusValidade = 'vencido';
    } else if (diasParaVencer <= 30) {
      statusValidade = 'vencendo';
    }

    const card = document.createElement("div");
    card.classList.add("medicamento-card");
    card.dataset.id = medicamento.id;
    card.innerHTML = `
      <div class="medicamento-info">
        <div class="icon-circle"><i class="fa fa-pills"></i></div>
        <div>
          <strong>${medicamento.nome} <span class="medicamento-dosagem">${medicamento.dosagem}</span></strong>
          <p class="text-muted">Princípio Ativo: ${medicamento.principioAtivo} • Fabricante: ${medicamento.fabricante}</p>
          <small class="text-secondary">
            Lote: <span class="medicamento-lote">${medicamento.numeroLote}</span> • 
            Validade: <span class="medicamento-validade ${statusValidade}">${formatarData(medicamento.dataValidade)}</span>
          </small>
        </div>
      </div>
      <div class="medicamento-actions">
        <span class="status-badge ${statusNorm}">${getStatusText(medicamento.status)}</span>
        <button class="btn btn-outline-secondary btn-sm btn-detalhes"><i class="fa fa-eye"></i> Detalhes</button>
      </div>
    `;
    listaMedicamentos.appendChild(card);

    // Clique no card → abre modal de detalhes
    card.addEventListener("click", () => {
      const id = parseInt(card.dataset.id);
      abrirDetalhes(id);
    });

    // Botão "Detalhes" → abre modal de detalhes
    card.querySelector(".btn-detalhes").addEventListener("click", (e) => {
      e.stopPropagation();
      const id = parseInt(card.dataset.id);
      abrirDetalhes(id);
    });
  }

  // ===== ABRIR MODAL DE DETALHES (TODOS OS DADOS) =====
  function abrirDetalhes(id) {
    const medicamento = todosMedicamentos.find(m => m.id === id);
    if (!medicamento) return;

    // Preenche o modal
    detalhesCorpo.innerHTML = `
      <div class="row g-3">
        <div class="col-12">
          <h5><i class="fa fa-pills"></i> ${medicamento.nome} ${medicamento.dosagem}</h5>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>Princípio Ativo</strong></label>
          <p class="form-control-plaintext">${medicamento.principioAtivo}</p>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>Fabricante</strong></label>
          <p class="form-control-plaintext">${medicamento.fabricante}</p>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>Tipo</strong></label>
          <p class="form-control-plaintext">${medicamento.tipo}</p>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>Número do Lote</strong></label>
          <p class="form-control-plaintext">${medicamento.numeroLote}</p>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>Data de Validade</strong></label>
          <p class="form-control-plaintext">${formatarData(medicamento.dataValidade)}</p>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>Quantidade em Estoque</strong></label>
          <p class="form-control-plaintext">${medicamento.quantidadeEstoque} unidades</p>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>Preço Unitário</strong></label>
          <p class="form-control-plaintext">R$ ${medicamento.precoUnitario.toFixed(2)}</p>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>Requer Receita</strong></label>
          <p class="form-control-plaintext">${medicamento.requerReceita}</p>
        </div>
        <div class="col-12">
          <label class="form-label"><strong>Condição de Armazenamento</strong></label>
          <p class="form-control-plaintext">${medicamento.condicaoArmazenamento || 'Não informada'}</p>
        </div>
        <div class="col-12">
          <label class="form-label"><strong>Descrição/Observações</strong></label>
          <p class="form-control-plaintext">${medicamento.descricao || 'Não informada'}</p>
        </div>
      </div>
    `;

    // Guarda o ID no modal para edição
    detalhesModal.setAttribute("data-id", medicamento.id);

    // Abre o modal
    new bootstrap.Modal(detalhesModal).show();
  }

  // ===== BOTÃO "EDITAR" NO MODAL DE DETALHES =====
  btnEditarDoDetalhe.addEventListener("click", () => {
    const id = parseInt(detalhesModal.getAttribute("data-id"));
    const medicamento = todosMedicamentos.find(m => m.id === id);
    if (!medicamento) return;

    // Preenche o formulário de edição
    medicamentoId.value = medicamento.id;
    nomeInput.value = medicamento.nome;
    principioAtivoInput.value = medicamento.principioAtivo;
    dosagemInput.value = medicamento.dosagem;
    fabricanteInput.value = medicamento.fabricante;
    tipoInput.value = medicamento.tipo;
    numeroLoteInput.value = medicamento.numeroLote;
    dataValidadeInput.value = medicamento.dataValidade;
    quantidadeEstoqueInput.value = medicamento.quantidadeEstoque;
    precoUnitarioInput.value = medicamento.precoUnitario;
    descricaoInput.value = medicamento.descricao;
    requerReceitaInput.value = medicamento.requerReceita;
    condicaoArmazenamentoInput.value = medicamento.condicaoArmazenamento;

    modalTitle.textContent = "Editar Medicamento";

    // Fecha o modal de detalhes
    bootstrap.Modal.getInstance(detalhesModal)?.hide();

    // Abre o modal de edição
    new bootstrap.Modal(medicamentoModal).show();
  });

  // ===== BUSCA E FILTRO =====
  function aplicarFiltros() {
    listaMedicamentos.innerHTML = "";

    const termo = (inputBusca?.value || "").toLowerCase().trim();
    const filtro = selectFiltro?.value || "";

    const filtrados = todosMedicamentos.filter(m => {
      const matchBusca =
        m.nome.toLowerCase().includes(termo) ||
        m.principioAtivo.toLowerCase().includes(termo) ||
        m.fabricante.toLowerCase().includes(termo) ||
        m.numeroLote.toLowerCase().includes(termo);

      const matchFiltro =
        filtro === "" || m.status === filtro;

      return matchBusca && matchFiltro;
    });

    if (filtrados.length === 0) {
      listaMedicamentos.innerHTML = `
        <div class="empty-state">
          <i class="fa fa-pills"></i>
          <h4>Nenhum medicamento encontrado</h4>
          <p>Tente ajustar os filtros de busca ou cadastre um novo medicamento.</p>
        </div>
      `;
    } else {
      filtrados.forEach(criarCard);
    }
  }

  // ===== SALVAR NOVO OU EDITAR =====
  formMedicamento.addEventListener("submit", function (e) {
    e.preventDefault();

    const id = medicamentoId.value ? parseInt(medicamentoId.value) : Date.now();
    const dados = {
      id,
      nome: nomeInput.value.trim(),
      principioAtivo: principioAtivoInput.value.trim(),
      dosagem: dosagemInput.value.trim(),
      fabricante: fabricanteInput.value.trim(),
      tipo: tipoInput.value,
      numeroLote: numeroLoteInput.value.trim(),
      dataValidade: dataValidadeInput.value,
      quantidadeEstoque: parseInt(quantidadeEstoqueInput.value),
      precoUnitario: parseFloat(precoUnitarioInput.value) || 0,
      descricao: descricaoInput.value.trim(),
      requerReceita: requerReceitaInput.value,
      condicaoArmazenamento: condicaoArmazenamentoInput.value
    };

    // Validação básica
    if (!dados.nome || !dados.principioAtivo || !dados.dosagem || !dados.fabricante || 
        !dados.tipo || !dados.numeroLote || !dados.dataValidade || dados.quantidadeEstoque < 0) {
      alert("Preencha todos os campos obrigatórios.");
      return;
    }

    // Determina o status baseado na quantidade
    dados.status = getStatusByQuantity(dados.quantidadeEstoque);

    const index = todosMedicamentos.findIndex(m => m.id === id);
    if (index !== -1) {
      todosMedicamentos[index] = dados;
    } else {
      todosMedicamentos.push(dados);
    }

    aplicarFiltros();
    formMedicamento.reset();
    medicamentoId.value = "";
    modalTitle.textContent = "Cadastrar Novo Medicamento";
    const modalInstance = bootstrap.Modal.getInstance(medicamentoModal);
    if (modalInstance) {
      modalInstance.hide();
    }
  });

  // ===== BOTÃO "NOVO MEDICAMENTO" =====
  document.querySelector("[data-bs-target='#medicamentoModal']").addEventListener("click", () => {
    formMedicamento.reset();
    medicamentoId.value = "";
    modalTitle.textContent = "Cadastrar Novo Medicamento";
  });

  // ===== EVENTOS DE BUSCA E FILTRO =====
  inputBusca?.addEventListener("input", aplicarFiltros);
  selectFiltro?.addEventListener("change", aplicarFiltros);

  // ===== FUNÇÕES AUXILIARES =====
  function formatarData(dataString) {
    const data = new Date(dataString);
    return data.toLocaleDateString('pt-BR');
  }

  function getStatusText(status) {
    switch (status) {
      case 'disponivel': return 'Disponível';
      case 'baixo': return 'Estoque Baixo';
      case 'esgotado': return 'Esgotado';
      default: return 'Sem Status';
    }
  }

  function getStatusByQuantity(quantidade) {
    if (quantidade === 0) return 'esgotado';
    if (quantidade <= 30) return 'baixo';
    return 'disponivel';
  }

  // ===== INICIALIZA A LISTA =====
  aplicarFiltros();
});

