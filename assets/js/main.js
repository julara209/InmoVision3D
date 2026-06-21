/*InmoVision 3D - JavaScript Principal*/

document.addEventListener('DOMContentLoaded', function() {
    initMobileMenu();
    initDropdownMenu();
    initFavorites();
    initSmoothScroll();
    initHeaderScroll();
    initUploadPreview();
    initModals();
    initAlerts();
});

/* Menú móvil*/
function initMobileMenu() {
    const menuToggle = document.getElementById('menuToggle');
    const nav = document.getElementById('mainNav');

    if (menuToggle && nav) {
        menuToggle.addEventListener('click', () => {
            nav.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });

        // Cerrar al hacer clic en un enlace
        const navLinks = nav.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                nav.classList.remove('active');
                menuToggle.classList.remove('active');
            });
        });
    }
}

/* Menú dropdown del perfil*/
function initDropdownMenu() {
    const profileNav = document.getElementById('profileNav');
    const dropdownMenu = document.getElementById('dropdownMenu');

    if (profileNav && dropdownMenu) {
        profileNav.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });

        document.addEventListener('click', (e) => {
            if (!profileNav.contains(e.target)) {
                dropdownMenu.classList.remove('show');
            }
        });
    }
}

/* Sistema de favoritos*/
function initFavorites() {
    const favoriteButtons = document.querySelectorAll('.btn-favorite');

    favoriteButtons.forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();

            const inmuebleId = this.dataset.inmuebleId;
            
            try {
                const response = await fetch('api/favoritos.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=toggle&inmueble_id=${inmuebleId}`
                });

                const data = await response.json();

                if (data.require_login) {
                    // Redirigir al login
                    window.location.href = 'views/auth/login.php?redirect=' + encodeURIComponent(window.location.href);
                    return;
                }

                if (data.success) {
                    this.classList.toggle('active', data.is_favorite);
                    
                    // Animación
                    this.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 200);

                    // Si estamos en la página de favoritos, eliminar la card
                    if (!data.is_favorite && document.querySelector('.favoritos-page')) {
                        const card = this.closest('.inmueble-card, .favorito-card');
                        if (card) {
                            card.style.opacity = '0';
                            card.style.transform = 'translateX(-20px)';
                            setTimeout(() => card.remove(), 300);
                        }
                    }
                }
            } catch (error) {
                console.error('Error al actualizar favorito:', error);
            }
        });
    });
}

/**
 * Scroll suave para enlaces internos
 */
function initSmoothScroll() {
    const links = document.querySelectorAll('a[href^="#"]');
    
    links.forEach(link => {
        link.addEventListener('click', (e) => {
            const targetId = link.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                e.preventDefault();
                const headerOffset = 80;
                const elementPosition = targetElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}

/**
 * Efecto del header al hacer scroll
 */
function initHeaderScroll() {
    const header = document.querySelector('.header');
    
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.style.background = 'rgba(15, 23, 42, 0.98)';
                header.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.3)';
            } else {
                header.style.background = 'rgba(15, 23, 42, 0.95)';
                header.style.boxShadow = 'none';
            }
        });
    }
}

/**
 * Preview de imágenes al subir
 */
function initUploadPreview() {
    const uploadBoxes = document.querySelectorAll('.upload-box');

    uploadBoxes.forEach(box => {
        const input = box.querySelector('input[type="file"]');
        const preview = box.nextElementSibling;

        if (input) {
            box.addEventListener('click', () => input.click());

            input.addEventListener('change', function() {
                if (preview && preview.classList.contains('upload-preview')) {
                    preview.innerHTML = '';

                    Array.from(this.files).forEach((file, index) => {
                        if (file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            
                            reader.onload = (e) => {
                                const item = document.createElement('div');
                                item.className = 'upload-preview-item';
                                item.innerHTML = `
                                    <img src="${e.target.result}" alt="Preview">
                                    <button type="button" class="remove-btn" data-index="${index}">&times;</button>
                                `;
                                preview.appendChild(item);
                            };

                            reader.readAsDataURL(file);
                        }
                    });
                }
            });
        }
    });
}

/**
 * Sistema de modales
 */
function initModals() {
    // Abrir modal
    document.querySelectorAll('[data-modal]').forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const modalId = trigger.dataset.modal;
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        });
    });

    // Cerrar modal
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay || e.target.classList.contains('modal-close')) {
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    });

    // Cerrar con Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.show').forEach(modal => {
                modal.classList.remove('show');
                document.body.style.overflow = '';
            });
        }
    });
}

/**
 * Auto-ocultar alertas
 */
function initAlerts() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
}

/**
 * Mostrar notificación
 */
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(20px)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * Confirmar acción
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Formatear precio
 */
function formatPrice(price) {
    return '$' + new Intl.NumberFormat('es-CO').format(price);
}

/**
 * Cargar más inmuebles (paginación infinita)
 */
