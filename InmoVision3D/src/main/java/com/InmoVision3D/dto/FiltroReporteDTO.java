package com.InmoVision3D.dto;

/**
 * Filtros multicriterio para el módulo de Reportes.
 */
public class FiltroReporteDTO {

    private String estado;      // disponible | vendido | arrendado | pausado
    private String operacion;   // venta | arriendo
    private String tipo;        // casa | apartamento | local | ...
    private Long idPublicador;
    private String busqueda;    // texto libre: busca en título o ubicación

    public String getEstado() {
        return estado;
    }

    public void setEstado(String estado) {
        this.estado = estado;
    }

    public String getOperacion() {
        return operacion;
    }

    public void setOperacion(String operacion) {
        this.operacion = operacion;
    }

    public String getTipo() {
        return tipo;
    }

    public void setTipo(String tipo) {
        this.tipo = tipo;
    }

    public Long getIdPublicador() {
        return idPublicador;
    }

    public void setIdPublicador(Long idPublicador) {
        this.idPublicador = idPublicador;
    }

    public String getBusqueda() {
        return busqueda;
    }

    public void setBusqueda(String busqueda) {
        this.busqueda = busqueda;
    }
}
