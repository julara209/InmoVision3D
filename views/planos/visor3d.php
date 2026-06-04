<?php
/**
 * Visor 3D de Inmuebles - InmoVision3D
 * Permite recorrer el inmueble en 3D estilo Home Planning
 */
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Inmueble.php';
require_once __DIR__ . '/../../models/Plano.php';

$inmuebleId = intval($_GET['id'] ?? 0);
if (!$inmuebleId) {
    header('Location: ' . BASE_URL . 'views/inmuebles/listar.php');
    exit;
}

$inmuebleModel = new Inmueble();
$planoModel = new Plano();

$inmueble = $inmuebleModel->obtenerPorId($inmuebleId);
$plano = $planoModel->obtenerPorInmueble($inmuebleId);

if (!$inmueble || !$plano) {
    header('Location: ' . BASE_URL . 'views/inmuebles/detalle.php?id=' . $inmuebleId);
    exit;
}

// Obtener datos del plano 3D
$planoData = $plano['datos_3d'] ? json_decode($plano['datos_3d'], true) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recorrido 3D - <?php echo htmlspecialchars($inmueble['titulo']); ?> - InmoVision3D</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            overflow: hidden;
            font-family: 'Inter', sans-serif;
        }

        #visor-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #0a0a0f;
        }

        #canvas-3d {
            width: 100%;
            height: 100%;
        }

        /* Header del visor */
        .visor-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 1rem 2rem;
            background: linear-gradient(180deg, rgba(0,0,0,0.8) 0%, transparent 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
        }

        .visor-header .back-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            transition: background 0.2s ease;
        }

        .visor-header .back-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        .visor-header h1 {
            color: white;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .visor-header .badge-3d {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Controles del visor */
        .visor-controls {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 0.5rem;
            padding: 0.5rem;
            background: rgba(0,0,0,0.7);
            border-radius: 12px;
            backdrop-filter: blur(10px);
            z-index: 100;
        }

        .control-btn {
            width: 48px;
            height: 48px;
            border: none;
            background: rgba(255,255,255,0.1);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            transition: all 0.2s ease;
        }

        .control-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        .control-btn.active {
            background: var(--accent-primary);
        }

        /* Panel de información */
        .info-panel {
            position: fixed;
            right: 2rem;
            top: 50%;
            transform: translateY(-50%);
            width: 280px;
            background: rgba(0,0,0,0.8);
            border-radius: 16px;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
            z-index: 100;
            display: none;
        }

        .info-panel.active {
            display: block;
        }

        .info-panel h3 {
            color: white;
            font-size: 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-panel h3 i {
            color: var(--accent-primary);
        }

        .room-list {
            list-style: none;
        }

        .room-list li {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background 0.2s ease;
        }

        .room-list li:hover {
            background: rgba(255,255,255,0.1);
        }

        .room-list li.active {
            background: rgba(59, 130, 246, 0.3);
            border: 1px solid var(--accent-primary);
        }

        .room-list li i {
            width: 20px;
            text-align: center;
        }

        /* Minimap */
        .minimap {
            position: fixed;
            left: 2rem;
            bottom: 2rem;
            width: 200px;
            height: 200px;
            background: rgba(0,0,0,0.8);
            border-radius: 12px;
            padding: 1rem;
            backdrop-filter: blur(10px);
            z-index: 100;
        }

        .minimap canvas {
            width: 100%;
            height: 100%;
            border-radius: 8px;
        }

        .minimap .player-marker {
            position: absolute;
            width: 10px;
            height: 10px;
            background: var(--accent-primary);
            border-radius: 50%;
            transform: translate(-50%, -50%);
        }

        /* Instructions */
        .instructions {
            position: fixed;
            left: 2rem;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.7);
            border-radius: 12px;
            padding: 1rem;
            backdrop-filter: blur(10px);
            z-index: 100;
            max-width: 200px;
        }

        .instructions h4 {
            color: white;
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
        }

        .instructions p {
            color: rgba(255,255,255,0.7);
            font-size: 0.75rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .instructions kbd {
            background: rgba(255,255,255,0.1);
            padding: 0.125rem 0.375rem;
            border-radius: 4px;
            font-family: monospace;
        }

        /* Loading screen */
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #0a0a0f;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            transition: opacity 0.5s ease;
        }

        .loading-screen.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 3px solid rgba(255,255,255,0.1);
            border-top-color: var(--accent-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1.5rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-screen h2 {
            color: white;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .loading-screen p {
            color: rgba(255,255,255,0.6);
            font-size: 0.875rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .info-panel,
            .instructions {
                display: none !important;
            }

            .minimap {
                width: 120px;
                height: 120px;
                left: 1rem;
                bottom: 6rem;
            }

            .visor-controls {
                bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading-screen" id="loading-screen">
        <div class="loading-spinner"></div>
        <h2>Cargando Recorrido 3D</h2>
        <p>Preparando el inmueble para ti...</p>
    </div>

    <!-- Visor Container -->
    <div id="visor-container">
        <canvas id="canvas-3d"></canvas>
    </div>

    <!-- Header -->
    <header class="visor-header">
        <a href="<?php echo BASE_URL; ?>views/inmuebles/detalle.php?id=<?php echo $inmuebleId; ?>" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            <span>Volver</span>
        </a>
        <h1><?php echo htmlspecialchars($inmueble['titulo']); ?></h1>
        <span class="badge-3d"><i class="fas fa-cube"></i> Recorrido 3D</span>
    </header>

    <!-- Controles -->
    <div class="visor-controls">
        <button class="control-btn" id="btn-walk" title="Modo Caminar" onclick="setViewMode('walk')">
            <i class="fas fa-walking"></i>
        </button>
        <button class="control-btn active" id="btn-orbit" title="Modo Orbital" onclick="setViewMode('orbit')">
            <i class="fas fa-sync-alt"></i>
        </button>
        <button class="control-btn" id="btn-top" title="Vista Superior" onclick="setViewMode('top')">
            <i class="fas fa-border-all"></i>
        </button>
        <button class="control-btn" id="btn-fullscreen" title="Pantalla Completa" onclick="toggleFullscreen()">
            <i class="fas fa-expand"></i>
        </button>
        <button class="control-btn" id="btn-info" title="Informacion" onclick="toggleInfoPanel()">
            <i class="fas fa-info"></i>
        </button>
    </div>

    <!-- Instrucciones -->
    <div class="instructions" id="instructions">
        <h4><i class="fas fa-gamepad"></i> Controles</h4>
        <p><kbd>W A S D</kbd> Mover</p>
        <p><kbd>Mouse</kbd> Rotar vista</p>
        <p><kbd>Scroll</kbd> Zoom</p>
        <p><kbd>Espacio</kbd> Subir</p>
        <p><kbd>Shift</kbd> Bajar</p>
    </div>

    <!-- Panel de información -->
    <div class="info-panel" id="info-panel">
        <h3><i class="fas fa-door-open"></i> Habitaciones</h3>
        <ul class="room-list" id="room-list">
            <!-- Se llena dinamicamente -->
        </ul>
    </div>

    <!-- Minimap -->
    <div class="minimap">
        <canvas id="minimap-canvas"></canvas>
        <div class="player-marker" id="player-marker"></div>
    </div>

    <!-- Three.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script>
    // Configuración inicial
    const BASE_URL = '<?php echo BASE_URL; ?>';
    const planoData = <?php echo $plano['datos_3d'] ? $plano['datos_3d'] : 'null'; ?>;
    const planoArchivo = '<?php echo $plano['archivo_2d']; ?>';

    // Variables globales Three.js
    let scene, camera, renderer, controls;
    let viewMode = 'orbit';
    let rooms = [];
    let player = { x: 0, y: 1.7, z: 0, rotation: 0 };
    let keys = {};

    // Inicializar escena
    function initScene() {
        // Escena
        scene = new THREE.Scene();
        scene.background = new THREE.Color(0x1a1a2e);
        scene.fog = new THREE.Fog(0x1a1a2e, 10, 50);

        // Camara
        camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        camera.position.set(15, 15, 15);
        camera.lookAt(0, 0, 0);

        // Renderer
        renderer = new THREE.WebGLRenderer({
            canvas: document.getElementById('canvas-3d'),
            antialias: true
        });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;

        // Luces
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.4);
        scene.add(ambientLight);

        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(10, 20, 10);
        directionalLight.castShadow = true;
        directionalLight.shadow.mapSize.width = 2048;
        directionalLight.shadow.mapSize.height = 2048;
        scene.add(directionalLight);

        // Crear el modelo 3D
        if (planoData) {
            createFromPlanoData(planoData);
        } else {
            createDefaultModel();
        }

        // Piso exterior
        const floorGeometry = new THREE.PlaneGeometry(100, 100);
        const floorMaterial = new THREE.MeshStandardMaterial({ 
            color: 0x2d3748,
            roughness: 0.8
        });
        const floor = new THREE.Mesh(floorGeometry, floorMaterial);
        floor.rotation.x = -Math.PI / 2;
        floor.position.y = -0.01;
        floor.receiveShadow = true;
        scene.add(floor);

        // Grid helper
        const gridHelper = new THREE.GridHelper(50, 50, 0x444444, 0x333333);
        gridHelper.position.y = 0.01;
        scene.add(gridHelper);

        // Event listeners
        window.addEventListener('resize', onWindowResize);
        document.addEventListener('keydown', (e) => keys[e.key.toLowerCase()] = true);
        document.addEventListener('keyup', (e) => keys[e.key.toLowerCase()] = false);

        // Orbit controls simple
        setupOrbitControls();

        // Ocultar loading
        setTimeout(() => {
            document.getElementById('loading-screen').classList.add('hidden');
        }, 1500);

        // Iniciar animacion
        animate();
    }

    // Crear modelo desde datos del plano
    function createFromPlanoData(data) {
        const wallHeight = 2.8;
        const wallThickness = 0.15;

        // Materiales
        const wallMaterial = new THREE.MeshStandardMaterial({ 
            color: 0xf5f5f5,
            roughness: 0.9
        });
        const floorMaterial = new THREE.MeshStandardMaterial({ 
            color: 0x8B4513,
            roughness: 0.7
        });
        const windowMaterial = new THREE.MeshStandardMaterial({ 
            color: 0x87CEEB,
            transparent: true,
            opacity: 0.5
        });

        // Crear habitaciones
        if (data.rooms) {
            data.rooms.forEach((room, index) => {
                const roomGroup = new THREE.Group();
                roomGroup.name = room.name || 'Habitacion ' + (index + 1);

                // Piso de la habitacion
                const roomFloor = new THREE.Mesh(
                    new THREE.PlaneGeometry(room.width, room.depth),
                    floorMaterial.clone()
                );
                roomFloor.rotation.x = -Math.PI / 2;
                roomFloor.position.set(room.x, 0.01, room.z);
                roomFloor.receiveShadow = true;
                roomGroup.add(roomFloor);

                // Paredes
                createWalls(roomGroup, room, wallHeight, wallThickness, wallMaterial);

                scene.add(roomGroup);
                rooms.push({ 
                    group: roomGroup, 
                    name: room.name, 
                    x: room.x, 
                    z: room.z,
                    width: room.width,
                    depth: room.depth
                });
            });
        }

        // Crear paredes desde lineas
        if (data.walls) {
            data.walls.forEach(wall => {
                const length = Math.sqrt(
                    Math.pow(wall.x2 - wall.x1, 2) + 
                    Math.pow(wall.z2 - wall.z1, 2)
                );
                const angle = Math.atan2(wall.z2 - wall.z1, wall.x2 - wall.x1);

                const wallMesh = new THREE.Mesh(
                    new THREE.BoxGeometry(length, wallHeight, wallThickness),
                    wallMaterial
                );
                wallMesh.position.set(
                    (wall.x1 + wall.x2) / 2,
                    wallHeight / 2,
                    (wall.z1 + wall.z2) / 2
                );
                wallMesh.rotation.y = -angle;
                wallMesh.castShadow = true;
                wallMesh.receiveShadow = true;
                scene.add(wallMesh);
            });
        }

        updateRoomList();
    }

    // Crear paredes para una habitacion
    function createWalls(group, room, height, thickness, material) {
        const walls = [
            { x: room.x, z: room.z - room.depth/2, rotY: 0, width: room.width }, // Frente
            { x: room.x, z: room.z + room.depth/2, rotY: 0, width: room.width }, // Atras
            { x: room.x - room.width/2, z: room.z, rotY: Math.PI/2, width: room.depth }, // Izquierda
            { x: room.x + room.width/2, z: room.z, rotY: Math.PI/2, width: room.depth }  // Derecha
        ];

        walls.forEach((wallData, i) => {
            // Verificar si hay puerta o ventana
            const hasDoor = room.doors && room.doors.includes(i);
            const hasWindow = room.windows && room.windows.includes(i);

            if (hasDoor) {
                // Pared con puerta
                createWallWithDoor(group, wallData, height, thickness, material);
            } else if (hasWindow) {
                // Pared con ventana
                createWallWithWindow(group, wallData, height, thickness, material);
            } else {
                // Pared solida
                const wall = new THREE.Mesh(
                    new THREE.BoxGeometry(wallData.width, height, thickness),
                    material
                );
                wall.position.set(wallData.x, height/2, wallData.z);
                wall.rotation.y = wallData.rotY;
                wall.castShadow = true;
                wall.receiveShadow = true;
                group.add(wall);
            }
        });
    }

    function createWallWithDoor(group, wallData, height, thickness, material) {
        const doorWidth = 0.9;
        const doorHeight = 2.1;
        const sideWidth = (wallData.width - doorWidth) / 2;

        // Parte superior
        const topWall = new THREE.Mesh(
            new THREE.BoxGeometry(wallData.width, height - doorHeight, thickness),
            material
        );
        topWall.position.set(wallData.x, height - (height - doorHeight)/2, wallData.z);
        topWall.rotation.y = wallData.rotY;
        topWall.castShadow = true;
        group.add(topWall);

        // Lados
        if (sideWidth > 0) {
            [-1, 1].forEach(side => {
                const sideWall = new THREE.Mesh(
                    new THREE.BoxGeometry(sideWidth, doorHeight, thickness),
                    material
                );
                const offset = (sideWidth + doorWidth) / 2 * side;
                sideWall.position.set(
                    wallData.x + (wallData.rotY === 0 ? offset : 0),
                    doorHeight/2,
                    wallData.z + (wallData.rotY !== 0 ? offset : 0)
                );
                sideWall.rotation.y = wallData.rotY;
                sideWall.castShadow = true;
                group.add(sideWall);
            });
        }
    }

    function createWallWithWindow(group, wallData, height, thickness, material) {
        const windowWidth = 1.2;
        const windowHeight = 1.2;
        const windowBottom = 0.9;

        // Similar estructura a puerta pero con ventana en el medio
        // Implementacion simplificada
        const wall = new THREE.Mesh(
            new THREE.BoxGeometry(wallData.width, height, thickness),
            material
        );
        wall.position.set(wallData.x, height/2, wallData.z);
        wall.rotation.y = wallData.rotY;
        wall.castShadow = true;
        group.add(wall);
    }

    // Crear modelo por defecto (demo)
    function createDefaultModel() {
        const defaultData = {
            rooms: [
                { name: 'Sala', x: 0, z: 0, width: 6, depth: 5, doors: [0], windows: [2, 3] },
                { name: 'Cocina', x: 0, z: 5.5, width: 4, depth: 3, doors: [0] },
                { name: 'Habitacion 1', x: 5.5, z: 0, width: 4, depth: 4, doors: [2], windows: [1, 3] },
                { name: 'Habitacion 2', x: 5.5, z: 4.5, width: 4, depth: 3.5, doors: [2], windows: [1] },
                { name: 'Bano', x: 4.5, z: 5.5, width: 2, depth: 2.5, doors: [2] }
            ]
        };
        createFromPlanoData(defaultData);
    }

    // Actualizar lista de habitaciones
    function updateRoomList() {
        const list = document.getElementById('room-list');
        list.innerHTML = '';

        const icons = {
            'Sala': 'fa-couch',
            'Cocina': 'fa-utensils',
            'Habitacion': 'fa-bed',
            'Bano': 'fa-bath',
            'default': 'fa-door-open'
        };

        rooms.forEach((room, index) => {
            const li = document.createElement('li');
            const icon = icons[room.name] || icons['default'];
            li.innerHTML = `<i class="fas ${icon}"></i> ${room.name}`;
            li.onclick = () => goToRoom(index);
            list.appendChild(li);
        });
    }

    // Ir a una habitacion
    function goToRoom(index) {
        const room = rooms[index];
        if (!room) return;

        // Animar camara
        const targetPos = { x: room.x, y: 10, z: room.z + 5 };
        animateCamera(targetPos, { x: room.x, y: 0, z: room.z });

        // Actualizar activo en lista
        document.querySelectorAll('.room-list li').forEach((li, i) => {
            li.classList.toggle('active', i === index);
        });
    }

    // Animar camara
    function animateCamera(position, lookAt) {
        const startPos = { ...camera.position };
        const duration = 1000;
        const startTime = Date.now();

        function animate() {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);

            camera.position.x = startPos.x + (position.x - startPos.x) * eased;
            camera.position.y = startPos.y + (position.y - startPos.y) * eased;
            camera.position.z = startPos.z + (position.z - startPos.z) * eased;
            camera.lookAt(lookAt.x, lookAt.y, lookAt.z);

            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        }
        animate();
    }

    // Controles orbitales simples
    function setupOrbitControls() {
        let isDragging = false;
        let previousMousePosition = { x: 0, y: 0 };
        let spherical = { theta: Math.PI / 4, phi: Math.PI / 4, radius: 20 };

        const canvas = renderer.domElement;

        canvas.addEventListener('mousedown', (e) => {
            isDragging = true;
            previousMousePosition = { x: e.clientX, y: e.clientY };
        });

        canvas.addEventListener('mousemove', (e) => {
            if (!isDragging || viewMode === 'walk') return;

            const deltaX = e.clientX - previousMousePosition.x;
            const deltaY = e.clientY - previousMousePosition.y;

            spherical.theta -= deltaX * 0.01;
            spherical.phi = Math.max(0.1, Math.min(Math.PI / 2 - 0.1, spherical.phi + deltaY * 0.01));

            updateCameraFromSpherical();
            previousMousePosition = { x: e.clientX, y: e.clientY };
        });

        canvas.addEventListener('mouseup', () => isDragging = false);
        canvas.addEventListener('mouseleave', () => isDragging = false);

        canvas.addEventListener('wheel', (e) => {
            spherical.radius = Math.max(5, Math.min(50, spherical.radius + e.deltaY * 0.05));
            updateCameraFromSpherical();
        });

        function updateCameraFromSpherical() {
            camera.position.x = spherical.radius * Math.sin(spherical.phi) * Math.cos(spherical.theta);
            camera.position.y = spherical.radius * Math.cos(spherical.phi);
            camera.position.z = spherical.radius * Math.sin(spherical.phi) * Math.sin(spherical.theta);
            camera.lookAt(0, 0, 0);
        }
    }

    // Modo de vista
    function setViewMode(mode) {
        viewMode = mode;
        document.querySelectorAll('.visor-controls .control-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.getElementById('btn-' + mode).classList.add('active');

        if (mode === 'top') {
            camera.position.set(0, 25, 0);
            camera.lookAt(0, 0, 0);
        } else if (mode === 'walk') {
            camera.position.set(player.x, player.y, player.z);
        } else {
            camera.position.set(15, 15, 15);
            camera.lookAt(0, 0, 0);
        }
    }

    // Toggle fullscreen
    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        } else {
            document.exitFullscreen();
        }
    }

    // Toggle panel info
    function toggleInfoPanel() {
        document.getElementById('info-panel').classList.toggle('active');
    }

    // Resize handler
    function onWindowResize() {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    }

    // Movimiento en modo walk
    function updateWalkMode() {
        if (viewMode !== 'walk') return;

        const speed = 0.1;
        if (keys['w']) {
            player.x -= Math.sin(player.rotation) * speed;
            player.z -= Math.cos(player.rotation) * speed;
        }
        if (keys['s']) {
            player.x += Math.sin(player.rotation) * speed;
            player.z += Math.cos(player.rotation) * speed;
        }
        if (keys['a']) {
            player.x -= Math.cos(player.rotation) * speed;
            player.z += Math.sin(player.rotation) * speed;
        }
        if (keys['d']) {
            player.x += Math.cos(player.rotation) * speed;
            player.z -= Math.sin(player.rotation) * speed;
        }
        if (keys[' ']) player.y += speed;
        if (keys['shift']) player.y = Math.max(0.5, player.y - speed);

        camera.position.set(player.x, player.y, player.z);
    }

    // Update minimap
    function updateMinimap() {
        const canvas = document.getElementById('minimap-canvas');
        const ctx = canvas.getContext('2d');
        const scale = 8;

        canvas.width = 180;
        canvas.height = 180;

        ctx.fillStyle = '#1a1a2e';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Dibujar habitaciones
        ctx.strokeStyle = '#3b82f6';
        ctx.lineWidth = 2;
        rooms.forEach(room => {
            ctx.strokeRect(
                90 + room.x * scale - room.width * scale / 2,
                90 + room.z * scale - room.depth * scale / 2,
                room.width * scale,
                room.depth * scale
            );
        });

        // Marcador del jugador
        const marker = document.getElementById('player-marker');
        marker.style.left = (90 + camera.position.x * scale) + 'px';
        marker.style.top = (90 + camera.position.z * scale) + 'px';
    }

    // Animation loop
    function animate() {
        requestAnimationFrame(animate);
        updateWalkMode();
        updateMinimap();
        renderer.render(scene, camera);
    }

    // Iniciar
    document.addEventListener('DOMContentLoaded', initScene);
    </script>
</body>
</html>
