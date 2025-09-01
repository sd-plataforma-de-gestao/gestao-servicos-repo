// js/main.js
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('formPaciente');

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      // Simula navegação para próxima etapa
      alert('Paciente cadastrado! Redirecionando para perguntas...');
      // window.location.href = 'atendimento-cronico.html'; // ou agudo
    });
  }

  // Máscara de CPF (opcional)
  const cpfInput = form.querySelector('input[placeholder="000.000.000-00"]');
  if (cpfInput) {
    cpfInput.addEventListener('input', function (e) {
      let value = e.target.value.replace(/\D/g, '');
      if (value.length > 11) value = value.slice(0, 11);
      if (value.length > 9) value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
      else if (value.length > 6) value = value.replace(/(\d{3})(\d{3})(\d{3})/, '$1.$2.$3');
      else if (value.length > 3) value = value.replace(/(\d{3})(\d{3})/, '$1.$2');
      else if (value.length > 0) value = value.replace(/(\d{3})/, '$1');
      e.target.value = value;
    });
  }
});