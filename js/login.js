// Dados de login fixos
const CREDENCIAIS = {
  usuario: "12345",
  senha: "ana123"
};

// Função que será chamada no envio do formulário
function fazerLogin(event) {
  // Evita o recarregamento da página
  event.preventDefault();

  // Captura os valores dos campos
  const inputUsuario = document.getElementById("usuario").value.trim();
  const inputSenha = document.getElementById("senha").value.trim();
  const mensagemEl = document.getElementById("mensagem");

  // Limpa mensagem anterior
  if (mensagemEl) mensagemEl.textContent = "";

  // Verifica as credenciais
  if (inputUsuario === CREDENCIAIS.usuario && inputSenha === CREDENCIAIS.senha) {
    if (mensagemEl) {
      mensagemEl.textContent = "Login bem-sucedido! Redirecionando...";
      mensagemEl.style.color = "green";
    }

    // Redireciona para index.html após 1 segundo
    setTimeout(() => {
      window.location.href = "index.html";
    }, 1000);
  } else {
    if (mensagemEl) {
      mensagemEl.textContent = "Usuário ou senha incorretos.";
      mensagemEl.style.color = "red";
    }
  }
}

// Adiciona o evento quando a página carregar
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("loginForm");
  if (form) {
    form.addEventListener("submit", fazerLogin);
  } else {
    console.warn("Formulário com ID 'loginForm' não encontrado.");
  }
});