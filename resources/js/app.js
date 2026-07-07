import './bootstrap';

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

window.showNotification = function(message, type = 'success', duration = 2500) {
    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    const colors = { success: '#28a745', error: '#dc3545', warning: '#ffc107', info: '#17a2b8' };
    Swal.fire({
        text: message,
        icon: type === 'success' ? 'success' : type === 'error' ? 'error' : type === 'warning' ? 'warning' : 'info',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: duration,
        timerProgressBar: true,
        background: '#fff',
        color: '#000',
        customClass: { popup: 'fw-bold' },
    });
};