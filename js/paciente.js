document.addEventListener("DOMContentLoaded", function () {
    const formPaciente = document.getElementById("formPaciente");
    const pacienteModalElement = document.getElementById("pacienteModal");
    const listaPacientes = document.getElementById("lista-pacientes");

    if (!formPaciente || !listaPacientes) {
        console.error("❌ Elementos não encontrados: formPaciente ou listaPacientes");
        return;
    }

    // Função para recarregar APENAS a lista
    function recarregarLista() {
        fetch('paciente.php?action=load_list')
            .then(r => r.text())
            .then(html => {
                listaPacientes.innerHTML = html;
            })
            .catch(err => {
                console.error("Erro ao recarregar lista:", err);
                listaPacientes.innerHTML = '<p class="text-danger">Erro ao carregar a lista.</p>';
            });
    }

    // Evento de submit
    formPaciente.addEventListener("submit", function (e) {
        e.preventDefault();

        // Pega o primeiro botão de submit
        const btn = formPaciente.querySelector('[type="submit"]');
        if (!btn) {
            console.error("❌ Botão de cadastro não encontrado!");
            return;
        }

        if (btn.disabled) return;

        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';

        const formData = new FormData(formPaciente);

        fetch('paciente.php', {
            method: "POST",
            body: formData,
            headers: { "X-Requested-With": "XMLHttpRequest" }
        })
        .then(response => response.text())
        .then(result => {
            if (result.trim() === "success") {
                const modal = bootstrap.Modal.getInstance(pacienteModalElement);
                if (modal) modal.hide();
                formPaciente.reset();
                recarregarLista();
                alert("✅ Paciente cadastrado com sucesso!");
            } else {
                alert("❌ " + result.replace("error: ", ""));
            }
        })
        .catch(() => {
            alert("⚠️ Erro de conexão.");
        })
        .finally(() => {
            setTimeout(() => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            }, 500);
        });
    });
});