package com.InmoVision3D.service.validacion;

import org.springframework.web.multipart.MultipartFile;

/**
 * PATRÓN GoF: Decorator — interfaz del "componente".
 *
 * Representa una regla de validación sobre un archivo subido. Se usa junto
 * con {@link ValidadorArchivoDecorator}: cada regla concreta (extensión
 * permitida, tamaño máximo, archivo no vacío...) se implementa como un
 * decorador independiente que envuelve a otro {@link ValidadorArchivo} y le
 * agrega una verificación más, sin que ninguno conozca a los demás.
 *
 * Ventaja frente a un único método con varios "if" seguidos (como tenía
 * antes {@code FileStorageService.guardar}): cada regla vive en su propia
 * clase, se puede probar por separado, y la cadena de validaciones para
 * imágenes puede ser distinta de la de planos simplemente combinando los
 * decoradores en otro orden o cantidad (ver
 * {@code FileStorageService.construirValidadorImagen/Plano}).
 *
 * Lanza {@link com.InmoVision3D.exception.BusinessException} si el archivo
 * no cumple la regla; no devuelve nada si es válido.
 */
public interface ValidadorArchivo {
    void validar(MultipartFile archivo);
}
