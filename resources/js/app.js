import './bootstrap';

/* =========================================================
   HELPERS GLOBALES
   ========================================================= */

/**
 * Escapa texto para insertarlo con innerHTML.
 *
 * POR QUE EXISTE
 * --------------
 * Las tablas de este proyecto se pintan con
 * `innerHTML = filas.map(f => `<td>${f.campo}</td>`).join('')`.
 * Los valores vienen de la base de datos y casi todos son texto libre
 * que escribe un usuario: nombre de producto, concepto de gasto, cliente,
 * proveedor. Interpolarlo crudo es XSS almacenado: basta con guardar
 * `<img src=x onerror=...>` como concepto para ejecutarlo en cada lectura.
 *
 * Uso obligatorio en cualquier interpolacion de datos:
 *
 *     `<td>${esc(f.nombre_producto)}</td>`
 *
 * Cuando el valor debe ser un numero, se pasa por `num()` en su lugar.
 */
window.esc = function (valor) {
    if (valor === null || valor === undefined) return '';
    return String(valor)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
};

/**
 * Escapa un valor destinado a un atributo entre comillas simples
 * dentro de un onclick="..." (p. ej. onclick="f('${valor}')").
 */
window.escAttr = function (valor) {
    return window.esc(valor).replace(/\n/g, ' ');
};

/**
 * Formatea un numero como texto monetario en bolivianos.
 * Devuelve cadena vacia si no es numerico, para no imprimir "NaN".
 */
window.bs = function (valor, decimales = 2) {
    const n = parseFloat(valor);
    if (Number.isNaN(n)) return '0.00';
    return n.toLocaleString('es-BO', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales,
    });
};

/** Alias corto usado en las tablas: numero con separador de miles. */
window.num = window.bs;

/** Valor o guion medio si esta vacio. */
window.txt = function (valor) {
    if (valor === null || valor === undefined) return '—';
    const s = String(valor).trim();
    return s === '' ? '—' : window.esc(s);
};

/* =========================================================
   BUSCADOR DESPLEGABLE
   ========================================================= */

/**
 * Normaliza texto para comparar: sin acentos y en minusculas.
 *
 * Sin esto, buscar "lubric" no encuentra "Lubricantes" y el operador
 * concluye que el producto no existe. Es el fallo clasico de un buscador
 * escrito para maquinas y no para personas.
 */
