import './bootstrap';

document.addEventListener('DOMContentLoaded', function() {
    initMenuDrawer();
    initLogout();
    initCategoryToggles();
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

function initCategoryToggles() {
    console.log('initCategoryToggles: listener global en document');
    document.removeEventListener('click', handleCategoryClick);
    document.addEventListener('click', handleCategoryClick);
    
    restoreCategoryStates();
}

function handleCategoryClick(e) {
    const button = e.target.closest('.category-toggle');
    if (!button) return;

    e.preventDefault();
    console.log('CLICK EN CATEGORÍA:', button);
    
    const items = button.nextElementSibling;
    console.log('nextElementSibling:', items);
    
    if (!items || !items.classList.contains('category-items')) {
        console.error('category-items NO ENCONTRADO', items);
        const items2 = button.parentElement.querySelector('.category-items');
        console.log('Buscando en parent:', items2);
        if (!items2) return;
        return;
    }

    const icon = button.querySelector('i.fa-chevron-down');
    const isOpen = items.classList.contains('open');
    const categoryName = button.querySelector('span')?.textContent?.trim();

    if (!isOpen) {
        items.style.display = 'block';
        items.classList.add('open');
        if (icon) icon.style.transform = 'rotate(180deg)';
        saveCategoryState(categoryName, true);
    } else {
        items.style.display = 'none';
        items.classList.remove('open');
        if (icon) icon.style.transform = 'rotate(0deg)';
        saveCategoryState(categoryName, false);
    }
}

function saveCategoryState(categoryName, isOpen) {
    try {
        const states = JSON.parse(localStorage.getItem('sidebarCategories') || '{}');
        states[categoryName] = isOpen;
        localStorage.setItem('sidebarCategories', JSON.stringify(states));
    } catch (e) {
        console.error('Error guardando estado:', e);
    }
}

function restoreCategoryStates() {
    try {
        const states = JSON.parse(localStorage.getItem('sidebarCategories') || '{}');
        document.querySelectorAll('.category-toggle').forEach(button => {
            const categoryName = button.querySelector('span')?.textContent?.trim();
            if (categoryName && states[categoryName] === true) {
                const items = button.nextElementSibling;
                if (items && items.classList.contains('category-items')) {
                    items.style.display = 'block';
                    items.classList.add('open');
                    const icon = button.querySelector('i.fa-chevron-down');
                    if (icon) icon.style.transform = 'rotate(180deg)';
                }
            }
        });
    } catch (e) {
        console.error('Error restaurando estados:', e);
    }
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