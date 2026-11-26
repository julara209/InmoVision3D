package com.InmoVision3D.service.notificacion;

import com.InmoVision3D.model.Solicitud;
import com.InmoVision3D.model.enums.EstadoSolicitud;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.stereotype.Component;

/**
 * PATRÓN GoF: Observer — observador concreto.
 *
 * Deja constancia en el log de cada transición de estado, para poder
 * auditar quién aceptó/rechazó qué solicitud y cuándo, sin mezclar esa
 * responsabilidad dentro de {@code SolicitudService}.
 */
@Component
public class RegistroSolicitudObserver implements SolicitudObserver {

    private static final Logger log = LoggerFactory.getLogger(RegistroSolicitudObserver.class);

    @Override
    public void onCambioEstado(Solicitud solicitud, EstadoSolicitud estadoAnterior) {
        log.info("Solicitud #{} (inmueble #{}, usuario {}): {} -> {}",
                solicitud.getId(),
                solicitud.getInmueble() != null ? solicitud.getInmueble().getId() : null,
                solicitud.getUsuario() != null ? solicitud.getUsuario().getEmail() : "?",
                estadoAnterior,
                solicitud.getEstado());
    }
}
