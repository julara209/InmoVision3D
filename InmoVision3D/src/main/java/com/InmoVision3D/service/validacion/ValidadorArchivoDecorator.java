package com.InmoVision3D.service.validacion;

import org.springframework.web.multipart.MultipartFile;

/**
 * PATRÓN GoF: Decorator — decorador abstracto.
 *
 * Cada subclase concreta ({@link ValidadorNoVacio}, {@link ValidadorExtension},
 * {@link ValidadorTamanoMaximo}) primero delega en el validador que envuelve
 * ({@code siguiente}) y luego agrega su propia comprobación. Así se pueden
 * combinar libremente en tiempo de ejecución, por ejemplo:
 *
 * <pre>
 *   ValidadorArchivo v = new ValidadorArchivoBase();
 *   v = new ValidadorNoVacio(v);
 *   v = new ValidadorExtension(v, List.of(".jpg", ".png"));
 *   v = new ValidadorTamanoMaximo(v, 5 * 1024 * 1024);
 *   v.validar(archivo); // corre las 3 reglas, en orden
 * </pre>
 */
public abstract class ValidadorArchivoDecorator implements ValidadorArchivo {

    private final ValidadorArchivo siguiente;

    protected ValidadorArchivoDecorator(ValidadorArchivo siguiente) {
        this.siguiente = siguiente;
    }

    @Override
    public final void validar(MultipartFile archivo) {
        siguiente.validar(archivo);
        validarPropio(archivo);
    }

    /** La regla concreta que agrega este decorador. */
    protected abstract void validarPropio(MultipartFile archivo);
}
