package com.InmoVision3D.controller;

import com.InmoVision3D.model.Usuario;
import com.InmoVision3D.model.enums.RolUsuario;
import com.InmoVision3D.repository.UsuarioRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

/**
 * NOTA DE COORDINACIÓN: este controller es un punto de encuentro entre
 * Seguridad (Persona 3) y Vistas/Usuario (Persona 2). Aquí solo se deja
 * lo mínimo para que login.html y registro.html funcionen de punta a
 * punta. Si Persona 2 ya tiene su propio AuthController, fusiona estos
 * métodos con el de ellos en vez de tener dos controllers en /auth/**.
 *
 * El login (POST /auth/login) NO se maneja aquí: lo procesa Spring
 * Security automáticamente según loginProcessingUrl en SecurityConfig.
 * Este controller solo entrega la vista GET del login.
 */
@Controller
@RequestMapping("/auth")
public class AuthViewController {

    @Autowired
    private UsuarioRepository usuarioRepository;

    @Autowired
    private PasswordEncoder passwordEncoder;

    @GetMapping("/login")
    public String mostrarLogin() {
        return "auth/login";
    }

    @GetMapping("/registro")
    public String mostrarRegistro() {
        return "auth/registro";
    }

    @PostMapping("/registro")
    public String procesarRegistro(@RequestParam String nombre,
                                    @RequestParam String apellido,
                                    @RequestParam String correo,
                                    @RequestParam(required = false) String telefono,
                                    @RequestParam String contrasena,
                                    @RequestParam("confirmar_contrasena") String confirmarContrasena,
                                    @RequestParam String rol,
                                    RedirectAttributes redirectAttributes) {

        if (!contrasena.equals(confirmarContrasena)) {
            redirectAttributes.addAttribute("error", true);
            return "redirect:/auth/registro";
        }

        if (usuarioRepository.findByEmail(correo).isPresent()) {
            redirectAttributes.addAttribute("error", true);
            return "redirect:/auth/registro";
        }

        // Solo se permiten estos dos roles desde el registro público;
        // ADMIN se asigna manualmente en la base de datos.
        RolUsuario rolFinal = "publicador".equalsIgnoreCase(rol) ? RolUsuario.PUBLICADOR : RolUsuario.CLIENTE;

        Usuario usuario = new Usuario();
        usuario.setNombre(nombre);
        usuario.setApellido(apellido);
        usuario.setEmail(correo);
        usuario.setTelefono(telefono);
        usuario.setPassword(passwordEncoder.encode(contrasena));
        usuario.setRol(rolFinal);

        usuarioRepository.save(usuario);

        return "redirect:/auth/login";
    }
}
