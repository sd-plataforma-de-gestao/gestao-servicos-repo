const CREDENCIAIS = {
    crf: "123456",
    senha: "ana123"
};
function fazerLogin() {
    const inputCRF = document.getElementById("CRF").value.trim();
    const inputSenha = document.getElementById("senha").value.trim();
    let mensagemEl = document.getElementById("mensagem");
    if (!mensagemEl) {
        mensagemEl = document.createElement("p");
        mensagemEl.id = "mensagem";
        mensagemEl.style.marginTop = "10px";
        mensagemEl.style.fontSize = "0.9em";
        mensagemEl.style.textAlign = "center";
        const button = document.querySelector(".btn-login");
        button.parentNode.insertBefore(mensagemEl, button.nextSibling);
    }
    mensagemEl.textContent = "";
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

document.addEventListener("DOMContentLoaded", function () {
    const botaoEntrar = document.querySelector(".btn-login");
    if (botaoEntrar) {
        botaoEntrar.addEventListener("click", function (event) {
            event.preventDefault(); 
            fazerLogin();
        });
    } else {
        console.warn("Botão com classe 'btn-login' não encontrado.");
    }
});