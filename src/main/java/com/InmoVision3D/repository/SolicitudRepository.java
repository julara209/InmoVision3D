package com.InmoVision3D.repository;

import com.InmoVision3D.model.Solicitud;
import com.InmoVision3D.model.enums.EstadoSolicitud;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface SolicitudRepository extends JpaRepository<Solicitud, Long> {

    List<Solicitud> findByUsuarioId(Long usuarioId);

    List<Solicitud> findByInmuebleId(Long inmuebleId);

    List<Solicitud> findByInmueble_Propietario_Id(Long propietarioId);

    List<Solicitud> findByEstado(EstadoSolicitud estado);
}
