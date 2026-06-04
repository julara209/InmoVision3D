CREATE DATABASE InmoVision;
USE InmoVision;

CREATE TABLE Usuarios (
    idUsuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    contrasena VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    rol ENUM('cliente','publicador','admin') NOT NULL
);

CREATE TABLE Inmuebles (
    idInmueble INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    precio DECIMAL(12,2) NOT NULL,
    ubicacion VARCHAR(150) NOT NULL,
    tipo ENUM('casa', 'apartamento', 'local', 'oficina', 'terreno', 'bodega') NOT NULL,
    operacion ENUM('venta', 'arriendo') NOT NULL,
    habitaciones INT NOT NULL,
    banos INT NOT NULL,
    area DECIMAL(10,2) NOT NULL,
    estado VARCHAR(50) NOT NULL,
    idPublicador INT NOT NULL,
    FOREIGN KEY (idPublicador) REFERENCES Usuarios(idUsuario)
);

CREATE TABLE Imagenes_inmueble (
    idImagen INT AUTO_INCREMENT PRIMARY KEY,
    urlImagen VARCHAR(255) NOT NULL,
    es_principal TINYINT(1) DEFAULT 0,
    idInmueble INT NOT NULL,
    FOREIGN KEY (idInmueble) REFERENCES Inmuebles(idInmueble)
);

CREATE TABLE Planos_2d (
    idPlano INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    archivo VARCHAR(255),
    idInmueble INT,
    FOREIGN KEY (idInmueble) REFERENCES Inmuebles(idInmueble)
);

CREATE TABLE Objetos_plano (
    idObjeto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    posicion_x INT NOT NULL,
    posicion_y INT NOT NULL,
    idPlano INT NOT NULL,
    FOREIGN KEY (idPlano) REFERENCES Planos_2d(idPlano)
);

CREATE TABLE Modelos_3d (
    idModelo INT AUTO_INCREMENT PRIMARY KEY,
    archivo_3d VARCHAR(255),
    idInmueble INT,
    FOREIGN KEY (idInmueble) REFERENCES Inmuebles(idInmueble)
);

CREATE TABLE Solicitudes (
    idSolicitud INT AUTO_INCREMENT PRIMARY KEY,
    mensaje TEXT NOT NULL,
    fecha DATE NOT NULL,
    estado VARCHAR(50) NOT NULL,
    fecha_cita DATE NOT NULL,
    hora_cita TIME NOT NULL,
    idCliente INT,
    idInmueble INT,
    FOREIGN KEY (idCliente) REFERENCES Usuarios(idUsuario),
    FOREIGN KEY (idInmueble) REFERENCES Inmuebles(idInmueble)
);

CREATE TABLE Favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    inmueble_id INT,
    FOREIGN KEY (usuario_id) REFERENCES Usuarios(idUsuario),
    FOREIGN KEY (inmueble_id) REFERENCES Inmuebles(idInmueble)
);

-- INSERTS
INSERT INTO Usuarios (nombre, apellido, correo, contrasena, telefono, rol) VALUES
('Admin', 'Sistema', 'admin@inmovision.com', 'admin05', '3001234567', 'administrador'),
('Laura', 'Gomez', 'laura@gmail.com', '12345', '3001111111', 'cliente'),
('Carlos', 'Perez', 'carlos@gmail.com', '12345', '3002222222', 'publicador');

INSERT INTO Inmuebles 
(titulo, descripcion, precio, ubicacion, tipo, operacion, habitaciones, banos, area, estado, idPublicador)
VALUES
('Apartamento Central', 'Apartamento bien ubicado', 250000000, 'Bogota Centro', 'Apartamento', 'Venta', 3, 2, 85, 'Disponible', 2),
('Casa Moderna en Las Colinas', 'Hermosa casa moderna con acabados de lujo, amplios espacios iluminados y jardín privado. Perfecta para familias.', 450000000, 'Las Colinas - Bogotá', 'Casa', 'Venta', 4, 3, 250, 'Disponible', 2),
('Apartamento de Lujo Centro', 'Apartamento exclusivo en el corazón de la ciudad, con vista panorámica y acceso a zonas comunes premium.', 180000000, 'Centro Histórico - Bogotá', 'Apartamento', 'Arriendo', 2, 2, 120,'Disponible', 2),
('Villa con Piscina Privada', 'Espectacular villa con piscina, zona BBQ, cancha de tenis y jardines. Ideal para quienes buscan exclusividad.', 780000000, 'Zona Residencial Norte - Bogotá', 'Casa', 'Venta', 5, 4, 380, 'Disponible', 2),
('Penthouse Vista al Mar', 'Penthouse de lujo con terraza privada y vista espectacular al mar. Acabados de diseñador.', 350000000, 'Zona Costera - Cartagena', 'Apartamento', 'Arriendo', 3, 3, 200, 'Disponible', 2),
('Casa Familiar con Jardín', 'Casa acogedora perfecta para familias, con amplio jardín, zona de juegos y excelente iluminación natural.', 320000000, 'Urbanización El Bosque - Medellín', 'Casa', 'Venta', 4, 3, 220, 'Disponible', 2),
('Loft Moderno Industrial', 'Loft de diseño industrial con techos altos, espacios abiertos y acabados únicos. Ideal para creativos.', 120000000, 'Distrito Creativo - Bogotá', 'Apartamento', 'Arriendo', 1, 1, 85,'Disponible', 2);


