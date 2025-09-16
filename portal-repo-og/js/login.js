// Credenciais de login fixas para teste
const CREDENCIAIS = {
    crf: "123456",
    senha: "ana123"
};

// Função que realiza a validação do login
function fazerLogin() {
    const inputCRF = document.getElementById("CRF").value.trim();
    const inputSenha = document.getElementById("senha").value.trim();
    let mensagemEl = document.getElementById("mensagem");

    // Se não existir um elemento de mensagem, cria um
    if (!mensagemEl) {
        mensagemEl = document.createElement("p");
        mensagemEl.id = "mensagem";
        mensagemEl.style.marginTop = "10px";
        mensagemEl.style.fontSize = "0.9em";
        mensagemEl.style.textAlign = "center";
        const button = document.querySelector(".btn-login");
        button.parentNode.insertBefore(mensagemEl, button.nextSibling);
    }

    // Limpa mensagem anterior
    mensagemEl.textContent = "";

    // Valida as credenciais
    if (inputCRF === CREDENCIAIS.crf && inputSenha === CREDENCIAIS.senha) {
        mensagemEl.textContent = "Login bem-sucedido! Redirecionando...";
        mensagemEl.style.color = "green";
        setTimeout(() => {
            window.location.href = "/index.html";
        }, 1000);
    } else {
        mensagemEl.textContent = "CRF ou senha incorretos.";
        mensagemEl.style.color = "red";
    }
}

// Adiciona o evento de clique ao botão "Entrar" quando a página carregar
document.addEventListener("DOMContentLoaded", function () {
    const botaoEntrar = document.querySelector(".btn-login");
    if (botaoEntrar) {
        botaoEntrar.addEventListener("click", function (event) {
            event.preventDefault(); // Evita comportamento padrão (útil se o botão estiver em um formulário no futuro)
            fazerLogin();
        });
    } else {
        console.warn("Botão com classe 'btn-login' não encontrado.");
    }
});