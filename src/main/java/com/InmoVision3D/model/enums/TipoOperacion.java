package com.InmoVision3D.model.enums;

/**
 * Tipo de operación del inmueble. Existía en el esquema PHP original
 * (bd.sql: campo "operacion" ENUM('venta','arriendo')) pero se había
 * omitido en la migración inicial a Spring Boot.
 */
public enum TipoOperacion {
    VENTA,
    ARRIENDO
}