INSERT INTO Imagenes_inmueble (urlImagen, es_principal, idInmueble) VALUES
('img1.jpg',0, 1),
('https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&h=600&fit=crop', 1, 2),
('https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800&h=600&fit=crop', 1, 3),
('https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800&h=600&fit=crop', 1, 4),
('https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800&h=600&fit=crop', 1, 5),
('https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=800&h=600&fit=crop', 1, 6),
('https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=800&h=600&fit=crop', 1, 7),
('https://images.unsplash.com/photo-1600573472550-8090b5e0745e?w=800&h=600&fit=crop', 1, 1);


INSERT INTO Planos_2d (nombre, archivo, idInmueble) VALUES
('Plano apartamento', 'plano1.pdf', 1);

INSERT INTO Objetos_plano (nombre, tipo, posicion_x, posicion_y, idPlano) VALUES
('Sofa', 'Mueble', 10, 20, 1);

INSERT INTO Modelos_3d (archivo_3d, idInmueble) VALUES
('modelo1.obj', 1);

INSERT INTO Solicitudes 
(mensaje, fecha, estado, fecha_cita, hora_cita, idCliente, idInmueble)
VALUES
('Me interesa el inmueble', '2026-04-09', 'Pendiente', '2026-04-12', '10:00:00', 1, 1);

INSERT INTO Favoritos (idCliente, idInmueble) VALUES
(1,1);

-- CONSULTAS
SELECT * FROM Usuarios;

SELECT * FROM Inmuebles WHERE estado = 'Disponible';

SELECT * FROM Inmuebles ORDER BY precio ASC;

SELECT i.titulo, i.precio, u.nombre, u.apellido
FROM Inmuebles i
JOIN Usuarios u ON i.idPublicador = u.idUsuario;

SELECT u.nombre, i.titulo
FROM Favoritos f
JOIN Usuarios u ON f.idCliente = u.idUsuario
JOIN Inmuebles i ON f.idInmueble = i.idInmueble;

SELECT s.mensaje, s.fecha, u.nombre AS cliente, i.titulo AS inmueble
FROM Solicitudes s
JOIN Usuarios u ON s.idCliente = u.idUsuario
JOIN Inmuebles i ON s.idInmueble = i.idInmueble;

-- PROCEDIMIENTO ALMACENADO
DELIMITER //

CREATE PROCEDURE sp_registrar_usuario (
    IN p_nombre VARCHAR(100),
    IN p_apellido VARCHAR(100),
    IN p_correo VARCHAR(100),
    IN p_contrasena VARCHAR(100),
    IN p_telefono VARCHAR(20),
    IN p_rol VARCHAR(20)
)
BEGIN
    INSERT INTO Usuarios(nombre, apellido, correo, contrasena, telefono, rol)
    VALUES (p_nombre, p_apellido, p_correo, p_contrasena, p_telefono, p_rol);
END //

DELIMITER ;

-- FUNCIÓN
DELIMITER //

CREATE FUNCTION fn_total_inmuebles_publicador(p_id INT)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE total INT;

    SELECT COUNT(*) INTO total
    FROM Inmuebles
    WHERE idPublicador = p_id;

    RETURN total;
END //

DELIMITER ;

-- TRIGGER FAVORITOS DUPLICADOS
DELIMITER //

CREATE TRIGGER trg_no_favoritos_duplicados
BEFORE INSERT ON Favoritos
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM Favoritos
        WHERE idCliente = NEW.idCliente
        AND idInmueble = NEW.idInmueble
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Este inmueble ya está en favoritos';
    END IF;
END //

DELIMITER ;

-- TRIGGER CORREO DUPLICADO
DELIMITER //

CREATE TRIGGER trg_validar_correo
BEFORE INSERT ON Usuarios
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM Usuarios
        WHERE correo = NEW.correo
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El correo ya está registrado';
    END IF;
END //

DELIMITER ;
