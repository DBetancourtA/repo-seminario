-- Plataforma de Seminario de Titulación
-- MySQL 5.7+ / MariaDB 10.3+  ·  utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rol           ENUM('admin','estudiante') NOT NULL DEFAULT 'estudiante',
  nombres       VARCHAR(100) NOT NULL,
  apellidos     VARCHAR(100) NOT NULL,
  cedula        VARCHAR(20)  NOT NULL,
  email         VARCHAR(150) NOT NULL,
  telefono      VARCHAR(20)  NULL,
  password_hash VARCHAR(255) NOT NULL,
  activo        TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_cedula (cedula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS convocatorias (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre             VARCHAR(150) NOT NULL,
  periodo            VARCHAR(50)  NOT NULL,
  carrera            VARCHAR(150) NOT NULL,
  codigo_inscripcion VARCHAR(12)  NOT NULL,
  fecha_inicio       DATE         NOT NULL,
  semanas            TINYINT UNSIGNED NOT NULL DEFAULT 12,
  dias_clase         VARCHAR(20)  NOT NULL DEFAULT '1,2,3,4',  -- ISO: 1=lunes … 7=domingo
  hora_inicio        TIME         NOT NULL DEFAULT '18:00:00',
  hora_fin           TIME         NOT NULL DEFAULT '20:00:00',
  ventana_horas      SMALLINT UNSIGNED NOT NULL DEFAULT 48,
  semana_congelamiento TINYINT UNSIGNED NOT NULL DEFAULT 3,  -- desde esta semana solo el docente edita la ficha
  estado             ENUM('borrador','abierta','en_curso','cerrada') NOT NULL DEFAULT 'abierta',
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_conv_codigo (codigo_inscripcion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inscripciones (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  convocatoria_id  INT UNSIGNED NOT NULL,
  user_id          INT UNSIGNED NOT NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_insc (convocatoria_id, user_id),
  FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sesiones (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  convocatoria_id  INT UNSIGNED NOT NULL,
  semana           TINYINT UNSIGNED NOT NULL,
  numero           TINYINT UNSIGNED NOT NULL,
  fecha            DATE NOT NULL,
  tema             VARCHAR(150) NULL,
  estado           ENUM('normal','suspendida') NOT NULL DEFAULT 'normal',
  UNIQUE KEY uq_ses (convocatoria_id, semana, numero),
  FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proyectos (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  convocatoria_id    INT UNSIGNED NOT NULL,
  user_id            INT UNSIGNED NOT NULL,
  titulo             VARCHAR(200) NOT NULL,
  descripcion        TEXT NULL,
  objetivo_general   TEXT NULL,
  repo_url           VARCHAR(255) NULL,
  estado             ENUM('registrado','en_revision','observado','aprobado','rechazado',
                          'tecnologias_definidas','en_desarrollo','finalizado') NOT NULL DEFAULT 'registrado',
  observacion        TEXT NULL,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_proy_conv (convocatoria_id, estado),
  FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proyecto_objetivos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proyecto_id INT UNSIGNED NOT NULL,
  orden       TINYINT UNSIGNED NOT NULL,
  texto       TEXT NOT NULL,
  FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proyecto_capturas (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proyecto_id INT UNSIGNED NOT NULL,
  ruta        VARCHAR(255) NOT NULL,
  descripcion VARCHAR(150) NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proyecto_estado_log (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proyecto_id INT UNSIGNED NOT NULL,
  desde       VARCHAR(30) NULL,
  hacia       VARCHAR(30) NOT NULL,
  user_id     INT UNSIGNED NULL,
  comentario  TEXT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS tecnologias (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(80) NOT NULL,
  categoria     ENUM('lenguaje','backend','frontend','base_datos','plataforma','infraestructura','herramienta') NOT NULL,
  aprobada      TINYINT(1) NOT NULL DEFAULT 1,
  propuesta_por INT UNSIGNED NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tec (categoria, nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proyecto_tecnologias (
  proyecto_id   INT UNSIGNED NOT NULL,
  tecnologia_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (proyecto_id, tecnologia_id),
  FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE,
  FOREIGN KEY (tecnologia_id) REFERENCES tecnologias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS avances (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proyecto_id INT UNSIGNED NOT NULL,
  sesion_id   INT UNSIGNED NOT NULL,
  actividad   TEXT NOT NULL,
  tipo        ENUM('codigo','interfaz','base_datos','documentacion','otro') NOT NULL DEFAULT 'codigo',
  enlace      VARCHAR(255) NULL,
  tardio      TINYINT(1) NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_avance (proyecto_id, sesion_id),
  FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE,
  FOREIGN KEY (sesion_id) REFERENCES sesiones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS avance_capturas (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  avance_id INT UNSIGNED NOT NULL,
  ruta      VARCHAR(255) NOT NULL,
  FOREIGN KEY (avance_id) REFERENCES avances(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS avance_comentarios (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  avance_id  INT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NOT NULL,
  texto      TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (avance_id) REFERENCES avances(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reportes (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proyecto_id INT UNSIGNED NOT NULL,
  semana      TINYINT UNSIGNED NOT NULL,
  codigo      VARCHAR(16) NOT NULL,
  hash        CHAR(64) NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_rep_codigo (codigo),
  FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_intentos (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  clave      VARCHAR(190) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_li (clave, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Catálogo inicial de tecnologías
INSERT IGNORE INTO tecnologias (nombre, categoria) VALUES
('PHP','lenguaje'),('JavaScript','lenguaje'),('TypeScript','lenguaje'),('Python','lenguaje'),
('Java','lenguaje'),('C#','lenguaje'),('Kotlin','lenguaje'),('Dart','lenguaje'),('Go','lenguaje'),('SQL','lenguaje'),
('Laravel','backend'),('CodeIgniter','backend'),('Node.js / Express','backend'),('NestJS','backend'),
('Django','backend'),('Flask','backend'),('FastAPI','backend'),('Spring Boot','backend'),('ASP.NET Core','backend'),('PHP nativo','backend'),
('React','frontend'),('Vue','frontend'),('Angular','frontend'),('Svelte','frontend'),('Next.js','frontend'),
('Bootstrap','frontend'),('Tailwind CSS','frontend'),('HTML/CSS','frontend'),('Flutter','frontend'),('React Native','frontend'),
('MySQL','base_datos'),('MariaDB','base_datos'),('PostgreSQL','base_datos'),('SQL Server','base_datos'),
('SQLite','base_datos'),('MongoDB','base_datos'),('Firebase / Firestore','base_datos'),('Supabase','base_datos'),('Sin base de datos','base_datos'),
('Aplicación web','plataforma'),('Aplicación móvil Android','plataforma'),('Aplicación móvil iOS','plataforma'),
('Aplicación de escritorio','plataforma'),('API REST','plataforma'),('IoT / Arduino / ESP32','plataforma'),
('Hosting compartido','infraestructura'),('VPS','infraestructura'),('Docker','infraestructura'),
('Vercel','infraestructura'),('Netlify','infraestructura'),('AWS','infraestructura'),('Google Cloud','infraestructura'),('Azure','infraestructura'),
('Git','herramienta'),('GitHub','herramienta'),('GitLab','herramienta'),('Figma','herramienta'),('Postman','herramienta'),('Visual Studio Code','herramienta');
