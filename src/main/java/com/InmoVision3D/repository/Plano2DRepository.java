package com.InmoVision3D.repository;

import com.InmoVision3D.model.Plano2D;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface Plano2DRepository extends JpaRepository<Plano2D, Long> {

    List<Plano2D> findByInmuebleId(Long inmuebleId);
}
