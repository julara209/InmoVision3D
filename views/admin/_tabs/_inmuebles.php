<?php
/**
 * Tab: Inmuebles
 * Incluido desde dashboard.php — tiene acceso a $inmuebleModel y la sesión
 */
$inmuebles = $inmuebleModel->listar();

function chipInmueble($valor, $tipo) {
    if ($tipo === 'operacion') {
        if ($valor === 'arriendo') return ['chip-arriendo', 'Arriendo'];
        if ($valor === 'venta')    return ['chip-venta',    'Venta'];
        return ['chip-cliente', ucfirst($valor)];
    }
    if ($tipo === 'estado') {
        if ($valor === 'disponible') return ['chip-disp',    'Disponible'];
        if ($valor === 'vendido')    return ['chip-vendido',  'Vendido'];
        if ($valor === 'arrendado')  return ['chip-arriendo', 'Arrendado'];
        if ($valor === 'pausado')    return ['chip-pausado',  'Pausado'];
        return ['chip-cliente', ucfirst($valor)];
    }
    return ['chip-cliente', ucfirst($valor)];
}
?>

<style>
    .chip-arriendo { background: rgba(245,158,11,0.15);  color: #f59e0b; }
    .chip-venta    { background: rgba(14,165,233,0.15);  color: #0ea5e9; }
    .chip-disp     { background: rgba(34,197,94,0.15);   color: #22c55e; }
    .chip-vendido  { background: rgba(139,92,246,0.15);  color: #8b5cf6; }
    .chip-pausado  { background: rgba(100,116,139,0.15); color: #64748b; }

    .filter-bar { display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; }
    .filter-bar select {
        background:#0f172a; border:1px solid rgba(255,255,255,0.1);
        border-radius:8px; padding:8px 12px; color:#94a3b8;
        font-family:inherit; font-size:0.8rem; cursor:pointer; transition:border-color .2s;
    }
    .filter-bar select:focus { outline:none; border-color:#0ea5e9; }
    .filter-bar select option { background:#1e293b; }
    .price-cell { color:#0ea5e9; font-weight:600; }
    .thumb-placeholder {
        width:40px; height:40px; border-radius:8px;
        background:rgba(14,165,233,0.1); border:1px solid rgba(14,165,233,0.2);
        display:flex; align-items:center; justify-content:center;
        flex-shrink:0; overflow:hidden;
    }
    .thumb-placeholder img { width:100%; height:100%; object-fit:cover; }

    /* ── Upload zone ── */
    .upload-zone {
        border: 2px dashed rgba(255,255,255,0.12); border-radius:10px;
        padding:1.5rem; text-align:center; cursor:pointer;
        transition:border-color .2s, background .2s; position:relative;
    }
    .upload-zone:hover, .upload-zone.drag-over {
        border-color:#0ea5e9; background:rgba(14,165,233,0.05);
    }
    .upload-zone input[type=file] {
        position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%;
    }
    .upload-zone-icon { font-size:1.8rem; margin-bottom:.5rem; }
    .upload-zone p { font-size:.8rem; color:#64748b; }
    .upload-zone strong { color:#0ea5e9; }

    /* ── Preview grid ── */
    .img-preview-grid {
        display:grid; grid-template-columns:repeat(auto-fill,minmax(90px,1fr));
        gap:.6rem; margin-top:.75rem;
    }
    .img-preview-item {
        position:relative; border-radius:8px; overflow:hidden;
        aspect-ratio:1; background:#0f172a;
        border:1px solid rgba(255,255,255,0.08);
    }
    .img-preview-item img { width:100%; height:100%; object-fit:cover; display:block; }
    .img-preview-item .img-remove {
        position:absolute; top:4px; right:4px;
        width:20px; height:20px; border-radius:50%;
        background:rgba(239,68,68,0.85); color:#fff;
        border:none; cursor:pointer; font-size:.75rem;
        display:flex; align-items:center; justify-content:center;
        opacity:0; transition:opacity .15s;
    }
    .img-preview-item:hover .img-remove { opacity:1; }
    .img-preview-item .img-badge {
        position:absolute; bottom:3px; left:3px;
        background:rgba(0,0,0,0.6); color:#fff;
        font-size:.55rem; padding:1px 5px; border-radius:4px;
    }

    /* Existentes en editar */
    .img-existing-item { border:1px solid rgba(34,197,94,0.3); }
    .img-existing-item .img-badge { background:rgba(34,197,94,0.7); }

    /* Divider en modal */
    .modal-divider {
        border:none; border-top:1px solid rgba(255,255,255,0.07);
        margin:1.25rem 0;
    }

    /* Textarea heredado */
    .form-group textarea {
        background:#0f172a; border:1px solid rgba(255,255,255,0.1); border-radius:8px;
        padding:10px 12px; color:#f1f5f9; font-family:inherit; font-size:.875rem;
        width:100%; resize:vertical; transition:border-color .2s;
    }
    .form-group textarea:focus { outline:none; border-color:#0ea5e9; }
</style>

<!-- ═══ PANEL INMUEBLES ═══ -->
<div class="adm-panel">
    <div class="adm-panel-header">
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;flex:1">
            <div class="search-wrap" style="min-width:180px;max-width:280px">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" id="searchInmuebles" placeholder="Buscar inmuebles…">
            </div>
            <div class="filter-bar">
                <select id="filtroTipo" onchange="filtrarTabla()">
                    <option value="">Todos los tipos</option>
                    <option value="casa">Casa</option>
                    <option value="apartamento">Apartamento</option>
                    <option value="local">Local</option>
                    <option value="oficina">Oficina</option>
                    <option value="lote">Lote</option>
                    <option value="bodega">Bodega</option>
                </select>
                <select id="filtroOperacion" onchange="filtrarTabla()">
                    <option value="">Venta y arriendo</option>
                    <option value="venta">Venta</option>
                    <option value="arriendo">Arriendo</option>
                </select>
                <select id="filtroEstado" onchange="filtrarTabla()">
                    <option value="">Todos los estados</option>
                    <option value="disponible">Disponible</option>
                    <option value="vendido">Vendido</option>
                    <option value="arrendado">Arrendado</option>
                    <option value="pausado">Pausado</option>
                </select>
            </div>
        </div>
        <button class="adm-tab active"
                style="border-radius:8px;padding:8px 16px;border:none;cursor:pointer;white-space:nowrap"
                onclick="abrirModalCrearInmueble()">
            + Nuevo inmueble
        </button>
    </div>

    <div style="overflow-x:auto">
        <table class="adm-table" id="tablaInmuebles">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Inmueble</th>
                    <th>Ubicación</th>
                    <th>Tipo</th>
                    <th>Operación</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th>Publicador</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyInmuebles">
                <?php if (empty($inmuebles)): ?>
                <tr>
                    <td colspan="9" style="text-align:center;color:#64748b;padding:2.5rem">
                        No hay inmuebles registrados aún.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($inmuebles as $i): ?>
                <?php
                    [$chipOp,  $labelOp]  = chipInmueble($i['operacion'] ?? '', 'operacion');
                    [$chipEst, $labelEst] = chipInmueble($i['estado'] ?? 'disponible', 'estado');
                    // Primera imagen si viene cargada, si no placeholder emoji
                    $imgenes   = $inmuebleModel->obtenerImagenes($i['idInmueble']);
                    $primeraImg = !empty($imgenes) ? htmlspecialchars($imgenes[0]['urlImagen']) : '';
                ?>
                <tr data-tipo="<?php echo htmlspecialchars($i['tipo']); ?>"
                    data-operacion="<?php echo htmlspecialchars($i['operacion'] ?? ''); ?>"
                    data-estado="<?php echo htmlspecialchars($i['estado'] ?? ''); ?>"
                    data-imagenes="<?php echo htmlspecialchars(json_encode($imgenes)); ?>">
                    <td style="color:#64748b;font-size:0.8rem">#<?php echo $i['idInmueble']; ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div class="thumb-placeholder">
                                <?php if ($primeraImg): ?>
                                    <img src="<?php echo $primeraImg; ?>" alt="foto">
                                <?php else: ?>
                                    🏠
                                <?php endif; ?>
                            </div>
                            <div>
                                <div style="font-weight:500;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                    <?php echo htmlspecialchars($i['titulo']); ?>
                                </div>
                                <div style="font-size:.72rem;color:#64748b">
                                    <?php echo (int)($i['habitaciones'] ?? 0); ?> hab ·
                                    <?php echo (int)($i['banos'] ?? 0); ?> baños ·
                                    <?php echo $i['area'] ?? '—'; ?> m²
                                    <?php if (!empty($imgenes)): ?>
                                        · <span style="color:#0ea5e9"><?php echo count($imgenes); ?> foto<?php echo count($imgenes)>1?'s':''; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="color:#64748b;font-size:.8rem;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <?php echo htmlspecialchars($i['ubicacion']); ?>
                    </td>
                    <td><?php echo ucfirst($i['tipo']); ?></td>
                    <td><span class="chip <?php echo $chipOp; ?>"><?php echo $labelOp; ?></span></td>
                    <td class="price-cell">$<?php echo number_format($i['precio'],0,',','.'); ?></td>
                    <td><span class="chip <?php echo $chipEst; ?>"><?php echo $labelEst; ?></span></td>
                    <td style="color:#64748b;font-size:.8rem">
                        <?php echo htmlspecialchars(($i['publicador_nombre']??'').' '.($i['publicador_apellido']??'')); ?>
                    </td>
                    <td>
                        <div class="tbl-actions">
                            <button class="btn-tbl" title="Editar inmueble"
                                onclick="abrirModalEditarInmueble(
                                    <?php echo $i['idInmueble']; ?>,
                                    '<?php echo htmlspecialchars(addslashes($i['titulo'])); ?>',
                                    '<?php echo htmlspecialchars(addslashes($i['descripcion'] ?? '')); ?>',
                                    <?php echo (float)$i['precio']; ?>,
                                    '<?php echo htmlspecialchars(addslashes($i['ubicacion'])); ?>',
                                    '<?php echo $i['tipo']; ?>',
                                    '<?php echo $i['operacion'] ?? ''; ?>',
                                    <?php echo (int)($i['habitaciones'] ?? 0); ?>,
                                    <?php echo (int)($i['banos'] ?? 0); ?>,
                                    <?php echo (float)($i['area'] ?? 0); ?>,
                                    '<?php echo $i['estado'] ?? 'disponible'; ?>',
                                    <?php echo htmlspecialchars(json_encode($imgenes)); ?>
                                )">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                            <button class="btn-tbl danger" title="Eliminar inmueble"
                                onclick="confirmarEliminarInmueble(<?php echo $i['idInmueble']; ?>, '<?php echo htmlspecialchars(addslashes($i['titulo'])); ?>')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                    <path d="M10 11v6"/><path d="M14 11v6"/>
                                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="padding:.75rem 1.5rem;border-top:1px solid rgba(255,255,255,0.05);
                font-size:.78rem;color:#64748b;display:flex;justify-content:space-between">
        <span id="contadorInmuebles">
            <?php echo count($inmuebles); ?> inmueble<?php echo count($inmuebles)!==1?'s':''; ?>
        </span>
        <span>Total en lista</span>
    </div>
</div>


<!-- ═══════════════════════════════════
     MODAL — CREAR INMUEBLE
═══════════════════════════════════ -->
<div class="modal-overlay" id="modalCrearInmueble">
    <div class="modal-box" style="max-width:640px">
        <div class="modal-header">
            <h2>Nuevo inmueble</h2>
            <button class="modal-close" onclick="closeModal('modalCrearInmueble')">✕</button>
        </div>

        <form id="formCrearInmueble" onsubmit="submitCrearInmueble(event)" enctype="multipart/form-data">

            <div class="form-grid">
                <div class="form-group span2">
                    <label>Título *</label>
                    <input type="text" name="titulo" placeholder="Ej: Apartamento moderno en el centro" required>
                </div>
                <div class="form-group span2">
                    <label>Descripción</label>
                    <textarea name="descripcion" rows="3" placeholder="Describe el inmueble…"></textarea>
                </div>
                <div class="form-group">
                    <label>Precio (COP) *</label>
                    <input type="number" name="precio" placeholder="0" min="0" step="1000" required>
                </div>
                <div class="form-group">
                    <label>Área (m²)</label>
                    <input type="number" name="area" placeholder="0" min="0" step="0.5">
                </div>
                <div class="form-group">
                    <label>Habitaciones</label>
                    <input type="number" name="habitaciones" placeholder="0" min="0">
                </div>
                <div class="form-group">
                    <label>Baños</label>
                    <input type="number" name="banos" placeholder="0" min="0">
                </div>
                <div class="form-group span2">
                    <label>Ubicación *</label>
                    <input type="text" name="ubicacion" placeholder="Ciudad, barrio, dirección…" required>
                </div>
                <div class="form-group">
                    <label>Tipo *</label>
                    <select name="tipo" required>
                        <option value="">Seleccionar…</option>
                        <option value="casa">Casa</option>
                        <option value="apartamento">Apartamento</option>
                        <option value="local">Local</option>
                        <option value="oficina">Oficina</option>
                        <option value="lote">Lote</option>
                        <option value="bodega">Bodega</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Operación *</label>
                    <select name="operacion" required>
                        <option value="">Seleccionar…</option>
                        <option value="venta">Venta</option>
                        <option value="arriendo">Arriendo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado">
                        <option value="disponible">Disponible</option>
                        <option value="pausado">Pausado</option>
                    </select>
                </div>
                <input type="hidden" name="idPublicador" value="<?php echo $_SESSION['usuario_id']; ?>">
            </div>

            <hr class="modal-divider">

            <!-- Zona de imágenes -->
            <div class="form-group">
                <label>Imágenes del inmueble</label>
                <div class="upload-zone" id="uploadZoneCrear"
                     ondragover="onDragOver(event,'uploadZoneCrear')"
                     ondragleave="onDragLeave('uploadZoneCrear')"
                     ondrop="onDrop(event,'crearImgPreview','inputImagenesCrear')">
                    <input type="file" name="imagenes[]" id="inputImagenesCrear"
                           multiple accept="image/jpeg,image/png,image/webp"
                           onchange="previewImagenes(this,'crearImgPreview')">
                    <div class="upload-zone-icon">🖼️</div>
                    <p><strong>Haz clic o arrastra</strong> imágenes aquí</p>
                    <p style="margin-top:4px">JPG, PNG o WEBP · Máx. 5 MB c/u · Varias a la vez</p>
                </div>
                <div class="img-preview-grid" id="crearImgPreview"></div>
            </div>

            <div class="form-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modalCrearInmueble')">Cancelar</button>
                <button type="submit" class="btn-primary" id="btnCrearInmuebleSubmit">Publicar inmueble</button>
            </div>
        </form>
    </div>
</div>


<!-- ═══════════════════════════════════
     MODAL — EDITAR INMUEBLE
═══════════════════════════════════ -->
<div class="modal-overlay" id="modalEditarInmueble">
    <div class="modal-box" style="max-width:640px">
        <div class="modal-header">
            <h2>Editar inmueble</h2>
            <button class="modal-close" onclick="closeModal('modalEditarInmueble')">✕</button>
        </div>

        <form id="formEditarInmueble" onsubmit="submitEditarInmueble(event)" enctype="multipart/form-data">
            <input type="hidden" name="id" id="editInmuebleId">

            <div class="form-grid">
                <div class="form-group span2">
                    <label>Título *</label>
                    <input type="text" name="titulo" id="editInmuebleTitulo" required>
                </div>
                <div class="form-group span2">
                    <label>Descripción</label>
                    <textarea name="descripcion" id="editInmuebleDesc" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Precio (COP) *</label>
                    <input type="number" name="precio" id="editInmueblePrecio" min="0" step="1000" required>
                </div>
                <div class="form-group">
                    <label>Área (m²)</label>
                    <input type="number" name="area" id="editInmuebleArea" min="0" step="0.5">
                </div>
                <div class="form-group">
                    <label>Habitaciones</label>
                    <input type="number" name="habitaciones" id="editInmuebleHab" min="0">
                </div>
                <div class="form-group">
                    <label>Baños</label>
                    <input type="number" name="banos" id="editInmuebleBanos" min="0">
                </div>
                <div class="form-group span2">
                    <label>Ubicación *</label>
                    <input type="text" name="ubicacion" id="editInmuebleUbicacion" required>
                </div>
                <div class="form-group">
                    <label>Tipo *</label>
                    <select name="tipo" id="editInmuebleTipo" required>
                        <option value="casa">Casa</option>
                        <option value="apartamento">Apartamento</option>
                        <option value="local">Local</option>
                        <option value="oficina">Oficina</option>
                        <option value="lote">Lote</option>
                        <option value="bodega">Bodega</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Operación *</label>
                    <select name="operacion" id="editInmuebleOp" required>
                        <option value="venta">Venta</option>
                        <option value="arriendo">Arriendo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" id="editInmuebleEstado">
                        <option value="disponible">Disponible</option>
                        <option value="vendido">Vendido</option>
                        <option value="arrendado">Arrendado</option>
                        <option value="pausado">Pausado</option>
                    </select>
                </div>
            </div>

            <hr class="modal-divider">

            <!-- Imágenes existentes -->
            <div class="form-group" id="seccionImagenesExistentes">
                <label>Fotos actuales <span id="conteoExistentes" style="color:#64748b;font-weight:400"></span></label>
                <div class="img-preview-grid" id="editImgExistentes"></div>
                <!-- IDs de imágenes a eliminar se agregan aquí dinámicamente -->
            </div>

            <hr class="modal-divider" id="dividerNuevas">

            <!-- Nuevas imágenes -->
            <div class="form-group">
                <label>Agregar nuevas fotos</label>
                <div class="upload-zone" id="uploadZoneEditar"
                     ondragover="onDragOver(event,'uploadZoneEditar')"
                     ondragleave="onDragLeave('uploadZoneEditar')"
                     ondrop="onDrop(event,'editImgPreview','inputImagenesEditar')">
                    <input type="file" name="imagenes[]" id="inputImagenesEditar"
                           multiple accept="image/jpeg,image/png,image/webp"
                           onchange="previewImagenes(this,'editImgPreview')">
                    <div class="upload-zone-icon">➕</div>
                    <p><strong>Haz clic o arrastra</strong> más imágenes</p>
                    <p style="margin-top:4px">JPG, PNG o WEBP · Máx. 5 MB c/u</p>
                </div>
                <div class="img-preview-grid" id="editImgPreview"></div>
            </div>

            <div class="form-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modalEditarInmueble')">Cancelar</button>
                <button type="submit" class="btn-primary" id="btnEditarInmuebleSubmit">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>


<!-- ═══════════════════════════════════
     MODAL — CONFIRMAR ELIMINAR
═══════════════════════════════════ -->
<div class="modal-overlay" id="modalEliminarInmueble">
    <div class="modal-box" style="max-width:420px;text-align:center">
        <div style="margin-bottom:1.25rem">
            <div style="width:52px;height:52px;border-radius:50%;background:rgba(239,68,68,0.12);
                        color:#ef4444;font-size:1.5rem;margin:0 auto 1rem;
                        display:flex;align-items:center;justify-content:center">⚠</div>
            <h2 style="font-size:1.05rem;margin-bottom:.5rem">¿Eliminar inmueble?</h2>
            <p id="eliminarInmuebleMsg" style="color:#64748b;font-size:.875rem">
                Esta acción eliminará el inmueble y sus imágenes.
            </p>
        </div>
        <div class="form-footer" style="justify-content:center">
            <button class="btn-cancel" onclick="closeModal('modalEliminarInmueble')">Cancelar</button>
            <button class="btn-primary" id="btnConfirmarEliminarInmueble"
                    style="background:linear-gradient(135deg,#ef4444,#b91c1c)">
                Sí, eliminar
            </button>
        </div>
    </div>
</div>


<script>
/* ────────────────────────────────────────
   FILTROS + BÚSQUEDA
──────────────────────────────────────── */
function filtrarTabla() {
    const q   = (document.getElementById('searchInmuebles').value || '').toLowerCase();
    const tip = document.getElementById('filtroTipo').value.toLowerCase();
    const op  = document.getElementById('filtroOperacion').value.toLowerCase();
    const est = document.getElementById('filtroEstado').value.toLowerCase();
    let vis = 0;
    document.querySelectorAll('#tbodyInmuebles tr[data-tipo]').forEach(tr => {
        const ok = (!q   || tr.textContent.toLowerCase().includes(q))
                && (!tip || tr.dataset.tipo      === tip)
                && (!op  || tr.dataset.operacion === op)
                && (!est || tr.dataset.estado    === est);
        tr.style.display = ok ? '' : 'none';
        if (ok) vis++;
    });
    const c = document.getElementById('contadorInmuebles');
    if (c) c.textContent = vis + ' inmueble' + (vis !== 1 ? 's' : '');
}
document.getElementById('searchInmuebles').addEventListener('input', filtrarTabla);

/* ────────────────────────────────────────
   UPLOAD — preview de imágenes nuevas
──────────────────────────────────────── */
// Mapa de archivos pendientes por zona: { gridId -> DataTransfer }
const _dt = {};

function previewImagenes(input, gridId) {
    const grid = document.getElementById(gridId);
    if (!_dt[gridId]) _dt[gridId] = new DataTransfer();

    Array.from(input.files).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        _dt[gridId].items.add(file);

        const reader = new FileReader();
        reader.onload = e => {
            const item = document.createElement('div');
            item.className = 'img-preview-item';
            item.dataset.name = file.name;
            item.innerHTML = `
                <img src="${e.target.result}" alt="">
                <button type="button" class="img-remove" onclick="quitarNueva(this,'${gridId}','${input.id}')">✕</button>
                <span class="img-badge">nueva</span>`;
            grid.appendChild(item);
        };
        reader.readAsDataURL(file);
    });

    // Sincronizar el input real
    input.files = _dt[gridId].files;
}

function quitarNueva(btn, gridId, inputId) {
    const item = btn.closest('.img-preview-item');
    const name = item.dataset.name;

    // Reconstruir DataTransfer sin ese archivo
    const dt = new DataTransfer();
    Array.from(_dt[gridId].files).forEach(f => {
        if (f.name !== name) dt.items.add(f);
    });
    _dt[gridId] = dt;
    document.getElementById(inputId).files = dt.files;

    item.remove();
}

/* ── Drag & drop ── */
function onDragOver(e, zoneId) {
    e.preventDefault();
    document.getElementById(zoneId).classList.add('drag-over');
}
function onDragLeave(zoneId) {
    document.getElementById(zoneId).classList.remove('drag-over');
}
function onDrop(e, gridId, inputId) {
    e.preventDefault();
    const zone = e.currentTarget;
    zone.classList.remove('drag-over');
    const input = document.getElementById(inputId);
    // Simular files en el input
    const dt2 = new DataTransfer();
    if (_dt[gridId]) Array.from(_dt[gridId].files).forEach(f => dt2.items.add(f));
    Array.from(e.dataTransfer.files).forEach(f => dt2.items.add(f));
    input.files = dt2.files;
    previewImagenes(input, gridId);
}

/* ────────────────────────────────────────
   IMÁGENES EXISTENTES (en editar)
──────────────────────────────────────── */
let _imagenesAEliminar = [];

function renderImagenesExistentes(imagenes) {
    _imagenesAEliminar = [];
    const grid = document.getElementById('editImgExistentes');
    grid.innerHTML = '';

    // Limpiar inputs hidden de eliminación previos
    document.querySelectorAll('#formEditarInmueble input[name="eliminar_imagen[]"]')
            .forEach(el => el.remove());

    const seccion = document.getElementById('seccionImagenesExistentes');
    const divider = document.getElementById('dividerNuevas');

    if (!imagenes || imagenes.length === 0) {
        seccion.style.display = 'none';
        divider.style.display = 'none';
        return;
    }

    seccion.style.display = '';
    divider.style.display = '';
    document.getElementById('conteoExistentes').textContent =
        '(' + imagenes.length + ')';

    imagenes.forEach(img => {
        const item = document.createElement('div');
        item.className = 'img-preview-item img-existing-item';
        item.dataset.idImagen = img.idImagen;
        item.innerHTML = `
            <img src="${img.urlImagen}" alt="foto" onerror="this.src='data:image/svg+xml,<svg xmlns=\\'http://www.w3.org/2000/svg\\'><rect width=\\'100%\\' height=\\'100%\\' fill=\\'%230f172a\\'/></svg>'">
            <button type="button" class="img-remove" onclick="marcarEliminarImagen(this,${img.idImagen})">✕</button>
            <span class="img-badge">guardada</span>`;
        grid.appendChild(item);
    });
}

function marcarEliminarImagen(btn, idImagen) {
    const item = btn.closest('.img-preview-item');

    if (_imagenesAEliminar.includes(idImagen)) {
        // Desmarcar
        _imagenesAEliminar = _imagenesAEliminar.filter(x => x !== idImagen);
        item.style.opacity = '1';
        item.style.border = '1px solid rgba(34,197,94,0.3)';
        document.querySelector(`#formEditarInmueble input[name="eliminar_imagen[]"][value="${idImagen}"]`)
                ?.remove();
    } else {
        // Marcar para eliminar
        _imagenesAEliminar.push(idImagen);
        item.style.opacity = '.35';
        item.style.border = '1px solid rgba(239,68,68,0.4)';
        const hidden = document.createElement('input');
        hidden.type  = 'hidden';
        hidden.name  = 'eliminar_imagen[]';
        hidden.value = idImagen;
        document.getElementById('formEditarInmueble').appendChild(hidden);
    }
}

/* ────────────────────────────────────────
   MODALES
──────────────────────────────────────── */
function abrirModalCrearInmueble() {
    document.getElementById('formCrearInmueble').reset();
    document.getElementById('crearImgPreview').innerHTML = '';
    _dt['crearImgPreview'] = new DataTransfer();
    openModal('modalCrearInmueble');
}

function abrirModalEditarInmueble(id, titulo, desc, precio, ubicacion, tipo, operacion, hab, banos, area, estado, imagenes) {
    document.getElementById('editInmuebleId').value        = id;
    document.getElementById('editInmuebleTitulo').value    = titulo;
    document.getElementById('editInmuebleDesc').value      = desc;
    document.getElementById('editInmueblePrecio').value    = precio;
    document.getElementById('editInmuebleArea').value      = area;
    document.getElementById('editInmuebleHab').value       = hab;
    document.getElementById('editInmuebleBanos').value     = banos;
    document.getElementById('editInmuebleUbicacion').value = ubicacion;
    document.getElementById('editInmuebleTipo').value      = tipo;
    document.getElementById('editInmuebleOp').value        = operacion;
    document.getElementById('editInmuebleEstado').value    = estado;

    // Limpiar nuevas imágenes
    document.getElementById('editImgPreview').innerHTML = '';
    _dt['editImgPreview'] = new DataTransfer();
    document.getElementById('inputImagenesEditar').value = '';

    // Cargar existentes
    renderImagenesExistentes(imagenes || []);

    openModal('modalEditarInmueble');
}

/* ── Eliminar ── */
let _eliminarInmuebleId = null;
function confirmarEliminarInmueble(id, titulo) {
    _eliminarInmuebleId = id;
    document.getElementById('eliminarInmuebleMsg').textContent =
        `Vas a eliminar "${titulo}" y todas sus fotos. Esta acción no se puede deshacer.`;
    openModal('modalEliminarInmueble');
}

document.getElementById('btnConfirmarEliminarInmueble').addEventListener('click', async () => {
    if (!_eliminarInmuebleId) return;
    const btn = document.getElementById('btnConfirmarEliminarInmueble');
    btn.disabled = true; btn.textContent = 'Eliminando…';
    try {
        const res  = await fetch(`<?php echo SITE_URL; ?>/controllers/InmuebleApi.php?action=eliminar&id=${_eliminarInmuebleId}`, { method:'POST' });
        const data = await res.json();
        closeModal('modalEliminarInmueble');
        data.success
            ? (showToast('Inmueble eliminado', 'success'), setTimeout(() => location.reload(), 900))
            : showToast(data.error || 'Error al eliminar', 'error');
    } catch { showToast('Error de conexión', 'error'); }
    finally { btn.disabled = false; btn.textContent = 'Sí, eliminar'; _eliminarInmuebleId = null; }
});

/* ── Crear ── */
async function submitCrearInmueble(e) {
    e.preventDefault();
    const btn = document.getElementById('btnCrearInmuebleSubmit');
    btn.disabled = true; btn.textContent = 'Publicando…';
    try {
        const form = new FormData(document.getElementById('formCrearInmueble'));
        const res  = await fetch(`<?php echo SITE_URL; ?>/controllers/InmuebleApi.php?action=crear`, { method:'POST', body:form });
        const data = await res.json();
        if (data.success) {
            closeModal('modalCrearInmueble');
            showToast('Inmueble publicado correctamente', 'success');
            setTimeout(() => location.reload(), 900);
        } else { showToast(data.error || 'Error al crear', 'error'); }
    } catch { showToast('Error de conexión', 'error'); }
    finally { btn.disabled = false; btn.textContent = 'Publicar inmueble'; }
}

/* ── Editar ── */
async function submitEditarInmueble(e) {
    e.preventDefault();
    const btn = document.getElementById('btnEditarInmuebleSubmit');
    btn.disabled = true; btn.textContent = 'Guardando…';
    try {
        const form = new FormData(document.getElementById('formEditarInmueble'));
        const id   = document.getElementById('editInmuebleId').value;
        const res  = await fetch(`<?php echo SITE_URL; ?>/controllers/InmuebleApi.php?action=actualizar&id=${id}`, { method:'POST', body:form });
        const data = await res.json();
        if (data.success) {
            closeModal('modalEditarInmueble');
            showToast('Inmueble actualizado correctamente', 'success');
            setTimeout(() => location.reload(), 900);
        } else { showToast(data.error || 'Error al actualizar', 'error'); }
    } catch { showToast('Error de conexión', 'error'); }
    finally { btn.disabled = false; btn.textContent = 'Guardar cambios'; }
}

/* ── Escape ── */
document.addEventListener('keydown', e => {
    if (e.key === 'Escape')
        ['modalCrearInmueble','modalEditarInmueble','modalEliminarInmueble'].forEach(closeModal);
});
</script>