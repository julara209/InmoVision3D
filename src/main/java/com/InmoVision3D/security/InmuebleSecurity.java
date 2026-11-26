package com.InmoVision3D.security;

import com.InmoVision3D.model.Inmueble;
import com.InmoVision3D.model.Usuario;
import com.InmoVision3D.repository.InmuebleRepository;
import com.InmoVision3D.repository.UsuarioRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Component;

/**
 * Evita que un PUBLICADOR edite/elimine inmuebles de otro publicador
 * cambiando el ID directamente en la URL.
 *
 * Uso en un Controller (de Persona 2):
 *   @PreAuthorize("hasRole('ADMIN') or @inmuebleSecurity.esPropietario(#id, authentication.name)")
 */
@Component("inmuebleSecurity")
public class InmuebleSecurity {

    @Autowired
    private InmuebleRepository inmuebleRepository;

    @Autowired
    private UsuarioRepository usuarioRepository;

    public boolean esPropietario(Long idInmueble, String correoUsuarioLogueado) {
        Inmueble inmueble = inmuebleRepository.findById(idInmueble).orElse(null);
        if (inmueble == null) return false;

        Usuario usuario = usuarioRepository.findByEmail(correoUsuarioLogueado).orElse(null);
        if (usuario == null) return false;

        return inmueble.getPropietario() != null
                && inmueble.getPropietario().getId().equals(usuario.getId());
    }
}
