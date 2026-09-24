package com.InmoVision3D.controller;

import com.InmoVision3D.model.Inmueble;
import com.InmoVision3D.model.Usuario;
import com.InmoVision3D.model.enums.EstadoInmueble;
import com.InmoVision3D.model.enums.TipoInmueble;
import com.InmoVision3D.model.enums.TipoOperacion;
import com.InmoVision3D.repository.FavoritoRepository;
import com.InmoVision3D.repository.InmuebleRepository;
import com.InmoVision3D.repository.UsuarioRepository;
import com.InmoVision3D.service.InmuebleService;
import com.InmoVision3D.service.filtro.InmuebleFiltroBuilder;
import jakarta.validation.Valid;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.PageRequest;
import org.springframework.data.domain.Sort;
import org.springframework.data.jpa.domain.Specification;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.*;

import java.math.BigDecimal;
import java.util.HashSet;
import java.util.Set;


@Controller
@RequestMapping("/inmuebles")
public class InmuebleViewController {

    private static final int TAMANIO_PAGINA = 9;

    private final InmuebleRepository inmuebleRepository;
    private final UsuarioRepository usuarioRepository;
    private final FavoritoRepository favoritoRepository;
    private final InmuebleService inmuebleService;

    public InmuebleViewController(InmuebleRepository inmuebleRepository, UsuarioRepository usuarioRepository,
                                   FavoritoRepository favoritoRepository, InmuebleService inmuebleService) {
        this.inmuebleRepository = inmuebleRepository;
        this.usuarioRepository = usuarioRepository;
        this.favoritoRepository = favoritoRepository;
        this.inmuebleService = inmuebleService;
    }

    @GetMapping
    public String listar(@RequestParam(required = false) String tipo,
                          @RequestParam(required = false) String ubicacion,
                          @RequestParam(required = false) String operacion,
                          @RequestParam(name = "precio_max", required = false) BigDecimal precioMax,
                          @RequestParam(required = false) Integer habitaciones,
                          @RequestParam(defaultValue = "1") int pagina,
                          Authentication authentication,
                          Model model) {

        // PATRÓN GoF: Builder — el mismo InmuebleFiltroBuilder que usa el
        // Centro de Reportes (ver InmuebleSpecification.conFiltros), así el
        // catálogo público y los reportes interpretan cada filtro igual.
        Specification<Inmueble> spec = InmuebleFiltroBuilder.nuevo()
                .conEstado(EstadoInmueble.DISPONIBLE)
                .conTipo(parseEnumSeguro(TipoInmueble.class, tipo))
                .conUbicacion(ubicacion)
                .conOperacion(parseEnumSeguro(TipoOperacion.class, operacion))
                .conPrecioMaximo(precioMax)
                .conHabitacionesMinimas(habitaciones)
                .build();

        int paginaSegura = Math.max(pagina, 1);
        Page<Inmueble> resultado = inmuebleRepository.findAll(spec,
                PageRequest.of(paginaSegura - 1, TAMANIO_PAGINA, Sort.by(Sort.Direction.DESC, "fechaPublicacion")));

        model.addAttribute("inmuebles", resultado.getContent());
        model.addAttribute("total", resultado.getTotalElements());
        model.addAttribute("totalPaginas", Math.max(resultado.getTotalPages(), 1));
        model.addAttribute("paginaActual", paginaSegura);
        model.addAttribute("favoritosIds", favoritosDelUsuario(authentication));
        model.addAttribute("usuarioActual", usuarioActual(authentication));
        model.addAttribute("tipo", tipo);
        model.addAttribute("ubicacion", ubicacion);
        model.addAttribute("operacion", operacion);
        model.addAttribute("precioMax", precioMax);
        model.addAttribute("habitaciones", habitaciones);

        return "inmuebles/listar";
    }

    @GetMapping("/{id}")
    public String detalle(@PathVariable Long id, Authentication authentication, Model model) {
        Inmueble inmueble = inmuebleService.obtenerPorId(id);
        model.addAttribute("inmueble", inmueble);

        Usuario usuarioActual = usuarioActual(authentication);
        model.addAttribute("usuarioActual", usuarioActual);
        boolean esFavorito = usuarioActual != null
                && favoritoRepository.existsByUsuarioIdAndInmuebleId(usuarioActual.getId(), id);
        model.addAttribute("esFavorito", esFavorito);
        model.addAttribute("totalFavoritos", favoritoRepository.countByInmuebleId(id));

        return "inmuebles/detalle";
    }

    @GetMapping("/publicar")
    @PreAuthorize("hasAnyRole('PUBLICADOR','ADMIN')")
    public String formularioPublicar(Model model, Authentication authentication) {
        model.addAttribute("inmueble", new Inmueble());
        model.addAttribute("usuarioActual", usuarioActual(authentication));
        return "inmuebles/publicar";
    }

    @PostMapping("/publicar")
    @PreAuthorize("hasAnyRole('PUBLICADOR','ADMIN')")
    public String publicar(@Valid @ModelAttribute Inmueble inmueble, Authentication authentication) {
        Usuario propietario = usuarioActual(authentication);
        Inmueble creado = inmuebleService.crear(inmueble, propietario.getId());
        return "redirect:/inmuebles/" + creado.getId();
    }

    private Set<Long> favoritosDelUsuario(Authentication authentication) {
        Set<Long> ids = new HashSet<>();
        Usuario usuarioActual = usuarioActual(authentication);
        if (usuarioActual != null) {
            favoritoRepository.findByUsuarioId(usuarioActual.getId())
                    .forEach(f -> ids.add(f.getInmueble().getId()));
        }
        return ids;
    }

    private Usuario usuarioActual(Authentication authentication) {
        if (authentication == null || !authentication.isAuthenticated()
                || "anonymousUser".equals(authentication.getPrincipal())) {
            return null;
        }
        return usuarioRepository.findByEmail(authentication.getName()).orElse(null);
    }

    private <E extends Enum<E>> E parseEnumSeguro(Class<E> tipoEnum, String valor) {
        if (valor == null || valor.isBlank()) {
            return null;
        }
        try {
            return Enum.valueOf(tipoEnum, valor.trim().toUpperCase());
        } catch (IllegalArgumentException ex) {
            return null;
        }
    }
}
