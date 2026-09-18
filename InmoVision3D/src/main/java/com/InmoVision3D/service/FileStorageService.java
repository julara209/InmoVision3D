package com.InmoVision3D.service;

import com.InmoVision3D.exception.BusinessException;
import com.InmoVision3D.service.validacion.ValidadorArchivo;
import com.InmoVision3D.service.validacion.ValidadorArchivoBase;
import com.InmoVision3D.service.validacion.ValidadorExtension;
import com.InmoVision3D.service.validacion.ValidadorNoVacio;
import com.InmoVision3D.service.validacion.ValidadorTamanoMaximo;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import jakarta.annotation.PostConstruct;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;
import org.springframework.util.StringUtils;
import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.nio.file.StandardCopyOption;
import java.util.List;
import java.util.UUID;

/**
 * Guarda archivos subidos (imágenes de inmuebles, planos 2D) en disco y
 * devuelve la URL pública bajo la que quedan disponibles (servida por
 * WebConfig en /uploads/**).
 *
 * Destino por defecto (ver app.upload-dir en application.yaml):
 *     src/main/resources/static/uploads/inmuebles
 *     src/main/resources/static/uploads/planos
 *
 * Reemplaza la lógica de move_uploaded_file() hacia assets/uploads/ que
 * tenía la app PHP original — esa parte se había perdido por completo en
 * la migración inicial (el backend Spring solo aceptaba una URL de texto).
 *
 * Las reglas de validación (no vacío, extensión, tamaño) se aplican con
 * una cadena de decoradores — ver {@link #construirValidador} y el paquete
 * {@code com.InmoVision3D.service.validacion} (PATRÓN GoF: Decorator).
 */
@Service
public class FileStorageService {

    private static final List<String> EXTENSIONES_IMAGEN =
            List.of(".jpg", ".jpeg", ".png", ".webp", ".gif");
    private static final List<String> EXTENSIONES_PLANO =
            List.of(".jpg", ".jpeg", ".png", ".webp", ".pdf", ".dwg");

    private static final long TAMANO_MAX_IMAGEN = 5L * 1024 * 1024;   // 5 MB
    private static final long TAMANO_MAX_PLANO = 15L * 1024 * 1024;   // 15 MB (pueden ser PDF/DWG)

    /**
     * PATRÓN GoF: Decorator — cada cadena se arma combinando decoradores
     * independientes (ver paquete service.validacion). Antes estas 3 reglas
     * (no vacío, extensión, tamaño) eran "if" sueltos dentro de
     * {@link #guardar}; ahora cada una es una clase propia que se puede
     * reordenar, reutilizar o probar por separado, y la cadena de imágenes
     * puede tener un límite de tamaño distinto al de los planos sin
     * duplicar lógica.
     */
    private static ValidadorArchivo construirValidador(List<String> extensionesPermitidas, long tamanoMaximoBytes) {
        ValidadorArchivo validador = new ValidadorArchivoBase();
        validador = new ValidadorNoVacio(validador);
        validador = new ValidadorExtension(validador, extensionesPermitidas);
        validador = new ValidadorTamanoMaximo(validador, tamanoMaximoBytes);
        return validador;
    }

    private static final ValidadorArchivo VALIDADOR_IMAGEN =
            construirValidador(EXTENSIONES_IMAGEN, TAMANO_MAX_IMAGEN);
    private static final ValidadorArchivo VALIDADOR_PLANO =
            construirValidador(EXTENSIONES_PLANO, TAMANO_MAX_PLANO);

    private static final Logger log = LoggerFactory.getLogger(FileStorageService.class);

    /** Carpeta real donde se escriben los archivos. */
    private final Path raiz;

    /**
     * Copia espejo dentro de target/classes/static/uploads, si existe.
     *
     * Spring sirve los recursos del classpath desde target/classes, que solo se
     * refresca al recompilar. Al dejar tambien una copia ahi, la imagen recien
     * subida se ve aunque el servidor este resolviendo la peticion por el
     * classpath y no por la carpeta del proyecto.
     */
    private final Path espejoClasspath;

    public FileStorageService(@Value("${app.upload-dir:src/main/resources/static/uploads}") String uploadDir) {
        this.raiz = Paths.get(uploadDir).toAbsolutePath().normalize();
        Path clases = Paths.get("target", "classes", "static", "uploads").toAbsolutePath().normalize();
        this.espejoClasspath = Files.isDirectory(clases.getParent().getParent()) && !clases.equals(raiz)
                ? clases
                : null;
    }

    @PostConstruct
    public void init() {
        try {
            Files.createDirectories(raiz.resolve("inmuebles"));
            Files.createDirectories(raiz.resolve("planos"));
        } catch (IOException e) {
            throw new IllegalStateException("No se pudo crear el directorio de subidas: " + raiz, e);
        }
        // Se deja constancia en el log: si las imagenes no aparecen, lo primero
        // es comprobar que esta ruta es la que se espera (depende del directorio
        // de trabajo con el que se arranque la aplicacion).
        log.info("Directorio de subidas: {}", raiz);
    }

    public String guardarImagen(MultipartFile file) {
        return guardar(file, "inmuebles", VALIDADOR_IMAGEN);
    }

    public String guardarPlano(MultipartFile file) {
        return guardar(file, "planos", VALIDADOR_PLANO);
    }

    private String guardar(MultipartFile file, String subcarpeta, ValidadorArchivo validador) {
        validador.validar(file);

        String original = StringUtils.cleanPath(file.getOriginalFilename() != null ? file.getOriginalFilename() : "");
        String extension = obtenerExtension(original);
        String nombreUnico = UUID.randomUUID() + extension.toLowerCase();
        Path destino = raiz.resolve(subcarpeta).resolve(nombreUnico).normalize();

        if (!destino.getParent().equals(raiz.resolve(subcarpeta))) {
            throw new BusinessException("Ruta de archivo inválida");
        }

        try (var in = file.getInputStream()) {
            Files.copy(in, destino, StandardCopyOption.REPLACE_EXISTING);
        } catch (IOException e) {
            log.error("Error guardando {} en {}", original, destino, e);
            throw new BusinessException("No se pudo guardar el archivo: " + e.getMessage());
        }

        copiarAlClasspath(subcarpeta, nombreUnico, destino);

        log.info("Archivo guardado: {}", destino);
        return "/uploads/" + subcarpeta + "/" + nombreUnico;
    }


    /**
     * Deja una copia en target/classes/static/uploads para que la imagen se vea
     * de inmediato sin recompilar. Si falla no se interrumpe la subida: el
     * archivo original ya quedo guardado y WebConfig tambien sirve esa carpeta.
     */
    private void copiarAlClasspath(String subcarpeta, String nombre, Path origen) {
        if (espejoClasspath == null) {
            return;
        }
        try {
            Path carpeta = espejoClasspath.resolve(subcarpeta);
            Files.createDirectories(carpeta);
            Files.copy(origen, carpeta.resolve(nombre), StandardCopyOption.REPLACE_EXISTING);
        } catch (IOException e) {
            log.warn("No se pudo copiar {} al classpath: {}", nombre, e.getMessage());
        }
    }

    private String obtenerExtension(String filename) {
        int i = filename.lastIndexOf('.');
        return i >= 0 ? filename.substring(i) : "";
    }
}
