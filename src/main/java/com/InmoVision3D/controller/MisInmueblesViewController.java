package com.InmoVision3D.controller;

import com.InmoVision3D.model.Inmueble;
import com.InmoVision3D.model.Usuario;
import com.InmoVision3D.model.enums.EstadoInmueble;
import com.InmoVision3D.service.InmuebleService;
import com.InmoVision3D.service.UsuarioService;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;

import java.util.List;

@Controller
@RequestMapping("/usuario/mis-inmuebles")
@PreAuthorize("hasAnyRole('PUBLICADOR','ADMIN')")
public class MisInmueblesViewController {

    private final InmuebleService inmuebleService;
    private final UsuarioService usuarioService;

    public MisInmueblesViewController(InmuebleService inmuebleService, UsuarioService usuarioService) {
        this.inmuebleService = inmuebleService;
        this.usuarioService = usuarioService;
    }

    @GetMapping
    public String misInmuebles(@RequestParam(required = false) String todos,
                                Model model, Authentication authentication) {

        Usuario usuario = usuarioService.obtenerPorEmail(authentication.getName());
        boolean esAdmin = usuario.getRol().name().equals("ADMIN");

        List<Inmueble> inmuebles = (esAdmin && todos != null)
                ? inmuebleService.listarTodos()
                : inmuebleService.listarPorPropietario(usuario.getId());

        long disponibles = inmuebles.stream().filter(i -> i.getEstado() == EstadoInmueble.DISPONIBLE).count();
        long vendidos = inmuebles.stream().filter(i -> i.getEstado() == EstadoInmueble.VENDIDO).count();
        long arrendados = inmuebles.stream().filter(i -> i.getEstado() == EstadoInmueble.ALQUILADO).count();
        long pausados = inmuebles.stream().filter(i -> i.getEstado() == EstadoInmueble.RESERVADO).count();

        model.addAttribute("usuarioActual", usuario);
        model.addAttribute("esAdmin", esAdmin);
        model.addAttribute("verTodos", todos != null);
        model.addAttribute("inmuebles", inmuebles);
        model.addAttribute("total", inmuebles.size());
        model.addAttribute("disponibles", disponibles);
        model.addAttribute("vendidos", vendidos);
        model.addAttribute("arrendados", arrendados);
        model.addAttribute("pausados", pausados);

        return "usuario/mis-inmuebles";
    }
}
