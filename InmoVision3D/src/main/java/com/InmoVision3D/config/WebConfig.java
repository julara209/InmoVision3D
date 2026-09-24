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

    }
}
