document.addEventListener('DOMContentLoaded', function() {
    initMenuDrawer();
    initLogout();
});

function initMenuDrawer() {
    const menuToggle = document.getElementById('menuToggle');
    const menuDrawer = document.getElementById('menuDrawer');
    const menuBackdrop = document.getElementById('menuBackdrop');
    const hamburgerIcon = document.getElementById('hamburgerIcon');

    if (!menuToggle || !menuDrawer) return;

    function toggleMenu() {
        menuDrawer.classList.toggle('open');
        if (menuBackdrop) menuBackdrop.classList.toggle('open');
        if (hamburgerIcon) hamburgerIcon.classList.toggle('open');
    }

    menuToggle.addEventListener('click', toggleMenu);

    if (menuBackdrop) {
        menuBackdrop.addEventListener('click', toggleMenu);
    }

    document.querySelectorAll('.drawer-nav-links a').forEach(link => {
        link.addEventListener('click', toggleMenu);
    });
}

function initLogout() {
    const logoutBtn = document.getElementById('logoutBtn');
    const logoutForm = document.getElementById('logoutForm');

    if (!logoutBtn || !logoutForm) return;

    logoutBtn.addEventListener('click', function() {
        Swal.fire({
            title: '¿Cerrar sesión?',
            text: 'Se cerrará su sesión actual',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, salir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                logoutForm.submit();
            }
        });
    });
}

function showNotification(message, type = 'info', duration = 3000) {
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };

    const container = document.getElementById('notificationContainer');
    if (!container) return;

    const notif = document.createElement('div');
    notif.style.cssText = `
        position: fixed; top: 20px; right: 20px; padding: 15px 20px;
        background: ${colors[type] || colors.info}; color: ${type === 'warning' ? '#333' : 'white'};
        z-index: 2000; display: flex; justify-content: space-between;
        align-items: center; gap: 15px; min-width: 300px;
        animation: slideIn 0.3s ease; box-shadow: 0 2px 10px rgba(0,0,0,0.2); border-radius: 8px;
        font-weight: 700; text-transform: uppercase; font-size: 0.9rem;
    `;
    notif.innerHTML = `<span>${message}</span><button onclick="this.parentElement.remove()" style="background:none;border:none;color:inherit;font-size:20px;cursor:pointer;">✕</button>`;

    document.body.appendChild(notif);
    setTimeout(() => { if (notif.parentElement) notif.remove(); }, duration);
}

const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
    @keyframes slideUpDetails {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .detail-expanded-area { animation: slideUpDetails 0.4s ease-out; }
    .swal-bento-yellow {
        background: #ffcc00 !important;
        border: 4px solid #000 !important;
        box-shadow: 12px 12px 0px #000 !important;
        border-radius: 0 !important;
        color: #000 !important;
    }
`;
document.head.appendChild(style);