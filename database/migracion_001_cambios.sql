-- Migración 001: registro de cambios en la ficha y semana de congelamiento.
-- Ejecútala solo si instalaste una versión anterior (antes de esta función).
SET NAMES utf8mb4;
ALTER TABLE convocatorias ADD COLUMN semana_congelamiento TINYINT UNSIGNED NOT NULL DEFAULT 3 AFTER ventana_horas;

CREATE TABLE IF NOT EXISTS proyecto_cambios (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proyecto_id   INT UNSIGNED NOT NULL,
  campo         VARCHAR(30) NOT NULL,
  valor_anterior TEXT NULL,
  valor_nuevo   TEXT NULL,
  tipo          ENUM('completado','solicitud','docente') NOT NULL,
  estado        ENUM('pendiente','aceptado','rechazado','aplicado','cancelado') NOT NULL,
  motivo        TEXT NULL,
  user_id       INT UNSIGNED NULL,
  revisado_por  INT UNSIGNED NULL,
  comentario_revision TEXT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  revisado_at   DATETIME NULL,
  KEY idx_cambios (proyecto_id, estado),
  FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
