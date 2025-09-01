const { jsPDF } = window.jspdf;

    document.getElementById('btnPDF').addEventListener('click', () => {
      const btn = document.getElementById('btnPDF');
      const originalText = btn.innerHTML;

      btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Gerando...';
      btn.disabled = true;

      setTimeout(() => {
        const doc = new jsPDF({
          orientation: 'portrait',
          unit: 'pt',
          format: 'a4'
        });

        const elementHTML = document.getElementById('conteudoPDF');

        doc.html(elementHTML, {
          callback: function(doc) {
            doc.save('relatorio_farmaceutico.pdf');
            btn.innerHTML = '<i class="bi bi-download me-2"></i> Gerar Relatório PDF';
            btn.disabled = false;
          },
          x: 40,
          y: 40,
          width: 520,
          windowWidth: elementHTML.scrollWidth
        });
      }, 300);
    });