window.normalizar = function (texto) {
    return String(texto ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
};

/**
 * Busca `termino` dentro de `texto` por coincidencia de PALABRAS.
 *
 * Acepta dos formas, que es como escribe la gente:
 *
 *   "ace lub"  -> busca un producto que tenga una palabra que empiece por
 *                 "ace" y otra que empiece por "lub".
 *   "aceite"   -> busca el substring dentro de cualquier palabra.
 *
 * Devuelve la posicion del primer acierto (menor es mejor) o -1. El orden
 * por posicion es lo que hace que el resultado mas cercano a lo buscado
 * quede arriba, en vez de aparecer en el orden alfabetico de la base.
 */
window.coincidir = function (texto, termino) {
    const destino = window.normalizar(texto);

    if (!termino) return 0;

    const palabras = window.normalizar(termino).split(/\s+/).filter(Boolean);

    if (palabras.length === 0) return 0;

    // Camino corto: el termino completo esta en el texto.
    if (destino.includes(window.normalizar(termino))) {
        return destino.indexOf(window.normalizar(termino));
    }

    const letras = window.normalizar(termino).replace(/\s+/g, '');
    const partes = window.normalizar(texto).split(/[^a-z0-9]+/).filter(Boolean);

    if (letras.length > 1 && window.coincidePorIniciales(partes, letras)) {
        return 0;
    }

    // Cada palabra escrita tiene que aparecer al inicio de alguna palabra del
    // texto. Escribir de mas acorta la lista en vez de ampliarla.
    let mejorPosicion = Infinity;

    for (const palabra of palabras) {
        const patron = new RegExp(
            '\\b' + palabra.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
        );

        const comoPalabra = destino.match(patron);

        if (comoPalabra && comoPalabra.index !== undefined) {
            mejorPosicion = Math.min(mejorPosicion, comoPalabra.index);
            continue;
        }

        const dentro = destino.indexOf(palabra);

        if (dentro === -1) return -1;

        mejorPosicion = Math.min(mejorPosicion, dentro);
    }

    return mejorPosicion;
};

/**
 * Busca por INICIALES repartidas entre las palabras de un texto.
 *
 * "lyac" encuentra "Lubricantes y Aceites S.A.": toma "l" de Lubricantes y
 * "yac" del resto. Es la forma en que el operador recuerda un nombre largo,
 * y con muchos registros parecidos rinde mas que escribirlo entero.
 *
 * Las iniciales se consumen DENTRO de una palabra: primero se intenta que
 * cada letra sea el comienzo de una palabra sucesiva ("l", "y", "a", "c"), y
 * si eso no cierra, se avanza letra a letra dentro de cada palabra. Pedir una
 * letra distinta por palabra, sin mas, hacia que "lyac" no encontrara nada.
 *
 * @param {string[]} partes  Palabras normalizadas del texto.
 * @param {string}   letras  Término normalizado, sin espacios.
 */
window.coincidePorIniciales = function (partes, letras) {
    let i = 0;

    for (const palabra of partes) {
        if (i >= letras.length) return true;

        if (palabra.startsWith(letras[i])) {
            i += palabra.length;
            continue;
        }

        // La palabra aporta un prefijo común con lo que falta por cubrir.
        let comunes = 0;

        while (
            comunes < palabra.length &&
            i + comunes < letras.length &&
            palabra[comunes] === letras[i + comunes]
        ) {
            comunes++;
        }

        // Esta palabra no aporta nada: se ignora y se pasa a la siguiente.
        if (comunes === 0) continue;

        i += comunes;
    }

    return i >= letras.length;
};

/**
 * Convierte un <select> en un buscador con lista desplegable.
 *
 * POR QUE
 * -------
 * Un <select> con 400 proveedores es inutil: hay que abrirlo, hacer scroll
 * y leer la lista completa para encontrar uno. El operador ya sabe lo que
 * busca, asi que se escribe y se filtra.
 *
 * QUE HACE
 * --------
 *  - Filtra por codigo, nombre y el texto combinado.
 *  - "las" encuentra "Lubricantes y Aceites S.A." por iniciales.
 *  - Navegacion con flechas y Enter, y Escape para cerrar.
 *  - Conserva el <select> original: los formularios siguen enviando el mismo
 *    `name`, y sin JavaScript sigue siendo un desplegable normal.
 *
 * @param {HTMLSelectElement} select  Elemento a convertir.
 * @param {object}  [opciones]
 * @param {string}  [opciones.placeholder]  Texto del buscador.
 * @param {Function}[opciones.alElegir]     Se llama con el <option> elegido.
 * @param {number}  [opciones.maxVisible]    Cuántas filas se pintan a la vez.
 */
window.buscarEnSelect = function (select, opciones = {}) {
    if (!select) return;

    // Volver a construirlo sobre el mismo <select> no es un no-op: si las
    // opciones llegaron despues por fetch, hay que rehacer la envoltura para
    // que la lista tenga contenido. Se permite rehacer solo si cambio el
    // numero de opciones.
    if (select.dataset.buscadorListo === '1') {
        const yaMontadas = Number(select.dataset.buscadorOpciones ?? -1);

        if (yaMontadas === select.options.length) return;

        select.dataset.buscadorListo = '0';
        select.closest('.ds-bus')?.remove();
        select.classList.remove('ds-bus__oculto');
    }

    select.dataset.buscadorListo = '1';

    const placeholder = opciones.placeholder || 'ESCRIBA PARA BUSCAR...';
    const maxVisible = opciones.maxVisible ?? 60;

    // Si no hay opciones, no hay nada que buscar: se deja el select intacto.
    if (select.options.length < 2) return;

    const envoltura = document.createElement('div');
    envoltura.className = 'ds-bus';

    select.parentNode.insertBefore(envoltura, select);
    envoltura.appendChild(select);

    // El id tiene que ser unico por instancia. Con un id fijo, dos
    // buscadores en la misma pagina generaban `ds-bus-lista-opcion-0` dos
    // veces y el `aria-activedescendant` apuntaba al panel equivocado.
    const idUnico = 'ds-bus-' + Math.random().toString(36).slice(2, 9);

    const lista = document.createElement('ul');
    lista.className = 'ds-bus__lista';
    lista.id = idUnico + '-lista';
    lista.setAttribute('role', 'listbox');

    const buscador = document.createElement('input');
    buscador.type = 'text';
    buscador.className = 'ds-bus__campo';
    buscador.autocomplete = 'off';
    buscador.placeholder = placeholder;
    buscador.setAttribute('aria-label', placeholder);
    buscador.setAttribute('role', 'combobox');
    buscador.setAttribute('aria-expanded', 'false');
    buscador.setAttribute('aria-controls', lista.id);

    const disparador = document.createElement('button');
    disparador.type = 'button';
    disparador.className = 'ds-bus__lupa';
    disparador.innerHTML = '<i class="fas fa-magnifying-glass" aria-hidden="true"></i>';
    disparador.setAttribute('aria-label', 'Ver lista completa');
    disparador.tabIndex = -1;

    const fila = document.createElement('div');
    fila.className = 'ds-bus__fila';
    fila.appendChild(buscador);
    fila.appendChild(disparador);

    const panel = document.createElement('div');
    panel.className = 'ds-bus__panel';
    panel.hidden = true;

    const vacio = document.createElement('div');
    vacio.className = 'ds-bus__vacio';
    vacio.hidden = true;

    panel.appendChild(lista);
    panel.appendChild(vacio);
    envoltura.appendChild(fila);
    envoltura.appendChild(panel);

    // El select se oculta pero sigue siendo el que se envia. `hidden` lo saca
    // del layout y del envio de mas forma; la clase evita el salto visual.
    select.classList.add('ds-bus__oculto');

    // Se recuerda cuantas opciones tenía, para saber si hay que rehacerlo.
    select.dataset.buscadorOpciones = String(select.options.length);

    let highlighted = -1;
    let filasVisibles = [];

    /** Texto por el que se puede filtrar una opción. */
    const textoDe = (opcion) => {
        const extra = opcion.dataset.busqueda ? ' ' + opcion.dataset.busqueda : '';
        return (opcion.textContent || '').trim() + extra;
    };

    function cerrar() {
        panel.hidden = true;
        buscador.setAttribute('aria-expanded', 'false');
        highlighted = -1;
    }

    function abrir() {
        panel.hidden = false;
        buscador.setAttribute('aria-expanded', 'true');
    }

    function mover(direccion) {
        if (filasVisibles.length === 0) return;

        highlighted += direccion;

        if (highlighted < 0) highlighted = filasVisibles.length - 1;
        if (highlighted >= filasVisibles.length) highlighted = 0;

        filasVisibles.forEach((li, i) => {
            li.classList.toggle('ds-bus__opcion--activa', i === highlighted);
        });

        const activa = filasVisibles[highlighted];

        if (activa) {
            activa.scrollIntoView({ block: 'nearest' });
            buscador.setAttribute('aria-activedescendant', activa.id);
        }
    }

    function elegir(indice) {
        const li = filasVisibles[indice];

        if (!li) return;

        const indiceOriginal = Number(li.dataset.indice);

        select.selectedIndex = indiceOriginal;
        select.dispatchEvent(new Event('change', { bubbles: true }));

        if (typeof opciones.alElegir === 'function') {
            opciones.alElegir(select.options[indiceOriginal], select);
        }

        // Se muestra el texto chosen y se cierra: si el campo quedara con el
        // termino de busqueda, al reeditar no se sabria que quedo elegido.
        buscador.value = select.options[indiceOriginal].textContent.trim();

        cerrar();
    }

    function pintar() {
        const termino = buscador.value.trim();

        lista.innerHTML = '';
        filasVisibles = [];
        highlighted = -1;

        const coincidencias = [];

        for (let i = 0; i < select.options.length; i++) {
            const opcion = select.options[i];

            if (!opcion.value) continue; // la opción "seleccione..."

            const posicion = coincidir(textoDe(opcion), termino);

            if (posicion !== -1) {
                coincidencias.push({ indice: i, posicion });
            }
        }

        // Primero los que empiezan antes en el texto, luego por id para que el
        // orden sea estable entre búsquedas.
        coincidencias.sort((a, b) =>
            a.posicion === b.posicion ? a.indice - b.indice : a.posicion - b.posicion
        );

        vacio.hidden = coincidencias.length !== 0;

        if (coincidencias.length === 0) {
            vacio.textContent = termino
                ? 'No se encontraron coincidencias para "' + termino + '"'
                : 'No hay registros disponibles';
            return;
        }

        coincidencias.slice(0, maxVisible).forEach((coincidencia, posicion) => {
            const opcion = select.options[coincidencia.indice];
            const li = document.createElement('li');
            li.className = 'ds-bus__opcion';
            li.id = idUnico + '-opcion-' + posicion;
            li.dataset.indice = coincidencia.indice;
            li.setAttribute('role', 'option');

            const principal = document.createElement('span');
            principal.className = 'ds-bus__texto';
            principal.textContent = opcion.textContent.trim();

            li.appendChild(principal);

            if (opcion.dataset.detalle) {
                const detalle = document.createElement('span');
                detalle.className = 'ds-bus__detalle';
                detalle.textContent = opcion.dataset.detalle;
                li.appendChild(detalle);
            }

            lista.appendChild(li);
            filasVisibles.push(li);
        });

        // Se avisa si se recortó la lista: sin esto, el operador cree que
        // esos son todos los resultados.
        if (coincidencias.length > maxVisible) {
            const nota = document.createElement('div');
            nota.className = 'ds-bus__nota';
            nota.textContent =
                'Mostrando ' + maxVisible + ' de ' + coincidencias.length +
                '. Afine la búsqueda para ver el resto.';
            lista.appendChild(nota);
        }
    }

    buscador.addEventListener('input', function () {
        abrir();
        pintar();
    });

    buscador.addEventListener('focus', function () {
        abrir();
        pintar();
    });

    disparador.addEventListener('click', function () {
        if (panel.hidden) {
            buscador.focus();
            abrir();
            pintar();
        } else {
            cerrar();
        }
    });

    buscador.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            abrir();
            if (filasVisibles.length === 0) pintar();
            mover(1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            abrir();
            if (filasVisibles.length === 0) pintar();
            mover(-1);
        } else if (e.key === 'Enter') {
            // Sin esto, Enter con el panel abierto envia el formulario en vez
            // de elegir la fila resaltada.
            if (panel.hidden) return;

            e.preventDefault();

            if (highlighted === -1 && filasVisibles.length > 0) highlighted = 0;

            if (highlighted !== -1) elegir(highlighted);
        } else if (e.key === 'Escape') {
            cerrar();
        }
    });

    lista.addEventListener('mousedown', function (e) {
        // `mousedown` y no `click`: el click llega tarde, cuando el input ya
        // perdio el foco y el panel se cerro.
        const li = e.target.closest('.ds-bus__opcion');

        if (!li) return;

        e.preventDefault();

        elegir(Number(li.dataset.indice));
    });

    lista.addEventListener('mousemove', function (e) {
        const li = e.target.closest('.ds-bus__opcion');

        if (!li) return;

        highlighted = filasVisibles.indexOf(li);

        filasVisibles.forEach((fila, i) => {
            fila.classList.toggle('ds-bus__opcion--activa', i === highlighted);
        });
    });

    document.addEventListener('click', function (e) {
        if (!envoltura.contains(e.target)) cerrar();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !panel.hidden) cerrar();
    });

    // Refleja la seleccion actual: si el value cambia desde PHP (edicion o
    // old()), el buscador tiene que mostrar ese nombre.
    const sincronizar = () => {
        const elegida = select.options[select.selectedIndex];

        buscador.value = (elegida && elegida.value)
            ? elegida.textContent.trim()
            : '';
    };

    select.addEventListener('change', sincronizar);

    sincronizar();

    // Se expone para que quien lo inicialice pueda mantenerlo al dia si
    // recarga la lista de opciones despues.
    envoltura.refrescarBuscador = sincronizar;
    select.refrescarBuscador = sincronizar;
};

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

    /* BOTÓN HAMBURGUESA */

    menuToggle.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        toggleMenu();
    });


    /* BACKDROP */

    if (menuBackdrop) {
        menuBackdrop.addEventListener('click', () => {
            closeMenu();
        });
    }


    /* ENLACES DEL MENÚ */

    document
        .querySelectorAll('.drawer-nav-links a')
        .forEach((link) => {

            link.addEventListener('click', () => {
                closeMenu();
            });

        });


    /* ESC PARA CERRAR */

    document.addEventListener('keydown', (event) => {

        if (event.key === 'Escape') {
            closeMenu();
        }

    });
}


/* =========================================================
   CATEGORÍAS DEL MENÚ
   ========================================================= */

function initCategoryToggles() {

    document
        .querySelectorAll('.category-toggle')
        .forEach((button) => {

            button.addEventListener('click', (event) => {

                event.preventDefault();
                event.stopPropagation();

                toggleCategory(button);

            });

        });

    restoreCategoryStates();
}


/* =========================================================
   TOGGLE CATEGORÍA
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

    const icon =
        button.querySelector('.fa-chevron-down');

    const isOpen =
        items.classList.contains('open');

    if (isOpen) {
        closeCategory(button, items, icon);
    } else {
        openCategory(button, items, icon);
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

function saveCategoryState(categoryName, isOpen) {

    try {

        const saved =
            localStorage.getItem(
                'sidebarCategories'
            );

        const states =
            saved
                ? JSON.parse(saved)
                : {};

        states[categoryName] = isOpen;

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
