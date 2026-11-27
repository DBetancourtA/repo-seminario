-- Migración 002: registra el último ingreso de cada usuario.
-- Ejecútala una vez en phpMyAdmin (pestaña SQL). No modifica datos existentes.
ALTER TABLE users ADD COLUMN ultimo_ingreso DATETIME NULL AFTER activo;
