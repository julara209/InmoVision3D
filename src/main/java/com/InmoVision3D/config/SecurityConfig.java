package com.InmoVision3D.config;

import com.InmoVision3D.security.UsuarioDetailsService;

import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.security.authentication.AuthenticationProvider;
import org.springframework.security.authentication.dao.DaoAuthenticationProvider;
import org.springframework.security.config.annotation.method.configuration.EnableMethodSecurity;
import org.springframework.security.config.annotation.web.builders.HttpSecurity;
import org.springframework.security.config.annotation.web.configuration.EnableWebSecurity;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.security.web.SecurityFilterChain;
import org.springframework.security.web.servlet.util.matcher.PathPatternRequestMatcher;
import org.springframework.http.HttpMethod;

@Configuration
@EnableWebSecurity
@EnableMethodSecurity // habilita @PreAuthorize en los controllers
public class SecurityConfig {

    @Autowired
    private UsuarioDetailsService usuarioDetailsService;

    @Bean
    public PasswordEncoder passwordEncoder() {
        return new BCryptPasswordEncoder();
    }

    @Bean
    public AuthenticationProvider authenticationProvider() {
        DaoAuthenticationProvider provider =
            new DaoAuthenticationProvider(usuarioDetailsService);
            provider.setPasswordEncoder(passwordEncoder());
            return provider;
    }


    @Bean
    public SecurityFilterChain filterChain(HttpSecurity http) throws Exception {
        http
            .csrf(csrf -> csrf.ignoringRequestMatchers("/api/**"))
            .authorizeHttpRequests(auth -> auth
                // públicas
                .requestMatchers("/", "/index", "/inmuebles", "/inmuebles/{id}",
                                  "/auth/**", "/css/**", "/js/**", "/img/**", "/assets/**",
                                  "/uploads/**", "/media/**", "/favicon.ico").permitAll()

                // lectura pública de la API de inmuebles (catálogo)
                .requestMatchers(org.springframework.http.HttpMethod.GET, "/api/inmuebles/**").permitAll()

                // subida de archivos: solo publicadores/admin
                .requestMatchers("/api/uploads/**").hasAnyRole("PUBLICADOR", "ADMIN")

                // gestión de usuarios (crear/editar/eliminar/roles): solo ADMIN.
                // El registro público de cuentas NO pasa por aquí, usa /auth/registro.
                .requestMatchers("/api/usuarios/**").hasRole("ADMIN")

                // CLIENTE
                .requestMatchers("/favoritos/**")
                    .hasAnyRole("CLIENTE", "ADMIN")
                .requestMatchers("/solicitudes/**")
                    .hasAnyRole("CLIENTE", "PUBLICADOR", "ADMIN")

                // PUBLICADOR
                .requestMatchers("/inmuebles/publicar", "/inmuebles/editar/**")
                    .hasAnyRole("PUBLICADOR", "ADMIN")

                // ADMIN
                .requestMatchers("/admin/**")
                    .hasRole("ADMIN")

                .anyRequest().authenticated()
            )
            .formLogin(form -> form
                .loginPage("/auth/login")
                .loginProcessingUrl("/auth/login")
                .usernameParameter("correo")
                .passwordParameter("contrasena")
                .defaultSuccessUrl("/", true)
                .failureUrl("/auth/login?error=true")
                .permitAll()
            )
            .logout(logout -> logout
                .logoutRequestMatcher(PathPatternRequestMatcher.pathPattern(HttpMethod.GET, "/auth/logout"))
                .logoutSuccessUrl("/")
                .permitAll()
            )
            .exceptionHandling(ex -> ex
                .accessDeniedPage("/error/403")
            );

        return http.build();
    }
}