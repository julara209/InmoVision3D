package com.InmoVision3D.service;

import com.InmoVision3D.exception.ResourceNotFoundException;
import com.InmoVision3D.model.Inmueble;
import com.InmoVision3D.model.Usuario;
import com.InmoVision3D.model.enums.EstadoInmueble;
import com.InmoVision3D.model.enums.EstadoPublicacion;
import com.InmoVision3D.model.enums.RolUsuario;
import com.InmoVision3D.model.enums.TipoInmueble;
import com.InmoVision3D.repository.InmuebleRepository;
import com.InmoVision3D.repository.UsuarioRepository;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
import java.util.List;

@Service
@Transactional
public class InmuebleService {

    private final InmuebleRepository inmuebleRepository;
    private final UsuarioRepository usuarioRepository;

    public InmuebleService(InmuebleRepository inmuebleRepository, UsuarioRepository usuarioRepository) {
        this.inmuebleRepository = inmuebleRepository;
        this.usuarioRepository = usuarioRepository;
    }

    public Inmueble crear(Inmueble inmueble, Long propietarioId) {
        Usuario propietario = usuarioRepository.findById(propietarioId)
                .orElseThrow(() -> new ResourceNotFoundException("Usuario", propietarioId));
        inmueble.setId(null);
        inmueble.setPropietario(propietario);
        if (inmueble.getEstado() == null) {
            inmueble.setEstado(EstadoInmueble.DISPONIBLE);
        }
        inmueble.setEstadoPublicacion(EstadoPublicacion.PENDIENTE);
        inmueble.setObservacionRevision(null);
        return inmuebleRepository.save(inmueble);
    }

    @Transactional(readOnly = true)
    public Inmueble obtenerPorId(Long id) {
        return inmuebleRepository.findById(id)
                .orElseThrow(() -> new ResourceNotFoundException("Inmueble", id));
    }

    
    @Transactional(readOnly = true)
    public Inmueble obtenerVisiblePara(Long id, Usuario usuario) {
        Inmueble inmueble = obtenerPorId(id);
        if (inmueble.getEstadoPublicacion() == EstadoPublicacion.APROBADA) {
            return inmueble;
        }
        boolean esAdmin = usuario != null && usuario.getRol() == RolUsuario.ADMIN;
        boolean esPropietario = usuario != null && inmueble.getPropietario() != null
                && inmueble.getPropietario().getId().equals(usuario.getId());
        if (esAdmin || esPropietario) {
            return inmueble;
        }
        throw new ResourceNotFoundException("Inmueble", id);
    }

    /** Todos los inmuebles sin filtrar: solo para uso administrativo (panel admin, "ver todos"). */
    @Transactional(readOnly = true)
    public List<Inmueble> listarTodos() {
        return inmuebleRepository.findAll();
    }

    // ---- Consultas publicas (catalogo / API de lectura): solo APROBADA ----

    @Transactional(readOnly = true)
    public List<Inmueble> listarAprobados() {
        return inmuebleRepository.findByEstadoPublicacion(EstadoPublicacion.APROBADA);
    }

    @Transactional(readOnly = true)
    public List<Inmueble> listarPorEstado(EstadoInmueble estado) {
        return inmuebleRepository.findByEstadoAndEstadoPublicacion(estado, EstadoPublicacion.APROBADA);
    }

    @Transactional(readOnly = true)
    public List<Inmueble> listarPorTipo(TipoInmueble tipo) {
        return inmuebleRepository.findByTipoAndEstadoPublicacion(tipo, EstadoPublicacion.APROBADA);
    }

    @Transactional(readOnly = true)
    public List<Inmueble> listarPorCiudad(String ciudad) {
        return inmuebleRepository.findByCiudadIgnoreCaseContainingAndEstadoPublicacion(ciudad, EstadoPublicacion.APROBADA);
    }

    @Transactional(readOnly = true)
    public List<Inmueble> listarPorPropietario(Long propietarioId) {
        return inmuebleRepository.findByPropietarioId(propietarioId);
    }

    @Transactional(readOnly = true)
    public List<Inmueble> listarPorRangoPrecio(BigDecimal min, BigDecimal max) {
        return inmuebleRepository.findByPrecioBetweenAndEstadoPublicacion(min, max, EstadoPublicacion.APROBADA);
    }

    public Inmueble actualizar(Long id, Inmueble datos) {
        Inmueble existente = obtenerPorId(id);

        existente.setTitulo(datos.getTitulo());
        existente.setDescripcion(datos.getDescripcion());
        existente.setPrecio(datos.getPrecio());
        existente.setDireccion(datos.getDireccion());
        existente.setCiudad(datos.getCiudad());
        existente.setTipo(datos.getTipo());
        existente.setOperacion(datos.getOperacion());
        existente.setEstado(datos.getEstado());
        existente.setArea(datos.getArea());
        existente.setHabitaciones(datos.getHabitaciones());
        existente.setBanos(datos.getBanos());

        return inmuebleRepository.save(existente);
    }

    public void eliminar(Long id) {
        Inmueble existente = obtenerPorId(id);
        inmuebleRepository.delete(existente);
    }
}
