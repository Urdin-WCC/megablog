-- Script de inicialización de la base de datos para el sistema de autenticación

-- Usar la base de datos especificada
USE u643065128_phpblog;

-- Borrar tablas si existen (en orden inverso debido a las claves foráneas)
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS user_permissions;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

-- Crear tabla de roles
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    level INT NOT NULL,
    homepage VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Crear tabla de usuarios
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    civil_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    location VARCHAR(100),
    social_links TEXT,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    authorized_pages TEXT,
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Crear tabla de permisos de rol
CREATE TABLE role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    content_type VARCHAR(50) NOT NULL,
    content_id INT NOT NULL,
    permission VARCHAR(50) NOT NULL,
    granted_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_role_permission (role_id, content_type, content_id, permission)
);

-- Crear tabla de permisos de usuario
CREATE TABLE user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content_type VARCHAR(50) NOT NULL,
    content_id INT NOT NULL,
    permission VARCHAR(50) NOT NULL,
    granted_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_permission (user_id, content_type, content_id, permission)
);

-- Crear tabla de intentos de inicio de sesión
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) DEFAULT 0,
    user_agent VARCHAR(255),
    INDEX login_attempts_email_idx (email),
    INDEX login_attempts_ip_idx (ip_address)
);

-- Crear tabla de restablecimiento de contraseñas
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(100) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Crear tabla de sesiones
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    payload TEXT,
    last_activity TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Crear tabla de registro de actividad
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insertar roles (de mayor a menor nivel jerárquico)
INSERT INTO roles (name, level, homepage) VALUES 
('god', 800, '/pages/god/dashboard.php'),
('kk', 700, '/pages/kk/dashboard.php'),
('sumo', 600, '/pages/sumo/dashboard.php'),
('sacerdote', 500, '/pages/sacerdote/dashboard.php'),
('maestro', 400, '/pages/maestro/dashboard.php'),
('iniciado', 300, '/pages/iniciado/dashboard.php'),
('novicio', 200, '/pages/novicio/dashboard.php'),
('adepto', 100, '/pages/adepto/dashboard.php');

-- Insertar usuario administrador (necesario para relacionar los demás usuarios)
-- Contraseña: Admin123!
INSERT INTO users (username, civil_name, email, phone, location, password, role_id, notes, created_by) 
VALUES ('Admin', 'Administrador Principal', 'admin@ejemplo.com', '123456789', 'Oficina Central', 
'$2y$10$ZMoHaJ0hBEgOg6DYJ6w1euJkxKkDHQDAEWH.57l7gKSUzBwKpbPxK', 
(SELECT id FROM roles WHERE name = 'god'), 'Usuario administrador supremo', NULL);

-- Actualizar la referencia al usuario que creó el administrador
UPDATE users SET created_by = 1 WHERE id = 1;

-- Insertar usuarios de prueba para cada rol
-- Todos con contraseña según documentación

-- Administrador KK (Admin123!)
INSERT INTO users (username, civil_name, email, phone, location, password, role_id, notes, created_by) 
VALUES ('Admin2', 'Segundo Administrador', 'admin2@ejemplo.com', '123456788', 'Oficina Central', 
'$2y$10$ZMoHaJ0hBEgOg6DYJ6w1euJkxKkDHQDAEWH.57l7gKSUzBwKpbPxK', 
(SELECT id FROM roles WHERE name = 'kk'), 'Administrador general', 1);

-- Supervisor SUMO (Super123!)
INSERT INTO users (username, civil_name, email, phone, location, password, role_id, notes, created_by) 
VALUES ('Supervisor', 'Supervisor Principal', 'supervisor@ejemplo.com', '123456787', 'Oficina de Supervisión', 
'$2y$10$MiW94YTEy/9bO9giVn3EbuxkrM2VqujtdBMJ52zy.VQDASoaLfyWW', 
(SELECT id FROM roles WHERE name = 'sumo'), 'Supervisor del sistema', 1);

-- Moderador SACERDOTE (Moder123!)
INSERT INTO users (username, civil_name, email, phone, location, password, role_id, notes, created_by) 
VALUES ('Moderador', 'Moderador Principal', 'moderador@ejemplo.com', '123456786', 'Oficina de Moderación', 
'$2y$10$i.2y3GXxRgv7HNIvs7Vg2.uqPFzFUgIh66mJ4LntQD3MQ2Eq29zum', 
(SELECT id FROM roles WHERE name = 'sacerdote'), 'Moderador de contenidos', 1);

