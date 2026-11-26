package com.InmoVision3D.controller;

import com.InmoVision3D.model.Inmueble;
import com.InmoVision3D.model.Solicitud;
import com.InmoVision3D.model.Usuario;
import com.InmoVision3D.service.InmuebleService;
import com.InmoVision3D.service.SolicitudService;
import com.InmoVision3D.service.UsuarioService;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;

import java.util.List;

@Controller
@RequestMapping("/usuario")
public class SolicitudViewController {

    private final SolicitudService solicitudService;
    private final InmuebleService inmuebleService;
    private final UsuarioService usuarioService;

    public SolicitudViewController(SolicitudService solicitudService, InmuebleService inmuebleService,
                                    UsuarioService usuarioService) {
        this.solicitudService = solicitudService;
        this.inmuebleService = inmuebleService;
        this.usuarioService = usuarioService;
    }

    @GetMapping("/solicitudes")
    public String solicitudes(@RequestParam(required = false) Long nueva,
                               Model model, Authentication authentication) {

        Usuario usuarioActual = usuarioService.obtenerPorEmail(authentication.getName());
        String rol = usuarioActual.getRol().name();

        model.addAttribute("usuarioActual", usuarioActual);
        model.addAttribute("esCliente", rol.equals("CLIENTE"));
        model.addAttribute("esPublicador", rol.equals("PUBLICADOR"));
        model.addAttribute("esAdmin", rol.equals("ADMIN"));

        if (rol.equals("CLIENTE") || rol.equals("ADMIN")) {
            List<Solicitud> enviadas = solicitudService.listarPorUsuario(usuarioActual.getId());
            model.addAttribute("solicitudesEnviadas", enviadas);
        }

        if (rol.equals("PUBLICADOR")) {
            List<Solicitud> recibidas = solicitudService.listarPorPropietario(usuarioActual.getId());
            model.addAttribute("solicitudesRecibidas", recibidas);
        } else if (rol.equals("ADMIN")) {
            model.addAttribute("solicitudesRecibidas", solicitudService.listarTodas());
        }

        // Formulario de nueva solicitud (solo tiene sentido para un cliente)
        if (nueva != null && rol.equals("CLIENTE")) {
            Inmueble inmueble = inmuebleService.obtenerPorId(nueva);
            model.addAttribute("inmuebleNuevaSolicitud", inmueble);
        }

        return "usuario/solicitudes";
    }
}
