package com.InmoVision3D.controller;

import com.InmoVision3D.model.Usuario;
import com.InmoVision3D.service.FavoritoService;
import com.InmoVision3D.service.InmuebleService;
import com.InmoVision3D.service.SolicitudService;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;
import com.InmoVision3D.service.UsuarioService;

/**
 * Sirve /usuario/perfil (no existía ningún @Controller para esta vista;
 * solo había la API REST /api/usuarios).
 */
@Controller
@RequestMapping("/usuario")
public class UsuarioViewController {

    private final UsuarioService usuarioService;
    private final FavoritoService favoritoService;
    private final InmuebleService inmuebleService;
    private final SolicitudService solicitudService;

    public UsuarioViewController(UsuarioService usuarioService, FavoritoService favoritoService,
                                  InmuebleService inmuebleService, SolicitudService solicitudService) {
        this.usuarioService = usuarioService;
        this.favoritoService = favoritoService;
        this.inmuebleService = inmuebleService;
        this.solicitudService = solicitudService;
    }

    @GetMapping("/perfil")
    public String perfil(Model model, Authentication authentication) {
        Usuario usuario = usuarioService.obtenerPorEmail(authentication.getName());
        model.addAttribute("usuario", usuario);
        model.addAttribute("usuarioActual", usuario);
        if (!model.containsAttribute("mensaje")) model.addAttribute("mensaje", "");
        if (!model.containsAttribute("error")) model.addAttribute("error", "");

        // Estadísticas (antes se pedían a un endpoint /Api/estadisticas.php que no existe
        // en el backend Spring; se calculan aquí directamente).
        model.addAttribute("statFavoritos", favoritoService.listarPorUsuario(usuario.getId()).size());
        if (usuario.getRol().name().equals("PUBLICADOR") || usuario.getRol().name().equals("ADMIN")) {
            model.addAttribute("statPublicados", inmuebleService.listarPorPropietario(usuario.getId()).size());
            model.addAttribute("statSolicitudes", solicitudService.listarPorUsuario(usuario.getId()).size());
        }

        return "usuario/perfil";
    }

    @PostMapping("/perfil/datos")
    public String actualizarDatos(Authentication authentication,
                                   @RequestParam String nombre,
                                   @RequestParam String apellido,
                                   @RequestParam String email,
                                   @RequestParam(required = false) String telefono,
                                   RedirectAttributes redirectAttributes) {
        Usuario actual = usuarioService.obtenerPorEmail(authentication.getName());
        try {
            // Se usa actualizarPerfil (y no actualizar) para que el rol no se toque:
            // un publicador o un administrador que edite su perfil debe conservarlo.
            usuarioService.actualizarPerfil(actual.getId(), nombre, apellido, email, telefono);
            redirectAttributes.addFlashAttribute("mensaje", "Perfil actualizado correctamente");
        } catch (RuntimeException e) {
            redirectAttributes.addFlashAttribute("error", "Error al actualizar el perfil: " + e.getMessage());
        }
        return "redirect:/usuario/perfil";
    }

    @PostMapping("/perfil/password")
    public String cambiarPassword(Authentication authentication,
                                   @RequestParam("password_actual") String actual,
                                   @RequestParam("password_nueva") String nueva,
                                   @RequestParam("password_confirmar") String confirmar,
                                   RedirectAttributes redirectAttributes) {
        Usuario usuario = usuarioService.obtenerPorEmail(authentication.getName());

        if (!nueva.equals(confirmar)) {
            redirectAttributes.addFlashAttribute("error", "Las contraseñas no coinciden");
        } else if (nueva.length() < 6) {
            redirectAttributes.addFlashAttribute("error", "La contraseña debe tener al menos 6 caracteres");
        } else if (usuarioService.cambiarPassword(usuario.getId(), actual, nueva)) {
            redirectAttributes.addFlashAttribute("mensaje", "Contraseña actualizada correctamente");
        } else {
            redirectAttributes.addFlashAttribute("error", "La contraseña actual es incorrecta");
        }
        return "redirect:/usuario/perfil";
    }
}
