document.addEventListener("DOMContentLoaded", () => {
  // ===== REFERÊNCIAS AO DOM =====
  const listaPacientes = document.getElementById("lista-pacientes");
  const inputBusca = document.getElementById("buscaPaciente");
  const selectFiltro = document.getElementById("filtroStatus");

  // Modal de edição
  const formPaciente = document.getElementById("formPaciente");
  const pacienteModal = document.getElementById("pacienteModal");
  const modalTitle = document.getElementById("pacienteModalLabel");

  // Modal de detalhes (visualização)
  const detalhesModal = document.getElementById("detalhesPacienteModal");
  const detalhesCorpo = document.getElementById("detalhesCorpo");
  const btnEditarDoDetalhe = document.getElementById("btnEditarDoDetalhe");

  // Modal de prontuário
  const prontuarioModal = document.getElementById("prontuarioModal");
  const prontuarioCorpo = document.getElementById("prontuarioCorpo");

  // Campos do formulário de edição
  const pacienteId = document.getElementById("pacienteId");
  const nomeInput = document.getElementById("nomePaciente");
  const idadeInput = document.getElementById("idadePaciente");
  const sexoInput = document.getElementById("sexoPaciente");
  const cpfInput = document.getElementById("cpfPaciente");
  const telefoneInput = document.getElementById("telefonePaciente");
  const enderecoInput = document.getElementById("enderecoPaciente");
  const ultimaConsultaInput = document.getElementById("ultimaConsultaPaciente");
  const statusInput = document.getElementById("statusPaciente");

  // ===== DADOS DOS PACIENTES (com prontuário) =====
  let todosPacientes = [
    {
      id: 1,
      nome: "Maria Silva Santos",
      idade: "65",
      sexo: "Feminino",
      cpf: "111.111.111-11",
      telefone: "+(11) 99999-9999",
      endereco: "Rua das Flores, 123, São Paulo",
      ultimaConsulta: "10/05/2024",
      status: "Crônico",
      prontuario: [
        {
          data: "20/05/2024",
          medicamentos: "Losartana 50mg, Metformina 850mg",
          queixa: "Tontura ao levantar",
          adesao: "Média",
          prms: "Hipotensão relacionada ao medicamento",
          intervencao: "Orientado sobre levantar devagar",
          evolucao: "Paciente ciente. Sem intercorrências.",
          farmaceutico: "Dr. Carlos Mendes - CRF-SP 12345"
        },
        {
          data: "10/05/2024",
          medicamentos: "Losartana, Metformina, Aspirina",
          queixa: "Início de acompanhamento",
          adesao: "Alta",
          prms: "Nenhum identificado",
          intervencao: "Validação da lista de medicamentos",
          evolucao: "Paciente aderente. Iniciado plano de acompanhamento.",
          farmaceutico: "Dr. Carlos Mendes - CRF-SP 12345"
        }
      ]
    },
    {
      id: 2,
      nome: "João Carlos Oliveira",
      idade: "45",
      sexo: "Masculino",
      cpf: "222.222.222-22",
      telefone: "+(11) 88888-8888",
      endereco: "Avenida Brasil, 456, Rio de Janeiro",
      ultimaConsulta: "10/05/2024",
      status: "Agudo",
      prontuario: []
    }
  ];

  // ===== FORMATAÇÃO DE CAMPOS =====
  cpfInput.addEventListener("input", function (e) {
    let v = e.target.value.replace(/\D/g, "");
    if (v.length <= 11) {
      v = v.replace(/(\d{3})(\d)/, "$1.$2");
      v = v.replace(/(\d{3})(\d)/, "$1.$2");
      v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
    }
    e.target.value = v;
  });

  telefoneInput.addEventListener("input", function (e) {
    let v = e.target.value.replace(/\D/g, "");
    let formatado = "";
    if (v.length > 0) formatado = "+(";
    if (v.length >= 2) formatado += v.substring(0, 2) + ") ";
    if (v.length >= 7) formatado += v.substring(2, 7) + "-" + v.substring(7, 11);
    else formatado += v.substring(2);
    e.target.value = formatado;
  });

  // ===== CRIAR CARD DO PACIENTE =====
  function criarCard(paciente) {
    const statusNorm = (paciente.status || "")
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase()
      .replace(/\s+/g, "");

    const card = document.createElement("div");
    card.classList.add("paciente-card");
    card.dataset.id = paciente.id;
    card.innerHTML = `
      <div class="paciente-info">
        <div class="icon-circle"><i class="fa fa-user"></i></div>
        <div>
          <strong>${paciente.nome}</strong>
          <p class="text-muted">${paciente.idade} anos • ${paciente.sexo} • ${paciente.telefone}</p>
          <small class="text-secondary">CPF: ${paciente.cpf} • Última consulta: ${paciente.ultimaConsulta}</small>
        </div>
      </div>
      <div class="paciente-actions">
        <span class="status-badge ${statusNorm}">${paciente.status || 'Sem tipo'}</span>
        <button class="btn btn-outline-secondary btn-sm btn-prontuario"><i class="fa fa-file-medical"></i> Prontuário</button>
      </div>
    `;
    listaPacientes.appendChild(card);

    // Clique no card → abre modal de detalhes
    card.addEventListener("click", () => {
      const id = parseInt(card.dataset.id);
      abrirDetalhes(id);
    });

    // Botão "Prontuário" → abre modal de prontuário
    card.querySelector(".btn-prontuario").addEventListener("click", (e) => {
      e.stopPropagation();
      const id = parseInt(card.dataset.id);
      abrirProntuario(id);
    });
  }

  // ===== ABRIR MODAL DE DETALHES (TODOS OS DADOS) =====
  function abrirDetalhes(id) {
    const paciente = todosPacientes.find(p => p.id === id);
    if (!paciente) return;

    // Preenche o modal
    detalhesCorpo.innerHTML = `
      <div class="row g-3">
        <div class="col-12">
          <h5><i class="fa fa-user"></i> ${paciente.nome}</h5>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>Idade</strong></label>
          <p class="form-control-plaintext">${paciente.idade} anos</p>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>Sexo</strong></label>
          <p class="form-control-plaintext">${paciente.sexo}</p>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>CPF</strong></label>
          <p class="form-control-plaintext">${paciente.cpf}</p>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>Contato</strong></label>
          <p class="form-control-plaintext">${paciente.telefone}</p>
        </div>
        <div class="col-12">
          <label class="form-label"><strong>Endereço</strong></label>
          <p class="form-control-plaintext">${paciente.endereco}</p>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>Última Consulta</strong></label>
          <p class="form-control-plaintext">${paciente.ultimaConsulta || 'Não informada'}</p>
        </div>
        <div class="col-6">
          <label class="form-label"><strong>Tipo do Paciente</strong></label>
          <p class="form-control-plaintext">${paciente.status || 'Não informado'}</p>
        </div>
      </div>
    `;

    // Guarda o ID no modal para edição
    detalhesModal.setAttribute("data-id", paciente.id);

    // Abre o modal
    new bootstrap.Modal(detalhesModal).show();
  }

  // ===== BOTÃO "EDITAR" NO MODAL DE DETALHES =====
  btnEditarDoDetalhe.addEventListener("click", () => {
    const id = parseInt(detalhesModal.getAttribute("data-id"));
    const paciente = todosPacientes.find(p => p.id === id);
    if (!paciente) return;

    // Preenche o formulário de edição
    pacienteId.value = paciente.id;
    nomeInput.value = paciente.nome;
    idadeInput.value = paciente.idade;
    sexoInput.value = paciente.sexo;
    cpfInput.value = paciente.cpf;
    telefoneInput.value = paciente.telefone;
    enderecoInput.value = paciente.endereco;
    ultimaConsultaInput.value = paciente.ultimaConsulta ? paciente.ultimaConsulta.split('/').reverse().join('-') : "";
    statusInput.value = paciente.status;

    modalTitle.textContent = "Editar Paciente";

    // Fecha o modal de detalhes
    bootstrap.Modal.getInstance(detalhesModal)?.hide();

    // Abre o modal de edição
    new bootstrap.Modal(pacienteModal).show();
  });

  // ===== ABRIR PRONTUÁRIO FARMACÊUTICO =====
  function abrirProntuario(id) {
    const paciente = todosPacientes.find(p => p.id === id);
    if (!paciente) return;

    // Fecha outros modais se estiverem abertos
    const modalDetalhes = bootstrap.Modal.getInstance(detalhesModal);
    if (modalDetalhes) modalDetalhes.dispose();

    const modalProntuario = bootstrap.Modal.getInstance(prontuarioModal);
    if (modalProntuario) modalProntuario.dispose();

    const prontuario = paciente.prontuario || [];

    if (prontuario.length === 0) {
      prontuarioCorpo.innerHTML = `
        <div class="text-center py-4">
          <i class="fa fa-file-medical text-muted" style="font-size: 48px;"></i>
          <p class="text-muted mt-3">Nenhum atendimento registrado para este paciente.</p>
        </div>
      `;
    } else {
      const atendimentosHTML = prontuario.map(atendimento => `
        <div class="card mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <strong>${atendimento.data}</strong>
              <span class="badge bg-primary">${atendimento.adesao}</span>
            </div>
            <p><strong>Medicamentos:</strong> ${atendimento.medicamentos}</p>
            <p><strong>Queixa:</strong> ${atendimento.queixa}</p>
            <p><strong>PRMs:</strong> ${atendimento.prms}</p>
            <p><strong>Intervenção:</strong> ${atendimento.intervencao}</p>
            <p><strong>Evolução:</strong> ${atendimento.evolucao}</p>
            <small class="text-muted">${atendimento.farmaceutico}</small>
          </div>
        </div>
      `).join('');

      prontuarioCorpo.innerHTML = `
        <h5><i class="fa fa-file-medical"></i> Prontuário Farmacêutico</h5>
        <p><strong>Paciente:</strong> ${paciente.nome}</p>
        <hr>
        ${atendimentosHTML}
      `;
    }

    new bootstrap.Modal(prontuarioModal).show();
  }

  // ===== BUSCA E FILTRO =====
  function aplicarFiltros() {
    listaPacientes.innerHTML = "";

    const termo = (inputBusca?.value || "").toLowerCase().trim();
    const filtro = selectFiltro?.value || "";

    const filtrados = todosPacientes.filter(p => {
      const matchBusca =
        p.nome.toLowerCase().includes(termo) ||
        p.cpf.includes(termo) ||
        p.telefone.includes(termo);

      const statusNormalizado = (p.status || "")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase();

      const matchFiltro =
        filtro === "" ||
        (filtro === "cronico" && statusNormalizado.includes("cronico")) ||
        (filtro === "agudo" && statusNormalizado.includes("agudo"));

      return matchBusca && matchFiltro;
    });

    filtrados.forEach(criarCard);
  }

  // ===== SALVAR NOVO OU EDITAR =====
  formPaciente.addEventListener("submit", function (e) {
    e.preventDefault();

    const id = pacienteId.value ? parseInt(pacienteId.value) : Date.now();
    const dados = {
      id,
      nome: nomeInput.value.trim(),
      idade: idadeInput.value,
      sexo: sexoInput.value,
      cpf: cpfInput.value,
      telefone: telefoneInput.value,
      endereco: enderecoInput.value.trim(),
      ultimaConsulta: ultimaConsultaInput.value
        ? new Date(ultimaConsultaInput.value).toLocaleDateString("pt-BR")
        : "",
      status: statusInput.value
    };

    if (!dados.nome || !dados.idade || !dados.sexo || !dados.cpf || !dados.telefone || !dados.endereco) {
      alert("Preencha todos os campos obrigatórios.");
      return;
    }

    const index = todosPacientes.findIndex(p => p.id === id);
    if (index !== -1) {
      // Mantém o prontuário existente
      dados.prontuario = todosPacientes[index].prontuario;
      todosPacientes[index] = dados;
    } else {
      dados.prontuario = [];
      todosPacientes.push(dados);
    }

    aplicarFiltros();
    formPaciente.reset();
    pacienteId.value = "";
    modalTitle.textContent = "Cadastrar Novo Paciente";
    bootstrap.Modal.getInstance(pacienteModal)?.hide();
  });

  // ===== BOTÃO "NOVO PACIENTE" =====
  document.querySelector("[data-bs-target='#pacienteModal']").addEventListener("click", () => {
    formPaciente.reset();
    pacienteId.value = "";
    modalTitle.textContent = "Cadastrar Novo Paciente";
  });

  // ===== EVENTOS DE BUSCA E FILTRO =====
  inputBusca?.addEventListener("input", aplicarFiltros);
  selectFiltro?.addEventListener("change", aplicarFiltros);

  // ===== INICIALIZA A LISTA =====
  aplicarFiltros();
});