package com.InmoVision3D.service.Reportes;

import com.InmoVision3D.dto.FiltroReporteDTO;
import com.InmoVision3D.model.Inmueble;
import com.InmoVision3D.model.enums.EstadoInmueble;
import com.InmoVision3D.model.enums.TipoInmueble;
import com.InmoVision3D.model.enums.TipoOperacion;
import com.InmoVision3D.service.filtro.InmuebleFiltroBuilder;
import org.springframework.data.jpa.domain.Specification;


public class InmuebleSpecification {

    public static Specification<Inmueble> conFiltros(FiltroReporteDTO filtro) {
        InmuebleFiltroBuilder builder = InmuebleFiltroBuilder.nuevo();

        if (filtro == null) {
            return builder.build();
        }

        return builder
                .conEstado(parseEnumSeguro(EstadoInmueble.class, filtro.getEstado()))
                .conOperacion(parseEnumSeguro(TipoOperacion.class, filtro.getOperacion()))
                .conTipo(parseEnumSeguro(TipoInmueble.class, filtro.getTipo()))
                .conPropietario(filtro.getIdPublicador())
                .conBusquedaLibre(filtro.getBusqueda())
                .build();
    }

    private static <E extends Enum<E>> E parseEnumSeguro(Class<E> tipoEnum, String valor) {
        if (valor == null || valor.isBlank()) {
            return null;
        }
        try {
            return Enum.valueOf(tipoEnum, valor.trim().toUpperCase());
        } catch (IllegalArgumentException ex) {
            return null;
        }
    }
}
