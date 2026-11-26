/**
 * InmoVision 3D - Tema claro / oscuro
 *
 * Este script se carga en el <head> de cada página, ANTES de que el navegador
 * pinte el body, para que el tema guardado se aplique sin parpadeo.
 *
 * Cómo funciona:
 *  - Guarda la preferencia en localStorage ("inmovision-theme").
 *  - Si el usuario nunca eligió, usa la preferencia del sistema operativo
 *    (prefers-color-scheme) y sigue sus cambios.
 *  - Pone data-theme="light" | "dark" en <html>; el CSS hace el resto
 *    (ver el bloque de variables al inicio de css/styles.css).
 *  - Inyecta automáticamente el botón de cambio de tema en el header,
 *    así no hay que tocar el HTML de cada plantilla.
 */
(function () {
    'use strict';

    var KEY = 'inmovision-theme';

    function guardado() {
        try {
            var v = localStorage.getItem(KEY);
            return (v === 'light' || v === 'dark') ? v : null;
        } catch (e) {
            return null;
        }
    }

    function delSistema() {
        return (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches)
            ? 'light' : 'dark';
    }

    function aplicar(tema) {
        document.documentElement.setAttribute('data-theme', tema);
    }

    // 1) Aplicar de inmediato (antes del primer pintado)
    aplicar(guardado() || delSistema());

    // Si no hay preferencia explícita, seguir al sistema
    if (!guardado() && window.matchMedia) {
        var mq = window.matchMedia('(prefers-color-scheme: light)');
        var onChange = function (e) {
            if (!guardado()) aplicar(e.matches ? 'light' : 'dark');
        };
        if (mq.addEventListener) mq.addEventListener('change', onChange);
        else if (mq.addListener) mq.addListener(onChange);
    }

    var SOL = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
        'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<circle cx="12" cy="12" r="4"></circle>' +
        '<path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2' +
        'M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"></path></svg>';

    var LUNA = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
        'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"></path></svg>';

    function pintarBoton(btn) {
        var esClaro = document.documentElement.getAttribute('data-theme') === 'light';
        // En tema claro se ofrece pasar a oscuro (luna) y viceversa.
        btn.innerHTML = esClaro ? LUNA : SOL;
        btn.setAttribute('title', esClaro ? 'Cambiar a tema oscuro' : 'Cambiar a tema claro');
        btn.setAttribute('aria-label', btn.getAttribute('title'));
        btn.setAttribute('aria-pressed', esClaro ? 'true' : 'false');
    }

    // 2) Inyectar el botón cuando el DOM esté listo
    function montarBoton() {
        if (document.getElementById('themeToggle')) return;

        var btn = document.createElement('button');
        btn.id = 'themeToggle';
        btn.type = 'button';
        btn.className = 'theme-toggle';
        pintarBoton(btn);

        btn.addEventListener('click', function () {
            var nuevo = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
            aplicar(nuevo);
            try { localStorage.setItem(KEY, nuevo); } catch (e) { /* modo privado */ }
            pintarBoton(btn);
            // Avisar a otros scripts (p. ej. el fondo 3D) que el tema cambió
            document.dispatchEvent(new CustomEvent('themechange', { detail: { theme: nuevo } }));
        });

        var header = document.querySelector('header.adm-header, header.header');
        if (header) {
            // Colocarlo justo antes del perfil o del botón de iniciar sesión
            var ref = header.querySelector('.adm-profile, .btn-login-adm, .profile-nav, .user-menu, .btn-login');
            if (ref) header.insertBefore(btn, ref);
            else header.appendChild(btn);
        } else {
            // Páginas sin header (p. ej. error/403): botón flotante
            btn.classList.add('theme-toggle-floating');
            document.body.appendChild(btn);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', montarBoton);
    } else {
        montarBoton();
    }
})();
