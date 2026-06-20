<?php
/*Tab: Reportes*/

/* ── Datos para las stats ── */
$todosInmuebles = $inmuebleModel->listar();
if (!is_array($todosInmuebles)) $todosInmuebles = array();

$totalInmuebles = count($todosInmuebles);

$disponibles = 0; $vendidos = 0; $arrendados = 0; $pausados = 0;
$enVenta = 0;     $enArriendo = 0;
$sumaPrecios = 0; $precioMax = 0; $precioMin = 0;
$porTipo = array();

foreach ($todosInmuebles as $i) {
    $est = isset($i['estado'])    ? $i['estado']    : '';
    $op  = isset($i['operacion']) ? $i['operacion'] : '';
    $tp  = isset($i['tipo'])      ? $i['tipo']      : 'otro';
    $pr  = isset($i['precio'])    ? (float)$i['precio'] : 0;

    if ($est === 'disponible') $disponibles++;
    if ($est === 'vendido')    $vendidos++;
    if ($est === 'arrendado')  $arrendados++;
    if ($est === 'pausado')    $pausados++;
    if ($op  === 'venta')      $enVenta++;
    if ($op  === 'arriendo')   $enArriendo++;

    $sumaPrecios += $pr;
    if ($pr > $precioMax) $precioMax = $pr;
    if ($precioMin === 0 || ($pr > 0 && $pr < $precioMin)) $precioMin = $pr;

    if (!isset($porTipo[$tp])) $porTipo[$tp] = 0;
    $porTipo[$tp]++;
}

$precioPromedio = $totalInmuebles > 0 ? $sumaPrecios / $totalInmuebles : 0;
arsort($porTipo);
?>

