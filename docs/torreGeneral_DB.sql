-- Script de creación de base de datos y tablas
CREATE DATABASE IF NOT EXISTS proyecto_sistema CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE proyecto_sistema;

-- 1. Tabla de Roles
CREATE TABLE IF NOT EXISTS roles (
	id INT AUTO_INCREMENT PRIMARY KEY,
	nombre_rol VARCHAR(50) NOT NULL UNIQUE,
	descripcion TEXT,
	fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insertar roles por defecto
INSERT INTO roles (nombre_rol, descripcion) VALUES
	('SuperAdmin', 'Acceso total al sistema y gestión de usuarios'),
	('Editor', 'Puede modificar contenido pero no gestionar usuarios'),
	('Visualizador', 'Solo acceso de lectura');

-- 2. Tabla de Usuarios (Administradores)
CREATE TABLE IF NOT EXISTS usuarios (
	id INT AUTO_INCREMENT PRIMARY KEY,
	rol_id INT NOT NULL,
	nombre VARCHAR(100) NOT NULL,
	email VARCHAR(150) NOT NULL UNIQUE,
	password VARCHAR(255) NOT NULL,
	token_recuperacion VARCHAR(100) DEFAULT NULL,
	ultimo_login DATETIME DEFAULT NULL,
	fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 3. Tabla de Suscripciones (Landing Page)
CREATE TABLE IF NOT EXISTS suscripciones (
	id INT AUTO_INCREMENT PRIMARY KEY,
	email VARCHAR(150) NOT NULL UNIQUE,
	fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Usuario inicial (Password: admin123) - RECOMENDADO CAMBIAR AL INGRESAR
-- Nota: En producción el hash debe generarse con password_hash() de PHP
INSERT INTO usuarios (rol_id, nombre, email, password) VALUES
(1, 'Administrador Inicial', 'admin@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');