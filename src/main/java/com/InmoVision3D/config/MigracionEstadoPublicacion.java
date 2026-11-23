package com.InmoVision3D.config;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.boot.ApplicationArguments;
import org.springframework.boot.ApplicationRunner;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.stereotype.Component;

/**
 * Migracion de datos de la HU "Inmueble nuevo queda pendiente de revision".
 *
 * Hibernate (ddl-auto: update) agrega la columna estado_publicacion SIN valor
 * en las filas que ya existian. Este runner deja esos inmuebles anteriores como
 * APROBADA para que sigan visibles en el catalogo. Los inmuebles nuevos siempre
 * se guardan con PENDIENTE desde InmuebleService.crear(), asi que nunca tienen
 * la columna vacia y este UPDATE solo afecta a los registros antiguos.
 *
 * Es idempotente: se puede ejecutar en cada arranque sin efectos extra.
 */
@Component
public class MigracionEstadoPublicacion implements ApplicationRunner {

    private static final Logger log = LoggerFactory.getLogger(MigracionEstadoPublicacion.class);

    private final JdbcTemplate jdbcTemplate;

    public MigracionEstadoPublicacion(JdbcTemplate jdbcTemplate) {
        this.jdbcTemplate = jdbcTemplate;
    }

    @Override
    public void run(ApplicationArguments args) {
        int actualizados = jdbcTemplate.update(
                "UPDATE inmuebles SET estado_publicacion = 'APROBADA' "
              + "WHERE estado_publicacion IS NULL OR estado_publicacion = ''");
        if (actualizados > 0) {
            log.info("Migracion estado_publicacion: {} inmueble(s) existente(s) marcados como APROBADA", actualizados);
        }
    }
}
