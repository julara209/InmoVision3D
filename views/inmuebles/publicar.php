<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Inmueble.php';
require_once __DIR__ . '/../../models/Plano.php';

// Verificar autenticación y rol
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . 'views/auth/login.php');
    exit;
}

if ($_SESSION['rol'] === 'cliente') {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$inmuebleModel = new Inmueble();
$planoModel = new Plano();

$mensaje = '';
$error = '';
$editMode = false;
$inmueble = null;

// Modo edición
if (isset($_GET['edit'])) {
    $editMode = true;
    $inmueble = $inmuebleModel->obtenerPorId(intval($_GET['edit']));
    if (!$inmueble || ($inmueble['idPublicador'] !== $_SESSION['idPublicador'] && $_SESSION['rol'] !== 'administrador')) {
        header('Location: ' . BASE_URL . 'views/inmuebles/listar.php');
        exit;
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'titulo' => trim($_POST['titulo']),
        'descripcion' => trim($_POST['descripcion']),
        'tipo' => $_POST['tipo'],
        'estado' => $_POST['estado'],
        'precio' => floatval($_POST['precio']),
        'area' => floatval($_POST['area']),
        'habitaciones' => intval($_POST['habitaciones']),
        'banos' => intval($_POST['banos']),
        'idPublicador' => $_SESSION['idPublicador']
    ];

    // Validaciones
    if (empty($datos['titulo']) || empty($datos['descripcion'])) {
        $error = 'Por favor complete todos los campos obligatorios';
    } elseif ($datos['precio'] <= 0) {
        $error = 'El precio debe ser mayor a 0';
    } else {
        // Procesar imagen principal
        if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['imagen_principal']['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($ext, $permitidas)) {
                $nombreArchivo = 'inmueble_' . time() . '_' . uniqid() . '.' . $ext;
                $rutaDestino = __DIR__ . '/../../assets/uploads/inmuebles/' . $nombreArchivo;
                
                if (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $rutaDestino)) {
                    $datos['imagen_principal'] = $nombreArchivo;
                }
            }
        }

        if ($editMode) {
            // Actualizar inmueble
            if ($inmuebleModel->actualizar($inmueble['idInmueble'], $datos)) {
                $inmuebleId = $inmueble['idInmueble'];
                $mensaje = 'Inmueble actualizado correctamente';
            } else {
                $error = 'Error al actualizar el inmueble';
            }
        } else {
            // Crear nuevo inmueble
            $inmuebleId = $inmuebleModel->crear($datos);
            if ($inmuebleId) {
                $mensaje = 'Inmueble publicado correctamente';
            } else {
                $error = 'Error al publicar el inmueble';
            }
        }

        // Procesar imágenes adicionales
        if (!$error && isset($inmuebleId) && isset($_FILES['imagenes_adicionales'])) {
            $files = $_FILES['imagenes_adicionales'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
                    
                    if (in_array($ext, $permitidas)) {
                        $nombreArchivo = 'inmueble_' . $inmuebleId . '_' . time() . '_' . $i . '.' . $ext;
                        $rutaDestino = __DIR__ . '/../../assets/uploads/inmuebles/' . $nombreArchivo;
                        
                        if (move_uploaded_file($files['tmp_name'][$i], $rutaDestino)) {
                            $inmuebleModel->agregarImagen($inmuebleId, $nombreArchivo);
                        }
                    }
                }
            }
        }

        // Procesar plano 2D
        if (!$error && isset($inmuebleId) && isset($_FILES['plano_2d']) && $_FILES['plano_2d']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['plano_2d']['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'svg', 'pdf'];
            
            if (in_array($ext, $permitidas)) {
                $nombrePlano = 'plano_' . $inmuebleId . '_' . time() . '.' . $ext;
                $rutaPlano = __DIR__ . '/../../assets/uploads/planos/' . $nombrePlano;
                
                if (move_uploaded_file($_FILES['plano_2d']['tmp_name'], $rutaPlano)) {
                    // Crear registro de plano
                    $planoData = [
                        'inmueble_id' => $inmuebleId,
                        'archivo_2d' => $nombrePlano,
                        'tipo' => 'subido'
                    ];
                    $planoModel->crear($planoData);
                    
                    // Marcar inmueble como que tiene plano
                    $inmuebleModel->actualizar($inmuebleId, ['tiene_plano_3d' => 1]);
                }
            }
        }

        if ($mensaje && !$editMode) {
            // Redirigir a la pagina del inmueble
            header('Location: ' . BASE_URL . 'views/inmuebles/detalle.php?id=' . $inmuebleId . '&nuevo=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editMode ? 'Editar' : 'Publicar'; ?> Inmueble - InmoVision3D</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .publicar-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .publicar-form {
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .form-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
        }

        .form-header h1 {
            font-size: 1.5rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-header h1 i {
            color: var(--accent-primary);
        }

        .form-body {
            padding: 2rem;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section-title {
            font-size: 1.125rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-section-title i {
            color: var(--accent-primary);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .form-grid-3 {
            grid-template-columns: repeat(3, 1fr);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .form-group label .required {
            color: var(--accent-danger);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.875rem 1rem;
            background: var(--bg-dark);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: border-color 0.2s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent-primary);
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1rem;
            background: var(--bg-dark);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
        }

        .checkbox-group input {
            width: auto;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
        }

        /* Upload Area */
        .upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .upload-area:hover,
        .upload-area.dragover {
            border-color: var(--accent-primary);
            background: rgba(59, 130, 246, 0.05);
        }

        .upload-area i {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .upload-area h4 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .upload-area p {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .upload-area input {
            display: none;
        }

        .upload-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .preview-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
        }

        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-item .remove-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 24px;
            height: 24px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }

        /* Plano Section */
        .plano-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .plano-option {
            padding: 1.5rem;
            background: var(--bg-dark);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s ease;
        }

        .plano-option:hover,
        .plano-option.active {
            border-color: var(--accent-primary);
            background: rgba(59, 130, 246, 0.05);
        }

        .plano-option i {
            font-size: 2rem;
            color: var(--accent-primary);
            margin-bottom: 0.75rem;
        }

        .plano-option h4 {
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .plano-option p {
            color: var(--text-muted);
            font-size: 0.75rem;
        }

        .plano-upload,
        .plano-draw {
            display: none;
        }

        .plano-upload.active,
        .plano-draw.active {
            display: block;
        }

        /* Form Footer */
        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--border-color);
            background: rgba(0, 0, 0, 0.2);
        }

        .form-footer .btn-primary {
            padding: 1rem 2rem;
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .form-grid,
            .form-grid-3 {
                grid-template-columns: 1fr;
            }

            .plano-options {
                grid-template-columns: 1fr;
            }

            .form-footer {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="<?php echo BASE_URL; ?>index.php" class="logo">
                <i class="fas fa-building"></i>
                <span>InmoVision<span class="highlight">3D</span></span>
            </a>
            <nav class="nav-menu">
                <a href="<?php echo BASE_URL; ?>index.php">Inicio</a>
                <a href="<?php echo BASE_URL; ?>views/inmuebles/listar.php">Inmuebles</a>
                <a href="<?php echo BASE_URL; ?>views/usuario/favoritos.php">Favoritos</a>
                <a href="<?php echo BASE_URL; ?>views/inmuebles/publicar.php" class="active">Publicar</a>
                <?php if ($_SESSION['rol'] === 'administrador'): ?>
                <a href="<?php echo BASE_URL; ?>views/admin/dashboard.php">Admin</a>
                <?php endif; ?>
            </nav>
            <div class="header-actions">
                <a href="<?php echo BASE_URL; ?>views/usuario/perfil.php" class="btn-user">
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                </a>
                <a href="<?php echo BASE_URL; ?>controllers/AuthController.php?action=logout" class="btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="publicar-container">
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php if ($mensaje): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $mensaje; ?>
            </div>
            <?php endif; ?>

            <form class="publicar-form" method="POST" enctype="multipart/form-data">
                <div class="form-header">
                    <h1>
                        <i class="fas fa-<?php echo $editMode ? 'edit' : 'plus-circle'; ?>"></i>
                        <?php echo $editMode ? 'Editar Inmueble' : 'Publicar Nuevo Inmueble'; ?>
                    </h1>
                </div>

                <div class="form-body">
                    <!-- Información Básica -->
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-info-circle"></i> Informacion Basica
                        </h3>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="titulo">Titulo del Inmueble <span class="required">*</span></label>
                                <input type="text" id="titulo" name="titulo" required
                                       value="<?php echo htmlspecialchars($inmueble['titulo'] ?? ''); ?>"
                                       placeholder="Ej: Hermoso apartamento en el centro">
                            </div>
                            <div class="form-group">
                                <label for="tipo">Tipo de Inmueble <span class="required">*</span></label>
                                <select id="tipo" name="tipo" required>
                                    <option value="">Seleccionar tipo</option>
                                    <option value="casa" <?php echo ($inmueble['tipo'] ?? '') === 'casa' ? 'selected' : ''; ?>>Casa</option>
                                    <option value="apartamento" <?php echo ($inmueble['tipo'] ?? '') === 'apartamento' ? 'selected' : ''; ?>>Apartamento</option>
                                    <option value="local" <?php echo ($inmueble['tipo'] ?? '') === 'local' ? 'selected' : ''; ?>>Local Comercial</option>
                                    <option value="oficina" <?php echo ($inmueble['tipo'] ?? '') === 'oficina' ? 'selected' : ''; ?>>Oficina</option>
                                    <option value="terreno" <?php echo ($inmueble['tipo'] ?? '') === 'terreno' ? 'selected' : ''; ?>>Terreno</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="estado">Disponibilidad <span class="required">*</span></label>
                                <select id="estado" name="estado" required>
                                    <option value="venta" <?php echo ($inmueble['estado'] ?? '') === 'venta' ? 'selected' : ''; ?>>En Venta</option>
                                    <option value="arriendo" <?php echo ($inmueble['estado'] ?? '') === 'arriendo' ? 'selected' : ''; ?>>En Arriendo</option>
                                </select>
                            </div>
                            <div class="form-group full-width">
                                <label for="descripcion">Descripcion <span class="required">*</span></label>
                                <textarea id="descripcion" name="descripcion" required
                                          placeholder="Describe las caracteristicas del inmueble..."><?php echo htmlspecialchars($inmueble['descripcion'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Características -->
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-list"></i> Caracteristicas
                        </h3>
                        <div class="form-grid form-grid-3">
                            <div class="form-group">
                                <label for="precio">Precio (COP) <span class="required">*</span></label>
                                <input type="number" id="precio" name="precio" required min="0" step="1000"
                                       value="<?php echo $inmueble['precio'] ?? ''; ?>"
                                       placeholder="Ej: 150000000">
                            </div>
                            <div class="form-group">
                                <label for="area">Area (m2) <span class="required">*</span></label>
                                <input type="number" id="area" name="area" required min="0" step="0.01"
                                       value="<?php echo $inmueble['area'] ?? ''; ?>"
                                       placeholder="Ej: 85">
                            </div>
                            <div class="form-group">
                                <label for="habitaciones">Habitaciones</label>
                                <input type="number" id="habitaciones" name="habitaciones" min="0"
                                       value="<?php echo $inmueble['habitaciones'] ?? 0; ?>">
                            </div>
                            <div class="form-group">
                                <label for="banos">Banos</label>
                                <input type="number" id="banos" name="banos" min="0"
                                       value="<?php echo $inmueble['banos'] ?? 0; ?>">
                            </div>
                            <div class="form-group">
                                <label>Parqueadero</label>
                                <label class="checkbox-group">
                                    <input type="checkbox" name="parqueadero" 
                                           <?php echo ($inmueble['parqueadero'] ?? 0) ? 'checked' : ''; ?>>
                                    <span>Incluye parqueadero</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Ubicación -->
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-map-marker-alt"></i> Ubicacion
                        </h3>
                    </div>

                    <!-- Imágenes -->
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-images"></i> Imagenes
                        </h3>
                        <div class="form-group">
                            <label>Imagen Principal <span class="required">*</span></label>
                            <div class="upload-area" id="upload-principal" onclick="document.getElementById('imagen_principal').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <h4>Arrastra o haz clic para subir</h4>
                                <p>JPG, PNG o WEBP (max. 5MB)</p>
                                <input type="file" id="imagen_principal" name="imagen_principal" accept="image/*"
                                       <?php echo $editMode ? '' : 'required'; ?>>
                            </div>
                            <div class="upload-preview" id="preview-principal"></div>
                        </div>
                        <div class="form-group">
                            <label>Imagenes Adicionales (Opcional)</label>
                            <div class="upload-area" id="upload-adicionales" onclick="document.getElementById('imagenes_adicionales').click()">
                                <i class="fas fa-images"></i>
                                <h4>Agrega mas fotos del inmueble</h4>
                                <p>Puedes subir hasta 10 imagenes adicionales</p>
                                <input type="file" id="imagenes_adicionales" name="imagenes_adicionales[]" accept="image/*" multiple>
                            </div>
                            <div class="upload-preview" id="preview-adicionales"></div>
                        </div>
                    </div>

                    <!-- Plano 2D / 3D -->
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-cube"></i> Plano 2D para Vista 3D (Opcional)
                        </h3>
                        <p style="color: var(--text-muted); margin-bottom: 1rem;">
                            Sube un plano 2D o dibuja uno para que tus clientes puedan recorrer el inmueble en 3D
                        </p>
                        
                        <div class="plano-options">
                            <div class="plano-option" id="opt-upload" onclick="selectPlanoOption('upload')">
                                <i class="fas fa-file-upload"></i>
                                <h4>Subir Plano</h4>
                                <p>Sube una imagen o PDF de tu plano</p>
                            </div>
                            <div class="plano-option" id="opt-draw" onclick="selectPlanoOption('draw')">
                                <i class="fas fa-pencil-ruler"></i>
                                <h4>Dibujar Plano</h4>
                                <p>Crea un plano simple con nuestro editor</p>
                            </div>
                        </div>

                        <div class="plano-upload" id="plano-upload-section">
                            <div class="upload-area" onclick="document.getElementById('plano_2d').click()">
                                <i class="fas fa-drafting-compass"></i>
                                <h4>Sube tu plano arquitectonico</h4>
                                <p>JPG, PNG, SVG o PDF</p>
                                <input type="file" id="plano_2d" name="plano_2d" accept="image/*,.pdf">
                            </div>
                            <div class="upload-preview" id="preview-plano"></div>
                        </div>

                        <div class="plano-draw" id="plano-draw-section">
                            <div style="background: var(--bg-dark); border-radius: 12px; padding: 1.5rem; text-align: center;">
                                <p style="color: var(--text-muted); margin-bottom: 1rem;">
                                    El editor de planos se abrira en una nueva ventana despues de publicar el inmueble
                                </p>
                                <label class="checkbox-group" style="display: inline-flex; width: auto;">
                                    <input type="checkbox" name="crear_plano_despues" value="1">
                                    <span>Crear plano despues de publicar</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-footer">
                    <a href="<?php echo BASE_URL; ?>views/inmuebles/listar.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancelar
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-<?php echo $editMode ? 'save' : 'paper-plane'; ?>"></i>
                        <?php echo $editMode ? 'Guardar Cambios' : 'Publicar Inmueble'; ?>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-bottom">
            <p>&copy; 2024 InmoVision3D. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script>
    // Plano option selection
    function selectPlanoOption(option) {
        document.querySelectorAll('.plano-option').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.plano-upload, .plano-draw').forEach(el => el.classList.remove('active'));
        
        document.getElementById('opt-' + option).classList.add('active');
        document.getElementById('plano-' + option + '-section').classList.add('active');
    }

    // Image preview functions
    function setupImagePreview(inputId, previewId, multiple = false) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);

        input.addEventListener('change', function() {
            preview.innerHTML = '';
            const files = Array.from(this.files);
            
            files.forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'preview-item';
                        div.innerHTML = `
                            <img src="${e.target.result}" alt="Preview">
                            <button type="button" class="remove-btn" onclick="removePreview(this, '${inputId}', ${index})">
                                <i class="fas fa-times"></i>
                            </button>
                        `;
                        preview.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                }
            });
        });
    }

    function removePreview(btn, inputId, index) {
        const previewItem = btn.closest('.preview-item');
        previewItem.remove();
        
        // Clear the input if needed
        const input = document.getElementById(inputId);
        if (!input.multiple) {
            input.value = '';
        }
    }

    // Setup drag and drop
    function setupDragDrop(areaId, inputId) {
        const area = document.getElementById(areaId);
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            area.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            area.addEventListener(eventName, () => area.classList.add('dragover'));
        });

        ['dragleave', 'drop'].forEach(eventName => {
            area.addEventListener(eventName, () => area.classList.remove('dragover'));
        });

        area.addEventListener('drop', function(e) {
            const input = document.getElementById(inputId);
            const dt = e.dataTransfer;
            input.files = dt.files;
            input.dispatchEvent(new Event('change'));
        });
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        setupImagePreview('imagen_principal', 'preview-principal');
        setupImagePreview('imagenes_adicionales', 'preview-adicionales', true);
        setupImagePreview('plano_2d', 'preview-plano');

        setupDragDrop('upload-principal', 'imagen_principal');
        setupDragDrop('upload-adicionales', 'imagenes_adicionales');
    });

    // Format price input
    document.getElementById('precio').addEventListener('input', function() {
        let value = this.value.replace(/\D/g, '');
        this.value = value;
    });
    </script>
</body>
</html>
