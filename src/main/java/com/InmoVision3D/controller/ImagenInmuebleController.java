package com.InmoVision3D.controller;

import com.InmoVision3D.model.ImagenInmueble;
import com.InmoVision3D.service.ImagenInmuebleService;
import jakarta.validation.Valid;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/inmuebles/{inmuebleId}/imagenes")
public class ImagenInmuebleController {

    private final ImagenInmuebleService imagenService;

    public ImagenInmuebleController(ImagenInmuebleService imagenService) {
        this.imagenService = imagenService;
    }

    @PostMapping
    public ResponseEntity<ImagenInmueble> agregar(@PathVariable Long inmuebleId,
                                                   @Valid @RequestBody ImagenInmueble imagen) {
        return ResponseEntity.status(HttpStatus.CREATED).body(imagenService.agregar(inmuebleId, imagen));
    }

    @GetMapping
    public ResponseEntity<List<ImagenInmueble>> listar(@PathVariable Long inmuebleId) {
        return ResponseEntity.ok(imagenService.listarPorInmueble(inmuebleId));
    }

    @PutMapping("/{id}")
    public ResponseEntity<ImagenInmueble> actualizar(@PathVariable Long inmuebleId, @PathVariable Long id,
                                                      @Valid @RequestBody ImagenInmueble datos) {
        return ResponseEntity.ok(imagenService.actualizar(id, datos));
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<Void> eliminar(@PathVariable Long inmuebleId, @PathVariable Long id) {
        imagenService.eliminar(id);
        return ResponseEntity.noContent().build();
    }
}
