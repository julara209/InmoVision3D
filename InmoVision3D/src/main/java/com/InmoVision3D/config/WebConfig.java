package com.InmoVision3D.config;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Configuration;
import org.springframework.web.servlet.config.annotation.ResourceHandlerRegistry;
import org.springframework.web.servlet.config.annotation.WebMvcConfigurer;

import java.nio.file.Path;
import java.nio.file.Paths;

/**
 * Expone la carpeta de subidas (imágenes de inmuebles y planos 2D subidos por
 * los publicadores) como recursos estáticos públicos bajo /uploads/**.
 *
 * Los archivos se guardan ahora en:
 *     src/main/resources/static/uploads/inmuebles
 *     src/main/resources/static/uploads/planos
 *
 * Esa es la misma carpeta donde ya viven las imágenes de ejemplo del proyecto,
 * así que todo (ejemplos y subidas nuevas) queda junto y versionado con el
 * código fuente.
 *
 * ¿Por qué se registra el handler a mano si Spring Boot ya sirve
 * classpath:/static/**? Porque el classpath apunta a target/classes, que solo
 * se refresca al recompilar. Al apuntar el handler directamente a la carpeta
 * del proyecto (file:...), las imágenes recién subidas se ven de inmediato sin
 * reiniciar ni recompilar.
 *
 * /media/** se mantiene como alias del mismo directorio para que sigan
 * funcionando las URLs guardadas en base de datos antes de este cambio.
 */
@Configuration
public class WebConfig implements WebMvcConfigurer {

    @Value("${app.upload-dir:src/main/resources/static/uploads}")
    private String uploadDir;

    @Override
    public void addResourceHandlers(ResourceHandlerRegistry registry) {
        Path raiz = Paths.get(uploadDir).toAbsolutePath().normalize();
        String location = raiz.toUri().toString();

        // Prioridad sobre el handler por defecto de /** para que gane esta ruta
        registry.setOrder(0);

        registry.addResourceHandler("/uploads/**")
                .addResourceLocations(location, "classpath:/static/uploads/")
                .setCachePeriod(0);

        // Alias histórico: las URLs antiguas eran /media/...
        registry.addResourceHandler("/media/**")
                .addResourceLocations(location, "classpath:/static/uploads/")
                .setCachePeriod(0);
    }
}
