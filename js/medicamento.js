document.addEventListener("DOMContentLoaded", function () {
    const formFarmaceutico = document.querySelector("#medicamentoModal form"); // 👈 Seleciona o form dentro do modal
    const farmaceuticoModalElement = document.getElementById("medicamentoModal");
    const listaPacientes = document.getElementById("lista-pacientes"); // 👈 Mesmo ID usado no PHP

    if (!formFarmaceutico || !listaPacientes) return;

    // Função para recarregar APENAS a lista
    function recarregarLista() {
        fetch('medicamento.php?action=load_list')
            .then(r => r.text())
            .then(html => {
                listaPacientes.innerHTML = html;
            })
            .catch(err => {
                console.error("Erro ao recarregar lista:", err);
                listaPacientes.innerHTML = '<p class="text-danger">Erro ao carregar a lista. Tente novamente.</p>';
            });
    }

    // Evento de submit
    formFarmaceutico.addEventListener("submit", function (e) {
        e.preventDefault();

        const btn = formFarmaceutico.querySelector('[type="submit"]');
        if (btn.disabled) return;

        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';

        const formData = new FormData(formFarmaceutico);

        fetch('medicamento.php', {
            method: "POST",
            body: formData,
            headers: { "X-Requested-With": "XMLHttpRequest" }
        })
        .then(response => response.text())
        .then(result => {
            if (result.trim() === "success") {
                const modal = bootstrap.Modal.getInstance(farmaceuticoModalElement);
                if (modal) modal.hide();
                formFarmaceutico.reset();
                recarregarLista();
                alert("✅ Medicamento cadastrado com sucesso!");
            } else {
                alert("❌ " + result.replace("error: ", ""));
            }
        })
        .catch(() => {
            alert("⚠️ Erro de conexão. Verifique sua internet.");
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