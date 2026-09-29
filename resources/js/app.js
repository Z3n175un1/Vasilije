import './bootstrap';

document.addEventListener('DOMContentLoaded', function () {
    initMenuDrawer();
    initLogout();
    initCategoryToggles();
});


/* =========================================================
   MENU PRINCIPAL
   ========================================================= */

function initMenuDrawer() {

    const menuToggle = document.getElementById('menuToggle');
    const menuDrawer = document.getElementById('menuDrawer');
    const menuBackdrop = document.getElementById('menuBackdrop');
    const hamburgerIcon = document.getElementById('hamburgerIcon');

    if (!menuToggle || !menuDrawer) {
        console.warn('⚠️ No se encontraron los elementos del menú');
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

        menuToggle.setAttribute('aria-expanded', 'true');

        document.body.classList.add('menu-open');
    }


    function closeMenu() {

        menuDrawer.classList.remove('open');

        if (menuBackdrop) {
            menuBackdrop.classList.remove('open');
        }

        if (hamburgerIcon) {
            hamburgerIcon.classList.remove('open');
        }

        menuToggle.setAttribute('aria-expanded', 'false');

        document.body.classList.remove('menu-open');
    }


    function toggleMenu(event) {

        event.preventDefault();
        event.stopPropagation();

        if (menuDrawer.classList.contains('open')) {
            closeMenu();
        } else {
            openMenu();
        }
    }


    /* BOTÓN MENÚ */

    menuToggle.addEventListener('click', toggleMenu);


    /* FONDO */

    if (menuBackdrop) {

        menuBackdrop.addEventListener('click', function (event) {

            event.preventDefault();

            closeMenu();

        });

    }


    /* ENLACES */

    document.querySelectorAll('.drawer-nav-links a').forEach(function (link) {

        link.addEventListener('click', function () {
            closeMenu();
        });

    });


    /* ESC */

    document.addEventListener('keydown', function (event) {

        if (
            event.key === 'Escape' &&
            menuDrawer.classList.contains('open')
        ) {
            closeMenu();
        }

    });

}


/* =========================================================
   CATEGORÍAS
   ========================================================= */

function initCategoryToggles() {

    document.querySelectorAll('.category-toggle').forEach(function (button) {

        button.addEventListener('click', function (event) {

            event.preventDefault();
            event.stopPropagation();

            const category = button.closest('.nav-category');

            if (!category) return;

            const items = category.querySelector('.category-items');
            const icon = button.querySelector('.fa-chevron-down');

            if (!items) return;

            const isOpen = items.classList.contains('open');


            /* CERRAR */

            if (isOpen) {

                items.classList.remove('open');

                items.style.maxHeight = '0px';

                if (icon) {
                    icon.style.transform = 'rotate(0deg)';
                }

                button.setAttribute('aria-expanded', 'false');

            }


            /* ABRIR */

            else {

                items.classList.add('open');

                items.style.maxHeight = items.scrollHeight + 'px';

                if (icon) {
                    icon.style.transform = 'rotate(180deg)';
                }

                button.setAttribute('aria-expanded', 'true');

            }

        });

    });

}


/* =========================================================
   LOGOUT
   ========================================================= */

function initLogout() {

    const logoutBtn = document.getElementById('logoutBtn');
    const logoutForm = document.getElementById('logoutForm');

    if (!logoutBtn || !logoutForm) return;


    logoutBtn.addEventListener('click', function (event) {

        event.preventDefault();

        Swal.fire({

            title: '¿Cerrar sesión?',

            text: 'Se cerrará su sesión actual',

            icon: 'warning',

            showCancelButton: true,

            confirmButtonColor: '#3085d6',

            cancelButtonColor: '#d33',

            confirmButtonText: 'Sí, salir',

            cancelButtonText: 'Cancelar'

        }).then(function (result) {

            if (result.isConfirmed) {
                logoutForm.submit();
            }

        });

    });

}


/* =========================================================
   NOTIFICACIONES
   ========================================================= */

window.showNotification = function (
    message,
    type = 'success',
    duration = 2500
) {

    Swal.fire({

        text: message,

        icon: type,

        toast: true,

        position: 'top-end',

        showConfirmButton: false,

        timer: duration,

        timerProgressBar: true,

        background: '#fff',

        color: '#000',

        customClass: {
            popup: 'fw-bold'
        }

    });

};
