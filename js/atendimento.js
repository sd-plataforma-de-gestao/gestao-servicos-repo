function initAtendimento() {
  const selecaoPacienteEl = document.getElementById('selecao-paciente');
  const tipoAtendimentoEl = document.getElementById('tipo-atendimento');
  const chatContainer = document.getElementById('chat-container');
  const chatMessages = document.getElementById('chat-messages');
  const userInput = document.getElementById('user-input');
  const sendBtn = document.getElementById('send-btn');
  const buscaPaciente = document.getElementById('busca-paciente');
  const listaPacientes = document.getElementById('lista-pacientes');

  let pacienteSelecionado = null;
  let selectedType = null;
  let chatHistory = [];

  // Carregar pacientes com suporte a busca
  async function carregarPacientes(filtro = '') {
    try {
      const url = `atendimento.php?action=listar_pacientes&search=${encodeURIComponent(filtro)}`;
      const res = await fetch(url);
      const pacientes = await res.json();

      listaPacientes.innerHTML = pacientes.map(p =>
        `<a href="#" class="list-group-item list-group-item-action" data-id="${p.id}" data-nome="${p.nome}">
          <strong>${p.nome}</strong><br>
          <small>CPF: ${p.cpf ? p.cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4') : '—'}</small>
        </a>`
      ).join('') || '<p class="text-muted">Nenhum paciente encontrado.</p>';

    } catch (err) {
      console.error('Erro ao carregar pacientes:', err);
      listaPacientes.innerHTML = '<p class="text-danger">Erro ao carregar pacientes.</p>';
    }
  }

  // Selecionar paciente
  listaPacientes.addEventListener('click', (e) => {
    e.preventDefault();
    const item = e.target.closest('.list-group-item');
    if (!item) return;
    const id = item.getAttribute('data-id');
    const nome = item.getAttribute('data-nome');
    pacienteSelecionado = { id, nome };
    selecaoPacienteEl.classList.add('d-none');
    tipoAtendimentoEl.classList.remove('d-none');
  });

  // Busca em tempo real
  buscaPaciente.addEventListener('input', (e) => {
    carregarPacientes(e.target.value);
  });

  // Carrega inicialmente
  carregarPacientes();

  // === RESTANTE DO SEU CÓDIGO (SEM ALTERAÇÕES) ===

  function addMessage(text, isUser = false) {
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('message');
    messageDiv.classList.add(isUser ? 'user' : 'system');
    messageDiv.textContent = text;
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  function addSuggestion(text) {
    const suggestionDiv = document.createElement('div');
    suggestionDiv.classList.add('suggestion-bubble');
    suggestionDiv.textContent = text;
    chatMessages.appendChild(suggestionDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  function clearChat() {
    chatMessages.innerHTML = '';
    chatHistory = [];
  }

  function finalizarAtendimento() {
    if (!pacienteSelecionado) {
      alert('Nenhum paciente selecionado.');
      return;
    }

    fetch('atendimento.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        paciente_id: pacienteSelecionado.id,
        chatHistory: chatHistory,
        tipo_atendimento: selectedType === 'agudo' ? 'Agudo' : 'Crônico' // <-- NOVO CAMPO
      })
    })
      .then(r => r.text())
      .then(msg => {
        alert(msg);
        window.location.href = 'historico_atendimento.php'; // <-- REDIRECIONAMENTO
      })
      .catch(() => alert('Erro ao salvar o atendimento.'));
  }
  const tipoBtns = document.querySelectorAll('#tipo-atendimento .btn[data-tipo]');
  tipoBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      selectedType = btn.getAttribute('data-tipo');
      tipoBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      tipoAtendimentoEl.classList.add('d-none');
      chatContainer.classList.remove('d-none');
      userInput.disabled = false;
      sendBtn.disabled = false;
      userInput.focus();

      clearChat();
      addMessage(`✅ Atendimento ${selectedType === 'agudo' ? 'agudo' : 'crônico'} iniciado para ${pacienteSelecionado.nome}.`, false);
      addMessage("Como posso ajudar hoje?", false);

      addSuggestion("Pergunte sobre duração dos sintomas.");
      addSuggestion("Verifique uso de outros medicamentos.");
      addSuggestion("Avalie sinais de alerta.");
    });
  });

  function sendMessage() {
    const message = userInput.value.trim();
    if (!message || !selectedType) return;

    addMessage(message, true);
    userInput.value = '';

    if (message.toLowerCase() === "finalizar atendimento") {
      finalizarAtendimento();
      return;
    }

    callGeminiAPI(message);
  }

  sendBtn.addEventListener('click', sendMessage);
  userInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') sendMessage();
  });

  // === API do GEMINI (com URL corrigida) ===
  const GEMINI_API_KEY = 'AIzaSyBRlDmktgFcVV65lXSpat9Y9x9q8wDHcGk';

  async function callGeminiAPI(userMessage) {
    const thinkingElement = addMessage("Processando...", false);

    const systemPrompt = `
Você é um assistente farmacêutico virtual especializado em sintomas respiratórios, seguindo o algoritmo clínico para espirro e congestão nasal.

BASE DO ALGORITMO (seguir esta sequência):
1. TEMPO: Duração dos sintomas (até 10 dias ou mais de 10 dias)
2. CARACTERÍSTICAS: Tipo de secreção, cor, consistência
3. INTENSIDADE: Leve, moderada ou grave
4. FATORES DESENCADEANTES: Alergias, ambiente, situações específicas
5. SINTOMAS ASSOCIADOS: Febre, dor, mal-estar
6. PERFIL: Idade, condições especiais (gestação, comorbidades)
7. HISTÓRICO: Medicamentos em uso, tratamentos anteriores

DIRETRIZES:
- Inicie identificando o paciente (nome/CPF)
- Siga a sequência do algoritmo acima
- Adapte as perguntas conforme as respostas anteriores
- Identifique sinais de alerta que requerem encaminhamento médico
- Para sintomas até 10 dias sem alertas: sugira tratamento não farmacológico
- Para sintomas persistentes (>10 dias) ou com alertas: oriente busca por médico
- Use linguagem clara, profissional e empática
- Evite diagnósticos médicos - oriente sempre busca por médico quando necessário

FORMATO DE RESPOSTA:
[SUA RESPOSTA PRINCIPAL]
[SUGESTÕES]
1. [Próxima pergunta sugerida 1]
2. [Próxima pergunta sugerida 2]
3. [Próxima pergunta sugerida 3]
`;

    const messages = [
      { role: "user", parts: [{ text: systemPrompt }] },
      { role: "model", parts: [{ text: "Entendido. Estou pronto para ajudar no atendimento farmacêutico seguindo as regras." }] },
      ...chatHistory.map(msg => ({
        role: msg.isUser ? "user" : "model",
        parts: [{ text: msg.text }]
      })),
      { role: "user", parts: [{ text: userMessage }] }
    ];

    try {
      // ✅ URL CORRIGIDA: remova os espaços antes dos ":"
      const response = await fetch(`https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=${GEMINI_API_KEY}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          contents: messages
        })
      });

      const data = await response.json();

      if (!response.ok) {
        console.error("Erro da API:", data);
        throw new Error(data.error?.message || 'Erro desconhecido na API');
      }

      if (chatMessages.contains(thinkingElement)) {
        chatMessages.removeChild(thinkingElement);
      }

      let aiResponse = data.candidates[0]?.content?.parts[0]?.text || "Desculpe, não consegui formular uma resposta adequada.";

      let mainResponse = aiResponse;
      let suggestions = [];

      if (aiResponse.includes("[SUGESTÕES]")) {
        const parts = aiResponse.split("[SUGESTÕES]");
        mainResponse = parts[0].trim();
        const suggestionLines = parts[1].split("\n").filter(line => line.trim().startsWith("1.") || line.trim().startsWith("2.") || line.trim().startsWith("3."));
        suggestions = suggestionLines.map(line => line.replace(/^[0-9]+\.\s*/, "").trim());
      }

      addMessage(mainResponse, false);

      if (suggestions.length > 0) {
        suggestions.forEach(s => {
          if (s) addSuggestion(s);
        });
      } else {
        addSuggestion("Pergunte mais detalhes sobre os sintomas.");
        addSuggestion("Verifique histórico de alergias medicamentosas.");
        addSuggestion("Oriente repouso e hidratação se aplicável.");
      }

      chatHistory.push({ text: userMessage, isUser: true });
      chatHistory.push({ text: aiResponse, isUser: false });

    } catch (error) {
      console.error("Erro ao chamar a API do Gemini:", error);
      if (chatMessages.contains(thinkingElement)) {
        chatMessages.removeChild(thinkingElement);
      }
      addMessage("⚠️ Erro ao conectar com a IA. Verifique sua chave de API ou conexão.", false);
    }
  }
}