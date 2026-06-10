<?php
/**
 * Tab: Usuarios
 * Incluido desde dashboard.php — tiene acceso a $usuarioModel y la sesión
 */
$usuarios = $usuarioModel->listarTodos();
?>

<!-- ═══ PANEL USUARIOS ═══ -->
<div class="adm-panel">
    <div class="adm-panel-header">
        <div class="search-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" id="searchUsuarios" placeholder="Buscar por nombre, correo o rol…">
        </div>
        <button class="adm-tab active" style="border-radius:8px;padding:8px 16px;border:none;cursor:pointer"
                onclick="abrirModalCrear()">
            + Nuevo usuario
        </button>
    </div>

    <div style="overflow-x:auto">
        <table class="adm-table" id="tablaUsuarios">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;color:#64748b;padding:2.5rem">
                        No hay usuarios registrados aún.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td style="color:#64748b;font-size:0.8rem">#<?php echo $u['idUsuario']; ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:32px;height:32px;border-radius:50%;background:rgba(14,165,233,0.15);
                                        color:#0ea5e9;font-size:0.72rem;font-weight:700;
                                        display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                <?php echo strtoupper(substr($u['nombre'],0,2)); ?>
                            </div>
                            <div>
                                <div style="font-weight:500"><?php echo htmlspecialchars($u['nombre'] . ' ' . ($u['apellido'] ?? '')); ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="color:#64748b"><?php echo htmlspecialchars($u['correo']); ?></td>
                    <td style="color:#64748b"><?php echo htmlspecialchars($u['telefono'] ?? '—'); ?></td>
                    <td>
                        <?php
                        $rol = $u['rol'];
                        if ($rol === 'administrador' || $rol === 'admin') {
                            $chipClass = 'chip-admin';
                        } elseif ($rol === 'publicador') {
                            $chipClass = 'chip-pub';
                        } else {
                            $chipClass = 'chip-cliente';
                        }
                        ?>
                        <span class="chip <?php echo $chipClass; ?>"><?php echo $rol; ?></span>
                    </td>
                    <td>
                        <div class="tbl-actions">
                            <button class="btn-tbl" title="Editar usuario"
                                onclick="abrirModalEditar(
                                    <?php echo $u['idUsuario']; ?>,
                                    '<?php echo htmlspecialchars(addslashes($u['nombre'])); ?>',
                                    '<?php echo htmlspecialchars(addslashes($u['apellido'] ?? '')); ?>',
                                    '<?php echo htmlspecialchars(addslashes($u['correo'])); ?>',
                                    '<?php echo htmlspecialchars(addslashes($u['telefono'] ?? '')); ?>',
                                    '<?php echo $u['rol']; ?>'
                                )">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                            </button>
                            <?php if ($u['idUsuario'] != $_SESSION['usuario_id']): ?>
                            <button class="btn-tbl danger" title="Eliminar usuario"
                                onclick="confirmarEliminar(<?php echo $u['idUsuario']; ?>, '<?php echo htmlspecialchars(addslashes($u['nombre'])); ?>')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/>
                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                    <path d="M10 11v6"/><path d="M14 11v6"/>
                                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                </svg>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ═══════════════════════════════════
     MODAL — CREAR USUARIO
═══════════════════════════════════ -->
<div class="modal-overlay" id="modalCrear">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Nuevo usuario</h2>
            <button class="modal-close" onclick="closeModal('modalCrear')" title="Cerrar">✕</button>
        </div>

        <form id="formCrear" onsubmit="submitCrear(event)">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" placeholder="Juan" required>
                </div>
                <div class="form-group">
                    <label>Apellido</label>
                    <input type="text" name="apellido" placeholder="Pérez">
                </div>
                <div class="form-group span2">
                    <label>Correo electrónico *</label>
                    <input type="email" name="correo" placeholder="juan@ejemplo.com" required>
                </div>
                <div class="form-group">
                    <label>Contraseña *</label>
                    <input type="password" name="contrasena" placeholder="Mínimo 6 caracteres" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" placeholder="+57 300 000 0000">
                </div>
                <div class="form-group span2">
                    <label>Rol *</label>
                    <select name="rol" required>
                        <option value="cliente">Cliente</option>
                        <option value="publicador">Publicador</option>
                        <option value="administrador">Administrador</option>
                    </select>
                </div>
            </div>

            <div class="form-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modalCrear')">Cancelar</button>
                <button type="submit" class="btn-primary" id="btnCrearSubmit">Crear usuario</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════
     MODAL — EDITAR USUARIO
═══════════════════════════════════ -->
<div class="modal-overlay" id="modalEditar">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Editar usuario</h2>
            <button class="modal-close" onclick="closeModal('modalEditar')" title="Cerrar">✕</button>
        </div>

        <form id="formEditar" onsubmit="submitEditar(event)">
            <input type="hidden" name="id" id="editId">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" id="editNombre" required>
                </div>
                <div class="form-group">
                    <label>Apellido</label>
                    <input type="text" name="apellido" id="editApellido">
                </div>
                <div class="form-group span2" style="pointer-events:none;opacity:.5">
                    <label>Correo (no editable)</label>
                    <input type="email" name="correo" id="editCorreo" readonly>
                </div>
                <div class="form-group">
                    <label>Nueva contraseña</label>
                    <input type="password" name="contrasena" placeholder="Dejar vacío para no cambiar" minlength="6">
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" id="editTelefono">
                </div>
                <div class="form-group span2">
                    <label>Rol *</label>
                    <select name="rol" id="editRol" required>
                        <option value="cliente">Cliente</option>
                        <option value="publicador">Publicador</option>
                        <option value="administrador">Administrador</option>
                    </select>
                </div>
            </div>

            <div class="form-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modalEditar')">Cancelar</button>
                <button type="submit" class="btn-primary" id="btnEditarSubmit">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════
     MODAL — CONFIRMAR ELIMINAR
