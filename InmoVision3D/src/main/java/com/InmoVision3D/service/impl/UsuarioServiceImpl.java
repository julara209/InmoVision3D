package com.InmoVision3D.service.impl;

import com.InmoVision3D.exception.BusinessException;
import com.InmoVision3D.exception.ResourceNotFoundException;
import com.InmoVision3D.model.Usuario;
import com.InmoVision3D.repository.UsuarioRepository;
import com.InmoVision3D.service.UsuarioService;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;

@Service
@Transactional
public class UsuarioServiceImpl implements UsuarioService {

    private final UsuarioRepository usuarioRepository;
    private final PasswordEncoder passwordEncoder;

    public UsuarioServiceImpl(UsuarioRepository usuarioRepository, PasswordEncoder passwordEncoder) {
        this.usuarioRepository = usuarioRepository;
        this.passwordEncoder = passwordEncoder;
    }

    @Override
    public Usuario crear(Usuario usuario) {
        if (usuarioRepository.existsByEmail(usuario.getEmail())) {
            throw new BusinessException("Ya existe un usuario registrado con ese email");
        }
        usuario.setId(null);
        usuario.setPassword(passwordEncoder.encode(usuario.getPassword()));
        usuario.setActivo(true);
        // La columna apellido es NOT NULL: si no viene, se guarda vacio (no null).
        if (usuario.getApellido() == null) {
            usuario.setApellido("");
        }
        return usuarioRepository.save(usuario);
    }

    @Override
    @Transactional(readOnly = true)
    public Usuario obtenerPorId(Long id) {
        return usuarioRepository.findById(id)
                .orElseThrow(() -> new ResourceNotFoundException("Usuario", id));
    }

    @Override
    @Transactional(readOnly = true)
    public Usuario obtenerPorEmail(String email) {
        return usuarioRepository.findByEmail(email)
                .orElseThrow(() -> new ResourceNotFoundException("Usuario con email " + email + " no encontrado"));
    }

    @Override
    @Transactional(readOnly = true)
    public List<Usuario> listarTodos() {
        return usuarioRepository.findAll();
    }

    @Override
    public Usuario actualizar(Long id, Usuario datos) {
        Usuario existente = obtenerPorId(id);

        // Solo se aplican los campos que llegan con contenido. Antes se copiaban
        // tal cual, asi que un campo vacio (por ejemplo un usuario sin apellido)
        // dejaba la entidad invalida y Hibernate lanzaba una ConstraintViolation
        // al hacer flush: el panel mostraba "Error interno" al guardar.
        if (tieneTexto(datos.getNombre())) {
            existente.setNombre(datos.getNombre().trim());
        }
        if (datos.getApellido() != null) {
            existente.setApellido(datos.getApellido().trim());
        }
        if (existente.getApellido() == null) {
            existente.setApellido("");
        }
        // El telefono si puede quedar vacio: es opcional, se guarda null.
        if (datos.getTelefono() != null) {
            existente.setTelefono(datos.getTelefono().isBlank() ? null : datos.getTelefono().trim());
        }

        if (tieneTexto(datos.getEmail()) && !datos.getEmail().trim().equalsIgnoreCase(existente.getEmail())) {
            String nuevoEmail = datos.getEmail().trim();
            if (usuarioRepository.existsByEmail(nuevoEmail)) {
                throw new BusinessException("Ya existe un usuario registrado con ese email");
            }
            existente.setEmail(nuevoEmail);
        }

        if (tieneTexto(datos.getPassword())) {
            if (datos.getPassword().length() < 6) {
                throw new BusinessException("La contrasena debe tener al menos 6 caracteres");
            }
            existente.setPassword(passwordEncoder.encode(datos.getPassword()));
        }

        // El rol solo cambia si viene indicado de forma explicita.
        if (datos.getRol() != null) {
            existente.setRol(datos.getRol());
        }

        return usuarioRepository.save(existente);
    }

    /**
     * Actualiza unicamente los datos personales del propio usuario.
     *
     * Existe aparte de {@link #actualizar} porque la pantalla de perfil no envia
     * el rol: al construir un Usuario vacio para transportar los datos, el campo
     * rol tomaba su valor por defecto (CLIENTE) y terminaba degradando a los
     * publicadores y administradores que editaban su perfil.
     */
    @Override
    public Usuario actualizarPerfil(Long id, String nombre, String apellido, String email, String telefono) {
        Usuario datos = new Usuario();
        datos.setNombre(nombre);
        datos.setApellido(apellido);
        datos.setEmail(email);
        datos.setTelefono(telefono);
        datos.setRol(null);      // nunca se toca el rol desde el perfil
        datos.setPassword(null); // la contrasena tiene su propio formulario
        return actualizar(id, datos);
    }

    private boolean tieneTexto(String valor) {
        return valor != null && !valor.isBlank();
    }

    @Override
    public void eliminar(Long id) {
        Usuario existente = obtenerPorId(id);
        usuarioRepository.delete(existente);
    }

    @Override
    public boolean cambiarPassword(Long id, String actual, String nueva) {
        Usuario existente = obtenerPorId(id);
        if (!passwordEncoder.matches(actual, existente.getPassword())) {
            return false;
        }
        existente.setPassword(passwordEncoder.encode(nueva));
        usuarioRepository.save(existente);
        return true;
    }

    @Override
    public void desactivar(Long id) {
        Usuario existente = obtenerPorId(id);
        existente.setActivo(false);
        usuarioRepository.save(existente);
    }
}
