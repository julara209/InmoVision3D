package com.InmoVision3D.security;

import com.InmoVision3D.model.Usuario;
import com.InmoVision3D.repository.UsuarioRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.core.userdetails.UserDetails;
import org.springframework.security.core.userdetails.UserDetailsService;
import org.springframework.security.core.userdetails.UsernameNotFoundException;
import org.springframework.stereotype.Service;

/**
 * Puente entre la tabla "usuarios" y Spring Security.
 */
@Service
public class UsuarioDetailsService implements UserDetailsService {

    @Autowired
    private UsuarioRepository usuarioRepository;

    @Override
    public UserDetails loadUserByUsername(String correo) throws UsernameNotFoundException {
        Usuario usuario = usuarioRepository.findByEmail(correo)
                .orElseThrow(() -> new UsernameNotFoundException("Credenciales incorrectas"));

        return org.springframework.security.core.userdetails.User.builder()
                .username(usuario.getEmail())
                .password(usuario.getPassword())
                // ROLE_CLIENTE, ROLE_PUBLICADOR o ROLE_ADMIN según el enum Rol
                .authorities("ROLE_" + usuario.getRol().name())
                .build();
    }
}
