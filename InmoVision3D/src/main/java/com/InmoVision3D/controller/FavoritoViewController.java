package com.InmoVision3D.controller;

import com.InmoVision3D.model.Favorito;
import com.InmoVision3D.model.Inmueble;
import com.InmoVision3D.model.Usuario;
import com.InmoVision3D.service.FavoritoService;
import com.InmoVision3D.service.UsuarioService;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;

import java.util.List;
import java.util.stream.Collectors;


@Controller
@RequestMapping("/usuario/favoritos")
public class FavoritoViewController {

    private final FavoritoService favoritoService;
    private final UsuarioService usuarioService;

    public FavoritoViewController(FavoritoService favoritoService, UsuarioService usuarioService) {
        this.favoritoService = favoritoService;
        this.usuarioService = usuarioService;
    }

    @GetMapping
    public String favoritos(Model model, Authentication authentication) {
        Usuario usuario = usuarioService.obtenerPorEmail(authentication.getName());
        List<Favorito> favoritos = favoritoService.listarPorUsuario(usuario.getId());

        List<Inmueble> inmuebles = favoritos.stream()
                .map(Favorito::getInmueble)
                .collect(Collectors.toList());

        model.addAttribute("usuarioActual", usuario);
        model.addAttribute("favoritos", inmuebles);
        model.addAttribute("total", inmuebles.size());
        return "usuario/favoritos";
    }
}
