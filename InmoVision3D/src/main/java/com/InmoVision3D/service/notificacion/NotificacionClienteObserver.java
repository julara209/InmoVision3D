package com.InmoVision3D.service.notificacion;

import com.InmoVision3D.model.Solicitud;
import com.InmoVision3D.model.enums.EstadoSolicitud;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.stereotype.Component;

/**
 * PATRÓN GoF: Observer — segundo observador concreto, independiente del
 * primero.
 *
 * Reacciona solo cuando una solicitud queda ACEPTADA o RECHAZADA (una
 * PENDIENTE no le interesa). El proyecto no tiene todavía un servicio de
 * correo configurado (no hay JavaMailSender ni credenciales SMTP en
 * application.yaml), así que por ahora solo deja constancia en el log de
 * qué se enviaría; cuando se agregue el envío real de correo, basta con
 * completar este método — el resto del código (el "sujeto") no cambia.
 */
@Component
public class NotificacionClienteObserver implements SolicitudObserver {

    private static final Logger log = LoggerFactory.getLogger(NotificacionClienteObserver.class);

    @Override
    public void onCambioEstado(Solicitud solicitud, EstadoSolicitud estadoAnterior) {
        if (solicitud.getEstado() != EstadoSolicitud.ACEPTADA && solicitud.getEstado() != EstadoSolicitud.RECHAZADA) {
            return;
        }
        String email = solicitud.getUsuario() != null ? solicitud.getUsuario().getEmail() : null;
        if (email == null) {
            return;
        }
        // TODO: reemplazar por un envío de correo real cuando el proyecto
        // tenga un JavaMailSender configurado.
        log.info("Se notificaría por correo a {} que su solicitud #{} fue {}",
                email, solicitud.getId(), solicitud.getEstado());
    }
}
