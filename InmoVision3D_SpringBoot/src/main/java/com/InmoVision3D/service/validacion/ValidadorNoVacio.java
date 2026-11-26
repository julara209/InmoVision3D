package com.InmoVision3D.service.validacion;

import com.InmoVision3D.exception.BusinessException;
import org.springframework.web.multipart.MultipartFile;

/** PATRÓN GoF: Decorator — regla concreta: el archivo debe existir y no estar vacío. */
public class ValidadorNoVacio extends ValidadorArchivoDecorator {

    public ValidadorNoVacio(ValidadorArchivo siguiente) {
        super(siguiente);
    }

    @Override
    protected void validarPropio(MultipartFile archivo) {
        if (archivo == null || archivo.isEmpty()) {
            throw new BusinessException("El archivo está vacío");
        }
    }
}
