import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    initMenuDrawer();
    initLogout();
    initCategoryToggles();
});


/* =========================================================
   MENU LATERAL
   ========================================================= */

function initMenuDrawer() {
    const menuToggle = document.getElementById('menuToggle');
    const menuDrawer = document.getElementById('menuDrawer');
    const menuBackdrop = document.getElementById('menuBackdrop');
    const hamburgerIcon = document.getElementById('hamburgerIcon');

    if (!menuToggle || !menuDrawer) {
        console.warn('Elementos del menú no encontrados.');
        return;
    }

    function openMenu() {
        menuDrawer.classList.add('open');

        if (menuBackdrop) {
            menuBackdrop.classList.add('open');
        }

        if (hamburgerIcon) {
            hamburgerIcon.classList.add('open');
        }

        document.body.classList.add('menu-open');

        menuToggle.setAttribute('aria-expanded', 'true');
    }

    function closeMenu() {
        menuDrawer.classList.remove('open');

        if (menuBackdrop) {
            menuBackdrop.classList.remove('open');
        }

        if (hamburgerIcon) {
            hamburgerIcon.classList.remove('open');
        }

        document.body.classList.remove('menu-open');

        menuToggle.setAttribute('aria-expanded', 'false');
    }

    function toggleMenu() {
        const isOpen = menuDrawer.classList.contains('open');

        if (isOpen) {
            closeMenu();
        } else {
            openMenu();
        }
    }


    /* -----------------------------------------------------
       BOTÓN HAMBURGUESA
       ----------------------------------------------------- */

    menuToggle.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        toggleMenu();
    });


    /* -----------------------------------------------------
       BACKDROP
       ----------------------------------------------------- */

    if (menuBackdrop) {
        menuBackdrop.addEventListener('click', () => {
            closeMenu();
        });
    }


    /* -----------------------------------------------------
       ENLACES DEL MENÚ
       ----------------------------------------------------- */

    document.querySelectorAll('.drawer-nav-links a').forEach((link) => {

        link.addEventListener('click', () => {
            closeMenu();
        });

    });


    /* -----------------------------------------------------
       ESC PARA CERRAR
       ----------------------------------------------------- */

    document.addEventListener('keydown', (event) => {

        if (event.key === 'Escape') {
            closeMenu();
        }

    });


    /* -----------------------------------------------------
       CLICK FUERA DEL DRAWER
       ----------------------------------------------------- */

    document.addEventListener('click', (event) => {

        if (!menuDrawer.classList.contains('open')) {
            return;
        }

        const clickedInsideDrawer =
            menuDrawer.contains(event.target);

        const clickedToggle =
            menuToggle.contains(event.target);

        if (!clickedInsideDrawer && !clickedToggle) {
            closeMenu();
        }

    });
}


/* =========================================================
   CATEGORÍAS DEL MENU
   ========================================================= */

function initCategoryToggles() {

    document.querySelectorAll('.category-toggle').forEach((button) => {

        button.addEventListener('click', (event) => {

            event.preventDefault();
            event.stopPropagation();

            toggleCategory(button);

        });

    });

    restoreCategoryStates();
}


/* =========================================================
   ABRIR / CERRAR CATEGORÍA
   ========================================================= */

function toggleCategory(button) {

    const items = getCategoryItems(button);

    if (!items) {
        console.warn(
            'No se encontró .category-items para:',
            button
        );

        return;
    }

    const icon = button.querySelector('.fa-chevron-down');

    const isOpen =
        items.classList.contains('open');


    if (isOpen) {

        closeCategory(
            button,
            items,
            icon
        );

    } else {

        openCategory(
            button,
            items,
            icon
        );

    }
}


/* =========================================================
   ABRIR CATEGORÍA
   ========================================================= */

function openCategory(button, items, icon) {

    items.classList.add('open');

    button.classList.add('open');

    button.setAttribute(
        'aria-expanded',
        'true'
    );

    if (icon) {
        icon.style.transform = 'rotate(180deg)';
    }

    const categoryName =
        getCategoryName(button);

    if (categoryName) {
        saveCategoryState(
            categoryName,
            true
        );
    }
}


/* =========================================================
   CERRAR CATEGORÍA
   ========================================================= */

