package com.InmoVision3D.service;

import com.InmoVision3D.dto.EstadisticaTipoDTO;
import com.InmoVision3D.dto.FiltroReporteDTO;
import com.InmoVision3D.dto.ResumenPublicadorDTO;
import com.InmoVision3D.model.Inmueble;
import com.InmoVision3D.model.Usuario;
import com.InmoVision3D.service.Reportes.InmuebleSpecification;
import com.InmoVision3D.repository.InmuebleRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;

import java.math.BigDecimal;
import java.math.RoundingMode;
import java.util.Comparator;
import java.util.List;
import java.util.Map;
import java.util.Objects;
import java.util.stream.Collectors;

@Service
public class ReporteService {

    @Autowired
    private InmuebleRepository inmuebleRepository;

    /**
     * Listado plano filtrado. Usado por el reporte "Inventario general".
     */
    public List<Inmueble> obtenerFiltrados(FiltroReporteDTO filtro) {
        return inmuebleRepository.findAll(InmuebleSpecification.conFiltros(filtro));
    }

    /**
     * Agrupa los inmuebles filtrados por tipo y calcula cantidad, precio
     * mínimo/máximo/promedio y valor total de cada grupo. Usado por los
     * reportes "Por tipo de inmueble" y "Análisis de precios" (este último
     * llega con el filtro de operación ya aplicado desde el controller).
     */
    public List<EstadisticaTipoDTO> obtenerEstadisticasPorTipo(FiltroReporteDTO filtro) {
        List<Inmueble> inmuebles = obtenerFiltrados(filtro);

        Map<String, List<Inmueble>> agrupadoPorTipo = inmuebles.stream()
                .filter(i -> i.getTipo() != null)
                .collect(Collectors.groupingBy(i -> i.getTipo().name()));

        return agrupadoPorTipo.entrySet().stream()
                .map(entrada -> construirEstadistica(entrada.getKey(), entrada.getValue()))
                .sorted(Comparator.comparingLong(EstadisticaTipoDTO::getCantidad).reversed())
                .collect(Collectors.toList());
    }

    /**
     * Agrupa los inmuebles filtrados por publicador (propietario) y calcula
     * cuántos ha publicado cada uno y el valor total que representan.
     */
    public List<ResumenPublicadorDTO> obtenerResumenPorPublicador(FiltroReporteDTO filtro) {
        List<Inmueble> inmuebles = obtenerFiltrados(filtro);

        Map<Long, List<Inmueble>> agrupadoPorPublicador = inmuebles.stream()
                .filter(i -> i.getPropietario() != null)
                .collect(Collectors.groupingBy(i -> i.getPropietario().getId()));

        return agrupadoPorPublicador.values().stream()
                .map(this::construirResumenPublicador)
                .sorted(Comparator.comparingLong(ResumenPublicadorDTO::getCantidadInmuebles).reversed())
                .collect(Collectors.toList());
    }

    private EstadisticaTipoDTO construirEstadistica(String tipo, List<Inmueble> inmuebles) {
        long cantidad = inmuebles.size();

        List<BigDecimal> precios = inmuebles.stream()
                .map(Inmueble::getPrecio)
                .filter(Objects::nonNull)
                .collect(Collectors.toList());

        BigDecimal total = precios.stream().reduce(BigDecimal.ZERO, BigDecimal::add);
        BigDecimal promedio = precios.isEmpty()
                ? BigDecimal.ZERO
                : total.divide(BigDecimal.valueOf(precios.size()), 2, RoundingMode.HALF_UP);
        BigDecimal minimo = precios.stream().min(BigDecimal::compareTo).orElse(BigDecimal.ZERO);
        BigDecimal maximo = precios.stream().max(BigDecimal::compareTo).orElse(BigDecimal.ZERO);

        return new EstadisticaTipoDTO(tipo, cantidad, promedio, minimo, maximo, total);
    }

    private ResumenPublicadorDTO construirResumenPublicador(List<Inmueble> inmuebles) {
        Usuario publicador = inmuebles.get(0).getPropietario();

        BigDecimal total = inmuebles.stream()
                .map(Inmueble::getPrecio)
                .filter(Objects::nonNull)
                .reduce(BigDecimal.ZERO, BigDecimal::add);

        String nombreCompleto = ((publicador.getNombre() != null ? publicador.getNombre() : "") + " " +
                (publicador.getApellido() != null ? publicador.getApellido() : "")).trim();
        if (nombreCompleto.isEmpty()) {
            nombreCompleto = publicador.getEmail();
        }

        return new ResumenPublicadorDTO(publicador.getId(), nombreCompleto, publicador.getEmail(),
                inmuebles.size(), total);
    }
}