-- Gestor MAESTRO (Gestor123!)
INSERT INTO users (username, civil_name, email, phone, location, password, role_id, notes, created_by) 
VALUES ('Gestor', 'Gestor Principal', 'gestor@ejemplo.com', '123456785', 'Oficina de Gestión', 
'$2y$10$Ur4TpPxNGTBWsB4Sd3H1Z.jzMmMDl2Dh3iYSWTtdVHU1MZDwfdjDq', 
(SELECT id FROM roles WHERE name = 'maestro'), 'Gestor con permisos de crear usuarios', 1);

-- Usuario Avanzado INICIADO (Avan123!)
INSERT INTO users (username, civil_name, email, phone, location, password, role_id, notes, created_by) 
VALUES ('Avanzado', 'Usuario Avanzado', 'avanzado@ejemplo.com', '123456784', 'Sede Usuarios', 
'$2y$10$vgZlQ8IZ3xTn2mpQe1IENOvLsw6oLPyWVg8W.P9TK3P7MGjHnpnse', 
(SELECT id FROM roles WHERE name = 'iniciado'), 'Usuario con funcionalidades extendidas', 1);

-- Usuario Intermedio NOVICIO (Inter123!)
INSERT INTO users (username, civil_name, email, phone, location, password, role_id, notes, created_by) 
VALUES ('Intermedio', 'Usuario Intermedio', 'intermedio@ejemplo.com', '123456783', 'Sede Usuarios', 
'$2y$10$ILRgU7V85gvbBIDmT/CYoelqvUIwoA3YyOJJVNEbMHrhA0LViHWvu', 
(SELECT id FROM roles WHERE name = 'novicio'), 'Usuario con acceso intermedio', 1);

-- Usuario Básico ADEPTO (Basico123!)
INSERT INTO users (username, civil_name, email, phone, location, password, role_id, notes, created_by) 
VALUES ('Basico', 'Usuario Básico', 'basico@ejemplo.com', '123456782', 'Sede Usuarios', 
'$2y$10$pQo06LgWZ4Zl0RZ7qPyWGeeLqGNcYnY2NPLTcJBS2VbXXpV5Ht5l2', 
(SELECT id FROM roles WHERE name = 'adepto'), 'Usuario con acceso básico', 1);

-- Registramos algunas actividades iniciales
INSERT INTO activity_log (user_id, action, description, ip_address) VALUES
(1, 'user_created', 'Creación inicial de usuario administrador', '127.0.0.1'),
(1, 'user_created', 'Creación de usuario admin2@ejemplo.com', '127.0.0.1'),
(1, 'user_created', 'Creación de usuario supervisor@ejemplo.com', '127.0.0.1'),
(1, 'user_created', 'Creación de usuario moderador@ejemplo.com', '127.0.0.1'),
(1, 'user_created', 'Creación de usuario gestor@ejemplo.com', '127.0.0.1'),
(1, 'user_created', 'Creación de usuario avanzado@ejemplo.com', '127.0.0.1'),
(1, 'user_created', 'Creación de usuario intermedio@ejemplo.com', '127.0.0.1'),
(1, 'user_created', 'Creación de usuario basico@ejemplo.com', '127.0.0.1');

-- Configurar algunos permisos de ejemplo para roles
INSERT INTO role_permissions (role_id, content_type, content_id, permission, granted_by) VALUES
((SELECT id FROM roles WHERE name = 'adepto'), 'page', 1, 'read', 1),
((SELECT id FROM roles WHERE name = 'novicio'), 'page', 1, 'read', 1),
((SELECT id FROM roles WHERE name = 'novicio'), 'page', 2, 'read', 1),
((SELECT id FROM roles WHERE name = 'iniciado'), 'page', 1, 'read', 1),
((SELECT id FROM roles WHERE name = 'iniciado'), 'page', 2, 'read', 1),
((SELECT id FROM roles WHERE name = 'iniciado'), 'page', 3, 'read', 1);

-- Configurar algunos permisos de ejemplo para usuarios específicos
INSERT INTO user_permissions (user_id, content_type, content_id, permission, granted_by) VALUES
(7, 'page', 4, 'read', 1), -- Usuario "Intermedio" tiene acceso especial a la página 4
(6, 'blog', 1, 'comment', 1); -- Usuario "Avanzado" puede comentar en el blog 1
