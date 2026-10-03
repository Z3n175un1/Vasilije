{{--
    resources/views/components/page-transition.blade.php

    Uso: <x-page-transition /> como PRIMER elemento dentro de <body>.

    Flujo:
      1. Al hacer clic en un enlace interno (o enviar un formulario) el panel azul marino
         sube desde abajo y cubre la pantalla, con el camión avanzando por la ruta.
      2. La nueva página carga ya cubierta (sin parpadeo) y el panel se va hacia arriba.

    Opt-out: agrega data-no-transition a cualquier <a> o <form> (descargas, exportes, etc.).
--}}

<style>
    /* =====================================================
       TOKENS
       ===================================================== */
    :root {
        --ds-navy: #0a1f44;
        --ds-navy-deep: #06142f;
        --ds-amber: #f5b342;
        --ds-ease: cubic-bezier(.77, 0, .18, 1);
    }

    /* =====================================================
       PANEL
       Reposo: fuera de pantalla (abajo) y oculto.
       ===================================================== */
    .ds-tr {
        position: fixed;
        inset: 0;
        z-index: 99999;
        display: grid;
        place-items: center;
        overflow: hidden;
        background:
            radial-gradient(120% 90% at 50% 110%, #123070 0%, transparent 60%),
            var(--ds-navy);
        transform: translateY(100%);
        visibility: hidden;
        pointer-events: none;
    }

    /* Página recién cargada tras navegar: ya empieza cubierta */
    html.ds-pending .ds-tr {
        transform: translateY(0);
        visibility: visible;
        pointer-events: all;
    }

    /* Cubrir: sube desde abajo */
    .ds-tr.is-covering {
        transform: translateY(0);
        visibility: visible;
        pointer-events: all;
        transition: transform .5s var(--ds-ease);
    }

    /* Revelar: se va hacia arriba */
    .ds-tr.is-revealing {
        transform: translateY(-100%);
        visibility: visible;
        transition: transform .65s var(--ds-ease);
    }

    /* Borde superior ámbar que "corta" la pantalla al subir */
    .ds-tr::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 3px;
        background: var(--ds-amber);
        opacity: .9;
    }

    /* =====================================================
       CONTENIDO
       ===================================================== */
    .ds-tr__inner {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1.1rem;
        padding: 1.5rem;
        text-align: center;
        color: #fff;
        font-family: 'Uncut Sans', system-ui, sans-serif;
    }

    .ds-tr.is-covering .ds-tr__inner {
        animation: ds-zoom-in .55s .2s var(--ds-ease) both;
    }

    .ds-tr.is-revealing .ds-tr__inner {
        animation: ds-fade-out .25s ease-in both;
    }

    html.ds-pending .ds-tr .ds-tr__inner {
        animation: none;
        opacity: 1;
    }

    /* Escena: camión + ruta */
    .ds-tr__scene {
        position: relative;
        width: min(260px, 70vw);
        padding-top: 2.4rem;
        -webkit-mask-image: linear-gradient(90deg, transparent, #000 18%, #000 82%, transparent);
                mask-image: linear-gradient(90deg, transparent, #000 18%, #000 82%, transparent);
    }

    .ds-tr__truck {
        display: block;
        font-size: 2.6rem;
        line-height: 1;
        color: var(--ds-amber);
        animation: ds-bump .32s ease-in-out infinite alternate;
        filter: drop-shadow(0 6px 10px rgba(0, 0, 0, .35));
    }

    .ds-tr__road {
        height: 4px;
        margin-top: .35rem;
        border-radius: 2px;
        background: repeating-linear-gradient(
            90deg,
            rgba(255, 255, 255, .7) 0 22px,
            transparent 22px 44px
        );
        background-size: 44px 4px;
        animation: ds-road .45s linear infinite;
    }

    .ds-tr__brand {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: .02em;
    }

    .ds-tr__msg {
        margin: 0;
        font-size: .9rem;
        font-weight: 500;
        color: rgba(255, 255, 255, .7);
    }

    .ds-tr__dots span {
        display: inline-block;
        animation: ds-dot 1.2s infinite both;
    }
    .ds-tr__dots span:nth-child(2) { animation-delay: .2s; }
    .ds-tr__dots span:nth-child(3) { animation-delay: .4s; }

    /* =====================================================
       KEYFRAMES (misma idea que tailwind-animations)
       ===================================================== */
    @keyframes ds-zoom-in {
        from { opacity: 0; transform: scale(.85) translateY(12px); }
        to   { opacity: 1; transform: none; }
    }

    @keyframes ds-fade-out {
        from { opacity: 1; transform: none; }
        to   { opacity: 0; transform: translateY(-14px); }
    }

    @keyframes ds-road {
        to { background-position-x: -44px; }
    }

    @keyframes ds-bump {
        from { transform: translateY(0); }
        to   { transform: translateY(-3px); }
    }

    @keyframes ds-dot {
        0%, 80%, 100% { opacity: .2; }
        40%           { opacity: 1; }
    }

    /* Accesibilidad: sin movimiento, solo un fundido */
    @media (prefers-reduced-motion: reduce) {
        .ds-tr.is-covering,
        .ds-tr.is-revealing { transition-duration: .01s; }
        .ds-tr__truck,
        .ds-tr__road,
        .ds-tr__dots span,
        .ds-tr .ds-tr__inner { animation: none !important; }
    }
</style>

{{-- Detecta si venimos de una navegación y deja el panel cubriendo desde el primer frame --}}
<script>
    (function () {
        try {
            if (sessionStorage.getItem('ds-transition')) {
                document.documentElement.classList.add('ds-pending');
            }
        } catch (e) {}
    })();
</script>

<div class="ds-tr" id="dsTransition" role="status" aria-live="polite" aria-label="Cargando página">
    <div class="ds-tr__inner">

        <div class="ds-tr__scene" aria-hidden="true">
            <i class="fas fa-truck-fast ds-tr__truck"></i>
            <div class="ds-tr__road"></div>
        </div>

        <p class="ds-tr__brand">DS TRANSPORTE S.R.L</p>

        <p class="ds-tr__msg">
            Cargando
            <span class="ds-tr__dots" aria-hidden="true"><span>.</span><span>.</span><span>.</span></span>
        </p>

    </div>
</div>

<script>
    (function () {
        var KEY = 'ds-transition';
        var COVER_MS = 480;        // espera antes de navegar (dura igual que la subida del panel)
        var MIN_VISIBLE_MS = 700;  // tiempo mínimo que se ve el panel en la página nueva
        var FAILSAFE_MS = 10000;   // si la navegación no ocurre (descargas, AJAX), se libera la pantalla

        var root = document.documentElement;
        var overlay = document.getElementById('dsTransition');
        var startedAt = performance.now();
        var failsafe = null;

        function setFlag() { try { sessionStorage.setItem(KEY, '1'); } catch (e) {} }
        function clearFlag() { try { sessionStorage.removeItem(KEY); } catch (e) {} }

        function cover() {
            if (overlay.classList.contains('is-covering')) return false;
            overlay.classList.remove('is-revealing');
            overlay.classList.add('is-covering');
            setFlag();
            clearTimeout(failsafe);
            failsafe = setTimeout(reveal, FAILSAFE_MS);
            return true;
        }

        function reveal() {
            clearTimeout(failsafe);
            clearFlag();
            overlay.classList.remove('is-covering');
            overlay.classList.add('is-revealing');
            root.classList.remove('ds-pending');
        }

        function reset() {
            clearTimeout(failsafe);
            clearFlag();
            overlay.classList.remove('is-covering', 'is-revealing');
            root.classList.remove('ds-pending');
        }

        // Al terminar de salir, vuelve al reposo (abajo y oculto)
        overlay.addEventListener('transitionend', function (e) {
            if (e.target === overlay && e.propertyName === 'transform' &&
                overlay.classList.contains('is-revealing')) {
                overlay.classList.remove('is-revealing');
            }
        });

        // ---------- Página nueva: revelar cuando cargue ----------
        if (root.classList.contains('ds-pending')) {
            clearFlag(); // para que un F5 no vuelva a mostrar el panel
            var onLoaded = function () {
                var wait = Math.max(0, MIN_VISIBLE_MS - (performance.now() - startedAt));
                setTimeout(reveal, wait);
            };
            if (document.readyState === 'complete') onLoaded();
            else window.addEventListener('load', onLoaded);
            failsafe = setTimeout(reveal, FAILSAFE_MS);
        }

        // Botón "atrás" con caché del navegador: no dejar el panel pegado
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) reset();
        });

        // ---------- Enlaces internos ----------
        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button !== 0 ||
                e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

            var a = e.target.closest('a[href]');
            if (!a || a.closest('[data-no-transition]')) return;

            var raw = a.getAttribute('href');
            if (!raw || raw.charAt(0) === '#') return;
            if (a.hasAttribute('download')) return;
            if (a.target && a.target !== '_self') return;

            var url;
            try { url = new URL(a.href, location.href); } catch (err) { return; }
            if (url.protocol !== 'http:' && url.protocol !== 'https:') return;
            if (url.origin !== location.origin) return;
            if (url.pathname === location.pathname && url.search === location.search && url.hash) return;

            e.preventDefault();
            if (!cover()) return;
            setTimeout(function () { location.href = url.href; }, COVER_MS);
        });

        // ---------- Formularios ----------
        // Escucha en fase de burbuja: si otro script ya hizo preventDefault (AJAX, SweetAlert), no se muestra.
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (e.defaultPrevented || !form || form.hasAttribute('data-no-transition')) return;
            if (form.target && form.target !== '_self') return;
            cover();
        });
    })();
</script>