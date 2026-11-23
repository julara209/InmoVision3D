package com.InmoVision3D.exception;

public class ResourceNotFoundException extends RuntimeException {

    public ResourceNotFoundException(String mensaje) {
        super(mensaje);
    }

    public ResourceNotFoundException(String entidad, Long id) {
        super(entidad + " no encontrado con id: " + id);
    }
}
