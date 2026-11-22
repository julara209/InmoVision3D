package com.InmoVision3D.controller;

import com.InmoVision3D.model.Plano2D;
import com.InmoVision3D.service.Plano2DService;
import jakarta.validation.Valid;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/inmuebles/{inmuebleId}/planos")
public class Plano2DController {

    private final Plano2DService planoService;

    public Plano2DController(Plano2DService planoService) {
        this.planoService = planoService;
    }

    @PostMapping
    public ResponseEntity<Plano2D> agregar(@PathVariable Long inmuebleId, @Valid @RequestBody Plano2D plano) {
        return ResponseEntity.status(HttpStatus.CREATED).body(planoService.agregar(inmuebleId, plano));
    }

    @GetMapping
    public ResponseEntity<List<Plano2D>> listar(@PathVariable Long inmuebleId) {
        return ResponseEntity.ok(planoService.listarPorInmueble(inmuebleId));
    }

    @PutMapping("/{id}")
    public ResponseEntity<Plano2D> actualizar(@PathVariable Long inmuebleId, @PathVariable Long id,
                                               @Valid @RequestBody Plano2D datos) {
        return ResponseEntity.ok(planoService.actualizar(id, datos));
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<Void> eliminar(@PathVariable Long inmuebleId, @PathVariable Long id) {
        planoService.eliminar(id);
        return ResponseEntity.noContent().build();
    }
}
