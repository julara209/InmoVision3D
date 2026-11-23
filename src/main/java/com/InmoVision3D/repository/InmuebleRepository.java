package com.InmoVision3D.repository;

import com.InmoVision3D.model.Inmueble;
import com.InmoVision3D.model.enums.EstadoInmueble;
import com.InmoVision3D.model.enums.TipoInmueble;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.JpaSpecificationExecutor;

import java.math.BigDecimal;
import java.util.List;

public interface InmuebleRepository extends JpaRepository<Inmueble, Long>, JpaSpecificationExecutor<Inmueble> {

    List<Inmueble> findByEstado(EstadoInmueble estado);

    List<Inmueble> findByTipo(TipoInmueble tipo);

    List<Inmueble> findByCiudadIgnoreCaseContaining(String ciudad);

    List<Inmueble> findByPropietarioId(Long propietarioId);

    List<Inmueble> findByPrecioBetween(BigDecimal precioMin, BigDecimal precioMax);

    List<Inmueble> findByTituloContainingIgnoreCase(String titulo);
}
