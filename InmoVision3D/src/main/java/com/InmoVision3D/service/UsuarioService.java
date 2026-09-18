package com.InmoVision3D.service;

import com.InmoVision3D.model.Usuario;

import java.util.List;

public interface UsuarioService {

    Usuario crear(Usuario usuario);

    Usuario obtenerPorId(Long id);

    Usuario obtenerPorEmail(String email);

    List<Usuario> listarTodos();

    Usuario actualizar(Long id, Usuario datos);

    /**
     * Actualiza los datos personales del propio usuario sin tocar nunca
     * su rol ni su contrasena.
     */
    Usuario actualizarPerfil(Long id, String nombre, String apellido, String email, String telefono);

    boolean cambiarPassword(Long id, String actual, String nueva);

    void eliminar(Long id);

    void desactivar(Long id);
}
