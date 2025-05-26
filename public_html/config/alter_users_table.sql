-- Script para añadir columnas de gestión de intentos de inicio de sesión a la tabla users

-- Añadir columna login_attempts
ALTER TABLE users ADD COLUMN login_attempts INT DEFAULT 0 AFTER is_active;

-- Añadir columna account_locked
ALTER TABLE users ADD COLUMN account_locked TINYINT(1) DEFAULT 0 AFTER login_attempts;

-- Añadir columna last_attempt_time
ALTER TABLE users ADD COLUMN last_attempt_time TIMESTAMP NULL AFTER account_locked;
