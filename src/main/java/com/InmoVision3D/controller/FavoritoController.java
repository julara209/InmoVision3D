package com.InmoVision3D.controller;

import com.InmoVision3D.model.Favorito;
import com.InmoVision3D.service.FavoritoService;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/favoritos")
public class FavoritoController {

    private final FavoritoService favoritoService;

    public FavoritoController(FavoritoService favoritoService) {
        this.favoritoService = favoritoService;
    }

    @PostMapping
    public ResponseEntity<Favorito> agregar(@RequestParam Long usuarioId, @RequestParam Long inmuebleId) {
        return ResponseEntity.status(HttpStatus.CREATED).body(favoritoService.agregar(usuarioId, inmuebleId));
    }

    @GetMapping("/usuario/{usuarioId}")
    public ResponseEntity<List<Favorito>> listarPorUsuario(@PathVariable Long usuarioId) {
        return ResponseEntity.ok(favoritoService.listarVisiblesPorUsuario(usuarioId));
    }

    @DeleteMapping
    public ResponseEntity<Void> eliminar(@RequestParam Long usuarioId, @RequestParam Long inmuebleId) {
        favoritoService.eliminar(usuarioId, inmuebleId);
        return ResponseEntity.noContent().build();
    }
}
