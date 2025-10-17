document.addEventListener('DOMContentLoaded', function() {
    initializeSidebar();
    initializeActionButtons();
    initializeTooltips();
    initializeNavigation();
    loadDashboardData();
});

function initializeSidebar() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }
    
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            sidebarLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });
    });
}

function initializeActionButtons() {
    const actionButtons = document.querySelectorAll('.action-btn');
    
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const title = this.querySelector('.action-title').textContent;
            handleActionClick(title);
        });
    });
}

function handleActionClick(action) {
    switch(action) {
        case 'Novo Atendimento':
            showModal('Novo Atendimento', 'Iniciando novo atendimento farmacêutico...');
            break;
        case 'Buscar Paciente':
            showSearchModal();
            break;
        case 'Agendar Retorno':
            showScheduleModal();
            break;
        case 'Gerar Relatório':
            generateReport();
            break;
        default:
            console.log('Ação não implementada:', action);
    }
}

function showModal(title, content) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">${title}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>${content}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary-custom">Confirmar</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
    
    modal.addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(modal);
    });
}

function showSearchModal() {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buscar Paciente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="searchInput" class="form-label">Nome ou CPF do paciente</label>
                        <input type="text" class="form-control" id="searchInput" placeholder="Digite o nome ou CPF...">
                    </div>
                    <div id="searchResults" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary-custom" onclick="performSearch()">Buscar</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
    
    modal.addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(modal);
    });
}

function showScheduleModal() {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agendar Retorno</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="patientSelect" class="form-label">Paciente</label>
                        <select class="form-control" id="patientSelect">
                            <option value="">Selecione um paciente...</option>
                            <option value="1">Maria Santos</option>
                            <option value="2">João Silva</option>
                            <option value="3">Ana Costa</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="scheduleDate" class="form-label">Data</label>
                        <input type="date" class="form-control" id="scheduleDate">
                    </div>
                    <div class="mb-3">
                        <label for="scheduleTime" class="form-label">Horário</label>
                        <input type="time" class="form-control" id="scheduleTime">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary-custom" onclick="scheduleAppointment()">Agendar</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
    
    modal.addEventListener('hidden.bs.modal', function() {
        document.body.removeChild(modal);
    });
}
function performSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    const query = searchInput.value.trim();
    
    if (query.length < 3) {
        searchResults.innerHTML = '<div class="alert alert-warning">Digite pelo menos 3 caracteres para buscar.</div>';
        return;
    }
    
    const mockResults = [
        { name: 'Maria Santos', cpf: '123.456.789-01', lastVisit: '2024-01-15' },
        { name: 'João Silva', cpf: '987.654.321-09', lastVisit: '2024-01-10' }
    ];
    
    searchResults.innerHTML = `
        <h6>Resultados encontrados:</h6>
        ${mockResults.map(patient => `
            <div class="card mb-2">
                <div class="card-body p-3">
                    <h6 class="card-title mb-1">${patient.name}</h6>
                    <p class="card-text small mb-1">CPF: ${patient.cpf}</p>
                    <p class="card-text small mb-0">Última consulta: ${patient.lastVisit}</p>
                </div>
            </div>
        `).join('')}
    `;
}

function scheduleAppointment() {
    const patient = document.getElementById('patientSelect').value;
    const date = document.getElementById('scheduleDate').value;
    const time = document.getElementById('scheduleTime').value;
    
    if (!patient || !date || !time) {
        alert('Por favor, preencha todos os campos.');
        return;
    }
    
    showNotification('Retorno agendado com sucesso!', 'success');
    const modal = document.querySelector('.modal.show');
    if (modal) {
        const bootstrapModal = bootstrap.Modal.getInstance(modal);
        bootstrapModal.hide();
    }
}

function generateReport() {
    showNotification('Gerando relatório...', 'info');
    
    setTimeout(() => {
        showNotification('Relatório gerado com sucesso!', 'success');
    }, 2000);
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show notification`;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

function initializeNavigation() {
    const dropdownToggles = document.querySelectorAll('[data-bs-toggle="collapse"]');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('data-bs-target'));
            if (target) {
                const isShowing = target.classList.contains('show');
                document.querySelectorAll('.collapse.show').forEach(collapse => {
                    if (collapse !== target) {
                        collapse.classList.remove('show');
                    }
                });
                target.classList.toggle('show', !isShowing);
                this.setAttribute('aria-expanded', !isShowing);
            }
        });
    });
}

function loadDashboardData() {
    updateStats();
    updateActivities();
    
    setInterval(updateStats, 30000);
    setInterval(updateActivities, 60000);
}

function updateStats() {
    const statValues = document.querySelectorAll('.stat-value');
    statValues.forEach((stat, index) => {
        stat.style.opacity = '0.7';
        setTimeout(() => {
            stat.style.opacity = '1';
        }, 300);
    });
}

function updateActivities() {
    const now = new Date().toLocaleTimeString('pt-BR');
    console.log(`Atividades atualizadas às ${now}`);
}

window.addEventListener('resize', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (window.innerWidth > 768 && sidebar) {
        sidebar.classList.remove('show');
        if (overlay) {
            overlay.classList.remove('show');
        }
    }
});

function formatDate(date) {
    return new Date(date).toLocaleDateString('pt-BR');
}

function formatTime(time) {
    return new Date(time).toLocaleTimeString('pt-BR', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function setActiveSidebarLink() {
  const currentPage = window.location.pathname.split("/").pop();

  const sidebarLinks = document.querySelectorAll(".sidebar-link");

  sidebarLinks.forEach(link => {
    const linkPage = link.getAttribute("href").split("/").pop();

    if (linkPage === currentPage) {
      link.classList.add("active");
    } else {
      link.classList.remove("active");
    }
  });

  
}
/**
 * Exibe alertas padronizados usando SweetAlert2 (Swal).
 * @param {string} type - Tipo de alerta ('success', 'error', 'warning', 'info').
 * @param {string} title - Título do alerta.
 * @param {string} text - Mensagem detalhada.
 */
function showCustomAlert(type, title, text) {
    // ⚠️ ATENÇÃO: ESSA É A CHECAGEM CRÍTICA!
    if (typeof Swal === 'undefined') {
        console.error("SweetAlert2 não está carregado. Alerta não pode ser exibido. Usando fallback.");
        // Isso é o ALERTA SEM ESTILO que você viu antes, mas agora é só um fallback de emergência.
        alert(title + "\n\n" + text);
        return;
    }

    let confirmColor = '#1C5B40'; // Cor padrão (verde)
    
    // Define a cor do botão de confirmação com base no tipo
    if (type === 'error' || type === 'warning') {
        confirmColor = '#DC3545'; // Cor de Erro/Alerta (Vermelho)
    }

    Swal.fire({
        icon: type,
        title: title,
        text: text,
        showConfirmButton: true,
        confirmButtonText: 'OK',
        confirmButtonColor: confirmColor,
        // Auto-fecha em 3s se for sucesso, se não for, espera o clique
        timer: type === 'success' ? 3000 : false,
        timerProgressBar: type === 'success' ? true : false
    });
}
