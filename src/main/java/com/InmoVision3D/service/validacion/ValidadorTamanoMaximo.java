package com.InmoVision3D.service.validacion;

import com.InmoVision3D.exception.BusinessException;
import org.springframework.web.multipart.MultipartFile;

/**
 * PATRÓN GoF: Decorator — regla concreta: el archivo no debe superar un
 * tamaño máximo (en bytes), independiente del límite global del servlet
 * configurado en application.yaml.
 */
public class ValidadorTamanoMaximo extends ValidadorArchivoDecorator {

    private final long tamanoMaximoBytes;

    public ValidadorTamanoMaximo(ValidadorArchivo siguiente, long tamanoMaximoBytes) {
        super(siguiente);
        this.tamanoMaximoBytes = tamanoMaximoBytes;
    }

    @Override
    protected void validarPropio(MultipartFile archivo) {
        if (archivo.getSize() > tamanoMaximoBytes) {
            double maximoMb = tamanoMaximoBytes / (1024.0 * 1024.0);
            throw new BusinessException(
                    String.format("El archivo supera el tamaño máximo permitido (%.0f MB)", maximoMb));
        }
    }
}
