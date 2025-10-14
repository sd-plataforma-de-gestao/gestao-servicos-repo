// Função para formatar CRF: 2 letras maiúsculas + até 6 dígitos
function formatarCRF(valor) {
    // Remove tudo que não for letra ou número
    valor = valor.replace(/[^A-Za-z0-9]/g, '');
    // Limita a 8 caracteres (2 letras + 6 dígitos)
    if (valor.length > 8) valor = valor.substring(0, 8);
    // Converte para maiúsculo
    return valor.toUpperCase();
}

// Aplica máscara em tempo real
document.addEventListener('input', function(e) {
    if (e.target.matches('#farmaceuticoModal input[name="crf"]')) {
        const valorAtual = e.target.value;
        const valorFormatado = formatarCRF(valorAtual);
        if (valorAtual !== valorFormatado) {
            const pos = e.target.selectionStart;
            e.target.value = valorFormatado;
            const novaPos = pos + (valorFormatado.length - valorAtual.length);
            e.target.setSelectionRange(novaPos, novaPos);
        }
    }
});

// Impede digitar além do limite (8 caracteres)
document.addEventListener('keydown', function(e) {
    if (e.target.matches('#farmaceuticoModal input[name="crf"]')) {
        if (e.target.value.length >= 8 && 
            !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(e.key)) {
            e.preventDefault();
        }
    }
});

// Garante maxlength
document.addEventListener('focusin', function(e) {
    if (e.target.matches('#farmaceuticoModal input[name="crf"]')) {
        e.target.setAttribute('maxlength', '8');
    }
});

// --- RESTANTE DO CÓDIGO (submit, recarregar lista, etc) ---

document.addEventListener("DOMContentLoaded", function () {
    const formFarmaceutico = document.getElementById("formFarmaceutico");
    const farmaceuticoModalElement = document.getElementById("farmaceuticoModal");
    const listaFarmaceuticos = document.getElementById("lista-farmaceuticos");

    if (!formFarmaceutico || !listaFarmaceuticos) {
        console.error("❌ Elementos não encontrados");
        return;
    }

    function recarregarLista() {
        fetch('farmaceutico.php?action=load_list')
            .then(r => r.text())
            .then(html => {
                listaFarmaceuticos.innerHTML = html;
            })
            .catch(err => {
                console.error("Erro ao recarregar lista:", err);
                listaFarmaceuticos.innerHTML = '<p class="text-danger">Erro ao carregar a lista.</p>';
            });
    }

    formFarmaceutico.addEventListener("submit", function (e) {
        e.preventDefault();

        const btn = formFarmaceutico.querySelector('[type="submit"]');
        if (!btn) return;

        if (btn.disabled) return;

        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';

        const formData = new FormData(formFarmaceutico);

        fetch('farmaceutico.php', {
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
                alert("✅ Farmacêutico cadastrado com sucesso!");
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

