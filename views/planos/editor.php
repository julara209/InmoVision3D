<?php
/**
 * Editor de Planos 2D - InmoVision3D
 * Permite dibujar planos que se convierten a 3D
 */
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Inmueble.php';
require_once __DIR__ . '/../../models/Plano.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . 'views/auth/login.php');
    exit;
}

$inmuebleId = intval($_GET['id'] ?? 0);
$inmuebleModel = new Inmueble();
$planoModel = new Plano();

$inmueble = null;
$plano = null;

if ($inmuebleId) {
    $inmueble = $inmuebleModel->obtenerPorId($inmuebleId);
    if ($inmueble && ($inmueble['usuario_id'] !== $_SESSION['usuario_id'] && $_SESSION['rol'] !== 'administrador')) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
    $plano = $planoModel->obtenerPorInmueble($inmuebleId);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor de Planos - InmoVision3D</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --editor-bg: #0f0f15;
            --toolbar-bg: #1a1a24;
            --panel-bg: #15151f;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --grid-color: #2a2a3a;
            --wall-color: #f5f5f5;
            --text-primary: #ffffff;
            --text-secondary: #9ca3af;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--editor-bg);
            color: var(--text-primary);
            overflow: hidden;
            height: 100vh;
        }

        .editor-layout {
            display: grid;
            grid-template-columns: 60px 1fr 300px;
            grid-template-rows: 60px 1fr;
            height: 100vh;
        }

        /* Header */
        .editor-header {
            grid-column: 1 / -1;
            background: var(--toolbar-bg);
            border-bottom: 1px solid var(--grid-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .header-left .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 700;
        }

        .header-left .logo i {
            color: var(--accent);
        }

        .project-name {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .header-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--grid-color);
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.05);
            color: var(--text-primary);
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
        }

        /* Toolbar */
        .toolbar {
            background: var(--toolbar-bg);
            border-right: 1px solid var(--grid-color);
            display: flex;
            flex-direction: column;
            padding: 0.5rem;
            gap: 0.25rem;
        }

        .tool-btn {
            width: 44px;
            height: 44px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.125rem;
            transition: all 0.2s ease;
            position: relative;
        }

        .tool-btn:hover {
            background: rgba(255,255,255,0.05);
            color: var(--text-primary);
        }

        .tool-btn.active {
            background: var(--accent);
            color: white;
        }

        .tool-btn::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            margin-left: 0.5rem;
            padding: 0.375rem 0.625rem;
            background: var(--panel-bg);
            border: 1px solid var(--grid-color);
            border-radius: 6px;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }

        .tool-btn:hover::after {
            opacity: 1;
        }

        .tool-divider {
            height: 1px;
            background: var(--grid-color);
            margin: 0.5rem 0;
        }

        /* Canvas Area */
        .canvas-container {
            position: relative;
            overflow: hidden;
            background: var(--editor-bg);
        }

        #editor-canvas {
            position: absolute;
            top: 0;
            left: 0;
            cursor: crosshair;
        }

        #grid-canvas {
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none;
        }

        /* Sidebar */
        .sidebar {
            background: var(--panel-bg);
            border-left: 1px solid var(--grid-color);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .sidebar-tabs {
            display: flex;
            border-bottom: 1px solid var(--grid-color);
        }

        .tab-btn {
            flex: 1;
            padding: 1rem;
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.2s ease;
            border-bottom: 2px solid transparent;
        }

        .tab-btn:hover {
            color: var(--text-primary);
        }

        .tab-btn.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }

        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }

        .panel-section {
            margin-bottom: 1.5rem;
        }

        .panel-section h4 {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: 0.75rem;
        }

        /* Room List */
        .room-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--grid-color);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .room-item:hover {
            background: rgba(255,255,255,0.05);
        }

        .room-item.selected {
            border-color: var(--accent);
            background: rgba(59, 130, 246, 0.1);
        }

        .room-color {
            width: 24px;
            height: 24px;
            border-radius: 6px;
        }

        .room-info {
            flex: 1;
        }

        .room-name {
            font-size: 0.875rem;
            font-weight: 500;
        }

        .room-size {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .room-delete {
            width: 24px;
            height: 24px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            border-radius: 4px;
        }

        .room-delete:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        /* Properties Form */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: 0.375rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.625rem 0.75rem;
            background: var(--editor-bg);
            border: 1px solid var(--grid-color);
            border-radius: 6px;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .color-picker {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .color-option {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .color-option:hover,
        .color-option.selected {
            border-color: white;
            transform: scale(1.1);
        }

        /* Preview 3D */
        .preview-3d {
            height: 200px;
            background: var(--editor-bg);
            border-radius: 8px;
            border: 1px solid var(--grid-color);
            overflow: hidden;
            position: relative;
        }

        .preview-3d canvas {
            width: 100%;
            height: 100%;
        }

        .preview-overlay {
            position: absolute;
            bottom: 0.5rem;
            right: 0.5rem;
            display: flex;
            gap: 0.25rem;
        }

        .preview-btn {
            width: 28px;
            height: 28px;
            border: none;
            background: rgba(0,0,0,0.5);
            color: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.75rem;
        }

        /* Status Bar */
        .status-bar {
            position: fixed;
            bottom: 0;
            left: 60px;
            right: 300px;
            height: 32px;
            background: var(--toolbar-bg);
            border-top: 1px solid var(--grid-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .status-info {
            display: flex;
            gap: 1.5rem;
        }

        .status-info span {
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        /* Zoom Controls */
        .zoom-controls {
            position: absolute;
            bottom: 3rem;
            right: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .zoom-btn {
            width: 36px;
            height: 36px;
            border: none;
            background: var(--toolbar-bg);
            color: var(--text-secondary);
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
        }

        .zoom-btn:hover {
            background: var(--panel-bg);
            color: var(--text-primary);
        }

        /* Room type icons */
        .room-type-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
        }

        .room-type-btn {
            aspect-ratio: 1;
            border: 1px solid var(--grid-color);
            background: transparent;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            color: var(--text-secondary);
            font-size: 0.625rem;
            transition: all 0.2s ease;
        }

        .room-type-btn i {
            font-size: 1.25rem;
        }

        .room-type-btn:hover,
        .room-type-btn.active {
            border-color: var(--accent);
            color: var(--accent);
            background: rgba(59, 130, 246, 0.1);
        }
    </style>
</head>
<body>
    <div class="editor-layout">
        <!-- Header -->
        <header class="editor-header">
            <div class="header-left">
                <a href="<?php echo BASE_URL; ?>index.php" class="logo">
                    <i class="fas fa-building"></i>
                    <span>InmoVision3D</span>
                </a>
                <span class="project-name">
                    <?php echo $inmueble ? htmlspecialchars($inmueble['titulo']) : 'Nuevo Plano'; ?>
                </span>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="clearCanvas()">
                    <i class="fas fa-trash"></i> Limpiar
                </button>
                <button class="btn btn-secondary" onclick="undoAction()">
                    <i class="fas fa-undo"></i> Deshacer
                </button>
                <button class="btn btn-primary" onclick="savePlano()">
                    <i class="fas fa-save"></i> Guardar
                </button>
                <?php if ($inmueble): ?>
                <a href="<?php echo BASE_URL; ?>views/planos/visor3d.php?id=<?php echo $inmuebleId; ?>" class="btn btn-primary">
                    <i class="fas fa-cube"></i> Ver en 3D
                </a>
                <?php endif; ?>
            </div>
        </header>

        <!-- Toolbar -->
        <aside class="toolbar">
            <button class="tool-btn active" data-tool="select" data-tooltip="Seleccionar (V)" onclick="setTool('select')">
                <i class="fas fa-mouse-pointer"></i>
            </button>
            <button class="tool-btn" data-tool="room" data-tooltip="Dibujar Habitacion (R)" onclick="setTool('room')">
                <i class="fas fa-vector-square"></i>
            </button>
            <button class="tool-btn" data-tool="wall" data-tooltip="Dibujar Pared (W)" onclick="setTool('wall')">
                <i class="fas fa-minus"></i>
            </button>
            <div class="tool-divider"></div>
            <button class="tool-btn" data-tool="door" data-tooltip="Puerta (D)" onclick="setTool('door')">
                <i class="fas fa-door-open"></i>
            </button>
            <button class="tool-btn" data-tool="window" data-tooltip="Ventana (N)" onclick="setTool('window')">
                <i class="fas fa-border-none"></i>
            </button>
            <div class="tool-divider"></div>
            <button class="tool-btn" data-tool="furniture" data-tooltip="Muebles (F)" onclick="setTool('furniture')">
                <i class="fas fa-couch"></i>
            </button>
            <div class="tool-divider"></div>
            <button class="tool-btn" data-tool="measure" data-tooltip="Medir (M)" onclick="setTool('measure')">
                <i class="fas fa-ruler"></i>
            </button>
        </aside>

        <!-- Canvas -->
        <div class="canvas-container" id="canvas-container">
            <canvas id="grid-canvas"></canvas>
            <canvas id="editor-canvas"></canvas>
            <div class="zoom-controls">
                <button class="zoom-btn" onclick="zoomIn()"><i class="fas fa-plus"></i></button>
                <button class="zoom-btn" onclick="zoomOut()"><i class="fas fa-minus"></i></button>
                <button class="zoom-btn" onclick="resetView()"><i class="fas fa-crosshairs"></i></button>
            </div>
        </div>

        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-tabs">
                <button class="tab-btn active" onclick="switchTab('rooms')">Habitaciones</button>
                <button class="tab-btn" onclick="switchTab('properties')">Propiedades</button>
                <button class="tab-btn" onclick="switchTab('preview')">Vista 3D</button>
            </div>
            <div class="sidebar-content">
                <!-- Tab: Rooms -->
                <div class="tab-content" id="tab-rooms">
                    <div class="panel-section">
                        <h4>Tipo de Habitacion</h4>
                        <div class="room-type-grid">
                            <button class="room-type-btn active" data-type="sala" onclick="setRoomType('sala')">
                                <i class="fas fa-couch"></i>
                                <span>Sala</span>
                            </button>
                            <button class="room-type-btn" data-type="cocina" onclick="setRoomType('cocina')">
                                <i class="fas fa-utensils"></i>
                                <span>Cocina</span>
                            </button>
                            <button class="room-type-btn" data-type="habitacion" onclick="setRoomType('habitacion')">
                                <i class="fas fa-bed"></i>
                                <span>Habitacion</span>
                            </button>
                            <button class="room-type-btn" data-type="bano" onclick="setRoomType('bano')">
                                <i class="fas fa-bath"></i>
                                <span>Bano</span>
                            </button>
                            <button class="room-type-btn" data-type="comedor" onclick="setRoomType('comedor')">
                                <i class="fas fa-chair"></i>
                                <span>Comedor</span>
                            </button>
                            <button class="room-type-btn" data-type="otro" onclick="setRoomType('otro')">
                                <i class="fas fa-door-open"></i>
                                <span>Otro</span>
                            </button>
                        </div>
                    </div>
                    <div class="panel-section">
                        <h4>Habitaciones (<span id="room-count">0</span>)</h4>
                        <div id="room-list">
                            <!-- Se llena dinamicamente -->
                        </div>
                    </div>
                </div>

                <!-- Tab: Properties -->
                <div class="tab-content" id="tab-properties" style="display:none;">
                    <div class="panel-section">
                        <h4>Habitacion Seleccionada</h4>
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" id="prop-name" placeholder="Nombre de la habitacion">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Ancho (m)</label>
                                <input type="number" id="prop-width" step="0.1" min="0.5">
                            </div>
                            <div class="form-group">
                                <label>Alto (m)</label>
                                <input type="number" id="prop-depth" step="0.1" min="0.5">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Color del Piso</label>
                            <div class="color-picker">
                                <div class="color-option selected" style="background:#8B4513" data-color="#8B4513"></div>
                                <div class="color-option" style="background:#D2B48C" data-color="#D2B48C"></div>
                                <div class="color-option" style="background:#808080" data-color="#808080"></div>
                                <div class="color-option" style="background:#F5F5DC" data-color="#F5F5DC"></div>
                                <div class="color-option" style="background:#2F4F4F" data-color="#2F4F4F"></div>
                            </div>
                        </div>
                    </div>
                    <div class="panel-section">
                        <h4>Pared Seleccionada</h4>
                        <div class="form-group">
                            <label>Altura (m)</label>
                            <input type="number" id="prop-wall-height" value="2.8" step="0.1" min="2" max="5">
                        </div>
                        <div class="form-group">
                            <label>Grosor (cm)</label>
                            <input type="number" id="prop-wall-thickness" value="15" step="1" min="10" max="30">
                        </div>
                    </div>
                </div>

                <!-- Tab: Preview -->
                <div class="tab-content" id="tab-preview" style="display:none;">
                    <div class="panel-section">
                        <h4>Vista Previa 3D</h4>
                        <div class="preview-3d" id="preview-container">
                            <canvas id="preview-canvas"></canvas>
                            <div class="preview-overlay">
                                <button class="preview-btn" onclick="rotatePreview(-1)"><i class="fas fa-undo"></i></button>
                                <button class="preview-btn" onclick="rotatePreview(1)"><i class="fas fa-redo"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="panel-section">
                        <h4>Opciones de Exportacion</h4>
                        <div class="form-group">
                            <label>Altura de Paredes</label>
                            <select id="export-wall-height">
                                <option value="2.4">2.4 metros</option>
                                <option value="2.8" selected>2.8 metros</option>
                                <option value="3.0">3.0 metros</option>
                                <option value="3.5">3.5 metros</option>
                            </select>
                        </div>
                        <button class="btn btn-primary" style="width:100%;" onclick="generate3D()">
                            <i class="fas fa-cube"></i> Generar Modelo 3D
                        </button>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <!-- Status Bar -->
    <div class="status-bar">
        <div class="status-info">
            <span><i class="fas fa-mouse-pointer"></i> <span id="cursor-pos">0, 0</span></span>
            <span><i class="fas fa-ruler"></i> <span id="selection-size">-</span></span>
            <span><i class="fas fa-th"></i> Grid: 0.5m</span>
        </div>
        <div class="status-info">
            <span id="zoom-level">100%</span>
        </div>
    </div>

    <!-- Three.js para preview -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    const inmuebleId = <?php echo $inmuebleId ?: 'null'; ?>;
    const existingPlanoData = <?php echo $plano && $plano['datos_3d'] ? $plano['datos_3d'] : 'null'; ?>;

    // Estado del editor
    let currentTool = 'select';
    let currentRoomType = 'sala';
    let rooms = [];
    let walls = [];
    let doors = [];
    let windows = [];
    let selectedElement = null;
    let isDrawing = false;
    let startPoint = null;
    let scale = 50; // pixels per meter
    let offset = { x: 0, y: 0 };
    let history = [];

    // Canvas setup
    const container = document.getElementById('canvas-container');
    const gridCanvas = document.getElementById('grid-canvas');
    const editorCanvas = document.getElementById('editor-canvas');
    const gridCtx = gridCanvas.getContext('2d');
    const ctx = editorCanvas.getContext('2d');

    // Room colors
    const roomColors = {
        sala: '#4a90d9',
        cocina: '#d94a4a',
        habitacion: '#4ad99a',
        bano: '#9a4ad9',
        comedor: '#d9a84a',
        otro: '#808080'
    };

    // Initialize
    function init() {
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);
        
        // Mouse events
        editorCanvas.addEventListener('mousedown', onMouseDown);
        editorCanvas.addEventListener('mousemove', onMouseMove);
        editorCanvas.addEventListener('mouseup', onMouseUp);
        editorCanvas.addEventListener('wheel', onWheel);

        // Keyboard shortcuts
        document.addEventListener('keydown', onKeyDown);

        // Load existing data
        if (existingPlanoData) {
            loadPlanoData(existingPlanoData);
        }

        drawGrid();
        render();
        initPreview3D();
    }

    function resizeCanvas() {
        const rect = container.getBoundingClientRect();
        gridCanvas.width = rect.width;
        gridCanvas.height = rect.height;
        editorCanvas.width = rect.width;
        editorCanvas.height = rect.height;
        offset.x = rect.width / 2;
        offset.y = rect.height / 2;
        drawGrid();
        render();
    }

    function drawGrid() {
        const w = gridCanvas.width;
        const h = gridCanvas.height;
        gridCtx.clearRect(0, 0, w, h);
        
        gridCtx.strokeStyle = '#1a1a2a';
        gridCtx.lineWidth = 1;

        const gridSize = scale * 0.5; // 0.5 meter grid
        const startX = offset.x % gridSize;
        const startY = offset.y % gridSize;

        // Minor grid
        for (let x = startX; x < w; x += gridSize) {
            gridCtx.beginPath();
            gridCtx.moveTo(x, 0);
            gridCtx.lineTo(x, h);
            gridCtx.stroke();
        }
        for (let y = startY; y < h; y += gridSize) {
            gridCtx.beginPath();
            gridCtx.moveTo(0, y);
            gridCtx.lineTo(w, y);
            gridCtx.stroke();
        }

        // Major grid (1 meter)
        gridCtx.strokeStyle = '#2a2a3a';
        const majorGridSize = scale;
        const majorStartX = offset.x % majorGridSize;
        const majorStartY = offset.y % majorGridSize;

        for (let x = majorStartX; x < w; x += majorGridSize) {
            gridCtx.beginPath();
            gridCtx.moveTo(x, 0);
            gridCtx.lineTo(x, h);
            gridCtx.stroke();
        }
        for (let y = majorStartY; y < h; y += majorGridSize) {
            gridCtx.beginPath();
            gridCtx.moveTo(0, y);
            gridCtx.lineTo(w, y);
            gridCtx.stroke();
        }

        // Center axes
        gridCtx.strokeStyle = '#3a3a4a';
        gridCtx.lineWidth = 2;
        gridCtx.beginPath();
        gridCtx.moveTo(offset.x, 0);
        gridCtx.lineTo(offset.x, h);
        gridCtx.moveTo(0, offset.y);
        gridCtx.lineTo(w, offset.y);
        gridCtx.stroke();
    }

    function render() {
        const w = editorCanvas.width;
        const h = editorCanvas.height;
        ctx.clearRect(0, 0, w, h);

        // Draw rooms
        rooms.forEach((room, index) => {
            const x = offset.x + room.x * scale;
            const y = offset.y + room.z * scale;
            const width = room.width * scale;
            const depth = room.depth * scale;

            // Fill
            ctx.fillStyle = roomColors[room.type] + '40';
            ctx.fillRect(x - width/2, y - depth/2, width, depth);

            // Stroke
            ctx.strokeStyle = room === selectedElement ? '#ffffff' : roomColors[room.type];
            ctx.lineWidth = room === selectedElement ? 3 : 2;
            ctx.strokeRect(x - width/2, y - depth/2, width, depth);

            // Label
            ctx.fillStyle = '#ffffff';
            ctx.font = '12px Inter';
            ctx.textAlign = 'center';
            ctx.fillText(room.name, x, y);
            ctx.font = '10px Inter';
            ctx.fillStyle = '#888888';
            ctx.fillText(`${room.width}m x ${room.depth}m`, x, y + 14);
        });

        // Draw walls
        walls.forEach(wall => {
            ctx.strokeStyle = wall === selectedElement ? '#ffffff' : '#f5f5f5';
            ctx.lineWidth = 8;
            ctx.beginPath();
            ctx.moveTo(offset.x + wall.x1 * scale, offset.y + wall.z1 * scale);
            ctx.lineTo(offset.x + wall.x2 * scale, offset.y + wall.z2 * scale);
            ctx.stroke();
        });

        // Draw doors
        doors.forEach(door => {
            const x = offset.x + door.x * scale;
            const y = offset.y + door.z * scale;
            ctx.fillStyle = '#8B4513';
            ctx.fillRect(x - 15, y - 5, 30, 10);
        });

        // Draw windows
        windows.forEach(win => {
            const x = offset.x + win.x * scale;
            const y = offset.y + win.z * scale;
            ctx.fillStyle = '#87CEEB';
            ctx.fillRect(x - 20, y - 3, 40, 6);
        });

        // Drawing preview
        if (isDrawing && startPoint) {
            const endPoint = lastMousePos;
            if (currentTool === 'room') {
                const x = Math.min(startPoint.x, endPoint.x);
                const y = Math.min(startPoint.y, endPoint.y);
                const w = Math.abs(endPoint.x - startPoint.x);
                const h = Math.abs(endPoint.y - startPoint.y);
                
                ctx.fillStyle = roomColors[currentRoomType] + '40';
                ctx.fillRect(x, y, w, h);
                ctx.strokeStyle = roomColors[currentRoomType];
                ctx.lineWidth = 2;
                ctx.setLineDash([5, 5]);
                ctx.strokeRect(x, y, w, h);
                ctx.setLineDash([]);
            } else if (currentTool === 'wall') {
                ctx.strokeStyle = '#f5f5f5';
                ctx.lineWidth = 8;
                ctx.setLineDash([5, 5]);
                ctx.beginPath();
                ctx.moveTo(startPoint.x, startPoint.y);
                ctx.lineTo(endPoint.x, endPoint.y);
                ctx.stroke();
                ctx.setLineDash([]);
            }
        }

        updateRoomList();
    }

    let lastMousePos = { x: 0, y: 0 };

    function onMouseDown(e) {
        const rect = editorCanvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        if (currentTool === 'room' || currentTool === 'wall') {
            isDrawing = true;
            startPoint = { x, y };
        } else if (currentTool === 'select') {
            // Check for selection
            selectedElement = null;
            rooms.forEach(room => {
                const rx = offset.x + room.x * scale;
                const ry = offset.y + room.z * scale;
                if (x >= rx - room.width * scale / 2 && x <= rx + room.width * scale / 2 &&
                    y >= ry - room.depth * scale / 2 && y <= ry + room.depth * scale / 2) {
                    selectedElement = room;
                }
            });
            render();
        } else if (currentTool === 'door' || currentTool === 'window') {
            const worldX = (x - offset.x) / scale;
            const worldZ = (y - offset.y) / scale;
            if (currentTool === 'door') {
                doors.push({ x: worldX, z: worldZ });
            } else {
                windows.push({ x: worldX, z: worldZ });
            }
            saveHistory();
            render();
        }
    }

    function onMouseMove(e) {
        const rect = editorCanvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        lastMousePos = { x, y };

        // Update cursor position
        const worldX = ((x - offset.x) / scale).toFixed(2);
        const worldZ = ((y - offset.y) / scale).toFixed(2);
        document.getElementById('cursor-pos').textContent = `${worldX}, ${worldZ}`;

        if (isDrawing && startPoint) {
            // Update selection size
            const width = Math.abs(x - startPoint.x) / scale;
            const depth = Math.abs(y - startPoint.y) / scale;
            document.getElementById('selection-size').textContent = `${width.toFixed(1)}m x ${depth.toFixed(1)}m`;
            render();
        }
    }

    function onMouseUp(e) {
        if (!isDrawing || !startPoint) return;

        const rect = editorCanvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        if (currentTool === 'room') {
            const roomX = ((startPoint.x + x) / 2 - offset.x) / scale;
            const roomZ = ((startPoint.y + y) / 2 - offset.y) / scale;
            const width = Math.abs(x - startPoint.x) / scale;
            const depth = Math.abs(y - startPoint.y) / scale;

            if (width > 0.5 && depth > 0.5) {
                const roomNumber = rooms.filter(r => r.type === currentRoomType).length + 1;
                rooms.push({
                    name: currentRoomType.charAt(0).toUpperCase() + currentRoomType.slice(1) + ' ' + roomNumber,
                    type: currentRoomType,
                    x: roomX,
                    z: roomZ,
                    width: Math.round(width * 2) / 2,
                    depth: Math.round(depth * 2) / 2,
                    doors: [],
                    windows: []
                });
                saveHistory();
            }
        } else if (currentTool === 'wall') {
            const x1 = (startPoint.x - offset.x) / scale;
            const z1 = (startPoint.y - offset.y) / scale;
            const x2 = (x - offset.x) / scale;
            const z2 = (y - offset.y) / scale;

            const length = Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(z2 - z1, 2));
            if (length > 0.3) {
                walls.push({ x1, z1, x2, z2 });
                saveHistory();
            }
        }

        isDrawing = false;
        startPoint = null;
        document.getElementById('selection-size').textContent = '-';
        render();
        updatePreview3D();
    }

    function onWheel(e) {
        e.preventDefault();
        const delta = e.deltaY > 0 ? 0.9 : 1.1;
        scale = Math.max(20, Math.min(200, scale * delta));
        document.getElementById('zoom-level').textContent = Math.round(scale * 2) + '%';
        drawGrid();
        render();
    }

    function onKeyDown(e) {
        if (e.key === 'v') setTool('select');
        if (e.key === 'r') setTool('room');
        if (e.key === 'w') setTool('wall');
        if (e.key === 'd') setTool('door');
        if (e.key === 'n') setTool('window');
        if (e.key === 'Delete' && selectedElement) {
            const index = rooms.indexOf(selectedElement);
            if (index > -1) {
                rooms.splice(index, 1);
                selectedElement = null;
                saveHistory();
                render();
            }
        }
        if (e.ctrlKey && e.key === 'z') {
            undoAction();
        }
    }

    function setTool(tool) {
        currentTool = tool;
        document.querySelectorAll('.tool-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tool === tool);
        });
        editorCanvas.style.cursor = tool === 'select' ? 'default' : 'crosshair';
    }

    function setRoomType(type) {
        currentRoomType = type;
        document.querySelectorAll('.room-type-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.type === type);
        });
    }

    function switchTab(tabName) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
        
        document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.add('active');
        document.getElementById('tab-' + tabName).style.display = 'block';

        if (tabName === 'preview') {
            updatePreview3D();
        }
    }

    function updateRoomList() {
        document.getElementById('room-count').textContent = rooms.length;
        const list = document.getElementById('room-list');
        list.innerHTML = rooms.map((room, index) => `
            <div class="room-item ${room === selectedElement ? 'selected' : ''}" onclick="selectRoom(${index})">
                <div class="room-color" style="background:${roomColors[room.type]}"></div>
                <div class="room-info">
                    <div class="room-name">${room.name}</div>
                    <div class="room-size">${room.width}m x ${room.depth}m</div>
                </div>
                <button class="room-delete" onclick="deleteRoom(${index}); event.stopPropagation();">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
    }

    function selectRoom(index) {
        selectedElement = rooms[index];
        render();
    }

    function deleteRoom(index) {
        rooms.splice(index, 1);
        selectedElement = null;
        saveHistory();
        render();
        updatePreview3D();
    }

    function saveHistory() {
        history.push(JSON.stringify({ rooms, walls, doors, windows }));
        if (history.length > 50) history.shift();
    }

    function undoAction() {
        if (history.length > 0) {
            const state = JSON.parse(history.pop());
            rooms = state.rooms;
            walls = state.walls;
            doors = state.doors;
            windows = state.windows;
            render();
            updatePreview3D();
        }
    }

    function clearCanvas() {
        if (confirm('Esta seguro de que desea limpiar todo el plano?')) {
            saveHistory();
            rooms = [];
            walls = [];
            doors = [];
            windows = [];
            selectedElement = null;
            render();
            updatePreview3D();
        }
    }

    function zoomIn() {
        scale = Math.min(200, scale * 1.2);
        document.getElementById('zoom-level').textContent = Math.round(scale * 2) + '%';
        drawGrid();
        render();
    }

    function zoomOut() {
        scale = Math.max(20, scale * 0.8);
        document.getElementById('zoom-level').textContent = Math.round(scale * 2) + '%';
        drawGrid();
        render();
    }

    function resetView() {
        offset.x = editorCanvas.width / 2;
        offset.y = editorCanvas.height / 2;
        scale = 50;
        document.getElementById('zoom-level').textContent = '100%';
        drawGrid();
        render();
    }

    function loadPlanoData(data) {
        if (data.rooms) rooms = data.rooms;
        if (data.walls) walls = data.walls;
        if (data.doors) doors = data.doors || [];
        if (data.windows) windows = data.windows || [];
    }

    function getPlanoData() {
        return {
            rooms: rooms,
            walls: walls,
            doors: doors,
            windows: windows
        };
    }

    async function savePlano() {
        const data = getPlanoData();
        
        try {
            const response = await fetch(BASE_URL + 'api/planos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'save',
                    inmueble_id: inmuebleId,
                    datos_3d: data
                })
            });

            const result = await response.json();
            if (result.success) {
                alert('Plano guardado correctamente');
            } else {
                alert('Error: ' + (result.error || 'No se pudo guardar'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al guardar el plano');
        }
    }

    // Preview 3D
    let previewScene, previewCamera, previewRenderer;

    function initPreview3D() {
        const container = document.getElementById('preview-container');
        const canvas = document.getElementById('preview-canvas');

        previewScene = new THREE.Scene();
        previewScene.background = new THREE.Color(0x0f0f15);

        previewCamera = new THREE.PerspectiveCamera(60, 280 / 200, 0.1, 1000);
        previewCamera.position.set(10, 10, 10);
        previewCamera.lookAt(0, 0, 0);

        previewRenderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true });
        previewRenderer.setSize(280, 200);

        const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
        previewScene.add(ambientLight);

        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(5, 10, 5);
        previewScene.add(directionalLight);

        updatePreview3D();
        animatePreview();
    }

    function updatePreview3D() {
        if (!previewScene) return;

        // Clear existing objects
        while (previewScene.children.length > 2) {
            previewScene.remove(previewScene.children[2]);
        }

        const wallHeight = parseFloat(document.getElementById('export-wall-height')?.value || 2.8);

        // Add floor
        const floorGeometry = new THREE.PlaneGeometry(20, 20);
        const floorMaterial = new THREE.MeshStandardMaterial({ color: 0x2d3748 });
        const floor = new THREE.Mesh(floorGeometry, floorMaterial);
        floor.rotation.x = -Math.PI / 2;
        previewScene.add(floor);

        // Add rooms
        rooms.forEach(room => {
            // Room floor
            const roomFloor = new THREE.Mesh(
                new THREE.PlaneGeometry(room.width, room.depth),
                new THREE.MeshStandardMaterial({ color: roomColors[room.type].replace('#', '0x') })
            );
            roomFloor.rotation.x = -Math.PI / 2;
            roomFloor.position.set(room.x, 0.01, room.z);
            previewScene.add(roomFloor);

            // Walls
            const wallMaterial = new THREE.MeshStandardMaterial({ color: 0xf5f5f5 });
            const wallThickness = 0.15;

            // Front/Back walls
            [[-1, room.depth / 2], [1, room.depth / 2]].forEach(([sign, pos]) => {
                const wall = new THREE.Mesh(
                    new THREE.BoxGeometry(room.width, wallHeight, wallThickness),
                    wallMaterial
                );
                wall.position.set(room.x, wallHeight / 2, room.z + sign * pos);
                previewScene.add(wall);
            });

            // Left/Right walls
            [[-1, room.width / 2], [1, room.width / 2]].forEach(([sign, pos]) => {
                const wall = new THREE.Mesh(
                    new THREE.BoxGeometry(wallThickness, wallHeight, room.depth),
                    wallMaterial
                );
                wall.position.set(room.x + sign * pos, wallHeight / 2, room.z);
                previewScene.add(wall);
            });
        });
    }

    let previewRotation = 0;
    function animatePreview() {
        requestAnimationFrame(animatePreview);
        previewRotation += 0.002;
        previewCamera.position.x = 15 * Math.cos(previewRotation);
        previewCamera.position.z = 15 * Math.sin(previewRotation);
        previewCamera.lookAt(0, 0, 0);
        previewRenderer.render(previewScene, previewCamera);
    }

    function rotatePreview(direction) {
        previewRotation += direction * 0.5;
    }

    function generate3D() {
        if (inmuebleId) {
            window.location.href = BASE_URL + 'views/planos/visor3d.php?id=' + inmuebleId;
        } else {
            alert('Primero debes guardar el plano asociado a un inmueble');
        }
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>
