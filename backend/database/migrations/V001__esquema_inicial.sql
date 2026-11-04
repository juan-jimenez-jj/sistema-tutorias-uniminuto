-- =====================================================================
-- Sistema de Gestión de Tutorías — UNIMINUTO Zipaquirá
-- V001: esquema inicial (MySQL 8.0+ / MariaDB 10.6+)
--
-- Cambios frente al esquema heredado (Anexo_BaseDatos_Tutorias.sql):
--  * utf8mb4_unicode_ci, InnoDB, claves foráneas con reglas explícitas.
--  * Las franjas tienen cupo y la tutoría guarda una copia (snapshot) de horario y lugar,
--    de modo que editar una franja no altera el historial.
--  * Columna generada `reserva_activa` + índice único: la BD impide que el mismo
--    estudiante reserve dos veces la misma franja en la misma fecha (táctica de integridad).
--  * Outbox transaccional, auditoría, notificaciones internas e intentos de login.
--  * Índices pensados para las consultas reales (agenda, historial, reportes).
-- =====================================================================

CREATE TABLE usuarios (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_institucional    VARCHAR(20)  NOT NULL,
    nombre              VARCHAR(120) NOT NULL,
    email               VARCHAR(120) NOT NULL,
    password_hash       VARCHAR(255) NOT NULL,
    rol                 ENUM('estudiante','docente','administrador') NOT NULL,
    programa_academico  VARCHAR(120) NULL,
    estado              ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    consentimiento_datos_en DATETIME NULL COMMENT 'Autorización de tratamiento de datos (Ley 1581 de 2012)',
    ultimo_acceso_en    DATETIME NULL,
    creado_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_email (email),
    UNIQUE KEY uq_usuarios_id_institucional (id_institucional),
    KEY ix_usuarios_rol_estado (rol, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE materias (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo       VARCHAR(12)  NOT NULL,
    nombre       VARCHAR(120) NOT NULL,
    descripcion  VARCHAR(500) NULL,
    area         ENUM('Matemáticas','Física','Química','Estadística') NOT NULL DEFAULT 'Matemáticas',
    estado       ENUM('activa','inactiva') NOT NULL DEFAULT 'activa',
    creado_en    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_materias_codigo (codigo),
    KEY ix_materias_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE docente_materia (
    docente_id  INT UNSIGNED NOT NULL,
    materia_id  INT UNSIGNED NOT NULL,
    asignado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (docente_id, materia_id),
    KEY ix_docente_materia_materia (materia_id),
    CONSTRAINT fk_dm_docente FOREIGN KEY (docente_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_dm_materia FOREIGN KEY (materia_id) REFERENCES materias (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Franja semanal recurrente publicada por un docente para una materia.
CREATE TABLE franjas_disponibilidad (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    docente_id   INT UNSIGNED NOT NULL,
    materia_id   INT UNSIGNED NOT NULL,
    dia_semana   TINYINT UNSIGNED NOT NULL COMMENT '1=Lunes … 6=Sábado (ISO-8601)',
    hora_inicio  TIME NOT NULL,
    hora_fin     TIME NOT NULL,
    lugar        VARCHAR(100) NOT NULL,
    modalidad    ENUM('presencial','virtual') NOT NULL DEFAULT 'presencial',
    cupo         TINYINT UNSIGNED NOT NULL DEFAULT 1,
    activa       TINYINT(1) NOT NULL DEFAULT 1,
    creado_en    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY ix_franjas_busqueda (materia_id, docente_id, activa, dia_semana),
    KEY ix_franjas_docente (docente_id, dia_semana),
    CONSTRAINT ck_franjas_dia CHECK (dia_semana BETWEEN 1 AND 6),
    CONSTRAINT ck_franjas_horas CHECK (hora_fin > hora_inicio),
    CONSTRAINT ck_franjas_cupo CHECK (cupo BETWEEN 1 AND 30),
    CONSTRAINT fk_franjas_docente FOREIGN KEY (docente_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    CONSTRAINT fk_franjas_materia FOREIGN KEY (materia_id) REFERENCES materias (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tutorias (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo              CHAR(10) NOT NULL COMMENT 'Código legible para el usuario, p. ej. TUT-8K2F9Q',
    estudiante_id       INT UNSIGNED NOT NULL,
    docente_id          INT UNSIGNED NOT NULL,
    materia_id          INT UNSIGNED NOT NULL,
    franja_id           INT UNSIGNED NOT NULL,
    fecha               DATE NOT NULL,
    hora_inicio         TIME NOT NULL,
    hora_fin            TIME NOT NULL,
    lugar               VARCHAR(100) NOT NULL,
    modalidad           ENUM('presencial','virtual') NOT NULL DEFAULT 'presencial',
    tema                VARCHAR(500) NOT NULL,
    estado              ENUM('programada','completada','cancelada','no_asistio') NOT NULL DEFAULT 'programada',
    reserva_activa      TINYINT AS (IF(estado = 'programada', 1, NULL)) STORED,
    cancelada_por       ENUM('estudiante','docente','administrador') NULL,
    motivo_cancelacion  VARCHAR(500) NULL,
    cancelada_en        DATETIME NULL,
    observaciones_docente VARCHAR(1000) NULL,
    completada_en       DATETIME NULL,
    calificacion        TINYINT UNSIGNED NULL,
    comentario_estudiante VARCHAR(1000) NULL,
    calificada_en       DATETIME NULL,
    creado_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    version             INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Bloqueo optimista',
    UNIQUE KEY uq_tutorias_codigo (codigo),
    UNIQUE KEY uq_tutorias_reserva_unica (franja_id, fecha, estudiante_id, reserva_activa),
    KEY ix_tutorias_cupo (franja_id, fecha, estado),
    KEY ix_tutorias_estudiante (estudiante_id, fecha),
    KEY ix_tutorias_docente (docente_id, fecha),
    KEY ix_tutorias_reportes (fecha, estado, materia_id),
    CONSTRAINT ck_tutorias_calificacion CHECK (calificacion IS NULL OR calificacion BETWEEN 1 AND 5),
    CONSTRAINT fk_tutorias_estudiante FOREIGN KEY (estudiante_id) REFERENCES usuarios (id),
    CONSTRAINT fk_tutorias_docente FOREIGN KEY (docente_id) REFERENCES usuarios (id),
    CONSTRAINT fk_tutorias_materia FOREIGN KEY (materia_id) REFERENCES materias (id),
    CONSTRAINT fk_tutorias_franja FOREIGN KEY (franja_id) REFERENCES franjas_disponibilidad (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notificaciones internas que el usuario ve en la campana de la interfaz.
CREATE TABLE notificaciones (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED NOT NULL,
    tipo        VARCHAR(60)  NOT NULL,
    titulo      VARCHAR(150) NOT NULL,
    mensaje     VARCHAR(500) NOT NULL,
    enlace      VARCHAR(200) NULL,
    leida_en    DATETIME NULL,
    creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    origen_evento_id CHAR(32) NULL COMMENT 'Idempotencia: un evento genera una sola notificación por usuario',
    UNIQUE KEY uq_notif_evento_usuario (origen_evento_id, usuario_id),
    KEY ix_notif_usuario (usuario_id, leida_en, creado_en),
    CONSTRAINT fk_notif_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Patrón Transactional Outbox.
CREATE TABLE outbox_eventos (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id        CHAR(32) NOT NULL,
    tipo             VARCHAR(80) NOT NULL,
    payload          JSON NOT NULL,
    estado           ENUM('pendiente','procesando','procesado','fallido') NOT NULL DEFAULT 'pendiente',
    intentos         TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_error     VARCHAR(1000) NULL,
    ocurrido_en      DATETIME NOT NULL,
    disponible_desde DATETIME NOT NULL,
    procesado_en     DATETIME NULL,
    UNIQUE KEY uq_outbox_evento (evento_id),
    KEY ix_outbox_pendientes (estado, disponible_desde)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auditoria (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED NULL,
    accion      VARCHAR(60) NOT NULL,
    entidad     VARCHAR(60) NOT NULL,
    entidad_id  VARCHAR(40) NULL,
    detalle     JSON NULL,
    ip          VARCHAR(45) NULL,
    creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_auditoria_fecha (creado_en),
    KEY ix_auditoria_entidad (entidad, entidad_id),
    KEY ix_auditoria_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Limitación de intentos de inicio de sesión (mitiga fuerza bruta).
CREATE TABLE intentos_login (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(120) NOT NULL,
    ip         VARCHAR(45)  NULL,
    exitoso    TINYINT(1) NOT NULL,
    creado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY ix_intentos_email (email, creado_en),
    KEY ix_intentos_ip (ip, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
