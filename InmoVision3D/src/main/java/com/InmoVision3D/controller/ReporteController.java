package com.InmoVision3D.controller;

import com.InmoVision3D.dto.EstadisticaTipoDTO;
import com.InmoVision3D.dto.FiltroReporteDTO;
import com.InmoVision3D.dto.ResumenPublicadorDTO;
import com.InmoVision3D.exception.BusinessException;
import com.InmoVision3D.model.Inmueble;
import com.InmoVision3D.service.ReporteService;
import com.InmoVision3D.service.Reportes.ReporteExporter;
import com.InmoVision3D.service.Reportes.ReporteExporterFactory;

import jakarta.servlet.http.HttpServletResponse;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.*;

import java.io.IOException;
import java.io.OutputStream;
import java.util.List;

/**
 * Sirve el Centro de reportes del panel admin. Soporta 4 reportes
 * ("tipoReporte"): inventario (listado plano), portipo (agrupado por
 * tipo de inmueble), precios (analisis de precios por tipo) y
 * porpublicador (resumen por publicador). Cada uno exportable en
 * pdf, excel o word.
 *
 * PATRÓN GoF: Strategy + Factory Method — el formato de salida se resuelve
 * pidiendo la estrategia adecuada a {@link ReporteExporterFactory} y
 * llamándola a través de la interfaz {@link ReporteExporter}. Antes había
 * un switch de 3 vías (pdf/excel/word) repetido dentro de cada uno de los
 * 4 tipos de reporte; ahora ese switch no existe: agregar un formato nuevo
 * no requiere tocar este controller.
 */
@Controller
@RequestMapping("/admin/reportes")
@PreAuthorize("hasRole('ADMIN')")
public class ReporteController {

    @Autowired
    private ReporteService reporteService;

    @Autowired
    private ReporteExporterFactory exporterFactory;

    @GetMapping
    public String formulario(Model model) {
        model.addAttribute("filtro", new FiltroReporteDTO());
        return "admin/reportes";
    }

    @GetMapping("/generar")
    public void generar(@ModelAttribute FiltroReporteDTO filtro,
                         @RequestParam String formato,
                         @RequestParam(defaultValue = "inventario") String tipoReporte,
                         HttpServletResponse response) throws IOException {

        // PATRÓN GoF: Factory Method — la fábrica decide qué estrategia
        // concreta (PDF, Excel o Word) entregar. Este controller sirve una
        // vista (no es @RestController), así que el formato inválido se
        // valida aquí mismo en lugar de dejarlo propagar como 500.
        ReporteExporter exportador;
        try {
            exportador = exporterFactory.obtener(formato);
        } catch (BusinessException e) {
            response.sendError(HttpServletResponse.SC_BAD_REQUEST, e.getMessage());
            return;
        }
        configurarCabecera(response, exportador, nombreArchivo(tipoReporte));

        OutputStream out = response.getOutputStream();

        switch (tipoReporte) {
            case "portipo" -> {
                List<EstadisticaTipoDTO> stats = reporteService.obtenerEstadisticasPorTipo(filtro);
                exportador.generarPorTipo(stats, out);
            }
            case "precios" -> {
                List<EstadisticaTipoDTO> stats = reporteService.obtenerEstadisticasPorTipo(filtro);
                exportador.generarAnalisisPrecios(stats, out);
            }
            case "porpublicador" -> {
                List<ResumenPublicadorDTO> resumen = reporteService.obtenerResumenPorPublicador(filtro);
                exportador.generarPorPublicador(resumen, out);
            }
            default -> {
                List<Inmueble> inmuebles = reporteService.obtenerFiltrados(filtro);
                exportador.generar(inmuebles, out);
            }
        }
    }

    private String nombreArchivo(String tipoReporte) {
        String base = switch (tipoReporte) {
            case "portipo" -> "reporte_por_tipo_";
            case "precios" -> "reporte_analisis_precios_";
            case "porpublicador" -> "reporte_por_publicador_";
            default -> "reporte_inventario_";
        };
        return base + System.currentTimeMillis();
    }

    /** Configura Content-Type y Content-Disposition según la estrategia elegida. */
    private void configurarCabecera(HttpServletResponse response, ReporteExporter exportador, String nombreBase) {
        response.setContentType(exportador.getContentType());
        response.setHeader("Content-Disposition",
                "attachment; filename=" + nombreBase + "." + exportador.getExtension());
    }
}
