package com.InmoVision3D.service.Reportes;

import com.InmoVision3D.exception.BusinessException;
import org.springframework.stereotype.Component;

import java.util.List;
import java.util.Map;
import java.util.function.Function;
import java.util.stream.Collectors;

/**
 * PATRÓN GoF: Factory Method.
 *
 * Encapsula cómo se obtiene el {@link ReporteExporter} correcto a partir
 * del formato pedido ("pdf" | "excel" | "word"), para que
 * {@code ReporteController} dependa solo de la interfaz y no tenga que
 * conocer (ni hacer new de) las clases concretas
 * {@link ReportePdfService}, {@link ReporteExcelService} y
 * {@link ReporteWordService}.
 *
 * Spring inyecta aquí la lista de todos los beans que implementan
 * {@link ReporteExporter}; agregar un formato nuevo no requiere tocar esta
 * fábrica, solo crear otra implementación con {@code @Service}.
 */
@Component
public class ReporteExporterFactory {

    private final Map<String, ReporteExporter> exportadoresPorFormato;

    public ReporteExporterFactory(List<ReporteExporter> exportadores) {
        this.exportadoresPorFormato = exportadores.stream()
                .collect(Collectors.toMap(ReporteExporter::getFormato, Function.identity()));
    }

    /**
     * @throws BusinessException si el formato no es "pdf", "excel" ni "word".
     */
    public ReporteExporter obtener(String formato) {
        ReporteExporter exportador = exportadoresPorFormato.get(formato);
        if (exportador == null) {
            throw new BusinessException("Formato de reporte no soportado: " + formato);
        }
        return exportador;
    }
}