<style>
/* ══ REPORTES TAB ══════════════════════════════════════════ */
.rep-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.rep-metric-card {
    background: #1e293b;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 12px;
    padding: 1.1rem 1.25rem;
    display: flex; align-items: center; gap: .875rem;
    transition: border-color .2s;
}
.rep-metric-card:hover { border-color: rgba(14,165,233,0.25); }
.rep-metric-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem; flex-shrink: 0;
}
.rep-mi-blue   { background: rgba(14,165,233,0.12); color: #0ea5e9; }
.rep-mi-green  { background: rgba(34,197,94,0.12);  color: #22c55e; }
.rep-mi-amber  { background: rgba(245,158,11,0.12); color: #f59e0b; }
.rep-mi-violet { background: rgba(139,92,246,0.12); color: #8b5cf6; }
.rep-mi-rose   { background: rgba(244,63,94,0.12);  color: #f43f5e; }
.rep-mi-sky    { background: rgba(56,189,248,0.12); color: #38bdf8; }
.rep-mi-slate  { background: rgba(100,116,139,0.12);color: #64748b; }
.rep-mi-orange { background: rgba(249,115,22,0.12); color: #f97316; }
.rep-metric-num { font-size: 1.35rem; font-weight: 700; line-height: 1; }
.rep-metric-lbl { font-size: .7rem; color: #64748b; margin-top: 2px; }

.rep-dist-section {
    background: #1e293b;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 12px;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.5rem;
}
.rep-dist-title {
    font-size: .78rem; font-weight: 600; color: #64748b;
    text-transform: uppercase; letter-spacing: .05em;
    margin-bottom: 1rem;
    display: flex; align-items: center; gap: 8px;
}
.rep-dist-row {
    display: flex; align-items: center; gap: .75rem;
    margin-bottom: .6rem;
}
.rep-dist-label  { font-size: .8rem; color: #94a3b8; min-width: 90px; text-transform: capitalize; }
.rep-dist-bar-wrap { flex: 1; height: 8px; background: rgba(255,255,255,0.06); border-radius: 99px; overflow: hidden; }
.rep-dist-bar    { height: 100%; border-radius: 99px; }
.rep-dist-count  { font-size: .75rem; color: #64748b; min-width: 28px; text-align: right; }

.rep-section-title {
    font-size: .78rem; font-weight: 600; color: #64748b;
    text-transform: uppercase; letter-spacing: .05em;
    margin: 1.5rem 0 .75rem;
    display: flex; align-items: center; gap: 8px;
}
.rep-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.rep-card {
    background: #1e293b;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px;
    overflow: hidden;
    display: flex; flex-direction: column;
    transition: border-color .2s, transform .2s;
}
.rep-card:hover { border-color: rgba(14,165,233,0.3); transform: translateY(-2px); }
.rep-card-top {
    padding: 1.25rem 1.25rem .75rem;
    display: flex; align-items: flex-start; gap: .875rem;
}
.rep-card-icon {
    width: 44px; height: 44px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
}
.rep-card h3 { font-size: .9rem; font-weight: 600; margin-bottom: 3px; }
.rep-card p  { font-size: .75rem; color: #64748b; line-height: 1.4; }

.rep-card-filters {
    padding: .75rem 1.25rem;
    border-top: 1px solid rgba(255,255,255,0.05);
    display: flex; flex-direction: column; gap: .5rem;
}
.rep-filter-row   { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }
.rep-filter-label { font-size: .7rem; color: #64748b; min-width: 70px; }
.rep-filter-select {
    flex: 1; background: #0f172a;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 7px; padding: 6px 10px;
    color: #94a3b8; font-family: inherit; font-size: .78rem;
    cursor: pointer; transition: border-color .2s;
}
.rep-filter-select:focus { outline: none; border-color: #0ea5e9; }
.rep-filter-select option { background: #1e293b; }

.rep-card-footer {
    padding: .875rem 1.25rem;
    border-top: 1px solid rgba(255,255,255,0.05);
    display: flex; gap: .5rem; align-items: center; margin-top: auto;
}
.btn-rep-pdf {
    flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    padding: 9px 14px; border-radius: 8px; border: none; cursor: pointer;
    font-family: inherit; font-size: .8rem; font-weight: 600;
    background: linear-gradient(135deg, #0ea5e9, #f97316);
    color: #fff; transition: opacity .18s;
}
.btn-rep-pdf:hover    { opacity: .85; }
.btn-rep-pdf:disabled { opacity: .45; cursor: not-allowed; }

.rep-history {
    background: #1e293b;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 12px; overflow: hidden;
}
.rep-history-header {
    padding: .875rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    font-size: .78rem; font-weight: 600; color: #64748b;
    text-transform: uppercase; letter-spacing: .05em;
    display: flex; justify-content: space-between; align-items: center;
}
.rep-history-list  { max-height: 220px; overflow-y: auto; }
.rep-history-empty { padding: 2rem; text-align: center; font-size: .8rem; color: #334155; }
.rep-history-item {
    display: flex; align-items: center; gap: .75rem;
    padding: .75rem 1.25rem;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    font-size: .8rem; color: #94a3b8;
    transition: background .15s;
}
.rep-history-item:last-child { border-bottom: none; }
.rep-history-item:hover { background: rgba(255,255,255,0.025); }
.rep-history-icon { color: #ef4444; font-size: .9rem; flex-shrink: 0; }
.rep-history-name { flex: 1; font-weight: 500; color: #cbd5e1; }
.rep-history-time { font-size: .72rem; color: #475569; }

@keyframes rep-spin { to { transform: rotate(360deg); } }
.rep-spin { display: inline-block; animation: rep-spin .7s linear infinite; }
</style>

<!-- ═══ PANEL REPORTES ═══ -->
<div class="adm-panel" style="border-radius:16px">

    <div class="adm-panel-header" style="justify-content:space-between">
        <div style="display:flex;align-items:center;gap:10px">
            <div style="width:32px;height:32px;border-radius:8px;background:rgba(249,115,22,0.12);
                        display:flex;align-items:center;justify-content:center;color:#f97316">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
            </div>
            <span style="font-size:.95rem;font-weight:600">Centro de reportes</span>
        </div>
        <span style="font-size:.75rem;color:#475569">Genera reportes PDF con un clic</span>
    </div>

    <div style="padding:1.5rem">

        <!-- METRICAS -->
        <div class="rep-section-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
            Resumen general
        </div>

        <div class="rep-metrics">
            <div class="rep-metric-card">
                <div class="rep-metric-icon rep-mi-blue"><i class="fas fa-building"></i></div>
                <div><div class="rep-metric-num"><?php echo $totalInmuebles; ?></div><div class="rep-metric-lbl">Total inmuebles</div></div>
            </div>
            <div class="rep-metric-card">
                <div class="rep-metric-icon rep-mi-green"><i class="fas fa-circle-check"></i></div>
                <div><div class="rep-metric-num"><?php echo $disponibles; ?></div><div class="rep-metric-lbl">Disponibles</div></div>
            </div>
            <div class="rep-metric-card">
                <div class="rep-metric-icon rep-mi-violet"><i class="fas fa-tag"></i></div>
                <div><div class="rep-metric-num"><?php echo $vendidos; ?></div><div class="rep-metric-lbl">Vendidos</div></div>
            </div>
            <div class="rep-metric-card">
                <div class="rep-metric-icon rep-mi-amber"><i class="fas fa-key"></i></div>
                <div><div class="rep-metric-num"><?php echo $arrendados; ?></div><div class="rep-metric-lbl">Arrendados</div></div>
            </div>
            <div class="rep-metric-card">
                <div class="rep-metric-icon rep-mi-slate"><i class="fas fa-pause"></i></div>
                <div><div class="rep-metric-num"><?php echo $pausados; ?></div><div class="rep-metric-lbl">Pausados</div></div>
            </div>
            <div class="rep-metric-card">
                <div class="rep-metric-icon rep-mi-sky"><i class="fas fa-chart-line"></i></div>
                <div>
                    <div class="rep-metric-num">$<?php echo number_format($precioPromedio / 1000000, 1); ?>M</div>
                    <div class="rep-metric-lbl">Precio promedio</div>
                </div>
            </div>
            <div class="rep-metric-card">
                <div class="rep-metric-icon rep-mi-orange"><i class="fas fa-arrow-trend-up"></i></div>
                <div><div class="rep-metric-num"><?php echo $enVenta; ?></div><div class="rep-metric-lbl">En venta</div></div>
            </div>
            <div class="rep-metric-card">
                <div class="rep-metric-icon rep-mi-rose"><i class="fas fa-house-circle-check"></i></div>
                <div><div class="rep-metric-num"><?php echo $enArriendo; ?></div><div class="rep-metric-lbl">En arriendo</div></div>
            </div>
        </div>

        <!-- DISTRIBUCION POR TIPO -->
        <?php if (!empty($porTipo)): ?>
        <div class="rep-dist-section">
            <div class="rep-dist-title">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
                Distribucion por tipo de inmueble
            </div>
            <?php
            $coloresDist = array('#0ea5e9','#f97316','#22c55e','#8b5cf6','#f59e0b','#f43f5e','#38bdf8');
            $ci = 0;
            foreach ($porTipo as $tipo => $cant):
                $pct   = $totalInmuebles > 0 ? round($cant / $totalInmuebles * 100) : 0;
                $color = $coloresDist[$ci % count($coloresDist)];
                $ci++;
            ?>
            <div class="rep-dist-row">
                <span class="rep-dist-label"><?php echo ucfirst($tipo); ?></span>
                <div class="rep-dist-bar-wrap">
                    <div class="rep-dist-bar" style="width:<?php echo $pct; ?>%;background:<?php echo $color; ?>"></div>
                </div>
                <span class="rep-dist-count"><?php echo $cant; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- REPORTES DISPONIBLES -->
        <div class="rep-section-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
            Reportes disponibles
        </div>

        <div class="rep-cards-grid">

            <!-- Inventario general -->
            <div class="rep-card">
                <div class="rep-card-top">
                    <div class="rep-card-icon" style="background:rgba(14,165,233,0.12);color:#0ea5e9">
                        <i class="fas fa-list-check"></i>
                    </div>
                    <div>
                        <h3>Inventario general</h3>
                        <p>Listado completo de todos los inmuebles con precios, ubicacion y estado actual.</p>
                    </div>
                </div>
                <div class="rep-card-filters">
                    <div class="rep-filter-row">
                        <span class="rep-filter-label">Estado</span>
                        <select class="rep-filter-select" id="filtroEstadoInventario">
                            <option value="">Todos</option>
                            <option value="disponible">Disponible</option>
                            <option value="vendido">Vendido</option>
                            <option value="arrendado">Arrendado</option>
                            <option value="pausado">Pausado</option>
                        </select>
                    </div>
                    <div class="rep-filter-row">
                        <span class="rep-filter-label">Operacion</span>
                        <select class="rep-filter-select" id="filtroOpInventario">
                            <option value="">Todas</option>
                            <option value="venta">Venta</option>
                            <option value="arriendo">Arriendo</option>
                        </select>
                    </div>
                </div>
                <div class="rep-card-footer">
                    <button class="btn-rep-pdf" onclick="generarReporte('inventario', this)">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </button>
                </div>
            </div>

            <!-- Por tipo -->
            <div class="rep-card">
                <div class="rep-card-top">
                    <div class="rep-card-icon" style="background:rgba(139,92,246,0.12);color:#8b5cf6">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div>
                        <h3>Por tipo de inmueble</h3>
                        <p>Agrupacion y estadisticas clasificadas por tipo: casa, apartamento, local, etc.</p>
                    </div>
                </div>
                <div class="rep-card-filters">
                    <div class="rep-filter-row">
                        <span class="rep-filter-label">Tipo</span>
                        <select class="rep-filter-select" id="filtroTipoAgrupado">
                            <option value="">Todos los tipos</option>
                            <option value="casa">Casa</option>
                            <option value="apartamento">Apartamento</option>
                            <option value="local">Local</option>
                            <option value="oficina">Oficina</option>
                            <option value="lote">Lote</option>
                            <option value="bodega">Bodega</option>
                        </select>
                    </div>
                </div>
                <div class="rep-card-footer">
                    <button class="btn-rep-pdf" onclick="generarReporte('por_tipo', this)">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </button>
                </div>
            </div>

            <!-- Analisis de precios -->
            <div class="rep-card">
                <div class="rep-card-top">
                    <div class="rep-card-icon" style="background:rgba(34,197,94,0.12);color:#22c55e">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div>
                        <h3>Analisis de precios</h3>
                        <p>Promedio, maximo, minimo y rangos de precios por tipo de operacion.</p>
                    </div>
                </div>
                <div class="rep-card-filters">
                    <div class="rep-filter-row">
                        <span class="rep-filter-label">Operacion</span>
                        <select class="rep-filter-select" id="filtroOpPrecios">
                            <option value="">Venta y arriendo</option>
                            <option value="venta">Solo venta</option>
                            <option value="arriendo">Solo arriendo</option>
                        </select>
                    </div>
                </div>
                <div class="rep-card-footer">
                    <button class="btn-rep-pdf" onclick="generarReporte('precios', this)">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </button>
                </div>
            </div>

            <!-- Por publicador -->
            <div class="rep-card">
                <div class="rep-card-top">
                    <div class="rep-card-icon" style="background:rgba(245,158,11,0.12);color:#f59e0b">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h3>Por publicador</h3>
                        <p>Resumen de inmuebles publicados por cada agente o usuario del sistema.</p>
                    </div>
                </div>
                <div class="rep-card-filters">
                    <div class="rep-filter-row">
                        <span class="rep-filter-label" style="color:#475569;font-size:.72rem">
                            Incluye todos los publicadores activos
                        </span>
                    </div>
                </div>
                <div class="rep-card-footer">
                    <button class="btn-rep-pdf" onclick="generarReporte('publicadores', this)">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </button>
                </div>
            </div>

        </div>

        <!-- HISTORIAL -->
        <div class="rep-section-title">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            Historial de esta sesion
        </div>
        <div class="rep-history">
            <div class="rep-history-header">
                <span>Reportes generados</span>
                <button onclick="repLimpiarHistorial()"
                        style="background:none;border:none;cursor:pointer;color:#334155;font-size:.75rem;
                               font-family:inherit;transition:color .15s"
                        onmouseover="this.style.color='#94a3b8'"
                        onmouseout="this.style.color='#334155'">
                    Limpiar
                </button>
            </div>
            <div class="rep-history-list" id="repHistorialLista">
                <div class="rep-history-empty" id="repHistorialVacio">
                    Aun no has generado reportes en esta sesion.
                </div>
            </div>
        </div>

    </div><!-- /padding -->
</div><!-- /adm-panel -->

<script>
/* ══ REPORTES JS ══════════════════════════════════════════ */
var SITE_URL_REP = '<?php echo rtrim(SITE_URL, "/"); ?>';
var _repHistorial = [];

var repNombres = {
    inventario   : 'Inventario general',
    por_tipo     : 'Por tipo de inmueble',
    precios      : 'Analisis de precios',
    publicadores : 'Por publicador'
};

function repLeerFiltros(tipo) {
    var f = {};
    if (tipo === 'inventario') {
        var elEst = document.getElementById('filtroEstadoInventario');
        var elOp  = document.getElementById('filtroOpInventario');
        if (elEst) f.estado    = elEst.value;
        if (elOp)  f.operacion = elOp.value;
    } else if (tipo === 'por_tipo') {
        var elTipo = document.getElementById('filtroTipoAgrupado');
        if (elTipo) f.tipo_filtro = elTipo.value;
    } else if (tipo === 'precios') {
        var elOpP = document.getElementById('filtroOpPrecios');
        if (elOpP) f.operacion = elOpP.value;
    }
    return f;
}

function generarReporte(tipo, btn) {
    btn.disabled = true;
    var textoOrig = btn.innerHTML;
    btn.innerHTML = '<span class="rep-spin"><i class="fas fa-spinner"></i></span> Generando...';

    var filtros = repLeerFiltros(tipo);
    var params  = 'tipo=' + encodeURIComponent(tipo);
    for (var k in filtros) {
        if (filtros[k]) params += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(filtros[k]);
    }

    var url = SITE_URL_REP + '/Api/ReportesApi.php?' + params;

    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.responseType = 'blob';

    xhr.onload = function() {
        if (xhr.status === 200) {
            var contentType = xhr.getResponseHeader('Content-Type') || '';
            if (contentType.indexOf('application/json') !== -1) {
                // Error del servidor
                var reader = new FileReader();
                reader.onload = function() {
                    try {
                        var err = JSON.parse(reader.result);
                        showToast(err.error || 'Error al generar el reporte', 'error');
                    } catch(e) {
                        showToast('Error desconocido al generar el reporte', 'error');
                    }
                };
                reader.readAsText(xhr.response);
            } else {
                var blob     = xhr.response;
                var blobUrl  = URL.createObjectURL(blob);
                var filename = 'reporte_' + tipo + '_' + Date.now() + '.pdf';
                var a        = document.createElement('a');
                a.href       = blobUrl;
                a.download   = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                setTimeout(function() { URL.revokeObjectURL(blobUrl); }, 1000);

                _repHistorial.unshift({ tipo: tipo, filename: filename, ts: new Date() });
                repRenderHistorial();
                showToast('Reporte PDF descargado correctamente', 'success');
            }
        } else {
            showToast('Error ' + xhr.status + ' al generar el reporte', 'error');
        }
        btn.disabled = false;
        btn.innerHTML = textoOrig;
    };

    xhr.onerror = function() {
        showToast('Error de conexion al generar el reporte', 'error');
        btn.disabled = false;
        btn.innerHTML = textoOrig;
    };

    xhr.send();
}

function repRenderHistorial() {
    var lista = document.getElementById('repHistorialLista');
    var vacio = document.getElementById('repHistorialVacio');
    if (!lista) return;

    // Borrar items anteriores
    var items = lista.querySelectorAll('.rep-history-item');
    for (var x = 0; x < items.length; x++) items[x].parentNode.removeChild(items[x]);

    if (_repHistorial.length === 0) {
        if (vacio) vacio.style.display = '';
        return;
    }
    if (vacio) vacio.style.display = 'none';

    for (var idx = 0; idx < _repHistorial.length; idx++) {
        var entry = _repHistorial[idx];
        var hm    = entry.ts.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
        var item  = document.createElement('div');
        item.className = 'rep-history-item';
        item.innerHTML =
            '<i class="fas fa-file-pdf rep-history-icon"></i>' +
            '<span class="rep-history-name">' + (repNombres[entry.tipo] || entry.tipo) + '</span>' +
            '<span class="rep-history-time">' + hm + '</span>';
        lista.appendChild(item);
    }
}

function repLimpiarHistorial() {
    _repHistorial = [];
    repRenderHistorial();
}
</script>