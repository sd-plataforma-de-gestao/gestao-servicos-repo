function initAtendimento() {
    const tipoBtns = document.querySelectorAll('#tipo-atendimento .btn[data-tipo]');
    const chatContainer = document.getElementById('chat-container');
    const suggestionsContainer = document.getElementById('suggestions');
    const chatMessages = document.getElementById('chat-messages');
    const userInput = document.getElementById('user-input');
    const sendBtn = document.getElementById('send-btn');
    const suggestionList = document.getElementById('suggestion-list');

    let selectedType = null;
    let chatHistory = [];
    
    // Sua API KEY
    const GEMINI_API_KEY = 'AIzaSyBRlDmktgFcVV65lXSpat9Y9x9q8wDHcGk';

    function addMessage(text, isUser = false) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message');
        messageDiv.classList.add(isUser ? 'user' : 'system');
        messageDiv.textContent = text;
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function clearChat() {
        chatMessages.innerHTML = '';
        chatHistory = [];
    }

    function loadSuggestions(type) {
        suggestionList.innerHTML = '';

        const suggestions = {
            agudo: [
                "Pergunte sobre sintomas atuais e duração.",
                "Verifique se há febre, dor ou dificuldade respiratória.",
                "Recomende hidratação e repouso se for gripe leve.",
                "Verifique uso de medicamentos em casa.",
                "Pergunte se já procurou atendimento médico."
            ],
            cronico: [
                "Pergunte sobre adesão à medicação.",
                "Verifique sinais de piora ou efeitos colaterais.",
                "Avalie controle dos níveis de glicose, pressão arterial etc.",
                "Reforce importância da alimentação e exercícios.",
                "Pergunte sobre última consulta médica."
            ]
        };

        const sugestoes = suggestions[type] || [];
        sugestoes.forEach(s => {
            const li = document.createElement('li');
            li.classList.add('list-group-item');
            li.textContent = s;
            li.addEventListener('click', () => {
                userInput.value = s;
                userInput.focus();
            });
            suggestionList.appendChild(li);
        });
    }

    tipoBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            selectedType = btn.getAttribute('data-tipo');
            tipoBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            chatContainer.classList.remove('d-none');
            suggestionsContainer.classList.remove('d-none');

            clearChat();
            addMessage(`✅ Atendimento ${selectedType === 'agudo' ? 'agudo' : 'crônico'} iniciado.`, false);
            addMessage("Olá! Sou seu assistente farmacêutico. Como posso ajudar?", false);
            loadSuggestions(selectedType);
            userInput.disabled = false;
            userInput.focus();
        });
    });

    function sendMessage() {
        const message = userInput.value.trim();
        if (!message || !selectedType) return;

        addMessage(message, true);
        userInput.value = '';

        callGeminiAPI(message);
    }

    sendBtn.addEventListener('click', sendMessage);
    userInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    async function callGeminiAPI(userMessage) {
        const thinkingMsg = addMessage("Processando...", false);
        const thinkingElement = chatMessages.lastElementChild;

        const systemPrompt = `
Você é um assistente farmacêutico virtual, especializado em auxiliar farmacêuticos no atendimento ao paciente.
O tipo de atendimento selecionado é: ${selectedType === 'agudo' ? 'Agudo (sintomas recentes, curto prazo)' : 'Crônico (doenças de longo prazo, acompanhamento contínuo)'}.
Responda de forma clara, objetiva, profissional e empática. Dê sugestões práticas, perguntas relevantes e orientações seguras.
Evite diagnósticos médicos — oriente sempre que o paciente procure um médico quando necessário.
        `;

        let messages = [];

        if (chatHistory.length > 0) {
            messages = chatHistory.map(msg => ({
                role: msg.isUser ? "user" : "model",
                parts: [{ text: msg.text }]
            }));
            messages.push({
                role: "user",
                parts: [{ text: userMessage }]
            });
        } else {
            messages.push({
                role: "user",
                parts: [{ text: systemPrompt + "\n\n" + userMessage }]
            });
        }

        try {
            const response = await fetch(`https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${GEMINI_API_KEY}`, {
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

            const aiResponse = data.candidates[0]?.content?.parts[0]?.text || "Desculpe, não consegui formular uma resposta adequada.";

            chatHistory.push({ text: userMessage, isUser: true });
            chatHistory.push({ text: aiResponse, isUser: false });

            addMessage(aiResponse, false);

        } catch (error) {
            console.error("Erro ao chamar a API do Gemini:", error);

            if (chatMessages.contains(thinkingElement)) {
                chatMessages.removeChild(thinkingElement);
            }

            addMessage("⚠️ Erro ao conectar com a IA. Verifique a chave ou tente novamente.", false);
        }
    }
}