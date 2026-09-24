package com.InmoVision3D.service;

import com.InmoVision3D.exception.ResourceNotFoundException;
import com.InmoVision3D.model.ImagenInmueble;
import com.InmoVision3D.model.Inmueble;
import com.InmoVision3D.repository.ImagenInmuebleRepository;
import com.InmoVision3D.repository.InmuebleRepository;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;

@Service
@Transactional
public class ImagenInmuebleService {

    private final ImagenInmuebleRepository imagenRepository;
    private final InmuebleRepository inmuebleRepository;

    public ImagenInmuebleService(ImagenInmuebleRepository imagenRepository, InmuebleRepository inmuebleRepository) {
        this.imagenRepository = imagenRepository;
        this.inmuebleRepository = inmuebleRepository;
    }

    public ImagenInmueble agregar(Long inmuebleId, ImagenInmueble imagen) {
        Inmueble inmueble = inmuebleRepository.findById(inmuebleId)
                .orElseThrow(() -> new ResourceNotFoundException("Inmueble", inmuebleId));
        imagen.setId(null);
        imagen.setInmueble(inmueble);
        return imagenRepository.save(imagen);
    }

    @Transactional(readOnly = true)
    public List<ImagenInmueble> listarPorInmueble(Long inmuebleId) {
        return imagenRepository.findByInmuebleId(inmuebleId);
    }

    public ImagenInmueble actualizar(Long id, ImagenInmueble datos) {
        ImagenInmueble existente = imagenRepository.findById(id)
                .orElseThrow(() -> new ResourceNotFoundException("ImagenInmueble", id));
        existente.setUrl(datos.getUrl());
        existente.setDescripcion(datos.getDescripcion());
        existente.setPrincipal(datos.isPrincipal());
        return imagenRepository.save(existente);
    }

    public void eliminar(Long id) {
        if (!imagenRepository.existsById(id)) {
            throw new ResourceNotFoundException("ImagenInmueble", id);
        }
        imagenRepository.deleteById(id);
    }
}
