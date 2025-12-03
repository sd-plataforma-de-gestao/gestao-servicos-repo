document.addEventListener("DOMContentLoaded", function () {
    const formFarmaceutico = document.querySelector("#medicamentoModal form"); 
    const farmaceuticoModalElement = document.getElementById("medicamentoModal");
    const listaPacientes = document.getElementById("lista-pacientes");

    if (!formFarmaceutico || !listaPacientes) return;

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
});