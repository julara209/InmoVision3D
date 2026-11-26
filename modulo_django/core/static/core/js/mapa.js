(function () {
  const apiUrl = document.getElementById("mapa").dataset.api;
  const focoId = new URLSearchParams(location.search).get("id"); // llegada desde "Ver en el mapa"

  const mapa = L.map("mapa", { zoomControl: false }).setView([4.711, -74.0721], 12);
  L.control.zoom({ position: "bottomleft" }).addTo(mapa);

  L.tileLayer("https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png", {
    maxZoom: 20,
    attribution: "&copy; OpenStreetMap contributors &copy; CARTO",
  }).addTo(mapa);

  const cluster = L.markerClusterGroup({ showCoverageOnHover: false });
  mapa.addLayer(cluster);

  const panel = document.getElementById("panel");
  const aviso = document.getElementById("aviso");
  let pinActivo = null;
  let filtros = { tipo: "", operacion: "" };
  let reencuadrar = true;
  let foco = focoId;

  const cop = n => "$" + Number(n).toLocaleString("es-CO");
  const bonito = t => t.charAt(0) + t.slice(1).toLowerCase().replace("_", " ");

  function mostrarAviso(texto) {
    aviso.textContent = texto;
    aviso.style.display = texto ? "block" : "none";
  }

  function cerrarPanel() {
    panel.classList.remove("abierto");
    if (pinActivo) pinActivo.classList.remove("activo");
    pinActivo = null;
  }

  // Activa un enlace o, si no está disponible, lo muestra deshabilitado con otro texto (nunca un error)
  function configurarEnlace(el, disponible, url, textoOk, textoNo) {
    if (disponible) {
      el.href = url;
      el.target = "_blank";
      el.rel = "noopener";
      el.textContent = textoOk;
      el.classList.remove("deshabilitado");
      el.removeAttribute("aria-disabled");
      el.removeAttribute("tabindex");
    } else {
      el.removeAttribute("href");
      el.textContent = textoNo;
      el.classList.add("deshabilitado");
      el.setAttribute("aria-disabled", "true");
      el.tabIndex = -1;
    }
  }

  function abrirPanel(i, marker) {
    // textContent evita inyectar HTML desde datos escritos por usuarios
    const texto = (id, valor) => { document.getElementById(id).textContent = valor; };

    texto("p-tipo", bonito(i.tipo));
    texto("p-oper", i.operacion === "ARRIENDO" ? "Arriendo" : "Venta");
    texto("p-precio", cop(i.precio) + (i.operacion === "ARRIENDO" ? " / mes" : ""));
    texto("p-titulo", i.titulo);
    texto("p-dir", i.direccion + (i.ciudad ? ", " + i.ciudad : ""));
    texto("d-area", i.area ? i.area + " m²" : "—");
    texto("d-hab", i.habitaciones || "—");
    texto("d-ban", i.banos || "—");
    document.getElementById("p-link").href = i.url;

    // Plano 2D y visor 3D de ESTE inmueble; si faltan, se avisa en lugar de dar un enlace roto
    const tiene2d = !!i.plano_2d_url;
    configurarEnlace(document.getElementById("p-plano"), tiene2d, i.plano_2d_url, "Ver plano 2D", "Plano 2D no disponible");
    configurarEnlace(document.getElementById("p-3d"), i.tiene_3d, i.url_3d, "Ver recorrido 3D", "Recorrido 3D no disponible");

    const aviso = document.getElementById("p-aviso");
    let mensaje = "";
    if (!tiene2d && !i.tiene_3d) mensaje = "Este inmueble aún no tiene plano 2D ni modelo 3D.";
    else if (!tiene2d) mensaje = "Este inmueble aún no tiene plano 2D.";
    else if (!i.tiene_3d) mensaje = "Este inmueble tiene plano 2D, pero aún no tiene modelo 3D.";
    aviso.textContent = mensaje;
    aviso.hidden = !mensaje;

    const img = document.getElementById("p-img");
    img.style.display = i.imagen ? "block" : "none";
    img.onerror = () => { img.style.display = "none"; };   // foto rota: se oculta en vez de mostrar el icono roto
    img.src = i.imagen || "";

    if (pinActivo) pinActivo.classList.remove("activo");
    pinActivo = marker._icon ? marker._icon.querySelector(".pin") : null;
    if (pinActivo) pinActivo.classList.add("activo");

    panel.classList.add("abierto");
  }

  async function cargar() {
    cerrarPanel();
    let data;
    try {
      const resp = await fetch(apiUrl + "?" + new URLSearchParams(filtros));
      if (!resp.ok) throw new Error(resp.status);
      data = await resp.json();
    } catch (e) {
      mostrarAviso("No se pudieron cargar los inmuebles. Verifica que la base de datos esté encendida.");
      return;
    }

    cluster.clearLayers();
    const coords = [];
    let marcadorFoco = null;

    data.inmuebles.forEach(i => {
      const etiqueta = i.precio >= 1e6
        ? "$" + (i.precio / 1e6).toLocaleString("es-CO", { maximumFractionDigits: 1 }) + " M"
        : cop(i.precio);

      const icono = L.divIcon({ className: "", html: `<span class="pin">${etiqueta}</span>`, iconSize: [0, 0] });
      const marker = L.marker([i.lat, i.lng], { icon: icono });
      marker.on("click", e => { L.DomEvent.stopPropagation(e); abrirPanel(i, marker); });
      cluster.addLayer(marker);
      coords.push([i.lat, i.lng]);
      if (foco && String(i.id) === String(foco)) marcadorFoco = { i, marker };
    });

    // Ajusta el zoom para ver todos los inmuebles
    if (coords.length && reencuadrar) {
      mapa.fitBounds(L.latLngBounds(coords).pad(0.15), { maxZoom: 16 });
      reencuadrar = false;
    }

    // Llegaste desde el detalle de un inmueble: lo centra y abre su panel
    if (marcadorFoco) {
      cluster.zoomToShowLayer(marcadorFoco.marker, () => abrirPanel(marcadorFoco.i, marcadorFoco.marker));
      foco = null;
    }

    mostrarAviso(coords.length ? "" : "No hay inmuebles con ubicación para estos filtros.");
  }

  mapa.on("click", cerrarPanel);
  document.getElementById("cerrar").addEventListener("click", cerrarPanel);

  document.getElementById("filtros").addEventListener("click", e => {
    const btn = e.target.closest("button[data-filtro]");
    if (!btn) return;
    const grupo = btn.dataset.filtro;
    document.querySelectorAll('button[data-filtro="' + grupo + '"]').forEach(b => b.classList.remove("activo"));
    btn.classList.add("activo");
    filtros[grupo] = btn.dataset.valor;
    reencuadrar = true;
    cargar();
  });

  cargar();
  setTimeout(() => mapa.invalidateSize(), 200);
})();
