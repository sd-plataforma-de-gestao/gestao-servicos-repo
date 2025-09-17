document.addEventListener("DOMContentLoaded", function () {
    const formPaciente = document.querySelector("#pacienteModal form");
    const pacienteModalElement = document.getElementById("pacienteModal");
    const listaPacientes = document.getElementById("lista-pacientes");

    if (!formPaciente || !listaPacientes) return;

    // Carrega header e sidebar
    fetch('/templates/header.php')
        .then(r => r.text())
        .then(html => {
            const container = document.getElementById('header-container');
            if (container) container.innerHTML = html;
        })
        .catch(() => {});

    fetch('/templates/sidebar.php')
        .then(r => r.text())
        .then(html => {
            const container = document.getElementById('sidebar-container');
            if (container) container.innerHTML = html;
        })
        .catch(() => {});

    // Função para recarregar APENAS a lista
    function recarregarLista() {
        fetch('paciente.php?action=load_list')
            .then(r => r.text())
            .then(html => {
                listaPacientes.innerHTML = html;
            })
            .catch(err => console.error("Erro ao recarregar lista:", err));
    }

    // Evento de submit
    formPaciente.addEventListener("submit", function (e) {
        e.preventDefault();

        const btn = formPaciente.querySelector('[type="submit"]');
        if (btn.disabled) return;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';

        const formData = new FormData(formPaciente);

        fetch(window.location.pathname, {
            method: "POST",
            body: formData,
            headers: { "X-Requested-With": "XMLHttpRequest" }
        })
        .then(response => response.text())
        .then(result => {
            if (result.trim() === "success") {
                // Fecha modal
                const modal = bootstrap.Modal.getInstance(pacienteModalElement);
                if (modal) modal.hide();

                // Limpa formulário
                formPaciente.reset();

                // Recarrega lista
                recarregarLista();

                // 👉 NÃO REDIRECIONA AQUI — O PHP JÁ FEZ ISSO 👈
                alert("✅ Paciente cadastrado com sucesso!");
            } else {
                alert("❌ Erro ao cadastrar. Tente novamente.");
            }
        })
        .catch(() => alert("⚠️ Erro de conexão."))
        .finally(() => {
            setTimeout(() => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = "Salvar Paciente";
                }
            }, 500);
        });
    });
});