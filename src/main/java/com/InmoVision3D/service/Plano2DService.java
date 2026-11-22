package com.InmoVision3D.service;

import com.InmoVision3D.exception.ResourceNotFoundException;
import com.InmoVision3D.model.Inmueble;
import com.InmoVision3D.model.Plano2D;
import com.InmoVision3D.repository.InmuebleRepository;
import com.InmoVision3D.repository.Plano2DRepository;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;

@Service
@Transactional
public class Plano2DService {

    private final Plano2DRepository planoRepository;
    private final InmuebleRepository inmuebleRepository;

    public Plano2DService(Plano2DRepository planoRepository, InmuebleRepository inmuebleRepository) {
        this.planoRepository = planoRepository;
        this.inmuebleRepository = inmuebleRepository;
    }

    public Plano2D agregar(Long inmuebleId, Plano2D plano) {
        Inmueble inmueble = inmuebleRepository.findById(inmuebleId)
                .orElseThrow(() -> new ResourceNotFoundException("Inmueble", inmuebleId));
        plano.setId(null);
        plano.setInmueble(inmueble);
        return planoRepository.save(plano);
    }

    @Transactional(readOnly = true)
    public List<Plano2D> listarPorInmueble(Long inmuebleId) {
        return planoRepository.findByInmuebleId(inmuebleId);
    }

    public Plano2D actualizar(Long id, Plano2D datos) {
        Plano2D existente = planoRepository.findById(id)
                .orElseThrow(() -> new ResourceNotFoundException("Plano2D", id));
        existente.setNombre(datos.getNombre());
        existente.setUrl(datos.getUrl());
        existente.setPiso(datos.getPiso());
        return planoRepository.save(existente);
    }

    public void eliminar(Long id) {
        if (!planoRepository.existsById(id)) {
            throw new ResourceNotFoundException("Plano2D", id);
        }
        planoRepository.deleteById(id);
    }
}
