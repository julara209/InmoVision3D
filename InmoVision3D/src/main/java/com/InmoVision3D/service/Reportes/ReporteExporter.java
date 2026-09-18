package com.InmoVision3D.service.Reportes;

import com.InmoVision3D.dto.EstadisticaTipoDTO;
import com.InmoVision3D.dto.ResumenPublicadorDTO;
import com.InmoVision3D.model.Inmueble;

import java.io.IOException;
import java.io.OutputStream;
import java.util.List;

/**
 * PATRÓN GoF: Strategy.
 *
 * Cada formato de exportación del Centro de Reportes (PDF, Excel, Word) es
 * una estrategia intercambiable que sabe generar los mismos 4 reportes
 * (inventario, por tipo, análisis de precios, por publicador). 
 *
 */
public interface ReporteExporter {

    /** Identificador corto usado en la URL/formulario: "pdf" | "excel" | "word". */
    String getFormato();

    /** Extensión de archivo sin punto, para el nombre de descarga. */
    String getExtension();

    /** Content-Type con el que debe responder el controller. */
    String getContentType();

    void generar(List<Inmueble> inmuebles, OutputStream out) throws IOException;

    void generarPorTipo(List<EstadisticaTipoDTO> stats, OutputStream out) throws IOException;

    void generarAnalisisPrecios(List<EstadisticaTipoDTO> stats, OutputStream out) throws IOException;

    void generarPorPublicador(List<ResumenPublicadorDTO> resumen, OutputStream out) throws IOException;
}
