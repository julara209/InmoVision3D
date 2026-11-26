package com.InmoVision3D.model;

import com.fasterxml.jackson.annotation.JsonIgnore;
import jakarta.persistence.*;
import jakarta.validation.constraints.NotBlank;
import lombok.AllArgsConstructor;
import lombok.Data;
import lombok.NoArgsConstructor;

@Entity
@Table(name = "planos_2d")
@Data
@NoArgsConstructor
@AllArgsConstructor
public class Plano2D {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @NotBlank(message = "El nombre del plano es obligatorio")
    @Column(nullable = false, length = 150)
    private String nombre;

    @NotBlank(message = "La url del plano es obligatoria")
    @Column(nullable = false, length = 500)
    private String url;

    @Column(name = "numero_piso")
    private Integer piso;

    @Lob
    @Column(name = "datos_3d", columnDefinition = "TEXT")
    private String datos3d;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "inmueble_id", nullable = false)
    @JsonIgnore
    private Inmueble inmueble;
}
