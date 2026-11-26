package com.InmoVision3D.repository;

import com.InmoVision3D.model.Favorito;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;

public interface FavoritoRepository extends JpaRepository<Favorito, Long> {

    List<Favorito> findByUsuarioId(Long usuarioId);

    long countByInmuebleId(Long inmuebleId);

    Optional<Favorito> findByUsuarioIdAndInmuebleId(Long usuarioId, Long inmuebleId);

    boolean existsByUsuarioIdAndInmuebleId(Long usuarioId, Long inmuebleId);

    void deleteByUsuarioIdAndInmuebleId(Long usuarioId, Long inmuebleId);
}
