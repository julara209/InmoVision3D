package com.InmoVision3D.repository;

import com.InmoVision3D.model.ImagenInmueble;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface ImagenInmuebleRepository extends JpaRepository<ImagenInmueble, Long> {

    List<ImagenInmueble> findByInmuebleId(Long inmuebleId);
}
