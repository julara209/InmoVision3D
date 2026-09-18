package com.InmoVision3D.service.notificacion;

import com.InmoVision3D.model.Solicitud;
import com.InmoVision3D.model.enums.EstadoSolicitud;

/**
 * PATRÓN GoF: Observer.
 *
 * Cada implementación reacciona a un cambio de estado de una
 * {@link Solicitud} (por ejemplo, cuando el publicador la acepta o la
 * rechaza) sin que {@code SolicitudServiceImpl} (el "sujeto") necesite saber
 * cuántos observadores hay ni qué hacen. Spring inyecta automáticamente
 * todos los beans que implementen esta interfaz (ver el constructor de
 * {@code SolicitudServiceImpl}), así que agregar una reacción nueva —por
 * ejemplo, enviar un correo real cuando haya un servicio de mail configurado—
 * es tan simple como crear otra clase con {@code @Component} y no requiere
 * tocar el servicio de solicitudes.
 */
public interface SolicitudObserver {

    /**
     * @param solicitud       la solicitud ya guardada, con el estado nuevo.
     * @param estadoAnterior  el estado que tenía antes del cambio.
     */
    void onCambioEstado(Solicitud solicitud, EstadoSolicitud estadoAnterior);
}
