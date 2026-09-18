package com.InmoVision3D.service.Reportes;

import com.InmoVision3D.dto.EstadisticaTipoDTO;
import com.InmoVision3D.dto.ResumenPublicadorDTO;
import com.InmoVision3D.model.Inmueble;
import com.lowagie.text.*;
import com.lowagie.text.pdf.PdfPCell;
import com.lowagie.text.pdf.PdfPTable;
import com.lowagie.text.pdf.PdfWriter;
import org.springframework.stereotype.Service;

import java.awt.Color;
import java.io.IOException;
import java.io.OutputStream;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.List;

/**
 * Generación de PDF con OpenPDF (fork libre de iText, sin restricciones
 * de licencia AGPL). El diseño se inspira en el que ya tenían en PHP
 * con FPDF (Api/ReportesApi.php): barra superior, títulos y tabla con
 * filas alternas.
 *
 * Soporta los 4 reportes del Centro de reportes: inventario general
 * (listado), por tipo de inmueble, análisis de precios y por publicador.
 *
 * PATRÓN GoF: Strategy — implementación concreta para el formato "pdf".
 * Ver {@link ReporteExporter} y {@link ReporteExporterFactory}. La
 * interfaz declara sus métodos con "throws IOException", así que aquí se
 * atrapa la {@link DocumentException} propia de OpenPDF y se reempaqueta
 * como IOException; el resto de la lógica de generación no cambia.
 */
@Service
public class ReportePdfService implements ReporteExporter {

    private static final Color AZUL = new Color(2, 132, 199);
    private static final Color GRIS_OSCURO = new Color(15, 23, 42);
    private static final Color GRIS_TEXTO = new Color(100, 116, 139);
    private static final Color FILA_ALTERNA = new Color(241, 245, 249);

    @Override
    public String getFormato() {
        return "pdf";
    }

    @Override
    public String getExtension() {
        return "pdf";
    }

    @Override
    public String getContentType() {
        return "application/pdf";
    }

    // ═══════════════════ INVENTARIO GENERAL (listado) ═══════════════════
    @Override
    public void generar(List<Inmueble> inmuebles, OutputStream out) throws IOException {
        try {
            generarInterno(inmuebles, out);
        } catch (DocumentException e) {
            throw new IOException("Error generando el PDF de inventario: " + e.getMessage(), e);
        }
    }

    private void generarInterno(List<Inmueble> inmuebles, OutputStream out) throws DocumentException {
        Document document = iniciarDocumento(out, "InmoVision 3D - Reporte de Inventario");

        PdfPTable tabla = crearTablaConEncabezado(
                new String[]{"ID", "Título", "Tipo", "Ubicación", "Operación", "Precio", "Estado"});

        Font rowFont = new Font(Font.HELVETICA, 8);
        boolean alterna = false;
        for (Inmueble i : inmuebles) {
            Color fondo = alterna ? FILA_ALTERNA : Color.WHITE;
            addCelda(tabla, String.valueOf(i.getId()), rowFont, fondo);
            addCelda(tabla, i.getTitulo(), rowFont, fondo);
            addCelda(tabla, i.getTipo() != null ? i.getTipo().name() : "", rowFont, fondo);
            addCelda(tabla, i.getDireccion(), rowFont, fondo);
            addCelda(tabla, i.getOperacion() != null ? i.getOperacion().name() : "", rowFont, fondo);
            addCelda(tabla, formatoMoneda(i.getPrecio()), rowFont, fondo);
            addCelda(tabla, i.getEstado() != null ? i.getEstado().name() : "", rowFont, fondo);
            alterna = !alterna;
        }

        cerrarConTablaYVacio(document, tabla, inmuebles.isEmpty(),
                "No se encontraron inmuebles con los filtros aplicados.");
    }

    // ═══════════════════ POR TIPO DE INMUEBLE ═══════════════════
    @Override
    public void generarPorTipo(List<EstadisticaTipoDTO> stats, OutputStream out) throws IOException {
        try {
            generarPorTipoInterno(stats, out);
        } catch (DocumentException e) {
            throw new IOException("Error generando el PDF por tipo: " + e.getMessage(), e);
        }
    }

    private void generarPorTipoInterno(List<EstadisticaTipoDTO> stats, OutputStream out) throws DocumentException {
        Document document = iniciarDocumento(out, "InmoVision 3D - Reporte por Tipo de Inmueble");

        PdfPTable tabla = crearTablaConEncabezado(
                new String[]{"Tipo", "Cantidad", "Precio Promedio", "Valor Total"});

        Font rowFont = new Font(Font.HELVETICA, 9);
        boolean alterna = false;
        for (EstadisticaTipoDTO s : stats) {
            Color fondo = alterna ? FILA_ALTERNA : Color.WHITE;
            addCelda(tabla, s.getTipo(), rowFont, fondo);
            addCelda(tabla, String.valueOf(s.getCantidad()), rowFont, fondo);
            addCelda(tabla, formatoMoneda(s.getPrecioPromedio()), rowFont, fondo);
            addCelda(tabla, formatoMoneda(s.getValorTotal()), rowFont, fondo);
            alterna = !alterna;
        }

        cerrarConTablaYVacio(document, tabla, stats.isEmpty(),
                "No se encontraron inmuebles con los filtros aplicados.");
    }

    // ═══════════════════ ANALISIS DE PRECIOS ═══════════════════
    @Override
    public void generarAnalisisPrecios(List<EstadisticaTipoDTO> stats, OutputStream out) throws IOException {
        try {
            generarAnalisisPreciosInterno(stats, out);
        } catch (DocumentException e) {
            throw new IOException("Error generando el PDF de analisis de precios: " + e.getMessage(), e);
        }
    }

