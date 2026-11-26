package com.InmoVision3D.service;

import com.InmoVision3D.exception.ResourceNotFoundException;
import com.InmoVision3D.model.Inmueble;
import com.InmoVision3D.model.Usuario;
import com.InmoVision3D.model.enums.EstadoInmueble;
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
        return inmuebleRepository.save(inmueble);
    }

    @Transactional(readOnly = true)
    public Inmueble obtenerPorId(Long id) {
        return inmuebleRepository.findById(id)
                .orElseThrow(() -> new ResourceNotFoundException("Inmueble", id));
    }

    @Transactional(readOnly = true)
    public List<Inmueble> listarTodos() {
        return inmuebleRepository.findAll();
    }

    @Transactional(readOnly = true)
    public List<Inmueble> listarPorEstado(EstadoInmueble estado) {
        return inmuebleRepository.findByEstado(estado);
    }

    @Transactional(readOnly = true)
    public List<Inmueble> listarPorTipo(TipoInmueble tipo) {
        return inmuebleRepository.findByTipo(tipo);
    }

    @Transactional(readOnly = true)
    public List<Inmueble> listarPorCiudad(String ciudad) {
        return inmuebleRepository.findByCiudadIgnoreCaseContaining(ciudad);
    }

    @Transactional(readOnly = true)
    public List<Inmueble> listarPorPropietario(Long propietarioId) {
        return inmuebleRepository.findByPropietarioId(propietarioId);
    }

    @Transactional(readOnly = true)
    public List<Inmueble> listarPorRangoPrecio(BigDecimal min, BigDecimal max) {
        return inmuebleRepository.findByPrecioBetween(min, max);
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
