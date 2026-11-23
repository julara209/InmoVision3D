package com.InmoVision3D.controller;

import com.InmoVision3D.model.Solicitud;
import com.InmoVision3D.model.enums.EstadoSolicitud;
import com.InmoVision3D.service.SolicitudService;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.time.LocalDateTime;
import java.util.List;

@RestController
@RequestMapping("/api/solicitudes")
public class SolicitudController {

    private final SolicitudService solicitudService;

    public SolicitudController(SolicitudService solicitudService) {
        this.solicitudService = solicitudService;
    }

    public record NuevaSolicitudRequest(Long usuarioId, Long inmuebleId, String mensaje, LocalDateTime fechaCita) {}
    public record CambioEstadoRequest(EstadoSolicitud estado) {}

    @PostMapping
    public ResponseEntity<Solicitud> crear(@RequestBody NuevaSolicitudRequest request) {
        Solicitud creada = solicitudService.crear(request.usuarioId(), request.inmuebleId(),
                request.mensaje(), request.fechaCita());
        return ResponseEntity.status(HttpStatus.CREATED).body(creada);
    }

    @GetMapping("/{id}")
    public ResponseEntity<Solicitud> obtenerPorId(@PathVariable Long id) {
        return ResponseEntity.ok(solicitudService.obtenerPorId(id));
    }

    @GetMapping("/usuario/{usuarioId}")
    public ResponseEntity<List<Solicitud>> listarPorUsuario(@PathVariable Long usuarioId) {
        return ResponseEntity.ok(solicitudService.listarPorUsuario(usuarioId));
    }

    @GetMapping("/inmueble/{inmuebleId}")
    public ResponseEntity<List<Solicitud>> listarPorInmueble(@PathVariable Long inmuebleId) {
        return ResponseEntity.ok(solicitudService.listarPorInmueble(inmuebleId));
    }

    @PatchMapping("/{id}/estado")
    public ResponseEntity<Solicitud> cambiarEstado(@PathVariable Long id, @RequestBody CambioEstadoRequest request) {
        return ResponseEntity.ok(solicitudService.cambiarEstado(id, request.estado()));
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<Void> eliminar(@PathVariable Long id) {
        solicitudService.eliminar(id);
        return ResponseEntity.noContent().build();
    }
}