═══════════════════════════════════ -->
<div class="modal-overlay" id="modalEliminar">
    <div class="modal-box" style="max-width:420px;text-align:center">
        <div style="margin-bottom:1.25rem">
            <div style="width:52px;height:52px;border-radius:50%;background:rgba(239,68,68,0.12);
                        color:#ef4444;font-size:1.5rem;margin:0 auto 1rem;
                        display:flex;align-items:center;justify-content:center">
                ⚠
            </div>
            <h2 style="font-size:1.05rem;margin-bottom:.5rem">¿Eliminar usuario?</h2>
            <p id="eliminarMsg" style="color:#64748b;font-size:0.875rem">
                Esta acción no se puede deshacer.
            </p>
        </div>
        <div class="form-footer" style="justify-content:center">
            <button class="btn-cancel" onclick="closeModal('modalEliminar')">Cancelar</button>
            <button class="btn-primary" id="btnConfirmarEliminar"
                    style="background:linear-gradient(135deg,#ef4444,#b91c1c)">
                Sí, eliminar
            </button>
        </div>
    </div>
</div>

<script>
// ── Búsqueda en tabla ──
document.getElementById('searchUsuarios').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#tablaUsuarios tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

// ── Abrir modal CREAR ──
function abrirModalCrear() {
    document.getElementById('formCrear').reset();
    openModal('modalCrear');
}

// ── Abrir modal EDITAR ──
function abrirModalEditar(id, nombre, apellido, correo, telefono, rol) {
    document.getElementById('editId').value      = id;
    document.getElementById('editNombre').value  = nombre;
    document.getElementById('editApellido').value= apellido;
    document.getElementById('editCorreo').value  = correo;
    document.getElementById('editTelefono').value= telefono;
    document.getElementById('editRol').value     = rol;
    document.getElementById('formEditar').querySelector('[name=contrasena]').value = '';
    openModal('modalEditar');
}

// ── Confirmar ELIMINAR ──
let _eliminarId = null;
function confirmarEliminar(id, nombre) {
    _eliminarId = id;
    document.getElementById('eliminarMsg').textContent =
        `Vas a eliminar a "${nombre}". Esta acción no se puede deshacer.`;
    openModal('modalEliminar');
}

document.getElementById('btnConfirmarEliminar').addEventListener('click', async () => {
    if (!_eliminarId) return;
    const btn = document.getElementById('btnConfirmarEliminar');
    btn.disabled = true;
    btn.textContent = 'Eliminando…';

    try {
        const res  = await fetch(`<?php echo SITE_URL; ?>/controllers/AdminApi.php?action=eliminar&id=${_eliminarId}`, {
            method: 'POST'
        });
        const data = await res.json();
        closeModal('modalEliminar');
        if (data.success) {
            showToast('Usuario eliminado correctamente', 'success');
            setTimeout(() => location.reload(), 900);
        } else {
            showToast(data.error || 'Error al eliminar', 'error');
        }
    } catch (e) {
        showToast('Error de conexión', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Sí, eliminar';
        _eliminarId = null;
    }
});

// ── Submit CREAR ──
async function submitCrear(e) {
    e.preventDefault();
    const btn  = document.getElementById('btnCrearSubmit');
    btn.disabled = true; btn.textContent = 'Creando…';

    try {
        const form = new FormData(document.getElementById('formCrear'));
        const res  = await fetch(`<?php echo SITE_URL; ?>/controllers/AdminApi.php?action=crear`, {
            method: 'POST', body: form
        });
        const data = await res.json();
        if (data.success) {
            closeModal('modalCrear');
            showToast('Usuario creado correctamente', 'success');
            setTimeout(() => location.reload(), 900);
        } else {
            showToast(data.error || 'Error al crear usuario', 'error');
        }
    } catch (e) {
        showToast('Error de conexión', 'error');
    } finally {
        btn.disabled = false; btn.textContent = 'Crear usuario';
    }
}

// ── Submit EDITAR ──
async function submitEditar(e) {
    e.preventDefault();
    const btn = document.getElementById('btnEditarSubmit');
    btn.disabled = true; btn.textContent = 'Guardando…';

    try {
        const form = new FormData(document.getElementById('formEditar'));
        const id   = document.getElementById('editId').value;
        const res  = await fetch(`<?php echo SITE_URL; ?>/controllers/AdminApi.php?action=actualizar&id=${id}`, {
            method: 'POST', body: form
        });
        const data = await res.json();
        if (data.success) {
            closeModal('modalEditar');
            showToast('Usuario actualizado correctamente', 'success');
            setTimeout(() => location.reload(), 900);
        } else {
            showToast(data.error || 'Error al actualizar', 'error');
        }
    } catch (e) {
        showToast('Error de conexión', 'error');
    } finally {
        btn.disabled = false; btn.textContent = 'Guardar cambios';
    }
}

// Cerrar modales con Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        ['modalCrear','modalEditar','modalEliminar'].forEach(closeModal);
    }
});
</script>