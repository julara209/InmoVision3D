package com.InmoVision3D.model;

import com.fasterxml.jackson.annotation.JsonIgnore;
import jakarta.persistence.*;
import jakarta.validation.constraints.NotBlank;
import lombok.AllArgsConstructor;
import lombok.Data;
import lombok.NoArgsConstructor;

@Entity
@Table(name = "imagenes_inmueble")
@Data
@NoArgsConstructor
@AllArgsConstructor
public class ImagenInmueble {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @NotBlank(message = "La url de la imagen es obligatoria")
    @Column(nullable = false, length = 500)
    private String url;

    @Column(length = 255)
    private String descripcion;

    @Column(nullable = false)
    private boolean principal = false;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "inmueble_id", nullable = false)
    @JsonIgnore
    private Inmueble inmueble;
}
