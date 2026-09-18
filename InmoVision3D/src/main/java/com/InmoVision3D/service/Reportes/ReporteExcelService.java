package com.InmoVision3D.service.Reportes;

import com.InmoVision3D.dto.EstadisticaTipoDTO;
import com.InmoVision3D.dto.ResumenPublicadorDTO;
import com.InmoVision3D.model.Inmueble;
import org.apache.poi.ss.usermodel.*;
import org.apache.poi.xssf.usermodel.XSSFWorkbook;
import org.springframework.stereotype.Service;

import java.io.IOException;
import java.io.OutputStream;
import java.util.List;

/**
 * Genera los 4 reportes del Centro de reportes en formato Excel (.xlsx):
 * inventario general (listado), por tipo de inmueble, análisis de
 * precios y por publicador.
 *
 * PATRÓN GoF: Strategy — implementación concreta para el formato "excel".
 * Ver {@link ReporteExporter} y {@link ReporteExporterFactory}.
 */
@Service
public class ReporteExcelService implements ReporteExporter {

    @Override
    public String getFormato() {
        return "excel";
    }

    @Override
    public String getExtension() {
        return "xlsx";
    }

    @Override
    public String getContentType() {
        return "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
    }

    // ═══════════════════ INVENTARIO GENERAL (listado) ═══════════════════
    @Override
    public void generar(List<Inmueble> inmuebles, OutputStream out) throws IOException {
        try (Workbook workbook = new XSSFWorkbook()) {
            Sheet sheet = workbook.createSheet("Inmuebles");
            CellStyle headerStyle = crearEstiloEncabezado(workbook);

            String[] columnas = {"ID", "Título", "Tipo", "Ubicación", "Operación", "Precio", "Estado"};
            escribirEncabezado(sheet, columnas, headerStyle);

            int fila = 1;
            for (Inmueble i : inmuebles) {
                Row row = sheet.createRow(fila++);
                row.createCell(0).setCellValue(i.getId());
                row.createCell(1).setCellValue(i.getTitulo());
                row.createCell(2).setCellValue(i.getTipo() != null ? i.getTipo().name() : "");
                row.createCell(3).setCellValue(i.getDireccion());
                row.createCell(4).setCellValue(i.getOperacion() != null ? i.getOperacion().name() : "");
                row.createCell(5).setCellValue(i.getPrecio() != null ? i.getPrecio().doubleValue() : 0);
                row.createCell(6).setCellValue(i.getEstado() != null ? i.getEstado().name() : "");
            }

            autoajustarColumnas(sheet, columnas.length);
            workbook.write(out);
        }
    }

    // ═══════════════════ POR TIPO DE INMUEBLE ═══════════════════
    @Override
    public void generarPorTipo(List<EstadisticaTipoDTO> stats, OutputStream out) throws IOException {
        try (Workbook workbook = new XSSFWorkbook()) {
            Sheet sheet = workbook.createSheet("Por tipo");
            CellStyle headerStyle = crearEstiloEncabezado(workbook);

            String[] columnas = {"Tipo", "Cantidad", "Precio Promedio", "Valor Total"};
            escribirEncabezado(sheet, columnas, headerStyle);

            int fila = 1;
            for (EstadisticaTipoDTO s : stats) {
                Row row = sheet.createRow(fila++);
                row.createCell(0).setCellValue(s.getTipo());
                row.createCell(1).setCellValue(s.getCantidad());
                row.createCell(2).setCellValue(s.getPrecioPromedio() != null ? s.getPrecioPromedio().doubleValue() : 0);
                row.createCell(3).setCellValue(s.getValorTotal() != null ? s.getValorTotal().doubleValue() : 0);
            }

            autoajustarColumnas(sheet, columnas.length);
            workbook.write(out);
        }
    }

    // ═══════════════════ ANALISIS DE PRECIOS ═══════════════════
    @Override
    public void generarAnalisisPrecios(List<EstadisticaTipoDTO> stats, OutputStream out) throws IOException {
        try (Workbook workbook = new XSSFWorkbook()) {
            Sheet sheet = workbook.createSheet("Analisis de precios");
            CellStyle headerStyle = crearEstiloEncabezado(workbook);

            String[] columnas = {"Tipo", "Cantidad", "Precio Minimo", "Precio Maximo", "Precio Promedio"};
            escribirEncabezado(sheet, columnas, headerStyle);

            int fila = 1;
            for (EstadisticaTipoDTO s : stats) {
                Row row = sheet.createRow(fila++);
                row.createCell(0).setCellValue(s.getTipo());
                row.createCell(1).setCellValue(s.getCantidad());
                row.createCell(2).setCellValue(s.getPrecioMinimo() != null ? s.getPrecioMinimo().doubleValue() : 0);
                row.createCell(3).setCellValue(s.getPrecioMaximo() != null ? s.getPrecioMaximo().doubleValue() : 0);
                row.createCell(4).setCellValue(s.getPrecioPromedio() != null ? s.getPrecioPromedio().doubleValue() : 0);
            }

            autoajustarColumnas(sheet, columnas.length);
            workbook.write(out);
        }
    }

    // ═══════════════════ POR PUBLICADOR ═══════════════════
    @Override
    public void generarPorPublicador(List<ResumenPublicadorDTO> resumen, OutputStream out) throws IOException {
        try (Workbook workbook = new XSSFWorkbook()) {
            Sheet sheet = workbook.createSheet("Por publicador");
            CellStyle headerStyle = crearEstiloEncabezado(workbook);

            String[] columnas = {"Publicador", "Email", "Cantidad de Inmuebles", "Valor Total"};
            escribirEncabezado(sheet, columnas, headerStyle);

            int fila = 1;
            for (ResumenPublicadorDTO r : resumen) {
                Row row = sheet.createRow(fila++);
                row.createCell(0).setCellValue(r.getNombrePublicador());
                row.createCell(1).setCellValue(r.getEmail());
                row.createCell(2).setCellValue(r.getCantidadInmuebles());
                row.createCell(3).setCellValue(r.getValorTotal() != null ? r.getValorTotal().doubleValue() : 0);
            }

            autoajustarColumnas(sheet, columnas.length);
            workbook.write(out);
        }
    }

    // ═══════════════════ Helpers comunes ═══════════════════
    private CellStyle crearEstiloEncabezado(Workbook workbook) {
        CellStyle headerStyle = workbook.createCellStyle();
        Font headerFont = workbook.createFont();
        headerFont.setBold(true);
        headerFont.setColor(IndexedColors.WHITE.getIndex());
        headerStyle.setFont(headerFont);
        headerStyle.setFillForegroundColor(IndexedColors.BLUE_GREY.getIndex());
        headerStyle.setFillPattern(FillPatternType.SOLID_FOREGROUND);
        return headerStyle;
    }

    private void escribirEncabezado(Sheet sheet, String[] columnas, CellStyle headerStyle) {
        Row header = sheet.createRow(0);
        for (int i = 0; i < columnas.length; i++) {
            Cell cell = header.createCell(i);
            cell.setCellValue(columnas[i]);
            cell.setCellStyle(headerStyle);
        }
    }

    private void autoajustarColumnas(Sheet sheet, int cantidadColumnas) {
        for (int i = 0; i < cantidadColumnas; i++) {
            sheet.autoSizeColumn(i);
        }
    }
}
