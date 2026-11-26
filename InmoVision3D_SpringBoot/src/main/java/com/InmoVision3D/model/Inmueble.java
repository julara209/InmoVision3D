package com.InmoVision3D.model;

import com.InmoVision3D.model.enums.EstadoInmueble;
import com.InmoVision3D.model.enums.TipoInmueble;
import com.InmoVision3D.model.enums.TipoOperacion;
import com.fasterxml.jackson.annotation.JsonIgnore;
import jakarta.persistence.*;
import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import jakarta.validation.constraints.Positive;
import jakarta.validation.constraints.PositiveOrZero;
import lombok.AllArgsConstructor;
import lombok.Data;
import lombok.NoArgsConstructor;

import java.math.BigDecimal;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;

@Entity
@Table(name = "inmuebles")
@Data
@NoArgsConstructor
@AllArgsConstructor
public class Inmueble {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @NotBlank(message = "El titulo es obligatorio")
    @Column(nullable = false, length = 150)
    private String titulo;

    @Column(length = 2000)
    private String descripcion;

    @NotNull(message = "El precio es obligatorio")
    @Positive(message = "El precio debe ser mayor a cero")
    @Column(nullable = false, precision = 14, scale = 2)
    private BigDecimal precio;

    @NotBlank(message = "La direccion es obligatoria")
    @Column(nullable = false, length = 200)
    private String direccion;

    @Column(length = 100)
    private String ciudad;

    @Enumerated(EnumType.STRING)
    @Column(nullable = false, length = 30)
    private TipoInmueble tipo;

    @NotNull(message = "La operacion (venta/arriendo) es obligatoria")
    @Enumerated(EnumType.STRING)
    @Column(nullable = false, length = 20)
    private TipoOperacion operacion;

    @Enumerated(EnumType.STRING)
    @Column(nullable = false, length = 30)
    private EstadoInmueble estado = EstadoInmueble.DISPONIBLE;

    @PositiveOrZero
    private Double area;

    @PositiveOrZero
    private Integer habitaciones;

    @PositiveOrZero
    private Integer banos;

    @Column(name = "fecha_publicacion", nullable = false, updatable = false)
    private LocalDateTime fechaPublicacion;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "propietario_id", nullable = false)
    private Usuario propietario;

    @OneToMany(mappedBy = "inmueble", cascade = CascadeType.ALL, orphanRemoval = true)
    @JsonIgnore
    private List<ImagenInmueble> imagenes = new ArrayList<>();

    @OneToMany(mappedBy = "inmueble", cascade = CascadeType.ALL, orphanRemoval = true)
    @JsonIgnore
    private List<Plano2D> planos = new ArrayList<>();

    @OneToMany(mappedBy = "inmueble", cascade = CascadeType.ALL, orphanRemoval = true)
    @JsonIgnore
    private List<Favorito> favoritos = new ArrayList<>();

    @OneToMany(mappedBy = "inmueble", cascade = CascadeType.ALL, orphanRemoval = true)
    @JsonIgnore
    private List<Solicitud> solicitudes = new ArrayList<>();

    @PrePersist
    protected void alPersistir() {
        this.fechaPublicacion = LocalDateTime.now();
    }

    /**
     * Reemplaza al campo plano "imagen_principal" que traían las
     * consultas SQL en PHP. Busca la imagen marcada como principal
     * y, si no hay ninguna, cae a la primera imagen disponible.
     */
    @Transient
    public String getImagenPrincipalUrl() {
        return imagenes.stream()
                .filter(ImagenInmueble::isPrincipal)
                .map(ImagenInmueble::getUrl)
                .findFirst()
                .or(() -> imagenes.stream().map(ImagenInmueble::getUrl).findFirst())
                .orElse("/img/logo.png");
    }
}
