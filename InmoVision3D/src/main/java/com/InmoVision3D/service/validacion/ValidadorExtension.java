package com.InmoVision3D.service.validacion;

import com.InmoVision3D.exception.BusinessException;
import org.springframework.util.StringUtils;
import org.springframework.web.multipart.MultipartFile;

import java.util.List;

/**
 * PATRÓN GoF: Decorator — regla concreta: la extensión del archivo debe
 * estar en la lista permitida (distinta para imágenes y para planos).
 */
public class ValidadorExtension extends ValidadorArchivoDecorator {

    private final List<String> extensionesPermitidas;

    public ValidadorExtension(ValidadorArchivo siguiente, List<String> extensionesPermitidas) {
        super(siguiente);
        this.extensionesPermitidas = extensionesPermitidas;
    }

    @Override
    protected void validarPropio(MultipartFile archivo) {
        String nombre = StringUtils.cleanPath(
                archivo.getOriginalFilename() != null ? archivo.getOriginalFilename() : "");
        String extension = obtenerExtension(nombre).toLowerCase();
        if (!extensionesPermitidas.contains(extension)) {
            throw new BusinessException("Tipo de archivo no permitido: " + extension);
        }
    }

    private String obtenerExtension(String nombre) {
        int i = nombre.lastIndexOf('.');
        return i >= 0 ? nombre.substring(i) : "";
    }
}