    private void generarAnalisisPreciosInterno(List<EstadisticaTipoDTO> stats, OutputStream out) throws DocumentException {
        Document document = iniciarDocumento(out, "InmoVision 3D - Analisis de Precios por Tipo");

        PdfPTable tabla = crearTablaConEncabezado(
                new String[]{"Tipo", "Cantidad", "Precio Minimo", "Precio Maximo", "Precio Promedio"});

        Font rowFont = new Font(Font.HELVETICA, 9);
        boolean alterna = false;
        for (EstadisticaTipoDTO s : stats) {
            Color fondo = alterna ? FILA_ALTERNA : Color.WHITE;
            addCelda(tabla, s.getTipo(), rowFont, fondo);
            addCelda(tabla, String.valueOf(s.getCantidad()), rowFont, fondo);
            addCelda(tabla, formatoMoneda(s.getPrecioMinimo()), rowFont, fondo);
            addCelda(tabla, formatoMoneda(s.getPrecioMaximo()), rowFont, fondo);
            addCelda(tabla, formatoMoneda(s.getPrecioPromedio()), rowFont, fondo);
            alterna = !alterna;
        }

        cerrarConTablaYVacio(document, tabla, stats.isEmpty(),
                "No se encontraron inmuebles con los filtros aplicados.");
    }

    // ═══════════════════ POR PUBLICADOR ═══════════════════
    @Override
    public void generarPorPublicador(List<ResumenPublicadorDTO> resumen, OutputStream out) throws IOException {
        try {
            generarPorPublicadorInterno(resumen, out);
        } catch (DocumentException e) {
            throw new IOException("Error generando el PDF por publicador: " + e.getMessage(), e);
        }
    }

    private void generarPorPublicadorInterno(List<ResumenPublicadorDTO> resumen, OutputStream out) throws DocumentException {
        Document document = iniciarDocumento(out, "InmoVision 3D - Reporte por Publicador");

        PdfPTable tabla = crearTablaConEncabezado(
                new String[]{"Publicador", "Email", "Cantidad de Inmuebles", "Valor Total"});

        Font rowFont = new Font(Font.HELVETICA, 9);
        boolean alterna = false;
        for (ResumenPublicadorDTO r : resumen) {
            Color fondo = alterna ? FILA_ALTERNA : Color.WHITE;
            addCelda(tabla, r.getNombrePublicador(), rowFont, fondo);
            addCelda(tabla, r.getEmail(), rowFont, fondo);
            addCelda(tabla, String.valueOf(r.getCantidadInmuebles()), rowFont, fondo);
            addCelda(tabla, formatoMoneda(r.getValorTotal()), rowFont, fondo);
            alterna = !alterna;
        }

        cerrarConTablaYVacio(document, tabla, resumen.isEmpty(),
                "No se encontraron publicadores con los filtros aplicados.");
    }

    // ═══════════════════ Helpers comunes ═══════════════════
    private Document iniciarDocumento(OutputStream out, String tituloTexto) throws DocumentException {
        Document document = new Document(PageSize.A4, 36, 36, 54, 36);
        PdfWriter.getInstance(document, out);
        document.open();

        Font tituloFont = new Font(Font.HELVETICA, 18, Font.BOLD, GRIS_OSCURO);
        Paragraph titulo = new Paragraph(tituloTexto, tituloFont);
        titulo.setAlignment(Element.ALIGN_CENTER);
        document.add(titulo);

        Font fechaFont = new Font(Font.HELVETICA, 9, Font.ITALIC, GRIS_TEXTO);
        Paragraph fecha = new Paragraph(
                "Generado: " + LocalDateTime.now().format(DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm")),
                fechaFont);
        fecha.setAlignment(Element.ALIGN_CENTER);
        document.add(fecha);
        document.add(Chunk.NEWLINE);

        return document;
    }

    private PdfPTable crearTablaConEncabezado(String[] headers) {
        PdfPTable tabla = new PdfPTable(headers.length);
        tabla.setWidthPercentage(100);
        Font headerFont = new Font(Font.HELVETICA, 9, Font.BOLD, Color.WHITE);
        for (String h : headers) {
            PdfPCell cell = new PdfPCell(new Phrase(h, headerFont));
            cell.setBackgroundColor(AZUL);
            cell.setPadding(5);
            tabla.addCell(cell);
        }
        return tabla;
    }

    private void cerrarConTablaYVacio(Document document, PdfPTable tabla, boolean vacio, String mensajeVacio) {
        document.add(tabla);
        if (vacio) {
            Font vacioFont = new Font(Font.HELVETICA, 9, Font.ITALIC, GRIS_TEXTO);
            Paragraph parrafoVacio = new Paragraph(mensajeVacio, vacioFont);
            parrafoVacio.setAlignment(Element.ALIGN_CENTER);
            document.add(parrafoVacio);
        }
        document.close();
    }

    private void addCelda(PdfPTable tabla, String valor, Font font, Color fondo) {
        PdfPCell cell = new PdfPCell(new Phrase(valor != null ? valor : "", font));
        cell.setBackgroundColor(fondo);
        cell.setPadding(4);
        tabla.addCell(cell);
    }

    private String formatoMoneda(java.math.BigDecimal valor) {
        return valor != null ? String.format("$%,.0f", valor) : "$0";
    }
}
