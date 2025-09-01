// Gráfico de barras - Atendimentos da Semana
new Chart(document.getElementById('chartSemana'), {
  type: 'bar',
  data: {
    labels: ['Seg', 'Ter', 'Qua', 'Qui', 'Sex'],
    datasets: [{
      label: 'Atendimentos',
      data: [8, 11, 8, 13, 10],
      backgroundColor: '#0d6e38'
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: { beginAtZero: true }
    }
  }
});

// Gráfico de pizza - Distribuição por Condição
new Chart(document.getElementById('chartCondicoes'), {
  type: 'pie',
  data: {
    labels: ['Hipertensão', 'Diabetes', 'Asma', 'Gripe', 'Dor'],
    datasets: [{
      data: [35, 25, 12, 12, 8],
      backgroundColor: ['#0d6e38', '#a8b38f', '#d4e6c9', '#e7f5e0', '#2e3a2f']
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false }
    }
  }
});

// Gráfico de linha - Tendência Mensal
new Chart(document.getElementById('chartTendencia'), {
  type: 'line',
  data: {
    labels: ['Set', 'Out', 'Nov', 'Dez', 'Jan'],
    datasets: [{
      label: 'Atendimentos',
      data: [250, 300, 350, 400, 450],
      borderColor: '#0d6e38',
      backgroundColor: 'rgba(13,110,56,0.1)',
      fill: true,
      tension: 0.3
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      y: { beginAtZero: true }
    }
  }
});