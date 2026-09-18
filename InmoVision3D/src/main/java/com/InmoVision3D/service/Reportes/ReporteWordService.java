package com.InmoVision3D.service.Reportes;

import com.InmoVision3D.dto.EstadisticaTipoDTO;
import com.InmoVision3D.dto.ResumenPublicadorDTO;
import com.InmoVision3D.model.Inmueble;
import org.apache.poi.xwpf.usermodel.*;
import org.springframework.stereotype.Service;

import java.io.IOException;
import java.io.OutputStream;
import java.math.BigDecimal;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.List;

/**
 * Genera los 4 reportes del Centro de reportes en formato Word (.docx):
 * inventario general (listado), por tipo de inmueble, análisis de
 * precios y por publicador.
 *
 * PATRÓN GoF: Strategy — implementación concreta para el formato "word".
 * Ver {@link ReporteExporter} y {@link ReporteExporterFactory}.
 */
@Service
public class ReporteWordService implements ReporteExporter {

    @Override
    public String getFormato() {
        return "word";
    }

    @Override
    public String getExtension() {
        return "docx";
    }

    @Override
    public String getContentType() {
        return "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
    }

    // ═══════════════════ INVENTARIO GENERAL (listado) ═══════════════════
    @Override
    public void generar(List<Inmueble> inmuebles, OutputStream out) throws IOException {
        try (XWPFDocument doc = new XWPFDocument()) {
            escribirEncabezado(doc, "Reporte de Inventario - InmoVision 3D");

            String[] headers = {"ID", "Título", "Tipo", "Ubicación", "Operación", "Precio", "Estado"};
            XWPFTable tabla = crearTabla(doc, headers, inmuebles.size());

            for (int i = 0; i < inmuebles.size(); i++) {
                Inmueble im = inmuebles.get(i);
                XWPFTableRow fila = tabla.getRow(i + 1);
                fila.getCell(0).setText(String.valueOf(im.getId()));
                fila.getCell(1).setText(im.getTitulo() != null ? im.getTitulo() : "");
                fila.getCell(2).setText(im.getTipo() != null ? im.getTipo().name() : "");
                fila.getCell(3).setText(im.getDireccion() != null ? im.getDireccion() : "");
                fila.getCell(4).setText(im.getOperacion() != null ? im.getOperacion().name() : "");
                fila.getCell(5).setText(formatoMoneda(im.getPrecio()));
                fila.getCell(6).setText(im.getEstado() != null ? im.getEstado().name() : "");
            }

            doc.write(out);
        }
    }

    // ═══════════════════ POR TIPO DE INMUEBLE ═══════════════════
    @Override
    public void generarPorTipo(List<EstadisticaTipoDTO> stats, OutputStream out) throws IOException {
        try (XWPFDocument doc = new XWPFDocument()) {
            escribirEncabezado(doc, "Reporte por Tipo de Inmueble - InmoVision 3D");

            String[] headers = {"Tipo", "Cantidad", "Precio Promedio", "Valor Total"};
            XWPFTable tabla = crearTabla(doc, headers, stats.size());

            for (int i = 0; i < stats.size(); i++) {
                EstadisticaTipoDTO s = stats.get(i);
                XWPFTableRow fila = tabla.getRow(i + 1);
                fila.getCell(0).setText(s.getTipo());
                fila.getCell(1).setText(String.valueOf(s.getCantidad()));
                fila.getCell(2).setText(formatoMoneda(s.getPrecioPromedio()));
                fila.getCell(3).setText(formatoMoneda(s.getValorTotal()));
            }

            doc.write(out);
        }
    }

    // ═══════════════════ ANALISIS DE PRECIOS ═══════════════════
    @Override
    public void generarAnalisisPrecios(List<EstadisticaTipoDTO> stats, OutputStream out) throws IOException {
        try (XWPFDocument doc = new XWPFDocument()) {
            escribirEncabezado(doc, "Analisis de Precios por Tipo - InmoVision 3D");

            String[] headers = {"Tipo", "Cantidad", "Precio Minimo", "Precio Maximo", "Precio Promedio"};
            XWPFTable tabla = crearTabla(doc, headers, stats.size());

            for (int i = 0; i < stats.size(); i++) {
                EstadisticaTipoDTO s = stats.get(i);
                XWPFTableRow fila = tabla.getRow(i + 1);
                fila.getCell(0).setText(s.getTipo());
                fila.getCell(1).setText(String.valueOf(s.getCantidad()));
                fila.getCell(2).setText(formatoMoneda(s.getPrecioMinimo()));
                fila.getCell(3).setText(formatoMoneda(s.getPrecioMaximo()));
                fila.getCell(4).setText(formatoMoneda(s.getPrecioPromedio()));
            }

            doc.write(out);
        }
    }

    // ═══════════════════ POR PUBLICADOR ═══════════════════
    @Override
    public void generarPorPublicador(List<ResumenPublicadorDTO> resumen, OutputStream out) throws IOException {
        try (XWPFDocument doc = new XWPFDocument()) {
            escribirEncabezado(doc, "Reporte por Publicador - InmoVision 3D");

            String[] headers = {"Publicador", "Email", "Cantidad de Inmuebles", "Valor Total"};
            XWPFTable tabla = crearTabla(doc, headers, resumen.size());

            for (int i = 0; i < resumen.size(); i++) {
                ResumenPublicadorDTO r = resumen.get(i);
                XWPFTableRow fila = tabla.getRow(i + 1);
                fila.getCell(0).setText(r.getNombrePublicador());
                fila.getCell(1).setText(r.getEmail());
                fila.getCell(2).setText(String.valueOf(r.getCantidadInmuebles()));
                fila.getCell(3).setText(formatoMoneda(r.getValorTotal()));
            }

            doc.write(out);
        }
    }

    // ═══════════════════ Helpers comunes ═══════════════════
    private void escribirEncabezado(XWPFDocument doc, String tituloTexto) {
        XWPFParagraph titulo = doc.createParagraph();
        titulo.setAlignment(ParagraphAlignment.CENTER);
        XWPFRun run = titulo.createRun();
        run.setText(tituloTexto);
        run.setBold(true);
        run.setFontSize(16);

        XWPFParagraph fechaParrafo = doc.createParagraph();
        fechaParrafo.setAlignment(ParagraphAlignment.CENTER);
        XWPFRun fechaRun = fechaParrafo.createRun();
        fechaRun.setText("Generado: " +
                LocalDateTime.now().format(DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm")));
        fechaRun.setItalic(true);
        fechaRun.setFontSize(9);

        doc.createParagraph(); // espacio
    }

    private XWPFTable crearTabla(XWPFDocument doc, String[] headers, int cantidadFilas) {
        XWPFTable tabla = doc.createTable(cantidadFilas + 1, headers.length);
        for (int i = 0; i < headers.length; i++) {
            XWPFTableCell cell = tabla.getRow(0).getCell(i);
            cell.setText(headers[i]);
            cell.getParagraphs().get(0).getRuns().get(0).setBold(true);
        }
        return tabla;
    }

    private String formatoMoneda(BigDecimal valor) {
        return valor != null ? String.format("$%,.0f", valor) : "$0";
    }
}
