package com.InmoVision3D.service.validacion;

import org.springframework.web.multipart.MultipartFile;

/**
 * PATRÓN GoF: Decorator — componente base ("hoja") de la cadena.
 *
 * No valida nada por sí mismo: es el punto de partida sobre el que se van
 * envolviendo los decoradores concretos (ver
 * {@link ValidadorArchivoDecorator}). Sin esta base, cada cadena tendría que
 * arrancar con un caso especial para el primer decorador.
 */
public class ValidadorArchivoBase implements ValidadorArchivo {
    @Override
    public void validar(MultipartFile archivo) {
        // Intencionalmente vacío: es solo el punto de partida de la cadena.
    }
}
