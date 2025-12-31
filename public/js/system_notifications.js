document.addEventListener('DOMContentLoaded', function () {
    // 1. Injeta HTML do TOAST
    const toastHTML = `
    <div class="position-fixed p-3" style="z-index: 9999; top: 20px; left: 50%; transform: translateX(-50%); min-width: 300px;">
        <div id="systemToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true" data-delay="4000" style="min-width: 250px;">
            <div class="toast-header text-white">
                <i class="fas fa-info-circle mr-2" id="toastIcon"></i>
                <strong class="mr-auto" id="toastTitle">Sistema</strong>
                <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="toast-body font-weight-bold" id="toastMessage">
                Mensagem
            </div>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', toastHTML);

    // 2. Injeta HTML do MODAL (Substituto do Alert Bloqueante)
    const modalHTML = `
    <div class="modal fade" id="systemAlertModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 10000;">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="systemModalTitle">Aviso</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body font-weight-bold text-dark" id="systemModalBody">
                    ...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="systemModalBtn" data-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
});

// Alias global para Toast
window.systemToast = function (message, type = 'info') {
    const toast = document.getElementById('systemToast');
    if (!toast) return;

    const header = toast.querySelector('.toast-header');
    const title = toast.querySelector('#toastTitle');
    const body = toast.querySelector('#toastMessage');
    const icon = toast.querySelector('#toastIcon');

    // Reset classes
    header.className = 'toast-header text-white';
    icon.className = 'mr-2 fas';

    let iconClass = 'fa-info-circle';
    let bgClass = 'bg-info';
    let titleText = 'Informação';

    if (type === 'success') {
        bgClass = 'bg-success';
        iconClass = 'fa-check-circle';
        titleText = 'Sucesso';
    } else if (type === 'danger' || type === 'error') {
        bgClass = 'bg-danger';
        iconClass = 'fa-exclamation-circle';
        titleText = 'Erro';
    } else if (type === 'warning') {
        bgClass = 'bg-warning';
        iconClass = 'fa-exclamation-triangle';
        titleText = 'Atenção';
    }

    header.classList.add(bgClass);
    icon.classList.add(iconClass);
    title.textContent = titleText;
    body.textContent = message;

    if (typeof $ !== 'undefined') {
        $('#systemToast').toast('show');
    }
};

// Alias global para Modal (simula alert)
window.systemAlert = function (message, type = 'info', callback = null) {
    const modalEl = document.getElementById('systemAlertModal');
    if (!modalEl) { alert(message); return; } // Fallback

    const title = document.getElementById('systemModalTitle');
    const body = document.getElementById('systemModalBody');
    const header = modalEl.querySelector('.modal-header');
    const btn = document.getElementById('systemModalBtn');

    body.textContent = message;

    // Reset Header Style
    header.className = 'modal-header text-white';
    if (type === 'error' || type === 'danger') header.classList.add('bg-danger');
    else if (type === 'success') header.classList.add('bg-success');
    else if (type === 'warning') header.classList.add('bg-warning');
    else header.classList.add('bg-primary');

    title.textContent = (type === 'error' || type === 'danger') ? 'Erro' : (type === 'success' ? 'Sucesso' : 'Aviso');

    // Setup Callback
    // Remove eventos anteriores para evitar duplicação em múltiplas chamadas
    const newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);

    newBtn.onclick = function () {
        if (callback) callback();
    };

    if (typeof $ !== 'undefined') {
        $('#systemAlertModal').modal('show');
    }
};
