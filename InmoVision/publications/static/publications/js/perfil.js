/**
 * Menú de perfil y botón "ver contraseña" del módulo Django de InmoVision 3D.
 * Hace lo mismo que la parte equivalente de js/main.js en la aplicación Java.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // Menú desplegable del perfil
        var perfil = document.getElementById('profileBtn');
        var menu = document.getElementById('profileDropdown');

        if (perfil && menu) {
            perfil.addEventListener('click', function (e) {
                e.stopPropagation();
                menu.classList.toggle('open');
            });

            menu.addEventListener('click', function (e) {
                e.stopPropagation();
            });

            document.addEventListener('click', function () {
                menu.classList.remove('open');
            });
        }

        // Mostrar / ocultar contraseña
        document.querySelectorAll('.pw-toggle').forEach(function (boton) {
            boton.addEventListener('click', function () {
                var campo = document.getElementById(boton.getAttribute('data-target'));
                if (!campo) { return; }

                var oculto = campo.type === 'password';
                campo.type = oculto ? 'text' : 'password';
                boton.classList.toggle('visible', oculto);
            });
        });
    });
})();
