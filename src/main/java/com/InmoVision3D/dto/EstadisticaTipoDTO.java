package com.InmoVision3D.dto;

import java.math.BigDecimal;

/**
 * Fila agregada usada por los reportes "Por tipo de inmueble" y
 * "Análisis de precios": agrupa los inmuebles filtrados por su tipo
 * y calcula cantidad, precio mínimo/máximo/promedio y valor total.
 */
public class EstadisticaTipoDTO {

    private final String tipo;
    private final long cantidad;
    private final BigDecimal precioPromedio;
    private final BigDecimal precioMinimo;
    private final BigDecimal precioMaximo;
    private final BigDecimal valorTotal;

    public EstadisticaTipoDTO(String tipo, long cantidad, BigDecimal precioPromedio,
                               BigDecimal precioMinimo, BigDecimal precioMaximo, BigDecimal valorTotal) {
        this.tipo = tipo;
        this.cantidad = cantidad;
        this.precioPromedio = precioPromedio;
        this.precioMinimo = precioMinimo;
        this.precioMaximo = precioMaximo;
        this.valorTotal = valorTotal;
    }

    public String getTipo() {
        return tipo;
    }

    public long getCantidad() {
        return cantidad;
    }

    public BigDecimal getPrecioPromedio() {
        return precioPromedio;
    }

    public BigDecimal getPrecioMinimo() {
        return precioMinimo;
    }

    public BigDecimal getPrecioMaximo() {
        return precioMaximo;
    }

    public BigDecimal getValorTotal() {
        return valorTotal;
    }
}
