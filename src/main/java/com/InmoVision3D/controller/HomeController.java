package com.InmoVision3D.controller;

import com.InmoVision3D.model.Inmueble;
import com.InmoVision3D.model.Usuario;
import com.InmoVision3D.model.enums.EstadoInmueble;
import com.InmoVision3D.repository.FavoritoRepository;
import com.InmoVision3D.repository.InmuebleRepository;
import com.InmoVision3D.repository.UsuarioRepository;
import org.springframework.data.domain.PageRequest;
import org.springframework.data.domain.Sort;
import org.springframework.data.jpa.domain.Specification;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;

import java.util.HashSet;
import java.util.List;
import java.util.Set;

/**
 * Muestra los 6 inmuebles disponibles más recientes
 * como "destacados".
 */
@Controller
public class HomeController {

    private final InmuebleRepository inmuebleRepository;
    private final UsuarioRepository usuarioRepository;
    private final FavoritoRepository favoritoRepository;

    public HomeController(InmuebleRepository inmuebleRepository,
                           UsuarioRepository usuarioRepository,
                           FavoritoRepository favoritoRepository) {
        this.inmuebleRepository = inmuebleRepository;
        this.usuarioRepository = usuarioRepository;
        this.favoritoRepository = favoritoRepository;
    }

    @GetMapping("/")
    public String home(Authentication authentication, Model model) {

        Specification<Inmueble> disponibles =
                (root, query, cb) -> cb.equal(root.get("estado"), EstadoInmueble.DISPONIBLE);

        List<Inmueble> destacados = inmuebleRepository.findAll(
                disponibles,
                PageRequest.of(0, 6, Sort.by(Sort.Direction.DESC, "fechaPublicacion"))
        ).getContent();

        Usuario usuarioActual = usuarioActual(authentication);

        model.addAttribute("inmuebles", destacados);
        model.addAttribute("favoritosIds", favoritosDelUsuario(usuarioActual));
        model.addAttribute("usuarioActual", usuarioActual);

        return "home";
    }

    private Set<Long> favoritosDelUsuario(Usuario usuarioActual) {
        Set<Long> ids = new HashSet<>();
        if (usuarioActual != null) {
            favoritoRepository.findByUsuarioId(usuarioActual.getId())
                    .forEach(f -> ids.add(f.getInmueble().getId()));
        }
        return ids;
    }

    private Usuario usuarioActual(Authentication authentication) {
        if (authentication == null || !authentication.isAuthenticated()
                || "anonymousUser".equals(authentication.getPrincipal())) {
            return null;
        }
        return usuarioRepository.findByEmail(authentication.getName()).orElse(null);
    }
}
