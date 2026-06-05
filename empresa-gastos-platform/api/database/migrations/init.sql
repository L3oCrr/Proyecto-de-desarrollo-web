-- =============================================================================
-- B-002: Esquema de Base de Datos y Migración Inicial
-- Sistema de Gestión de Gastos Empresariales
-- Motor: InnoDB | Charset: utf8mb4_unicode_ci
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- Base de datos
-- -----------------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS empresa_gastos
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE empresa_gastos;

-- -----------------------------------------------------------------------------
-- Eliminación idempotente (orden inverso por dependencias FK)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS bitacora_auditoria;
DROP TABLE IF EXISTS gastos;
DROP TABLE IF EXISTS facturas_cfdi;
DROP TABLE IF EXISTS estatus_gastos;
DROP TABLE IF EXISTS catalogo_cuentas;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS presupuestos;
DROP TABLE IF EXISTS centro_costos;
DROP TABLE IF EXISTS areas;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- 1. roles
-- Perfiles RBAC del sistema (Capturista, Jefe de Área, CxP, Administrador).
-- =============================================================================
CREATE TABLE roles (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(50)     NOT NULL,
    codigo      VARCHAR(30)     NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME        NULL     DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_codigo (codigo)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 2. areas
-- Divisiones funcionales de la organización.
-- =============================================================================
CREATE TABLE areas (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(100)    NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME        NULL     DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 3. centro_costos
-- Unidades contables vinculadas a un área funcional.
-- =============================================================================
CREATE TABLE centro_costos (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    area_id         BIGINT UNSIGNED NOT NULL,
    codigo_contable VARCHAR(20)     NOT NULL,
    nombre          VARCHAR(100)    NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      DATETIME        NULL     DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_centro_costos_codigo_contable (codigo_contable),
    KEY idx_centro_costos_area_id (area_id),
    CONSTRAINT fk_centro_costos_area_id
        FOREIGN KEY (area_id) REFERENCES areas (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 4. presupuestos
-- Techos financieros mensuales por centro de costos.
-- =============================================================================
CREATE TABLE presupuestos (
    id               BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    centro_costos_id BIGINT UNSIGNED   NOT NULL,
    periodo_mes      TINYINT UNSIGNED  NOT NULL,
    periodo_anio     SMALLINT UNSIGNED NOT NULL,
    monto_assigned   DECIMAL(12, 4)    NOT NULL,
    created_at       DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME          NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_presupuestos_centro_periodo (centro_costos_id, periodo_mes, periodo_anio),
    KEY idx_presupuestos_centro_costos_id (centro_costos_id),
    CONSTRAINT fk_presupuestos_centro_costos_id
        FOREIGN KEY (centro_costos_id) REFERENCES centro_costos (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_presupuestos_periodo_mes
        CHECK (periodo_mes BETWEEN 1 AND 12)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 5. usuarios
-- Colaboradores autorizados, vinculados a rol y área.
-- =============================================================================
CREATE TABLE usuarios (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rol_id      BIGINT UNSIGNED NOT NULL,
    area_id     BIGINT UNSIGNED NOT NULL,
    nombre      VARCHAR(150)    NOT NULL,
    email       VARCHAR(100)    NOT NULL,
    password    VARCHAR(255)    NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME        NULL     DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email),
    KEY idx_usuarios_rol_id (rol_id),
    KEY idx_usuarios_area_id (area_id),
    CONSTRAINT fk_usuarios_rol_id
        FOREIGN KEY (rol_id) REFERENCES roles (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_usuarios_area_id
        FOREIGN KEY (area_id) REFERENCES areas (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 6. catalogo_cuentas
-- Subcuentas contables de naturaleza deudora (Grupo 600).
-- =============================================================================
CREATE TABLE catalogo_cuentas (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    numero_cuenta  VARCHAR(30)     NOT NULL,
    descripcion    VARCHAR(150)    NOT NULL,
    created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME        NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at     DATETIME        NULL     DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_catalogo_cuentas_numero_cuenta (numero_cuenta)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 7. estatus_gastos
-- Catálogo cerrado de estados del ciclo de vida del gasto.
-- =============================================================================
CREATE TABLE estatus_gastos (
    id     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(50)     NOT NULL,
    codigo VARCHAR(30)     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_estatus_gastos_codigo (codigo)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 8. facturas_cfdi
-- Información fiscal extraída del XML (CFDI). Relación 1:1 con gastos.
-- =============================================================================
CREATE TABLE facturas_cfdi (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid                VARCHAR(36)     NOT NULL,
    emisor_rfc          VARCHAR(13)     NOT NULL,
    emisor_razon_social VARCHAR(250)    NOT NULL,
    receptor_rfc        VARCHAR(13)     NOT NULL,
    monto_subtotal      DECIMAL(12, 4)  NOT NULL,
    monto_iva           DECIMAL(12, 4)  NOT NULL,
    monto_total         DECIMAL(12, 4)  NOT NULL,
    fecha_emision       DATE            NOT NULL,
    xml_file_path       VARCHAR(510)    NOT NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_facturas_cfdi_uuid (uuid)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 9. gastos
-- Entidad transaccional central del sistema.
-- =============================================================================
CREATE TABLE gastos (
    id                        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_capturista_id     BIGINT UNSIGNED NOT NULL,
    centro_costos_id          BIGINT UNSIGNED NOT NULL,
    cuenta_contable_id        BIGINT UNSIGNED NOT NULL,
    estatus_gasto_id          BIGINT UNSIGNED NOT NULL,
    factura_cfdi_id           BIGINT UNSIGNED NULL,
    monto_total               DECIMAL(12, 4)  NOT NULL,
    fecha_gasto               DATE            NOT NULL,
    concepto_descripcion      VARCHAR(255)    NOT NULL,
    comentarios_rechazo       VARCHAR(500)    NULL,
    folio_contable_interno    VARCHAR(50)     NULL,
    usuario_aprobador_jefe_id BIGINT UNSIGNED NULL,
    usuario_aprobador_cxp_id  BIGINT UNSIGNED NULL,
    created_at                DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                DATETIME        NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_gastos_factura_cfdi_id (factura_cfdi_id),
    KEY idx_gastos_usuario_capturista_id (usuario_capturista_id),
    KEY idx_gastos_centro_costos_id (centro_costos_id),
    KEY idx_gastos_cuenta_contable_id (cuenta_contable_id),
    KEY idx_gastos_estatus_gasto_id (estatus_gasto_id),
    KEY idx_gastos_usuario_aprobador_jefe_id (usuario_aprobador_jefe_id),
    KEY idx_gastos_usuario_aprobador_cxp_id (usuario_aprobador_cxp_id),
    KEY idx_gastos_presupuesto (centro_costos_id, estatus_gasto_id, fecha_gasto, monto_total),
    KEY idx_gastos_busqueda (fecha_gasto, estatus_gasto_id),
    CONSTRAINT fk_gastos_usuario_capturista_id
        FOREIGN KEY (usuario_capturista_id) REFERENCES usuarios (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_gastos_centro_costos_id
        FOREIGN KEY (centro_costos_id) REFERENCES centro_costos (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_gastos_cuenta_contable_id
        FOREIGN KEY (cuenta_contable_id) REFERENCES catalogo_cuentas (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_gastos_estatus_gasto_id
        FOREIGN KEY (estatus_gasto_id) REFERENCES estatus_gastos (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_gastos_factura_cfdi_id
        FOREIGN KEY (factura_cfdi_id) REFERENCES facturas_cfdi (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_gastos_usuario_aprobador_jefe_id
        FOREIGN KEY (usuario_aprobador_jefe_id) REFERENCES usuarios (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_gastos_usuario_aprobador_cxp_id
        FOREIGN KEY (usuario_aprobador_cxp_id) REFERENCES usuarios (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 10. bitacora_auditoria
-- Historial inmutable de acciones sobre gastos (solo INSERT a nivel aplicación).
-- =============================================================================
CREATE TABLE bitacora_auditoria (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    gasto_id                BIGINT UNSIGNED NOT NULL,
    usuario_id              BIGINT UNSIGNED NOT NULL,
    accion_realizada        VARCHAR(100)    NOT NULL,
    valores_anteriores_json TEXT            NULL,
    valores_nuevos_json     TEXT            NOT NULL,
    ip_address              VARCHAR(45)     NOT NULL,
    created_at              DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_bitacora_auditoria_gasto_id (gasto_id),
    KEY idx_bitacora_auditoria_usuario_id (usuario_id),
    KEY idx_bitacora_auditoria_created_at (created_at),
    CONSTRAINT fk_bitacora_auditoria_gasto_id
        FOREIGN KEY (gasto_id) REFERENCES gastos (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_bitacora_auditoria_usuario_id
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Fin del script init.sql
-- =============================================================================