let isLoading = false;
let currentPage = 1;

function loadMoreInmuebles(url, container) {
    if (isLoading) return;
    
    isLoading = true;
    currentPage++;

    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'loading';
    loadingDiv.innerHTML = '<div class="spinner"></div>';
    container.appendChild(loadingDiv);

    fetch(`${url}&page=${currentPage}`)
        .then(response => response.json())
        .then(data => {
            loadingDiv.remove();
            isLoading = false;

            if (data.inmuebles && data.inmuebles.length > 0) {
                data.inmuebles.forEach(inmueble => {
                    const card = createInmuebleCard(inmueble);
                    container.appendChild(card);
                });

                // Reinicializar favoritos para las nuevas cards
                initFavorites();
            }
        })
        .catch(error => {
            console.error('Error al cargar más inmuebles:', error);
            loadingDiv.remove();
            isLoading = false;
        });
}

/**
 * Crear card de inmueble dinámicamente
 */
function createInmuebleCard(inmueble) {
    const card = document.createElement('div');
    card.className = 'inmueble-card';
    card.innerHTML = `
        <div class="card-image">
            <img src="${inmueble.imagen_principal || 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=400&h=300&fit=crop'}" 
                 alt="${inmueble.titulo}">
            <span class="card-badge ${inmueble.operacion === 'arriendo' ? 'arriendo' : ''}">${inmueble.operacion}</span>
            <button class="btn-favorite ${inmueble.es_favorito ? 'active' : ''}" 
                    data-inmueble-id="${inmueble.id}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
            </button>
        </div>
        <div class="card-content">
            <h3><a href="views/inmuebles/detalle.php?id=${inmueble.id}">${inmueble.titulo}</a></h3>
            <p class="card-location">${inmueble.ubicacion}</p>
            <div class="card-features">
                <span>${inmueble.habitaciones} Hab.</span>
                <span>${inmueble.banos} Baños</span>
                <span>${inmueble.area} m²</span>
            </div>
            <p class="card-price">${formatPrice(inmueble.precio)}${inmueble.operacion === 'arriendo' ? '/mes' : ''}</p>
            <div class="card-buttons">
                <a href="views/planos/visor.php?inmueble=${inmueble.id}" class="btn-plano">Ver Plano 2D/3D</a>
                <a href="views/inmuebles/detalle.php?id=${inmueble.id}" class="btn-contacto">Ver Detalles</a>
            </div>
        </div>
    `;
    return card;
}

function cambiarImagen(elemento, src) {
    document.getElementById('imagenPrincipal').src = src;
}
// === Favoritos: lógica centralizada (corazón en cards + botón grande en detalle) ===
function inicializarFavoritos() {
    document.addEventListener('click', function (e) {
        const boton = e.target.closest('.btn-favorite, .btn-favorito-lg');
        if (!boton) return;
        e.preventDefault();
        toggleFavorito(boton);
    });

    // Si venimos de un login con una intención de favorito pendiente, la disparamos
    const pendienteId = sessionStorage.getItem('favoritoPendiente');
    if (pendienteId) {
        sessionStorage.removeItem('favoritoPendiente');
        const boton = document.querySelector(
            `[data-inmueble-id="${pendienteId}"].btn-favorite, [data-inmueble-id="${pendienteId}"].btn-favorito-lg`
        );
        if (boton) toggleFavorito(boton);
    }
}

async function toggleFavorito(boton) {
    if (boton.dataset.loading === '1') return; // evita doble clic mientras carga
    boton.dataset.loading = '1';
    boton.disabled = true;

    const inmuebleId = boton.dataset.inmuebleId;

    try {
        const response = await fetch('/InmoVision3D/Api/FavoritosApi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=toggle&inmueble_id=${inmuebleId}`
        });
        const data = await response.json();

        if (data.require_login) {
            sessionStorage.setItem('favoritoPendiente', inmuebleId);
            window.location.href = '/InmoVision3D/views/auth/login.php?redirect='
                + encodeURIComponent(window.location.href);
            return;
        }

        if (data.success) {
            actualizarUIFavorito(boton, data.is_favorite);
        }
    } catch (error) {
        console.error('Error favoritos:', error);
    } finally {
        boton.dataset.loading = '0';
        boton.disabled = false;
    }
}

function actualizarUIFavorito(boton, esFavorito) {
    boton.classList.toggle('active', esFavorito);
    const svg = boton.querySelector('svg');
    if (svg) svg.setAttribute('fill', esFavorito ? 'currentColor' : 'none');
    const span = boton.querySelector('span');
    if (span) span.textContent = esFavorito ? 'En favoritos' : 'Agregar a favoritos';
    boton.title = esFavorito ? 'Quitar de favoritos' : 'Agregar a favoritos';
}

document.addEventListener('DOMContentLoaded', inicializarFavoritos);