-- Migración 003: entregables por enlace (manuales, video, demo, presentación).
-- Ejecútala una vez en phpMyAdmin (pestaña SQL). No modifica datos existentes.
-- La lista de entregables por defecto la crea la plataforma automáticamente para cada convocatoria.

CREATE TABLE IF NOT EXISTS entregables (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  convocatoria_id  INT UNSIGNED NOT NULL,
  nombre           VARCHAR(120) NOT NULL,
  descripcion      VARCHAR(255) NULL,
  semana           TINYINT UNSIGNED NOT NULL,
  tipo             ENUM('documento','video','url') NOT NULL DEFAULT 'documento',
  orden            TINYINT UNSIGNED NOT NULL DEFAULT 0,
  KEY idx_ent_conv (convocatoria_id, orden),
  FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS entregas (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proyecto_id    INT UNSIGNED NOT NULL,
  entregable_id  INT UNSIGNED NOT NULL,
  url            VARCHAR(500) NOT NULL,
  estado         ENUM('entregado','observado','aprobado') NOT NULL DEFAULT 'entregado',
  comentario     TEXT NULL,
  entregado_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  revisado_at    DATETIME NULL,
  revisado_por   INT UNSIGNED NULL,
  UNIQUE KEY uq_entrega (proyecto_id, entregable_id),
  FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE,
  FOREIGN KEY (entregable_id) REFERENCES entregables(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
