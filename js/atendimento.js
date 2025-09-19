function initAtendimento() {
  const tipoBtns = document.querySelectorAll('#tipo-atendimento .btn[data-tipo]');
  const chatContainer = document.getElementById('chat-container');
  const chatMessages = document.getElementById('chat-messages');
  const userInput = document.getElementById('user-input');
  const sendBtn = document.getElementById('send-btn');

  let selectedType = null;
  let chatHistory = [];

  // Função para adicionar mensagem no chat
  function addMessage(text, isUser = false) {
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('message');
    messageDiv.classList.add(isUser ? 'user' : 'system');
    messageDiv.textContent = text;
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    return messageDiv;
  }

  // Função para adicionar sugestão NÃO CLICÁVEL (apenas visual)
  function addSuggestion(text) {
    const suggestionDiv = document.createElement('div');
    suggestionDiv.classList.add('suggestion-bubble');
    suggestionDiv.textContent = text;
    chatMessages.appendChild(suggestionDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    return suggestionDiv;
  }

  // Limpa o chat
  function clearChat() {
    chatMessages.innerHTML = '';
    chatHistory = [];
  }

  //Finaliza Atendimento
  function finalizarAtendimento() {
    const chatData = JSON.stringify(chatHistory);

    fetch('atendimento.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: chatData
    })
    .then(response => response.text())
    .then(result => {
      console.log('Chat salvo:', result);
      alert(result); // mostra resposta do PHP
    })
    .catch(error => {
      console.error('Erro ao salvar o chat:', error);
      alert('Erro ao salvar o atendimento.');
    });
}


  // Evento nos botões de tipo
  tipoBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      selectedType = btn.getAttribute('data-tipo');
      tipoBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      // Mostra o chat
      chatContainer.classList.remove('d-none');

      // Limpa e inicia chat
      clearChat();
      addMessage(`✅ Atendimento ${selectedType === 'agudo' ? 'agudo' : 'crônico'} iniciado.`, false);
      addMessage("Olá! Sou seu assistente farmacêutico. De inicio, vamos identificar o paciente, Digite o nome o CPF do paciente", false);

      // Primeiras sugestões gerais
      addSuggestion("Pergunte ao paciente sobre a duração dos sintomas.");
      addSuggestion("Verifique se há uso de outros medicamentos.");
      addSuggestion("Avalie sinais de alerta como febre alta ou falta de ar.");

      // Foca no input
      userInput.disabled = false;
      userInput.focus();
    });
  });

  // Enviar mensagem
  function sendMessage() {
    const message = userInput.value.trim();
    if (!message || !selectedType) return;

    addMessage(message, true);
    userInput.value = '';

    if (message.toLowerCase() === "finalizar atendimento") {
      finalizarAtendimento();
      return;
    }

    // Envia para a API do Gemini
    callGeminiAPI(message);
  }

  sendBtn.addEventListener('click', sendMessage);
  userInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') sendMessage();
  });

  // 🔑 SUA CHAVE DA API DO GEMINI
  const GEMINI_API_KEY = 'AIzaSyBRlDmktgFcVV65lXSpat9Y9x9q8wDHcGk';

  // 🚀 Função para chamar a API do Gemini — MODELO CORRETO: gemini-pro
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
      // ✅ MODELO CORRIGIDO: "gemini-pro" (nome oficial na API v1)
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

      // Remove "Processando..."
      if (chatMessages.contains(thinkingElement)) {
        chatMessages.removeChild(thinkingElement);
      }

      let aiResponse = data.candidates[0]?.content?.parts[0]?.text || "Desculpe, não consegui formular uma resposta adequada.";

      // Separa resposta da IA das sugestões
      let mainResponse = aiResponse;
      let suggestions = [];

      if (aiResponse.includes("[SUGESTÕES]")) {
        const parts = aiResponse.split("[SUGESTÕES]");
        mainResponse = parts[0].trim();
        const suggestionLines = parts[1].split("\n").filter(line => line.trim().startsWith("1.") || line.trim().startsWith("2.") || line.trim().startsWith("3."));
        suggestions = suggestionLines.map(line => line.replace(/^[0-9]+\.\s*/, "").trim());
      }

      // Exibe resposta principal
      addMessage(mainResponse, false);

      // Exibe sugestões
      if (suggestions.length > 0) {
        suggestions.forEach(s => {
          if (s) addSuggestion(s);
        });
      } else {
        // Fallback
        addSuggestion("Pergunte mais detalhes sobre os sintomas.");
        addSuggestion("Verifique histórico de alergias medicamentosas.");
        addSuggestion("Oriente repouso e hidratação se aplicável.");
      }

      // Atualiza histórico
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

  // Inicializa estado
  userInput.disabled = true;
}