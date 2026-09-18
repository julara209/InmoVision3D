package com.InmoVision3D.service.impl;

import com.InmoVision3D.exception.BusinessException;
import com.InmoVision3D.exception.ResourceNotFoundException;
import com.InmoVision3D.model.Inmueble;
import com.InmoVision3D.model.Solicitud;
import com.InmoVision3D.model.Usuario;
import com.InmoVision3D.model.enums.EstadoSolicitud;
import com.InmoVision3D.repository.InmuebleRepository;
import com.InmoVision3D.repository.SolicitudRepository;
import com.InmoVision3D.repository.UsuarioRepository;
import com.InmoVision3D.service.SolicitudService;
import com.InmoVision3D.service.notificacion.SolicitudObserver;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;
import java.util.List;

/**
 * PATRÓN GoF: Observer — este servicio es el "sujeto". Al cambiar el estado
 * de una solicitud avisa a todos los {@link SolicitudObserver} disponibles
 * (Spring inyecta aquí, en una sola lista, todos los beans que implementen
 * esa interfaz — ver el paquete service.notificacion), sin necesitar saber
 * cuántos hay ni qué hace cada uno.
 */
@Service
@Transactional
public class SolicitudServiceImpl implements SolicitudService {

    private final SolicitudRepository solicitudRepository;
    private final UsuarioRepository usuarioRepository;
    private final InmuebleRepository inmuebleRepository;
    private final List<SolicitudObserver> observadores;

    public SolicitudServiceImpl(SolicitudRepository solicitudRepository, UsuarioRepository usuarioRepository,
                                 InmuebleRepository inmuebleRepository, List<SolicitudObserver> observadores) {
        this.solicitudRepository = solicitudRepository;
        this.usuarioRepository = usuarioRepository;
        this.inmuebleRepository = inmuebleRepository;
        this.observadores = observadores;
    }

    @Override
    public Solicitud crear(Long usuarioId, Long inmuebleId, String mensaje, LocalDateTime fechaCita) {
        Usuario usuario = usuarioRepository.findById(usuarioId)
                .orElseThrow(() -> new ResourceNotFoundException("Usuario", usuarioId));
        Inmueble inmueble = inmuebleRepository.findById(inmuebleId)
                .orElseThrow(() -> new ResourceNotFoundException("Inmueble", inmuebleId));

        if (inmueble.getPropietario() != null && inmueble.getPropietario().getId().equals(usuarioId)) {
            throw new BusinessException("No puedes solicitar información sobre tu propio inmueble");
        }

        if (fechaCita != null && fechaCita.isBefore(LocalDateTime.now())) {
            throw new BusinessException("La fecha y hora de la cita debe ser en el futuro");
        }

        Solicitud solicitud = new Solicitud();
        solicitud.setUsuario(usuario);
        solicitud.setInmueble(inmueble);
        solicitud.setMensaje(mensaje);
        solicitud.setEstado(EstadoSolicitud.PENDIENTE);
        solicitud.setFechaCita(fechaCita);
        return solicitudRepository.save(solicitud);
    }

    @Override
    @Transactional(readOnly = true)
    public Solicitud obtenerPorId(Long id) {
        return solicitudRepository.findById(id)
                .orElseThrow(() -> new ResourceNotFoundException("Solicitud", id));
    }

    @Override
    @Transactional(readOnly = true)
    public List<Solicitud> listarPorUsuario(Long usuarioId) {
        return solicitudRepository.findByUsuarioId(usuarioId);
    }

    @Override
    @Transactional(readOnly = true)
    public List<Solicitud> listarPorInmueble(Long inmuebleId) {
        return solicitudRepository.findByInmuebleId(inmuebleId);
    }

    @Override
    @Transactional(readOnly = true)
    public List<Solicitud> listarPorPropietario(Long propietarioId) {
        return solicitudRepository.findByInmueble_Propietario_Id(propietarioId);
    }

    @Override
    @Transactional(readOnly = true)
    public List<Solicitud> listarTodas() {
        return solicitudRepository.findAll();
    }

    @Override
    public Solicitud cambiarEstado(Long id, EstadoSolicitud nuevoEstado) {
        Solicitud solicitud = obtenerPorId(id);
        EstadoSolicitud estadoAnterior = solicitud.getEstado();
        solicitud.setEstado(nuevoEstado);
        Solicitud actualizada = solicitudRepository.save(solicitud);

        // PATRÓN GoF: Observer — se notifica solo si el estado realmente
        // cambió, para no disparar avisos innecesarios cuando el nuevo
        // estado coincide con el que ya tenía.
        if (estadoAnterior != nuevoEstado) {
            observadores.forEach(observador -> observador.onCambioEstado(actualizada, estadoAnterior));
        }

        return actualizada;
    }

    @Override
    public void eliminar(Long id) {
        Solicitud solicitud = obtenerPorId(id);
        solicitudRepository.delete(solicitud);
    }
}
