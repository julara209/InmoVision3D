package com.InmoVision3D.dto;

import java.math.BigDecimal;

/**
 * Fila agregada usada por el reporte "Por publicador": resume cuántos
 * inmuebles (dentro de los filtros aplicados) ha publicado cada usuario
 * y el valor total que representan.
 */
public class ResumenPublicadorDTO {

    private final Long idPublicador;
    private final String nombrePublicador;
    private final String email;
    private final long cantidadInmuebles;
    private final BigDecimal valorTotal;

    public ResumenPublicadorDTO(Long idPublicador, String nombrePublicador, String email,
                                 long cantidadInmuebles, BigDecimal valorTotal) {
        this.idPublicador = idPublicador;
        this.nombrePublicador = nombrePublicador;
        this.email = email;
        this.cantidadInmuebles = cantidadInmuebles;
        this.valorTotal = valorTotal;
    }

    public Long getIdPublicador() {
        return idPublicador;
    }

    public String getNombrePublicador() {
        return nombrePublicador;
    }

    public String getEmail() {
        return email;
    }

    public long getCantidadInmuebles() {
        return cantidadInmuebles;
    }

    public BigDecimal getValorTotal() {
        return valorTotal;
    }
}
