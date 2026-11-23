package com.InmoVision3D.controller;

import com.InmoVision3D.exception.ResourceNotFoundException;
import com.InmoVision3D.model.Inmueble;
import com.InmoVision3D.model.Plano2D;
import com.InmoVision3D.model.Usuario;
import com.InmoVision3D.service.InmuebleService;
import com.InmoVision3D.service.Plano2DService;
import com.InmoVision3D.service.UsuarioService;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;

import java.util.List;


@Controller
@RequestMapping("/planos")
public class PlanoViewController {

    private final InmuebleService inmuebleService;
    private final Plano2DService planoService;
    private final UsuarioService usuarioService;

    public PlanoViewController(InmuebleService inmuebleService, Plano2DService planoService,
                                UsuarioService usuarioService) {
        this.inmuebleService = inmuebleService;
        this.planoService = planoService;
        this.usuarioService = usuarioService;
    }

    @GetMapping("/editor")
    @PreAuthorize("hasAnyRole('PUBLICADOR','ADMIN')")
    public String editorNuevo(Model model, Authentication authentication) {
        model.addAttribute("inmueble", null);
        model.addAttribute("inmuebleId", 0);
        model.addAttribute("planoExistenteJson", "null");
        model.addAttribute("usuarioActual", usuarioService.obtenerPorEmail(authentication.getName()));
        return "planos/editor";
    }

    @GetMapping("/editor/{inmuebleId}")
    @PreAuthorize("hasAnyRole('PUBLICADOR','ADMIN')")
    public String editor(@PathVariable Long inmuebleId, Model model, Authentication authentication) {
        Inmueble inmueble = inmuebleService.obtenerPorId(inmuebleId);
        Usuario usuarioActual = usuarioService.obtenerPorEmail(authentication.getName());

        boolean esDueno = inmueble.getPropietario() != null
                && inmueble.getPropietario().getId().equals(usuarioActual.getId());
        if (!esDueno && !usuarioActual.getRol().name().equals("ADMIN")) {
            return "redirect:/";
        }

        List<Plano2D> planos = planoService.listarPorInmueble(inmuebleId);
        String planoJson = planos.isEmpty() || planos.get(0).getDatos3d() == null
                ? "null" : planos.get(0).getDatos3d();

        model.addAttribute("inmueble", inmueble);
        model.addAttribute("inmuebleId", inmuebleId);
        model.addAttribute("planoExistenteJson", planoJson);
        model.addAttribute("usuarioActual", usuarioActual);
        return "planos/editor";
    }

    @GetMapping("/visor3d/{inmuebleId}")
    public String visor3d(@PathVariable Long inmuebleId, Model model) {
        Inmueble inmueble = inmuebleService.obtenerPorId(inmuebleId);
        List<Plano2D> planos = planoService.listarPorInmueble(inmuebleId);

        if (planos.isEmpty() || planos.get(0).getDatos3d() == null) {
            throw new ResourceNotFoundException("Este inmueble aún no tiene un plano 3D generado");
        }

        model.addAttribute("inmueble", inmueble);
        model.addAttribute("inmuebleId", inmuebleId);
        model.addAttribute("planoDataJson", planos.get(0).getDatos3d());
        model.addAttribute("planoArchivo", planos.get(0).getUrl());
        return "planos/visor3d";
    }
}