function closeCategory(button, items, icon) {

    items.classList.remove('open');

    button.classList.remove('open');

    button.setAttribute(
        'aria-expanded',
        'false'
    );

    if (icon) {
        icon.style.transform = 'rotate(0deg)';
    }

    const categoryName =
        getCategoryName(button);

    if (categoryName) {
        saveCategoryState(
            categoryName,
            false
        );
    }
}


/* =========================================================
   BUSCAR ITEMS DE CATEGORÍA
   ========================================================= */

function getCategoryItems(button) {

    const nextElement =
        button.nextElementSibling;

    if (
        nextElement &&
        nextElement.classList.contains('category-items')
    ) {
        return nextElement;
    }


    const parent =
        button.closest('.nav-category');

    if (parent) {

        const items =
            parent.querySelector('.category-items');

        if (items) {
            return items;
        }

    }

    return null;
}


/* =========================================================
   NOMBRE DE CATEGORÍA
   ========================================================= */

function getCategoryName(button) {

    const span =
        button.querySelector('span');

    if (!span) {
        return null;
    }

    return span.textContent.trim();
}


/* =========================================================
   GUARDAR ESTADO
   ========================================================= */

function saveCategoryState(
    categoryName,
    isOpen
) {

    try {

        const saved =
            localStorage.getItem(
                'sidebarCategories'
            );

        const states =
            saved
                ? JSON.parse(saved)
                : {};

        states[categoryName] =
            isOpen;

        localStorage.setItem(
            'sidebarCategories',
            JSON.stringify(states)
        );

    } catch (error) {

        console.warn(
            'No se pudo guardar el estado del menú:',
            error
        );

    }
}


/* =========================================================
   RESTAURAR ESTADOS
   ========================================================= */

function restoreCategoryStates() {

    let states = {};

    try {

        const saved =
            localStorage.getItem(
                'sidebarCategories'
            );

        if (saved) {
            states = JSON.parse(saved);
        }

    } catch (error) {

        console.warn(
            'No se pudieron restaurar los estados:',
            error
        );

        states = {};

    }


    document
        .querySelectorAll('.category-toggle')
        .forEach((button) => {

            const categoryName =
                getCategoryName(button);

            if (!categoryName) {
                return;
            }

            const items =
                getCategoryItems(button);

            if (!items) {
                return;
            }

            const icon =
                button.querySelector(
                    '.fa-chevron-down'
                );


            if (states[categoryName] === true) {

                items.classList.add('open');

                button.classList.add('open');

                button.setAttribute(
                    'aria-expanded',
                    'true'
                );

                if (icon) {
                    icon.style.transform =
                        'rotate(180deg)';
                }

            } else {

                items.classList.remove('open');

                button.classList.remove('open');

                button.setAttribute(
                    'aria-expanded',
                    'false'
                );

                if (icon) {
                    icon.style.transform =
                        'rotate(0deg)';
                }

            }

        });
}


/* =========================================================
   LOGOUT
   ========================================================= */

function initLogout() {

    const logoutBtn =
        document.getElementById('logoutBtn');

    const logoutForm =
        document.getElementById('logoutForm');

    if (!logoutBtn || !logoutForm) {
        return;
    }


    logoutBtn.addEventListener(
        'click',
        (event) => {

            event.preventDefault();

            Swal.fire({

                title: '¿Cerrar sesión?',

                text: 'Se cerrará su sesión actual',

                icon: 'warning',

                showCancelButton: true,

                confirmButtonColor: '#2f2c79',

                cancelButtonColor: '#dc3545',

                confirmButtonText: 'Sí, salir',

                cancelButtonText: 'Cancelar',

                reverseButtons: true

            }).then((result) => {

                if (result.isConfirmed) {

                    logoutForm.submit();

                }

            });

        }
    );
}


/* =========================================================
   NOTIFICACIONES
   ========================================================= */

window.showNotification = function (
    message,
    type = 'success',
    duration = 2500
) {

    const iconMap = {

        success: 'success',

        error: 'error',

        warning: 'warning',

        info: 'info'

    };


    Swal.fire({

        text: message,

        icon:
            iconMap[type] || 'info',

        toast: true,

        position: 'top-end',

        showConfirmButton: false,

        timer: duration,

        timerProgressBar: true,

        background: '#ffffff',

        color: '#000000',

        customClass: {
            popup: 'fw-bold'
        }

    });

};
