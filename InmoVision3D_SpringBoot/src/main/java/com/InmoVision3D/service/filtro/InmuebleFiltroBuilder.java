package com.InmoVision3D.service.filtro;

import com.InmoVision3D.model.Inmueble;
import com.InmoVision3D.model.enums.EstadoInmueble;
import com.InmoVision3D.model.enums.TipoInmueble;
import com.InmoVision3D.model.enums.TipoOperacion;
import org.springframework.data.jpa.domain.Specification;

import java.math.BigDecimal;

/**
 * PATRÓN GoF: Builder.
 *
 * Arma paso a paso una {@link Specification} de {@link Inmueble} combinando
 * solo los criterios que efectivamente se indican (cada método "con..." es
 * opcional y se puede omitir o encadenar en cualquier orden), y al final
 * {@link #build()} entrega el objeto complejo ya armado.
 *
 */
public final class InmuebleFiltroBuilder {

    private Specification<Inmueble> especificacion = (root, query, cb) -> cb.conjunction();

    private InmuebleFiltroBuilder() {
    }

    /** Punto de entrada del builder. */
    public static InmuebleFiltroBuilder nuevo() {
        return new InmuebleFiltroBuilder();
    }

    public InmuebleFiltroBuilder conEstado(EstadoInmueble estado) {
        if (estado != null) {
            especificacion = especificacion.and((root, query, cb) -> cb.equal(root.get("estado"), estado));
        }
        return this;
    }

    public InmuebleFiltroBuilder conTipo(TipoInmueble tipo) {
        if (tipo != null) {
            especificacion = especificacion.and((root, query, cb) -> cb.equal(root.get("tipo"), tipo));
        }
        return this;
    }

    public InmuebleFiltroBuilder conOperacion(TipoOperacion operacion) {
        if (operacion != null) {
            especificacion = especificacion.and((root, query, cb) -> cb.equal(root.get("operacion"), operacion));
        }
        return this;
    }

    /** Coincide si el texto aparece en la dirección o en la ciudad (sin distinguir mayúsculas). */
    public InmuebleFiltroBuilder conUbicacion(String ubicacion) {
        if (tieneTexto(ubicacion)) {
            String like = "%" + ubicacion.toLowerCase() + "%";
            especificacion = especificacion.and((root, query, cb) -> cb.or(
                    cb.like(cb.lower(root.get("direccion")), like),
                    cb.like(cb.lower(root.get("ciudad")), like)));
        }
        return this;
    }

    /** Coincide si el texto aparece en el título o en la dirección (búsqueda libre del Centro de Reportes). */
    public InmuebleFiltroBuilder conBusquedaLibre(String texto) {
        if (tieneTexto(texto)) {
            String like = "%" + texto.toLowerCase() + "%";
            especificacion = especificacion.and((root, query, cb) -> cb.or(
                    cb.like(cb.lower(root.get("titulo")), like),
                    cb.like(cb.lower(root.get("direccion")), like)));
        }
        return this;
    }

    public InmuebleFiltroBuilder conPrecioMaximo(BigDecimal precioMax) {
        if (precioMax != null) {
            especificacion = especificacion.and((root, query, cb) -> cb.lessThanOrEqualTo(root.get("precio"), precioMax));
        }
        return this;
    }

    public InmuebleFiltroBuilder conHabitacionesMinimas(Integer habitaciones) {
        if (habitaciones != null) {
            especificacion = especificacion.and(
                    (root, query, cb) -> cb.greaterThanOrEqualTo(root.get("habitaciones"), habitaciones));
        }
        return this;
    }

    public InmuebleFiltroBuilder conPropietario(Long propietarioId) {
        if (propietarioId != null) {
            especificacion = especificacion.and(
                    (root, query, cb) -> cb.equal(root.get("propietario").get("id"), propietarioId));
        }
        return this;
    }

    /** Entrega la especificación ya armada con todos los criterios agregados. */
    public Specification<Inmueble> build() {
        return especificacion;
    }

    private boolean tieneTexto(String valor) {
        return valor != null && !valor.isBlank();
    }
}
