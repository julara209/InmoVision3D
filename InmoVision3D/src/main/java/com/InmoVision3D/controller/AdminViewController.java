package com.InmoVision3D.controller;

import com.InmoVision3D.model.Inmueble;
import com.InmoVision3D.model.Solicitud;
import com.InmoVision3D.model.Usuario;
import com.InmoVision3D.model.enums.EstadoInmueble;
import com.InmoVision3D.model.enums.EstadoSolicitud;
import com.InmoVision3D.model.enums.TipoOperacion;
import com.InmoVision3D.service.InmuebleService;
import com.InmoVision3D.service.SolicitudService;
import com.InmoVision3D.service.UsuarioService;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;

import java.util.ArrayList;
import java.util.Comparator;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

@Controller
@RequestMapping("/admin/dashboard")
@PreAuthorize("hasRole('ADMIN')")
public class AdminViewController {

    private final UsuarioService usuarioService;
    private final InmuebleService inmuebleService;
    private final SolicitudService solicitudService;

    public AdminViewController(UsuarioService usuarioService, InmuebleService inmuebleService,
                                SolicitudService solicitudService) {
        this.usuarioService = usuarioService;
        this.inmuebleService = inmuebleService;
        this.solicitudService = solicitudService;
    }

    @GetMapping
    public String dashboard(@RequestParam(defaultValue = "usuarios") String tab, Model model,
                             Authentication authentication) {

        List<Usuario> usuarios = usuarioService.listarTodos();
        List<Inmueble> inmuebles = inmuebleService.listarTodos();
        List<Solicitud> solicitudes = solicitudService.listarTodas();

        Long usuarioActualId = usuarioService.obtenerPorEmail(authentication.getName()).getId();
        model.addAttribute("usuarioActualId", usuarioActualId);
        model.addAttribute("usuarioActual", usuarioService.obtenerPorEmail(authentication.getName()));

        long solicitudesPendientes = solicitudes.stream()
                .filter(s -> s.getEstado() == EstadoSolicitud.PENDIENTE)
                .count();

        model.addAttribute("tab", tab);
        model.addAttribute("usuarios", usuarios);
        model.addAttribute("inmuebles", inmuebles);
        model.addAttribute("solicitudes", solicitudes);
        model.addAttribute("totalUsuarios", usuarios.size());
        model.addAttribute("totalInmuebles", inmuebles.size());
        model.addAttribute("solicitudesPendientes", solicitudesPendientes);

        // ── Métricas para la pestaña de Reportes ──
        long disponibles = contarPorEstado(inmuebles, EstadoInmueble.DISPONIBLE);
        long vendidos = contarPorEstado(inmuebles, EstadoInmueble.VENDIDO);
        long arrendados = contarPorEstado(inmuebles, EstadoInmueble.ALQUILADO);
        long enVenta = contarPorOperacion(inmuebles, TipoOperacion.VENTA);
        long enArriendo = contarPorOperacion(inmuebles, TipoOperacion.ARRIENDO);

        Map<String, Long> porTipo = inmuebles.stream()
                .filter(i -> i.getTipo() != null)
                .collect(Collectors.groupingBy(i -> i.getTipo().name(), Collectors.counting()));
        Map<String, Long> porTipoOrdenado = porTipo.entrySet().stream()
                .sorted(Map.Entry.<String, Long>comparingByValue().reversed())
                .collect(Collectors.toMap(Map.Entry::getKey, Map.Entry::getValue,
                        (a, b) -> a, LinkedHashMap::new));

        model.addAttribute("disponibles", disponibles);
        model.addAttribute("vendidos", vendidos);
        model.addAttribute("arrendados", arrendados);
        model.addAttribute("enVenta", enVenta);
        model.addAttribute("enArriendo", enArriendo);
        model.addAttribute("porTipo", porTipoOrdenado);

        // ── Publicadores distintos (para el filtro del reporte "Por publicador") ──
        model.addAttribute("publicadores", publicadoresDistintos(inmuebles));

        return "Admin/dashboard";
    }

    private long contarPorEstado(List<Inmueble> inmuebles, EstadoInmueble estado) {
        return inmuebles.stream().filter(i -> i.getEstado() == estado).count();
    }

    private long contarPorOperacion(List<Inmueble> inmuebles, TipoOperacion operacion) {
        return inmuebles.stream().filter(i -> i.getOperacion() == operacion).count();
    }

    /**
     * Publicadores (propietarios) distintos que aparecen en los inmuebles
     * actuales, ordenados por nombre. Se usan para llenar el select del
     * reporte "Por publicador" sin mostrar usuarios que nunca han publicado.
     */
    private List<Usuario> publicadoresDistintos(List<Inmueble> inmuebles) {
        Map<Long, Usuario> unicos = new LinkedHashMap<>();
        for (Inmueble i : inmuebles) {
            if (i.getPropietario() != null) {
                unicos.putIfAbsent(i.getPropietario().getId(), i.getPropietario());
            }
        }
        List<Usuario> lista = new ArrayList<>(unicos.values());
        lista.sort(Comparator.comparing(u -> u.getNombre() == null ? "" : u.getNombre(),
                String.CASE_INSENSITIVE_ORDER));
        return lista;
    }
}
