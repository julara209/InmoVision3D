package com.InmoVision3D.exception;

import java.time.LocalDateTime;
import java.util.List;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.core.Ordered;
import org.springframework.core.annotation.Order;
import org.springframework.dao.DataIntegrityViolationException;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.access.AccessDeniedException;
import org.springframework.web.bind.MethodArgumentNotValidException;
import org.springframework.web.bind.annotation.ExceptionHandler;
import org.springframework.web.bind.annotation.RestControllerAdvice;

import jakarta.validation.ConstraintViolationException;

import com.InmoVision3D.controller.FavoritoController;
import com.InmoVision3D.controller.FileUploadController;
import com.InmoVision3D.controller.ImagenInmuebleController;
import com.InmoVision3D.controller.InmuebleController;
import com.InmoVision3D.controller.Plano2DController;
import com.InmoVision3D.controller.SolicitudController;
import com.InmoVision3D.controller.UsuarioController;

/**
 * Manejador de excepciones exclusivo para los controladores REST (bajo /api/**).
 * Convierte cualquier excepción lanzada en esos controladores en un
 * {@link ErrorResponse} en formato JSON, en lugar de dejar que se propague
 * como un stacktrace o una página HTML de error.
 *
 * Se restringe con "assignableTypes" a los @RestController del proyecto para
 * no interferir con {@link GlobalExceptionHandler}, que sigue encargándose
 * de las vistas Thymeleaf (@Controller). Se marca con la precedencia más alta
 * para que, ante un mismo tipo de excepción, Spring prefiera este manejador
 * cuando la excepción ocurre dentro de uno de estos controladores REST.
 */
@RestControllerAdvice(assignableTypes = {
        InmuebleController.class,
        UsuarioController.class,
        FileUploadController.class,
        SolicitudController.class,
        ImagenInmuebleController.class,
        FavoritoController.class,
        Plano2DController.class
})
@Order(Ordered.HIGHEST_PRECEDENCE)
public class ApiExceptionHandler {

    private static final Logger log = LoggerFactory.getLogger(ApiExceptionHandler.class);

    /** Recurso no encontrado (id inexistente, etc.) → 404. */
    @ExceptionHandler(ResourceNotFoundException.class)
    public ResponseEntity<ErrorResponse> handleNotFound(ResourceNotFoundException ex) {
        return build(HttpStatus.NOT_FOUND, "Recurso no encontrado", ex.getMessage(), null);
    }

    /** Regla de negocio incumplida (estado inválido, operación no permitida, etc.) → 400. */
    @ExceptionHandler(BusinessException.class)
    public ResponseEntity<ErrorResponse> handleBusiness(BusinessException ex) {
        return build(HttpStatus.BAD_REQUEST, "Solicitud inválida", ex.getMessage(), null);
    }

    /** Falla de @Valid en el cuerpo de la petición (@RequestBody) → 400 con detalle por campo. */
    @ExceptionHandler(MethodArgumentNotValidException.class)
    public ResponseEntity<ErrorResponse> handleValidation(MethodArgumentNotValidException ex) {
        List<String> detalles = ex.getBindingResult().getFieldErrors().stream()
                .map(err -> err.getField() + ": " + err.getDefaultMessage())
                .toList();
        return build(HttpStatus.BAD_REQUEST, "Error de validación",
                "Uno o más campos no son válidos", detalles);
    }

    /** Falla de validación en parámetros sueltos (@RequestParam, @PathVariable) → 400. */
    @ExceptionHandler(ConstraintViolationException.class)
    public ResponseEntity<ErrorResponse> handleConstraintViolation(ConstraintViolationException ex) {
        List<String> detalles = ex.getConstraintViolations().stream()
                .map(v -> v.getPropertyPath() + ": " + v.getMessage())
                .toList();
        return build(HttpStatus.BAD_REQUEST, "Error de validación",
                "Uno o más parámetros no son válidos", detalles);
    }

    /** Violación de una restricción de la base de datos (ej. email duplicado) → 409. */
    @ExceptionHandler(DataIntegrityViolationException.class)
    public ResponseEntity<ErrorResponse> handleDataIntegrity(DataIntegrityViolationException ex) {
        return build(HttpStatus.CONFLICT, "Conflicto de datos",
                "La operación viola una restricción de integridad (por ejemplo, un valor duplicado)", null);
    }

    /** Sin permisos suficientes para el recurso → 403 (en JSON, no la vista HTML de error/403). */
    @ExceptionHandler(AccessDeniedException.class)
    public ResponseEntity<ErrorResponse> handleAccessDenied(AccessDeniedException ex) {
        return build(HttpStatus.FORBIDDEN, "Acceso denegado",
                "No tienes permisos para realizar esta acción", null);
    }

    /** Archivo subido que excede el límite configurado (10MB) → 413. */
    @ExceptionHandler(org.springframework.web.multipart.MaxUploadSizeExceededException.class)
    public ResponseEntity<ErrorResponse> handleMaxUpload(org.springframework.web.multipart.MaxUploadSizeExceededException ex) {
        return build(HttpStatus.PAYLOAD_TOO_LARGE, "Archivo demasiado grande",
                "El archivo supera el tamaño máximo permitido", null);
    }

    /** Cualquier otro error no controlado → 500, sin exponer detalles internos. */
    @ExceptionHandler(Exception.class)
    public ResponseEntity<ErrorResponse> handleGeneric(Exception ex) {
        log.error("Error no controlado en la API", ex);
        return build(HttpStatus.INTERNAL_SERVER_ERROR, "Error interno",
                "Ocurrió un error inesperado. Intenta de nuevo más tarde.", null);
    }

    private ResponseEntity<ErrorResponse> build(HttpStatus status, String error, String mensaje, List<String> detalles) {
        ErrorResponse body = new ErrorResponse(LocalDateTime.now(), status.value(), error, mensaje, detalles);
        return ResponseEntity.status(status).body(body);
    }
}
