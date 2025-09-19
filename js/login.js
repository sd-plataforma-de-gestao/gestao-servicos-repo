document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("loginForm");
    if (!form) {
        console.error("Formulário #loginForm não encontrado.");
        return;
    }

    // Cria o elemento de mensagem se não existir
    let mensagemEl = document.getElementById("mensagem");
    if (!mensagemEl) {
        mensagemEl = document.createElement("p");
        mensagemEl.id = "mensagem";
        mensagemEl.style.marginTop = "15px";
        mensagemEl.style.fontSize = "0.9em";
        mensagemEl.style.textAlign = "center";
        form.appendChild(mensagemEl);
    }

    form.addEventListener("submit", async function (event) {
        event.preventDefault(); // Impede recarregar a página

        const formData = new FormData(form);
        const crf = formData.get("crf")?.trim();
        const senha = formData.get("senha")?.trim();

        if (!crf || !senha) {
            mensagemEl.textContent = "Preencha todos os campos.";
            mensagemEl.style.color = "red";
            return;
        }

        mensagemEl.textContent = "Verificando...";
        mensagemEl.style.color = "#007bff";

        try {
            const response = await fetch("/login.php", {
                method: "POST",
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                mensagemEl.textContent = "Login bem-sucedido! Redirecionando...";
                mensagemEl.style.color = "green";
                setTimeout(() => {
                    window.location.href = "/index.php"; // Alterado para .php
                }, 1200);
            } else {
                mensagemEl.textContent = result.message || "Erro desconhecido.";
                mensagemEl.style.color = "red";
            }
        } catch (error) {
            mensagemEl.textContent = "Erro de conexão com o servidor.";
            mensagemEl.style.color = "red";
            console.error("Erro:", error);
        }
    });
});