package com.InmoVision3D.controller;

import com.InmoVision3D.model.Inmueble;
import com.InmoVision3D.model.enums.EstadoInmueble;
import com.InmoVision3D.model.enums.TipoInmueble;
import com.InmoVision3D.service.InmuebleService;
import jakarta.validation.Valid;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.math.BigDecimal;
import java.util.List;

@RestController
@RequestMapping("/api/inmuebles")
public class InmuebleController {

    private final InmuebleService inmuebleService;

    public InmuebleController(InmuebleService inmuebleService) {
        this.inmuebleService = inmuebleService;
    }

    // CREATE - POST /api/inmuebles?propietarioId=1
    @PostMapping
    public ResponseEntity<Inmueble> crear(@Valid @RequestBody Inmueble inmueble,
                                           @RequestParam Long propietarioId) {
        Inmueble creado = inmuebleService.crear(inmueble, propietarioId);
        return ResponseEntity.status(HttpStatus.CREATED).body(creado);
    }

    // READ - GET /api/inmuebles
    @GetMapping
    public ResponseEntity<List<Inmueble>> listarTodos() {
        return ResponseEntity.ok(inmuebleService.listarTodos());
    }

    // READ - GET /api/inmuebles/{id}
    @GetMapping("/{id}")
    public ResponseEntity<Inmueble> obtenerPorId(@PathVariable Long id) {
        return ResponseEntity.ok(inmuebleService.obtenerPorId(id));
    }

    // READ - GET /api/inmuebles/estado/DISPONIBLE
    @GetMapping("/estado/{estado}")
    public ResponseEntity<List<Inmueble>> listarPorEstado(@PathVariable EstadoInmueble estado) {
        return ResponseEntity.ok(inmuebleService.listarPorEstado(estado));
    }

    // READ - GET /api/inmuebles/tipo/CASA
    @GetMapping("/tipo/{tipo}")
    public ResponseEntity<List<Inmueble>> listarPorTipo(@PathVariable TipoInmueble tipo) {
        return ResponseEntity.ok(inmuebleService.listarPorTipo(tipo));
    }

    // READ - GET /api/inmuebles/ciudad/{ciudad}
    @GetMapping("/ciudad/{ciudad}")
    public ResponseEntity<List<Inmueble>> listarPorCiudad(@PathVariable String ciudad) {
        return ResponseEntity.ok(inmuebleService.listarPorCiudad(ciudad));
    }

    // READ - GET /api/inmuebles/propietario/{propietarioId}
    @GetMapping("/propietario/{propietarioId}")
    public ResponseEntity<List<Inmueble>> listarPorPropietario(@PathVariable Long propietarioId) {
        return ResponseEntity.ok(inmuebleService.listarPorPropietario(propietarioId));
    }

    // READ - GET /api/inmuebles/precio?min=100000&max=500000
    @GetMapping("/precio")
    public ResponseEntity<List<Inmueble>> listarPorRangoPrecio(@RequestParam BigDecimal min,
                                                                 @RequestParam BigDecimal max) {
        return ResponseEntity.ok(inmuebleService.listarPorRangoPrecio(min, max));
    }

    // UPDATE - PUT /api/inmuebles/{id}
    @PutMapping("/{id}")
    public ResponseEntity<Inmueble> actualizar(@PathVariable Long id, @Valid @RequestBody Inmueble datos) {
        return ResponseEntity.ok(inmuebleService.actualizar(id, datos));
    }

    // DELETE - DELETE /api/inmuebles/{id}
    @DeleteMapping("/{id}")
    public ResponseEntity<Void> eliminar(@PathVariable Long id) {
        inmuebleService.eliminar(id);
        return ResponseEntity.noContent().build();
    }
}
