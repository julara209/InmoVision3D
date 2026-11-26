package com.InmoVision3D.controller;

import com.InmoVision3D.service.FileStorageService;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;

import java.util.Map;

/**
 * Sube imágenes de inmuebles y planos 2D a disco. El flujo desde el
 * frontend es: 1) subir el archivo aquí y recibir su URL pública,
 * 2) enviar esa URL a ImagenInmuebleController / Plano2DController para
 * asociarla al inmueble correspondiente.
 */
@RestController
@RequestMapping("/api/uploads")
public class FileUploadController {

    private final FileStorageService fileStorageService;

    public FileUploadController(FileStorageService fileStorageService) {
        this.fileStorageService = fileStorageService;
    }

    @PostMapping("/imagenes")
    public ResponseEntity<Map<String, String>> subirImagen(@RequestParam("file") MultipartFile file) {
        String url = fileStorageService.guardarImagen(file);
        return ResponseEntity.status(HttpStatus.CREATED).body(Map.of("url", url));
    }

    @PostMapping("/planos")
    public ResponseEntity<Map<String, String>> subirPlano(@RequestParam("file") MultipartFile file) {
        String url = fileStorageService.guardarPlano(file);
        return ResponseEntity.status(HttpStatus.CREATED).body(Map.of("url", url));
    }
}
