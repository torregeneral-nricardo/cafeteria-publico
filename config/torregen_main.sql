-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 17-01-2026 a las 18:23:30
-- Versión del servidor: 8.0.44-cll-lve
-- Versión de PHP: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `torregen_main`
--

DELIMITER $$
--
-- Funciones
--
CREATE DEFINER=`torregen`@`localhost` FUNCTION `generar_numero_ticket` () RETURNS VARCHAR(20) CHARSET utf8mb4  BEGIN
    DECLARE prefijo VARCHAR(3) DEFAULT 'TG';
    DECLARE fecha_str VARCHAR(6);
    DECLARE consecutivo INT;
    DECLARE nuevo_ticket VARCHAR(20);
    
    -- Obtener fecha en formato yymmdd
    SET fecha_str = DATE_FORMAT(CURDATE(), '%y%m%d');
    
    -- Obtener el máximo consecutivo del día actual + 1
    -- Usamos FOR UPDATE para bloquear el registro en transacciones concurrentes
    SELECT COALESCE(MAX(CAST(SUBSTRING(numero_ticket, 8, 4) AS UNSIGNED)), 0) + 1
    INTO consecutivo
    FROM ventas 
    WHERE numero_ticket LIKE CONCAT(prefijo, '-____-', fecha_str)
    FOR UPDATE;
    
    -- Formatear número de ticket
    SET nuevo_ticket = CONCAT(prefijo, '-', 
                              LPAD(consecutivo, 4, '0'), 
                              '-', 
                              fecha_str);
    
    RETURN nuevo_ticket;
END$$

CREATE DEFINER=`torregen`@`localhost` FUNCTION `generar_numero_ticket_v2` () RETURNS VARCHAR(20) CHARSET utf8mb4  BEGIN
    DECLARE prefijo VARCHAR(3) DEFAULT 'TG';
    DECLARE fecha_actual DATE;
    DECLARE fecha_str VARCHAR(6);
    DECLARE consecutivo INT;
    DECLARE nuevo_ticket VARCHAR(20);
    
    SET fecha_actual = CURDATE();
    SET fecha_str = DATE_FORMAT(fecha_actual, '%y%m%d');
    
    -- Usar INSERT ... ON DUPLICATE KEY UPDATE para manejar concurrencia
    INSERT INTO secuencia_tickets (fecha, consecutivo) 
    VALUES (fecha_actual, 1)
    ON DUPLICATE KEY UPDATE consecutivo = consecutivo + 1;
    
    -- Obtener el consecutivo actualizado
    SELECT consecutivo INTO consecutivo 
    FROM secuencia_tickets 
    WHERE fecha = fecha_actual;
    
    -- Formatear número de ticket
    SET nuevo_ticket = CONCAT(prefijo, '-', 
                              LPAD(consecutivo, 4, '0'), 
                              '-', 
                              fecha_str);
    
    RETURN nuevo_ticket;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades_log`
--

CREATE TABLE `actividades_log` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `accion` varchar(100) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `descripcion` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `metadata` json DEFAULT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `actividades_log`
--

INSERT INTO `actividades_log` (`id`, `usuario_id`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `metadata`, `fecha_registro`) VALUES
(1, 1, 'Inicio de sesión', 'Autenticación', 'Administrador inició sesión en el sistema', '192.168.1.100', NULL, NULL, '2025-12-29 08:45:23'),
(2, 1, 'Creación de usuario', 'Usuarios', 'Administrador creó nuevo usuario cajero', '192.168.1.100', NULL, NULL, '2025-12-29 08:45:23'),
(3, 2, 'Apertura de caja', 'Caja', 'María González abrió caja para el turno matutino', '192.168.1.101', NULL, NULL, '2025-12-29 08:45:23'),
(4, 2, 'Venta registrada', 'Ventas', 'Venta #001-2024 realizada por María González', '192.168.1.101', NULL, NULL, '2025-12-29 08:45:23'),
(5, 4, 'Reporte generado', 'Reportes', 'Ana Martínez generó reporte de ventas mensual', '192.168.1.102', NULL, NULL, '2025-12-29 08:45:23'),
(6, 5, 'Pago registrado', 'Inquilinos', 'Tech Solutions realizó pago de alquiler', '192.168.1.103', NULL, NULL, '2025-12-29 08:45:23'),
(7, 9, 'Mantenimiento registrado', 'Mantenimiento', 'Pedro López registró mantenimiento preventivo de ascensores', '192.168.1.104', NULL, NULL, '2025-12-29 08:45:23'),
(8, 10, 'Ronda de seguridad', 'Seguridad', 'Roberto Jiménez completó ronda de vigilancia en pisos 1-5', '192.168.1.105', NULL, NULL, '2025-12-29 08:45:23'),
(9, 11, 'Ronda de seguridad', 'Seguridad', 'Miguel Rojas completó ronda de vigilancia en pisos 6-10', '192.168.1.106', NULL, NULL, '2025-12-29 08:45:23'),
(10, 7, 'Acceso concedido', 'Visitantes', 'Carlos Mendoza accedió al edificio como visitante confirmado', '192.168.1.107', NULL, NULL, '2025-12-29 08:45:23'),
(11, 1, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.204.141.64', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 09:52:11'),
(12, 1, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.204.141.64', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 09:57:55'),
(13, 1, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.204.141.64', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 10:04:31'),
(14, 1, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.204.141.64', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 10:05:35'),
(15, 13, 'Registro público', 'Usuarios', 'Nuevo usuario registrado: correo@existente.com', '190.204.141.64', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 10:07:25'),
(16, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.204.141.64', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 10:17:16'),
(17, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.204.141.64', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 10:18:59'),
(18, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.204.141.64', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 10:33:11'),
(19, 5, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.204.141.64', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 10:50:16'),
(20, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.204.141.64', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 10:51:47'),
(21, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.204.141.64', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 10:54:01'),
(22, 5, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.204.141.64', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 11:01:23'),
(23, 5, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.204.141.64', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 11:11:05'),
(24, 11, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.204.141.64', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 11:11:21'),
(25, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '186.167.180.100', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2025-12-29 13:20:12'),
(26, 14, 'Registro público', 'Usuarios', 'Nuevo usuario registrado: fcoblancopaezp@gmail.com', '201.208.113.128', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 14:13:08'),
(27, 14, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '201.208.113.128', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 14:13:23'),
(28, 14, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '201.208.113.128', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 14:19:05'),
(29, 1, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '201.208.113.128', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 14:20:04'),
(30, 1, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '201.208.113.128', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 14:21:33'),
(31, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '201.208.113.128', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 14:22:05'),
(32, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '186.167.180.100', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2025-12-29 14:26:14'),
(33, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '201.208.113.128', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 14:29:38'),
(34, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '186.167.180.100', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2025-12-29 14:30:08'),
(35, 14, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '201.208.113.128', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 14:32:36'),
(36, 14, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '201.208.113.128', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 14:40:33'),
(37, 11, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '201.208.113.128', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 14:40:57'),
(38, 11, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '201.208.113.128', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 15:00:25'),
(39, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '201.208.113.128', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 15:10:02'),
(40, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '186.93.171.39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 15:44:45'),
(41, 14, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '186.93.171.39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 15:44:57'),
(42, 14, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '186.93.171.39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 15:45:11'),
(43, 14, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '186.93.171.39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 15:45:49'),
(44, 14, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '186.93.171.39', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-29 15:49:07'),
(45, 1, 'Restablecimiento contraseña', 'Autenticación', 'Contraseña restablecida exitosamente', '190.204.141.64', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-30 02:30:52'),
(46, 1, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.204.141.64', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2025-12-30 02:31:26'),
(47, 2, 'Prueba de sistema', 'Test', 'Probando funciones de auditoría desde test_conexion.php', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 04:19:43'),
(48, 2, 'Prueba de sistema', 'Test', 'Probando funciones de auditoría desde test_conexion.php', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 04:29:52'),
(49, 2, 'Prueba de sistema', 'Test', 'Probando funciones de auditoría desde test_conexion.php', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 04:30:17'),
(50, 2, 'Prueba de sistema', 'Test', 'Probando funciones de auditoría desde test_conexion.php', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 04:30:43'),
(51, 2, 'Prueba de sistema', 'Test', 'Probando funciones de auditoría desde test_conexion.php', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 04:31:51'),
(52, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 04:43:50'),
(53, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 04:57:27'),
(54, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 05:02:21'),
(55, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 05:32:25'),
(56, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 05:32:25'),
(57, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 05:58:41'),
(58, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 05:58:41'),
(59, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 06:02:36'),
(60, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 07:24:58'),
(61, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 07:24:58'),
(62, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:08:44'),
(63, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:08:44'),
(64, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:19:55'),
(65, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:19:55'),
(66, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:27:21'),
(67, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:27:38'),
(68, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:27:51'),
(69, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:27:55'),
(70, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:28:01'),
(71, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:28:21'),
(72, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:28:41'),
(73, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:29:06'),
(74, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:29:55'),
(75, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:33:38'),
(76, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:33:42'),
(77, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:33:47'),
(78, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:33:50'),
(79, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:33:54'),
(80, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:40:27'),
(81, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:41:56'),
(82, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:41:56'),
(83, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:45:17'),
(84, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:48:33'),
(85, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:49:45'),
(86, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:50:44'),
(87, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:53:36'),
(88, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:54:06'),
(89, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 08:59:18'),
(90, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:03:05'),
(91, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:03:07'),
(92, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:03:11'),
(93, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:03:14'),
(94, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:03:56'),
(95, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:04:05'),
(96, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:05:33'),
(97, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:05:51'),
(98, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:06:01'),
(99, 2, 'Cliente registrado', 'Clientes', 'Nuevo cliente: Ricardo Negrón (CI: 11690023)', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:09:30'),
(100, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:09:31'),
(101, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:10:07'),
(102, 2, 'Venta registrada', 'Ventas', 'Venta #TG-0001-260112 registrada por 12.00', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"total\": 12, \"venta_id\": \"1\", \"numero_ticket\": \"TG-0001-260112\"}', '2026-01-12 09:10:34'),
(103, 2, 'Ticket generado', 'Ventas', 'Ticket #TG-0001-260112 generado', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:10:34'),
(104, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:11:48'),
(105, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:14:32'),
(106, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:16:08'),
(107, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:16:09'),
(108, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:17:31'),
(109, 2, 'Acceso cierre caja', 'Caja', 'Ingresó al formulario de cierre de caja', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:17:35'),
(110, 2, 'Caja cerrada', 'Caja', 'Cierre de caja. Total ventas: 12.00', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"caja_id\": 1, \"total_ventas\": \"12.00\", \"total_transacciones\": 1}', '2026-01-12 09:19:49'),
(111, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:19:49'),
(112, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:20:22'),
(113, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:24:36'),
(114, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:24:39'),
(115, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:26:38'),
(116, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:26:40'),
(117, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:27:23'),
(118, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:27:25'),
(119, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:29:59'),
(120, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:30:02'),
(121, 2, 'Acceso apertura caja', 'Caja', 'Ingresó al formulario de apertura de caja', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:30:09'),
(122, 2, 'Caja abierta', 'Caja', 'Apertura de caja realizada. Turno: ', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:30:53'),
(123, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:30:53'),
(124, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:34:45'),
(125, 2, 'Ticket generado', 'Ventas', 'Ticket #TG-0001-260112 generado', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:36:12'),
(126, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:36:17'),
(127, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:39:39'),
(128, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:39:46'),
(129, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:39:50'),
(130, 2, 'Acceso cierre caja', 'Caja', 'Ingresó al formulario de cierre de caja', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:39:58'),
(131, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:40:02'),
(132, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-12 09:42:50'),
(133, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-12 09:42:50'),
(134, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-12 09:43:39'),
(135, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:46:45'),
(136, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:46:54'),
(137, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:46:54'),
(138, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 09:57:28'),
(139, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:03:09'),
(140, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:03:16'),
(141, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:03:19'),
(142, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:03:27'),
(143, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:03:37'),
(144, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:03:42'),
(145, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:08:02'),
(146, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:08:30'),
(147, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:09:19'),
(148, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:12:10'),
(149, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:12:13'),
(150, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:14:16'),
(151, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:14:20'),
(152, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:14:22'),
(153, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:25:00'),
(154, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:25:00'),
(155, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:35:04'),
(156, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:35:04'),
(157, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:51:09'),
(158, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:51:12'),
(159, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:56:36'),
(160, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:56:43'),
(161, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:56:43'),
(162, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:56:45'),
(163, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:56:50'),
(164, 2, 'Consulta clientes', 'Clientes', 'Consultó la lista de clientes con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:56:52'),
(165, 2, 'Consulta clientes', 'Clientes', 'Consultó la lista de clientes con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:58:13'),
(166, 2, 'Consulta clientes', 'Clientes', 'Consultó la lista de clientes con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 10:58:17'),
(167, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 11:06:28'),
(168, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 11:06:31'),
(169, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 11:45:13'),
(170, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 11:45:13'),
(171, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 11:45:16'),
(172, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 11:47:00'),
(173, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 11:47:47'),
(174, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 11:47:54'),
(175, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 11:47:56'),
(176, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 11:48:12'),
(177, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 11:58:29'),
(178, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:33:40'),
(179, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:33:40'),
(180, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:34:40'),
(181, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:35:40'),
(182, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:35:40'),
(183, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:35:59'),
(184, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:36:05'),
(185, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:36:09'),
(186, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:36:51'),
(187, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:36:56'),
(188, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:37:00'),
(189, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:37:02'),
(190, 2, 'Cliente registrado', 'Clientes', 'Nuevo cliente: Francisco Blanco (CI: 15795072)', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:37:55'),
(191, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:37:56'),
(192, 2, 'Venta registrada', 'Ventas', 'Venta #TG-18446744073709551357-260112 registrada por 16.00', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"total\": 16, \"venta_id\": \"2\", \"numero_ticket\": \"TG-18446744073709551357-260112\"}', '2026-01-12 13:38:07'),
(193, 2, 'Ticket generado', 'Ventas', 'Ticket #TG-18446744073709551357-260112 generado', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:38:07'),
(194, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:39:07'),
(195, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:39:19'),
(196, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:39:30'),
(197, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:40:10'),
(198, 2, 'Consulta clientes', 'Clientes', 'Consultó la lista de clientes con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:40:13'),
(199, 2, 'Consulta clientes', 'Clientes', 'Consultó la lista de clientes con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:40:48'),
(200, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:40:55'),
(201, 2, 'Consulta clientes', 'Clientes', 'Consultó la lista de clientes con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:43:23'),
(202, 2, 'Consulta clientes', 'Clientes', 'Consultó la lista de clientes con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:43:43'),
(203, 2, 'Consulta clientes', 'Clientes', 'Consultó la lista de clientes con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:44:07'),
(204, 2, 'Consulta clientes', 'Clientes', 'Consultó la lista de clientes con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:44:18');
INSERT INTO `actividades_log` (`id`, `usuario_id`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `metadata`, `fecha_registro`) VALUES
(205, 2, 'Consulta clientes', 'Clientes', 'Consultó la lista de clientes con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:44:25'),
(206, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:44:35'),
(207, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:44:40'),
(208, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:45:01'),
(209, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:45:04'),
(210, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:45:10'),
(211, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:45:54'),
(212, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:47:19'),
(213, 2, 'Acceso cierre caja', 'Caja', 'Ingresó al formulario de cierre de caja', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:47:24'),
(214, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:49:52'),
(215, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:49:56'),
(216, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:49:59'),
(217, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:50:00'),
(218, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:50:02'),
(219, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:50:05'),
(220, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:50:23'),
(221, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:51:13'),
(222, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:52:28'),
(223, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:52:44'),
(224, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:52:50'),
(225, 2, 'Registro producto', 'Inventario', 'Registró nuevo producto: Helado de Chocolate (POS-004)', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:55:23'),
(226, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:55:24'),
(227, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:55:37'),
(228, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:55:41'),
(229, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:56:07'),
(230, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:58:23'),
(231, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:59:05'),
(232, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:59:45'),
(233, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:02:06'),
(234, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:02:14'),
(235, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:26:10'),
(236, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:29:34'),
(237, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:29:34'),
(238, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:29:38'),
(239, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:29:45'),
(240, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:36:21'),
(241, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:36:24'),
(242, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:37:25'),
(243, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:37:27'),
(244, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:37:29'),
(245, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:37:30'),
(246, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:37:54'),
(247, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:38:06'),
(248, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:38:15'),
(249, 2, 'Cliente registrado', 'Clientes', 'Nuevo cliente: Rosalba Talabera (CI: 30303030)', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:40:09'),
(250, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:40:09'),
(251, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:42:27'),
(252, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:42:32'),
(253, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:42:47'),
(254, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:46:59'),
(255, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:47:36'),
(256, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:47:39'),
(257, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:47:46'),
(258, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:47:53'),
(259, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 14:47:53'),
(260, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 15:04:44'),
(261, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 15:04:59'),
(262, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 17:12:18'),
(263, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 17:12:18'),
(264, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 21:11:55'),
(265, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 21:11:56'),
(266, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 21:12:29'),
(267, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 21:13:02'),
(268, 2, 'Consulta clientes', 'Clientes', 'Consultó la lista de clientes con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 21:13:07'),
(269, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 21:13:13'),
(270, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 21:13:31'),
(271, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 21:13:31'),
(272, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-13 05:53:30'),
(273, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-13 05:53:39'),
(274, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-13 06:07:29'),
(275, 5, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-13 06:15:21'),
(276, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-13 06:15:48'),
(277, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-13 08:34:01'),
(278, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-13 08:34:01'),
(279, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-13 23:06:34'),
(280, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-13 23:06:34'),
(281, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-13 23:06:37'),
(282, 1, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-13 23:06:52'),
(283, 1, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-13 23:32:56'),
(284, 1, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-13 23:44:00'),
(285, 1, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 01:54:03'),
(286, 1, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 02:24:45'),
(287, 1, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 02:51:06'),
(288, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 02:51:18'),
(289, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 03:26:42'),
(290, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 03:30:43'),
(291, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 03:30:44'),
(292, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 03:30:51'),
(293, 1, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 03:31:00'),
(294, 1, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 03:38:02'),
(295, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 03:38:09'),
(296, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 03:38:09'),
(297, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 03:38:49'),
(298, 1, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 03:38:58'),
(299, 1, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 03:40:06'),
(300, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 03:40:13'),
(301, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 03:40:13'),
(302, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 04:25:49'),
(303, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 04:25:57'),
(304, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 04:25:57'),
(305, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 05:48:00'),
(306, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 05:48:00'),
(307, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 06:17:52'),
(308, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 06:17:52'),
(309, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 06:32:21'),
(310, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 06:32:21'),
(311, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 06:32:25'),
(312, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 06:32:32'),
(313, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 06:32:32'),
(314, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 06:32:35'),
(315, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 06:32:39'),
(316, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 06:34:08'),
(317, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 06:34:20'),
(318, 2, 'Consulta clientes', 'Clientes', 'Consultó la lista de clientes con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 06:34:24'),
(319, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 06:34:30'),
(320, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 06:44:07'),
(321, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:02:18'),
(322, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:02:25'),
(323, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:02:25'),
(324, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:02:27'),
(325, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:05:40'),
(326, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:05:45'),
(327, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:05:45'),
(328, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:07:25'),
(329, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:07:29'),
(330, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:20:57'),
(331, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:21:03'),
(332, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:21:04'),
(333, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:21:06'),
(334, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:21:18'),
(335, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:21:21'),
(336, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:21:24'),
(337, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:21:25'),
(338, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:21:35'),
(339, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:21:45'),
(340, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:21:53'),
(341, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:21:56'),
(342, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:26:57'),
(343, 2, 'Cliente registrado', 'Clientes', 'Nuevo cliente: AQUA PARAISO (CI: V-30252109)', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:27:51'),
(344, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:27:51'),
(345, 2, 'Venta registrada', 'Ventas', 'Venta #TG-0001-260114 por 23.5', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:28:22'),
(346, 2, 'Ticket generado', 'Ventas', 'Ticket #TG-0001-260114 generado', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:28:22'),
(347, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:30:36'),
(348, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:30:57'),
(349, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:35:58'),
(350, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:40:58'),
(351, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:42:31'),
(352, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 07:42:55'),
(353, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 08:28:06'),
(354, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 08:28:24'),
(355, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 08:28:24'),
(356, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 08:28:36'),
(357, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 08:28:58'),
(358, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 08:29:28'),
(359, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 08:29:29'),
(360, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 08:29:31'),
(361, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 08:29:34'),
(362, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 08:29:38'),
(363, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 08:29:49'),
(364, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 08:34:49'),
(365, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 08:39:49'),
(366, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 08:44:50'),
(367, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 08:49:49'),
(368, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 08:54:49'),
(369, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 08:59:49'),
(370, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 09:04:49'),
(371, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 09:09:49'),
(372, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 09:14:49'),
(373, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 09:19:49'),
(374, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 09:24:49'),
(375, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 09:29:49'),
(376, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 09:34:49'),
(377, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 09:39:49'),
(378, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 09:44:49'),
(379, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 09:49:49'),
(380, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 09:54:49'),
(381, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 09:59:49'),
(382, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 10:04:49'),
(383, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 10:09:49'),
(384, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 10:14:49'),
(385, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 10:19:49'),
(386, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 10:24:49'),
(387, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 10:29:49'),
(388, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 10:34:49'),
(389, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 10:39:49'),
(390, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 10:44:49'),
(391, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 10:49:49'),
(392, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 10:54:49'),
(393, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 10:59:49'),
(394, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 11:04:50'),
(395, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 11:09:49'),
(396, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 11:14:49'),
(397, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 11:19:49'),
(398, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 11:24:49'),
(399, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 11:29:49'),
(400, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 11:34:49'),
(401, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 11:39:50'),
(402, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 11:44:49'),
(403, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 11:49:49'),
(404, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 11:54:49'),
(405, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 11:59:50'),
(406, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 12:04:49'),
(407, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 12:09:49'),
(408, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 12:14:50');
INSERT INTO `actividades_log` (`id`, `usuario_id`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `metadata`, `fecha_registro`) VALUES
(409, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 12:19:49'),
(410, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 12:24:49'),
(411, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 12:29:49'),
(412, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 12:34:50'),
(413, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 12:39:49'),
(414, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 12:44:49'),
(415, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 12:49:50'),
(416, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 12:54:49'),
(417, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 12:59:50'),
(418, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 13:04:49'),
(419, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 13:09:49'),
(420, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 13:14:49'),
(421, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 13:19:49'),
(422, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 13:24:50'),
(423, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 13:29:50'),
(424, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 13:34:49'),
(425, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 13:39:50'),
(426, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 13:44:49'),
(427, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 13:49:49'),
(428, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 13:50:33'),
(429, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 13:50:55'),
(430, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 13:50:55'),
(431, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 13:50:58'),
(432, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 13:55:58'),
(433, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 14:00:58'),
(434, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 14:05:59'),
(435, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 14:10:58'),
(436, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 14:15:58'),
(437, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 14:20:59'),
(438, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 14:25:59'),
(439, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 14:30:58'),
(440, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 14:35:59'),
(441, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 14:40:58'),
(442, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 14:45:58'),
(443, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 14:47:33'),
(444, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 14:52:35'),
(445, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 14:57:35'),
(446, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:02:34'),
(447, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:05:39'),
(448, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:05:53'),
(449, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:05:56'),
(450, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:05:58'),
(451, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:06:13'),
(452, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:07:01'),
(453, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:07:10'),
(454, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:07:21'),
(455, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:07:28'),
(456, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:07:32'),
(457, 2, 'Venta registrada', 'Ventas', 'Venta #TG-2147-260114 por 13.5', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:07:38'),
(458, 2, 'Ticket generado', 'Ventas', 'Ticket #TG-2147-260114 generado', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:07:38'),
(459, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:07:59'),
(460, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:08:00'),
(461, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:08:10'),
(462, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:08:12'),
(463, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:08:13'),
(464, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:08:28'),
(465, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:08:32'),
(466, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:08:49'),
(467, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:09:00'),
(468, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:09:07'),
(469, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:10:31'),
(470, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:10:39'),
(471, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:10:44'),
(472, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:13:06'),
(473, 2, 'Acceso cierre caja', 'Caja', 'Ingresó al formulario de cierre de caja', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:13:08'),
(474, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:13:26'),
(475, 2, 'Consulta inventario', 'Inventario', 'Consultó el inventario de productos', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:13:46'),
(476, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:15:36'),
(477, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:18:08'),
(478, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:18:40'),
(479, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:18:42'),
(480, 1, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:31:53'),
(481, 1, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:33:09'),
(482, 1, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 15:39:26'),
(483, 4, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-14 15:53:01'),
(484, 4, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-14 15:54:56'),
(485, 1, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 16:53:25'),
(486, 1, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 16:55:24'),
(487, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:49:09'),
(488, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:49:09'),
(489, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:49:14'),
(490, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:49:32'),
(491, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:49:35'),
(492, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:49:36'),
(493, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:49:41'),
(494, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:49:43'),
(495, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:49:44'),
(496, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:50:20'),
(497, 2, 'Consulta clientes', 'Clientes', 'Consultó la lista de clientes con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:50:28'),
(498, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:50:38'),
(499, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:50:41'),
(500, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:51:18'),
(501, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:51:22'),
(502, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:51:46'),
(503, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:51:49'),
(504, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:51:53'),
(505, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:52:00'),
(506, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:52:38'),
(507, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:52:41'),
(508, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:52:42'),
(509, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:52:42'),
(510, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:52:48'),
(511, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:52:59'),
(512, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:53:02'),
(513, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:53:09'),
(514, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:53:12'),
(515, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:53:15'),
(516, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 17:53:16'),
(517, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:02:27'),
(518, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:02:29'),
(519, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:02:34'),
(520, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:02:37'),
(521, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:26:22'),
(522, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:27:02'),
(523, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:27:02'),
(524, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:27:05'),
(525, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:27:09'),
(526, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:27:17'),
(527, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:27:19'),
(528, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:27:20'),
(529, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:27:27'),
(530, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:27:29'),
(531, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:27:37'),
(532, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:27:39'),
(533, 2, 'Venta registrada', 'Ventas', 'Venta #TG-9580-260114 por 8', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:27:41'),
(534, 2, 'Ticket generado', 'Ventas', 'Ticket #TG-9580-260114 generado', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:27:42'),
(535, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:27:49'),
(536, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:27:55'),
(537, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:28:07'),
(538, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:28:11'),
(539, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:28:17'),
(540, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:28:20'),
(541, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:28:27'),
(542, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:28:29'),
(543, 2, 'Venta registrada', 'Ventas', 'Venta #TG-6461-260114 por 5.5', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:28:31'),
(544, 2, 'Ticket generado', 'Ventas', 'Ticket #TG-6461-260114 generado', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:28:31'),
(545, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:28:38'),
(546, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:28:47'),
(547, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:28:59'),
(548, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:29:03'),
(549, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:29:07'),
(550, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:29:14'),
(551, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:29:17'),
(552, 2, 'Venta registrada', 'Ventas', 'Venta #TG-9907-260114 por 4.5', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:29:20'),
(553, 2, 'Ticket generado', 'Ventas', 'Ticket #TG-9907-260114 generado', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:29:20'),
(554, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:29:24'),
(555, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:31:32'),
(556, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:31:35'),
(557, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:31:38'),
(558, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:31:55'),
(559, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:53:26'),
(560, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:53:28'),
(561, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:53:42'),
(562, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:53:47'),
(563, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:53:54'),
(564, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:53:58'),
(565, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:54:02'),
(566, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:54:02'),
(567, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:54:11'),
(568, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:54:13'),
(569, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:54:19'),
(570, 2, 'Venta registrada', 'Ventas', 'Venta #TG-2135-260114 por 12', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:54:33'),
(571, 2, 'Ticket generado', 'Ventas', 'Ticket #TG-2135-260114 generado', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:54:33'),
(572, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 18:55:58'),
(573, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:09:49'),
(574, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:09:59'),
(575, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:09:59'),
(576, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:10:04'),
(577, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:10:08'),
(578, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:10:10'),
(579, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:10:14'),
(580, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:10:20'),
(581, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:10:24'),
(582, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:10:33'),
(583, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:10:51'),
(584, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:26:02'),
(585, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:26:05'),
(586, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:26:12'),
(587, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:26:12'),
(588, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:26:15'),
(589, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:26:17'),
(590, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:26:18'),
(591, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:26:20'),
(592, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:26:21'),
(593, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:26:23'),
(594, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:26:45'),
(595, 2, 'Consulta clientes', 'Clientes', 'Consultó la lista de clientes con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:26:50'),
(596, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:27:07'),
(597, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:27:09'),
(598, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:27:15'),
(599, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:27:18'),
(600, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:27:24'),
(601, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:27:31'),
(602, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:27:35'),
(603, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:27:47'),
(604, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:27:53'),
(605, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:29:15'),
(606, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:29:28'),
(607, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:40:15'),
(608, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:40:17'),
(609, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:40:23'),
(610, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:40:23'),
(611, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:40:26'),
(612, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:40:28'),
(613, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:40:29'),
(614, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:40:33'),
(615, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:40:50'),
(616, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:41:00');
INSERT INTO `actividades_log` (`id`, `usuario_id`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `metadata`, `fecha_registro`) VALUES
(617, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:41:21'),
(618, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:59:01'),
(619, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:59:05'),
(620, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:59:10'),
(621, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:59:10'),
(622, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:59:16'),
(623, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:59:24'),
(624, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:59:27'),
(625, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:59:29'),
(626, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 19:59:31'),
(627, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:10:43'),
(628, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:10:45'),
(629, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:11:09'),
(630, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:11:09'),
(631, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:11:20'),
(632, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:11:24'),
(633, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:11:26'),
(634, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:11:35'),
(635, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:11:49'),
(636, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:13:42'),
(637, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:13:44'),
(638, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:13:56'),
(639, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:46:59'),
(640, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:46:59'),
(641, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:47:02'),
(642, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:47:07'),
(643, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:47:09'),
(644, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:47:13'),
(645, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:47:15'),
(646, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:49:26'),
(647, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:49:28'),
(648, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:49:36'),
(649, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:49:38'),
(650, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:49:47'),
(651, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:49:50'),
(652, 2, 'Venta registrada', 'Ventas', 'Venta #TG-3992-260114 por 5.5', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:49:52'),
(653, 2, 'Ticket generado', 'Ventas', 'Ticket #TG-3992-260114 generado', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:49:52'),
(654, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:49:58'),
(655, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:50:08'),
(656, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:50:20'),
(657, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:50:25'),
(658, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:50:55'),
(659, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:51:07'),
(660, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:51:11'),
(661, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:51:13'),
(662, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:51:22'),
(663, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:51:30'),
(664, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:51:32'),
(665, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:51:39'),
(666, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:51:45'),
(667, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:51:47'),
(668, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:51:53'),
(669, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:51:56'),
(670, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:52:00'),
(671, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:52:05'),
(672, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:52:11'),
(673, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:52:20'),
(674, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:52:25'),
(675, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:52:34'),
(676, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:52:37'),
(677, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:52:54'),
(678, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:52:58'),
(679, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:53:04'),
(680, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:58:22'),
(681, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:58:26'),
(682, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:58:30'),
(683, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:58:38'),
(684, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:58:40'),
(685, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:58:50'),
(686, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:58:59'),
(687, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:59:07'),
(688, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:59:10'),
(689, 2, 'Venta registrada', 'Ventas', 'Venta #TG-6345-260114 por 6.8', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:59:22'),
(690, 2, 'Ticket generado', 'Ventas', 'Ticket #TG-6345-260114 generado', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:59:22'),
(691, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:59:27'),
(692, 2, 'Venta registrada', 'Ventas', 'Venta #TG-3276-260114 por 4.5', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:59:31'),
(693, 2, 'Ticket generado', 'Ventas', 'Ticket #TG-3276-260114 generado', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:59:31'),
(694, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:59:33'),
(695, 2, 'Venta registrada', 'Ventas', 'Venta #TG-5640-260114 por 6', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:59:37'),
(696, 2, 'Ticket generado', 'Ventas', 'Ticket #TG-5640-260114 generado', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:59:37'),
(697, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:59:39'),
(698, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:59:43'),
(699, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 20:59:45'),
(700, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 21:01:54'),
(701, 2, 'Ticket generado', 'Ventas', 'Ticket #TG-9907-260114 generado', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 21:06:20'),
(702, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-14 21:07:05'),
(703, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 186.96.68.13', '186.96.68.13', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 00:57:17'),
(704, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '186.96.68.13', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 00:57:17'),
(705, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 01:37:08'),
(706, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 01:37:08'),
(707, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 01:37:24'),
(708, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 01:37:48'),
(709, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 02:33:59'),
(710, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 02:33:59'),
(711, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 02:34:02'),
(712, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 02:34:34'),
(713, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 02:34:55'),
(714, 2, 'Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 02:35:08'),
(715, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 02:36:11'),
(716, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 02:36:34'),
(717, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 02:36:52'),
(718, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 02:37:08'),
(719, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 02:37:11'),
(720, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 02:49:54'),
(721, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 02:50:12'),
(722, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 02:50:14'),
(723, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 02:50:53'),
(724, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 02:51:30'),
(725, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 02:52:44'),
(726, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 02:52:44'),
(727, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 02:52:50'),
(728, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 02:53:18'),
(729, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 02:53:20'),
(730, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 02:55:05'),
(731, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 02:55:41'),
(732, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 02:57:59'),
(733, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 02:58:01'),
(734, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 02:59:31'),
(735, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 02:59:33'),
(736, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 03:00:47'),
(737, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 03:00:50'),
(738, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 03:00:53'),
(739, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 03:00:56'),
(740, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 03:02:06'),
(741, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 03:03:18'),
(742, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 03:03:18'),
(743, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 03:05:50'),
(744, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 03:06:05'),
(745, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 03:06:29'),
(746, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 03:07:00'),
(747, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 11:10:01'),
(748, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 11:10:01'),
(749, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 11:10:27'),
(750, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 11:10:30'),
(751, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 11:18:04'),
(752, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 11:18:04'),
(753, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 11:35:17'),
(754, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 11:54:14'),
(755, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 11:54:29'),
(756, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 11:54:29'),
(757, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 11:54:50'),
(758, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 11:55:00'),
(759, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 11:56:08'),
(760, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 11:58:37'),
(761, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 11:58:39'),
(762, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 12:15:52'),
(763, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 12:16:05'),
(764, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 12:16:08'),
(765, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 12:16:28'),
(766, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 12:28:13'),
(767, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 12:28:23'),
(768, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 12:28:23'),
(769, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 12:28:29'),
(770, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 12:28:44'),
(771, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 12:31:55'),
(772, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 12:31:55'),
(773, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 12:32:08'),
(774, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 12:33:41'),
(775, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 12:33:58'),
(776, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 12:37:55'),
(777, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 12:39:17'),
(778, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 12:49:46'),
(779, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 12:53:32'),
(780, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 13:46:36'),
(781, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 13:46:36'),
(782, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 13:46:49'),
(783, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 13:47:33'),
(784, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 13:50:51'),
(785, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 13:57:16'),
(786, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 13:58:27'),
(787, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 13:58:27'),
(788, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 13:58:48'),
(789, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 14:02:07'),
(790, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 14:02:24'),
(791, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 14:22:32'),
(792, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 14:23:28'),
(793, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 14:23:28'),
(794, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 14:23:31'),
(795, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 14:28:56'),
(796, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 14:31:07'),
(797, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 14:31:15'),
(798, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 14:31:45'),
(799, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 14:31:57'),
(800, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 14:32:39'),
(801, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 14:39:59'),
(802, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 14:40:17'),
(803, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 14:41:52'),
(804, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 14:45:37'),
(805, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 14:45:49'),
(806, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 15:08:02'),
(807, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 15:09:50'),
(808, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 15:09:50'),
(809, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 15:09:58'),
(810, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 15:11:52'),
(811, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 15:13:10'),
(812, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 15:14:10'),
(813, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 186.167.160.122', '186.167.160.122', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 15:58:42'),
(814, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '186.167.160.122', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 15:58:43'),
(815, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '186.167.160.122', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 15:59:07'),
(816, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 186.167.160.122', '186.167.160.122', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 16:02:19'),
(817, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '186.167.160.122', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 16:02:19');
INSERT INTO `actividades_log` (`id`, `usuario_id`, `accion`, `modulo`, `descripcion`, `ip_address`, `user_agent`, `metadata`, `fecha_registro`) VALUES
(818, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 186.167.160.122', '186.167.160.122', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 16:02:48'),
(819, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '186.167.160.122', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-15 16:02:48'),
(820, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 186.167.160.122', '186.167.160.122', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 16:03:49'),
(821, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '186.167.160.122', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 16:03:49'),
(822, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '186.167.160.122', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 16:04:05'),
(823, 2, 'Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta', '186.167.160.122', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 16:04:53'),
(824, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '186.167.160.122', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 16:05:20'),
(825, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '186.167.160.122', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 16:05:25'),
(826, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-16 15:04:39'),
(827, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-16 15:04:39'),
(828, 2, 'Acceso cierre caja', 'Caja', 'Ingresó al formulario de cierre de caja', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-16 15:04:47'),
(829, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-16 15:04:52'),
(830, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-16 21:44:47'),
(831, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-16 21:44:48'),
(832, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-16 21:44:49'),
(833, 2, 'Cierre de sesión', 'Autenticación', 'Usuario cerró sesión en el sistema', '190.205.20.255', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, '2026-01-16 21:44:59'),
(834, 2, 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: 190.205.20.255', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', NULL, '2026-01-17 22:20:04'),
(835, 2, 'Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', NULL, '2026-01-17 22:20:04'),
(836, 2, 'Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero', '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', NULL, '2026-01-17 22:20:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `anulaciones_ventas`
--

CREATE TABLE `anulaciones_ventas` (
  `id` int NOT NULL,
  `venta_id` int NOT NULL,
  `cajero_id` int NOT NULL,
  `supervisor_id` int DEFAULT NULL,
  `motivo` text NOT NULL,
  `estado` enum('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
  `fecha_solicitud` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_resolucion` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria_precios`
--

CREATE TABLE `auditoria_precios` (
  `id` int NOT NULL,
  `producto_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `precio_anterior` decimal(10,2) NOT NULL,
  `precio_nuevo` decimal(10,2) NOT NULL,
  `motivo` varchar(200) DEFAULT NULL,
  `fecha_cambio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caja`
--

CREATE TABLE `caja` (
  `id` int NOT NULL,
  `cajero_id` int NOT NULL,
  `turno_id` int DEFAULT NULL,
  `monto_inicial` decimal(10,2) NOT NULL,
  `monto_final` decimal(10,2) DEFAULT NULL,
  `monto_esperado` decimal(10,2) DEFAULT NULL,
  `diferencia` decimal(10,2) DEFAULT NULL,
  `estado` enum('abierta','cerrada','auditada') DEFAULT 'abierta',
  `fecha_apertura` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_cierre` timestamp NULL DEFAULT NULL,
  `observaciones` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `caja`
--

INSERT INTO `caja` (`id`, `cajero_id`, `turno_id`, `monto_inicial`, `monto_final`, `monto_esperado`, `diferencia`, `estado`, `fecha_apertura`, `fecha_cierre`, `observaciones`) VALUES
(1, 2, NULL, 500.00, 12.00, 512.00, 0.00, 'cerrada', '2026-01-12 03:39:31', '2026-01-12 09:19:49', ''),
(2, 2, NULL, 0.00, NULL, 16.00, NULL, 'abierta', '2026-01-12 09:30:53', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_productos`
--

CREATE TABLE `categorias_productos` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text,
  `color_hex` varchar(7) DEFAULT '#6B7280',
  `orden` int DEFAULT '0',
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `categorias_productos`
--

INSERT INTO `categorias_productos` (`id`, `nombre`, `descripcion`, `color_hex`, `orden`, `estado`, `fecha_creacion`) VALUES
(1, 'Bebidas Calientes', 'Cafés, tés, chocolate caliente', '#DC2626', 1, 'activo', '2026-01-12 03:39:31'),
(2, 'Bebidas Frías', 'Refrescos, jugos, smoothies', '#2563EB', 2, 'activo', '2026-01-12 03:39:31'),
(3, 'Alimentos', 'Platos principales, comidas', '#059669', 3, 'activo', '2026-01-12 03:39:31'),
(4, 'Snacks', 'Aperitivos, bocadillos', '#D97706', 4, 'activo', '2026-01-12 03:39:31'),
(5, 'Postres', 'Dulces, pasteles, helados', '#7C3AED', 5, 'activo', '2026-01-12 03:39:31'),
(6, 'Otros', 'Productos varios', '#6B7280', 6, 'activo', '2026-01-12 03:39:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int NOT NULL,
  `prefijo_cedula` enum('V','E','J','O') DEFAULT 'V',
  `cedula` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(200) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text,
  `tipo_cliente` enum('ocasional','frecuente','empresa') DEFAULT 'ocasional',
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('activo','inactivo') DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `prefijo_cedula`, `cedula`, `nombre`, `apellido`, `email`, `telefono`, `direccion`, `tipo_cliente`, `fecha_registro`, `estado`) VALUES
(1, 'V', '12345678', 'Juan', 'Pérez', 'juan.perez@email.com', '+584141111111', NULL, 'frecuente', '2026-01-12 03:39:31', 'activo'),
(2, 'V', '23456789', 'María', 'García', 'maria.garcia@email.com', '+584142222222', NULL, 'frecuente', '2026-01-12 03:39:31', 'activo'),
(3, 'E', '87654321', 'Tech Solutions', 'C.A.', 'contacto@techsolutions.com', '+584143333333', NULL, 'empresa', '2026-01-12 03:39:31', 'activo'),
(4, 'V', '34567890', 'Carlos', 'Rodríguez', 'carlos.rodriguez@email.com', '+584144444444', NULL, 'ocasional', '2026-01-12 03:39:31', 'activo'),
(5, 'J', '1234567890', 'Restaurante', 'El Buen Sabor', 'info@elbuensabor.com', '+584145555555', NULL, 'empresa', '2026-01-12 03:39:31', 'activo'),
(6, 'V', '11690023', 'Ricardo', 'Negrón', 'nricardo.dev@gmail.com', '+584120318699', NULL, 'ocasional', '2026-01-12 09:09:30', 'activo'),
(7, 'V', '15795072', 'Francisco', 'Blanco', NULL, NULL, NULL, 'ocasional', '2026-01-12 13:37:55', 'activo'),
(8, 'V', '30303030', 'Rosalba', 'Talabera', NULL, NULL, NULL, 'ocasional', '2026-01-12 14:40:09', 'activo'),
(9, 'V', '30252109', 'AQUA', 'PARAISO', NULL, NULL, NULL, 'ocasional', '2026-01-14 07:27:51', 'activo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuraciones`
--

CREATE TABLE `configuraciones` (
  `id` int NOT NULL,
  `clave` varchar(100) NOT NULL,
  `valor` text,
  `tipo` varchar(20) DEFAULT 'texto',
  `categoria` varchar(50) DEFAULT 'General',
  `descripcion` text,
  `editable` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `configuraciones`
--

INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `categoria`, `descripcion`, `editable`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'nombre_sistema', 'Torre General - Sistema de Gestión', 'texto', 'General', 'Nombre del sistema', 1, '2025-12-29 08:45:23', '2025-12-29 08:45:23'),
(2, 'logo_sistema', 'assets/img/torre_general_logo_290x290.png', 'texto', 'General', 'Ruta del logo del sistema', 1, '2025-12-29 08:45:23', '2025-12-29 08:45:23'),
(3, 'modo_mantenimiento', '0', 'booleano', 'General', 'Modo mantenimiento del sistema (0=normal, 1=mantenimiento)', 1, '2025-12-29 08:45:23', '2025-12-29 08:45:23'),
(4, 'registro_publico', '1', 'booleano', 'Usuarios', 'Permitir registro público de usuarios', 1, '2025-12-29 08:45:23', '2025-12-29 08:45:23'),
(5, 'confirmacion_automatica', '0', 'booleano', 'Usuarios', 'Confirmación automática de nuevos usuarios', 1, '2025-12-29 08:45:23', '2025-12-29 08:45:23'),
(6, 'session_timeout', '30', 'numero', 'Seguridad', 'Tiempo de inactividad para cerrar sesión (minutos)', 1, '2025-12-29 08:45:23', '2025-12-29 08:45:23'),
(7, 'max_intentos_login', '5', 'numero', 'Seguridad', 'Máximo intentos de login fallidos', 1, '2025-12-29 08:45:23', '2025-12-29 08:45:23'),
(8, 'bloqueo_temporal', '15', 'numero', 'Seguridad', 'Minutos de bloqueo tras intentos fallidos', 1, '2025-12-29 08:45:23', '2025-12-29 08:45:23'),
(9, 'notificaciones_email', '1', 'booleano', 'Notificaciones', 'Enviar notificaciones por email', 1, '2025-12-29 08:45:23', '2025-12-29 08:45:23'),
(10, 'notificaciones_push', '1', 'booleano', 'Notificaciones', 'Mostrar notificaciones push', 1, '2025-12-29 08:45:23', '2025-12-29 08:45:23'),
(11, 'color_primario', '#EAB308', 'color', 'Apariencia', 'Color primario del sistema', 1, '2025-12-29 08:45:23', '2025-12-29 08:45:23'),
(12, 'color_secundario', '#1E293B', 'color', 'Apariencia', 'Color secundario del sistema', 1, '2025-12-29 08:45:23', '2025-12-29 08:45:23'),
(13, 'timezone', 'America/Caracas', 'texto', 'General', 'Zona horaria del sistema', 1, '2025-12-29 08:45:23', '2025-12-29 08:45:23'),
(14, 'formato_fecha', 'd/m/Y', 'texto', 'General', 'Formato de fecha', 1, '2025-12-29 08:45:23', '2025-12-29 08:45:23'),
(15, 'formato_hora', 'H:i:s', 'texto', 'General', 'Formato de hora', 1, '2025-12-29 08:45:23', '2025-12-29 08:45:23'),
(16, 'items_por_pagina', '25', 'numero', 'General', 'Elementos por página en listados', 1, '2025-12-29 08:45:23', '2025-12-29 08:45:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ventas`
--

CREATE TABLE `detalle_ventas` (
  `id` int NOT NULL,
  `venta_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `detalle_ventas`
--

INSERT INTO `detalle_ventas` (`id`, `venta_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 1, 13, 1, 2.00, 2.00),
(2, 1, 5, 1, 3.00, 3.00),
(3, 1, 9, 1, 4.50, 4.50),
(4, 1, 1, 1, 2.50, 2.50),
(5, 2, 8, 2, 5.00, 10.00),
(6, 2, 5, 2, 3.00, 6.00),
(7, 8, 12, 2, 2.50, 5.00),
(8, 8, 3, 1, 3.00, 3.00),
(9, 8, 9, 1, 4.50, 4.50),
(10, 8, 13, 2, 2.00, 4.00),
(11, 8, 6, 1, 2.50, 2.50),
(12, 8, 14, 1, 4.50, 4.50),
(13, 9, 2, 1, 3.50, 3.50),
(14, 9, 16, 1, 3.50, 3.50),
(15, 9, 7, 1, 1.50, 1.50),
(16, 9, 8, 1, 5.00, 5.00),
(17, 13, 7, 1, 1.50, 1.50),
(18, 13, 10, 1, 4.00, 4.00),
(19, 13, 6, 1, 2.50, 2.50),
(20, 14, 15, 1, 3.00, 3.00),
(21, 14, 12, 1, 2.50, 2.50),
(22, 15, 9, 1, 4.50, 4.50),
(23, 16, 15, 2, 3.00, 6.00),
(24, 16, 17, 2, 3.00, 6.00),
(25, 17, 7, 1, 1.50, 1.50),
(26, 17, 10, 1, 4.00, 4.00),
(27, 18, 11, 1, 1.80, 1.80),
(28, 18, 6, 2, 2.50, 5.00),
(29, 19, 9, 1, 4.50, 4.50),
(30, 20, 5, 2, 3.00, 6.00);

--
-- Disparadores `detalle_ventas`
--
DELIMITER $$
CREATE TRIGGER `after_insert_detalle_venta` AFTER INSERT ON `detalle_ventas` FOR EACH ROW BEGIN
    -- Descontar stock del producto
    UPDATE productos 
    SET stock = stock - NEW.cantidad,
        estado = CASE 
            WHEN (stock - NEW.cantidad) <= 0 THEN 'agotado'
            WHEN (stock - NEW.cantidad) <= stock_minimo THEN 'activo'
            ELSE estado
        END
    WHERE id = NEW.producto_id;
    
    -- Registrar movimiento de inventario
    INSERT INTO movimientos_inventario (producto_id, tipo, cantidad, stock_anterior, stock_nuevo, referencia_id, usuario_id)
    SELECT 
        NEW.producto_id,
        'venta',
        NEW.cantidad,
        p.stock + NEW.cantidad, -- Stock anterior (antes de descontar)
        p.stock, -- Stock nuevo (después de descontar)
        NEW.venta_id,
        v.cajero_id
    FROM productos p
    JOIN ventas v ON v.id = NEW.venta_id
    WHERE p.id = NEW.producto_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulos_sistema`
--

CREATE TABLE `modulos_sistema` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `descripcion` text,
  `icono` varchar(50) DEFAULT 'fa-cube',
  `ruta` varchar(100) DEFAULT '#',
  `orden` int DEFAULT '0',
  `visible` tinyint(1) DEFAULT '1',
  `requiere_confirmacion` tinyint(1) DEFAULT '0',
  `color_hex` varchar(7) DEFAULT '#6B7280',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `modulos_sistema`
--

INSERT INTO `modulos_sistema` (`id`, `nombre`, `codigo`, `descripcion`, `icono`, `ruta`, `orden`, `visible`, `requiere_confirmacion`, `color_hex`, `fecha_creacion`) VALUES
(1, 'Dashboard', 'dashboard', 'Panel principal del sistema', 'fa-tachometer-alt', 'dashboard.php', 1, 1, 0, '#3B82F6', '2025-12-29 08:45:23'),
(2, 'Usuarios', 'usuarios', 'Gestión de usuarios del sistema', 'fa-users', 'usuarios.php', 10, 1, 0, '#8B5CF6', '2025-12-29 08:45:23'),
(3, 'Roles', 'roles', 'Gestión de roles y permisos', 'fa-user-shield', 'roles.php', 20, 1, 0, '#EF4444', '2025-12-29 08:45:23'),
(4, 'Ventas POS', 'ventas', 'Punto de venta para cafetería', 'fa-cash-register', 'pos.php', 30, 1, 0, '#10B981', '2025-12-29 08:45:23'),
(5, 'Inventario', 'inventario', 'Gestión de productos y stock', 'fa-boxes', 'inventario.php', 40, 1, 0, '#F59E0B', '2025-12-29 08:45:23'),
(6, 'Caja', 'caja', 'Gestión de caja registradora', 'fa-cash-register', 'caja.php', 35, 1, 0, '#059669', '2025-12-29 08:45:23'),
(7, 'Reportes', 'reportes', 'Reportes y estadísticas', 'fa-chart-bar', 'reportes.php', 50, 1, 0, '#6366F1', '2025-12-29 08:45:23'),
(8, 'Inquilinos', 'inquilinos', 'Gestión de inquilinos', 'fa-building', 'inquilinos.php', 60, 1, 0, '#EC4899', '2025-12-29 08:45:23'),
(9, 'Visitantes', 'visitantes', 'Control de visitantes', 'fa-user-friends', 'visitantes.php', 70, 1, 0, '#0EA5E9', '2025-12-29 08:45:23'),
(10, 'Servicios', 'servicios', 'Solicitud de servicios', 'fa-concierge-bell', 'servicios.php', 65, 1, 0, '#F97316', '2025-12-29 08:45:23'),
(11, 'Mantenimiento', 'mantenimiento', 'Actividades de mantenimiento', 'fa-tools', 'mantenimiento.php', 80, 1, 0, '#64748B', '2025-12-29 08:45:23'),
(12, 'Seguridad', 'seguridad', 'Panel de seguridad y vigilancia', 'fa-shield-alt', 'seguridad.php', 90, 1, 0, '#DC2626', '2025-12-29 08:45:23'),
(13, 'Configuración', 'configuracion', 'Configuración del sistema', 'fa-cogs', 'configuracion.php', 100, 1, 0, '#475569', '2025-12-29 08:45:23'),
(14, 'Actividades', 'actividades', 'Registro de actividades', 'fa-history', 'actividades.php', 110, 1, 0, '#7C3AED', '2025-12-29 08:45:23'),
(15, 'Notificaciones', 'notificaciones', 'Notificaciones del sistema', 'fa-bell', 'notificaciones.php', 120, 1, 0, '#D97706', '2025-12-29 08:45:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_inventario`
--

CREATE TABLE `movimientos_inventario` (
  `id` int NOT NULL,
  `producto_id` int NOT NULL,
  `tipo` enum('venta','compra','ajuste','devolucion') NOT NULL,
  `cantidad` int NOT NULL,
  `stock_anterior` int NOT NULL,
  `stock_nuevo` int NOT NULL,
  `referencia_id` int DEFAULT NULL COMMENT 'ID de venta, compra, etc.',
  `observaciones` text,
  `usuario_id` int NOT NULL,
  `fecha_movimiento` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `movimientos_inventario`
--

INSERT INTO `movimientos_inventario` (`id`, `producto_id`, `tipo`, `cantidad`, `stock_anterior`, `stock_nuevo`, `referencia_id`, `observaciones`, `usuario_id`, `fecha_movimiento`) VALUES
(1, 13, 'venta', 1, 80, 79, 1, NULL, 2, '2026-01-12 09:10:34'),
(2, 5, 'venta', 1, 50, 49, 1, NULL, 2, '2026-01-12 09:10:34'),
(3, 9, 'venta', 1, 25, 24, 1, NULL, 2, '2026-01-12 09:10:34'),
(4, 1, 'venta', 1, 100, 99, 1, NULL, 2, '2026-01-12 09:10:34'),
(5, 8, 'venta', 2, 30, 28, 2, NULL, 2, '2026-01-12 13:38:07'),
(6, 5, 'venta', 2, 49, 47, 2, NULL, 2, '2026-01-12 13:38:07'),
(7, 12, 'venta', 2, 60, 58, 8, NULL, 2, '2026-01-14 07:28:22'),
(8, 3, 'venta', 1, 60, 59, 8, NULL, 2, '2026-01-14 07:28:22'),
(9, 9, 'venta', 1, 24, 23, 8, NULL, 2, '2026-01-14 07:28:22'),
(10, 13, 'venta', 2, 79, 77, 8, NULL, 2, '2026-01-14 07:28:22'),
(11, 6, 'venta', 1, 40, 39, 8, NULL, 2, '2026-01-14 07:28:22'),
(12, 14, 'venta', 1, 15, 14, 8, NULL, 2, '2026-01-14 07:28:22'),
(13, 2, 'venta', 1, 80, 79, 9, NULL, 2, '2026-01-14 15:07:38'),
(14, 16, 'venta', 1, 20, 19, 9, NULL, 2, '2026-01-14 15:07:38'),
(15, 7, 'venta', 1, 200, 199, 9, NULL, 2, '2026-01-14 15:07:38'),
(16, 8, 'venta', 1, 28, 27, 9, NULL, 2, '2026-01-14 15:07:38'),
(17, 7, 'venta', 1, 198, 197, 13, NULL, 2, '2026-01-14 18:27:41'),
(18, 10, 'venta', 1, 20, 19, 13, NULL, 2, '2026-01-14 18:27:41'),
(19, 6, 'venta', 1, 38, 37, 13, NULL, 2, '2026-01-14 18:27:41'),
(20, 15, 'venta', 1, 25, 24, 14, NULL, 2, '2026-01-14 18:28:31'),
(21, 12, 'venta', 1, 56, 55, 14, NULL, 2, '2026-01-14 18:28:31'),
(22, 9, 'venta', 1, 22, 21, 15, NULL, 2, '2026-01-14 18:29:19'),
(23, 15, 'venta', 2, 23, 21, 16, NULL, 2, '2026-01-14 18:54:33'),
(24, 17, 'venta', 2, 18, 16, 16, NULL, 2, '2026-01-14 18:54:33'),
(25, 7, 'venta', 1, 196, 195, 17, NULL, 2, '2026-01-14 20:49:52'),
(26, 10, 'venta', 1, 18, 17, 17, NULL, 2, '2026-01-14 20:49:52'),
(27, 11, 'venta', 1, 40, 39, 18, NULL, 2, '2026-01-14 20:59:22'),
(28, 6, 'venta', 2, 36, 34, 18, NULL, 2, '2026-01-14 20:59:22'),
(29, 9, 'venta', 1, 20, 19, 19, NULL, 2, '2026-01-14 20:59:31'),
(30, 5, 'venta', 2, 47, 45, 20, NULL, 2, '2026-01-14 20:59:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `mensaje` text NOT NULL,
  `tipo` enum('info','success','warning','error','system') DEFAULT 'info',
  `leida` tinyint(1) DEFAULT '0',
  `enlace` varchar(255) DEFAULT NULL,
  `fecha_envio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_leida` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id`, `usuario_id`, `titulo`, `mensaje`, `tipo`, `leida`, `enlace`, `fecha_envio`, `fecha_leida`) VALUES
(1, 1, 'Bienvenido al sistema', 'Bienvenido Administrador Principal al sistema de Torre General', 'system', 0, 'dashboard.php', '2025-12-29 08:45:23', NULL),
(2, 2, 'Recordatorio de caja', 'No olvide realizar el cierre de caja al finalizar su turno', 'warning', 0, 'caja.php', '2025-12-29 08:45:23', NULL),
(3, 4, 'Reporte mensual listo', 'El reporte de ventas del mes está disponible para revisión', 'info', 0, 'reportes.php', '2025-12-29 08:45:23', NULL),
(4, 5, 'Pago próximo a vencer', 'Su pago de alquiler vence en 5 días', 'warning', 0, 'inquilinos.php', '2025-12-29 08:45:23', NULL),
(5, 9, 'Mantenimiento programado', 'Tiene actividades de mantenimiento programadas para mañana', 'info', 0, 'mantenimiento.php', '2025-12-29 08:45:23', NULL),
(6, 10, 'Cambio de turno', 'Su turno de vigilancia comienza en 30 minutos', 'info', 0, 'seguridad.php', '2025-12-29 08:45:23', NULL),
(7, 1, 'Contraseña Actualizada', 'Su contraseña ha sido actualizada exitosamente.', 'success', 0, NULL, '2025-12-30 02:30:52', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id` int NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text,
  `modulo` varchar(50) NOT NULL,
  `categoria` varchar(50) DEFAULT 'General',
  `nivel_seguridad` int DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`id`, `codigo`, `nombre`, `descripcion`, `modulo`, `categoria`, `nivel_seguridad`, `fecha_creacion`) VALUES
(1, 'usuarios.ver', 'Ver usuarios', 'Ver lista de usuarios del sistema', 'usuarios', 'Administración', 1, '2025-12-29 08:45:23'),
(2, 'usuarios.crear', 'Crear usuarios', 'Crear nuevos usuarios en el sistema', 'usuarios', 'Administración', 3, '2025-12-29 08:45:23'),
(3, 'usuarios.editar', 'Editar usuarios', 'Editar información de usuarios existentes', 'usuarios', 'Administración', 3, '2025-12-29 08:45:23'),
(4, 'usuarios.eliminar', 'Eliminar usuarios', 'Eliminar usuarios del sistema', 'usuarios', 'Administración', 4, '2025-12-29 08:45:23'),
(5, 'usuarios.confirmar', 'Confirmar usuarios', 'Confirmar visitantes y nuevos usuarios', 'usuarios', 'Administración', 2, '2025-12-29 08:45:23'),
(6, 'usuarios.roles', 'Gestionar roles', 'Asignar y cambiar roles de usuarios', 'usuarios', 'Administración', 4, '2025-12-29 08:45:23'),
(7, 'roles.ver', 'Ver roles', 'Ver lista de roles del sistema', 'roles', 'Administración', 2, '2025-12-29 08:45:23'),
(8, 'roles.crear', 'Crear roles', 'Crear nuevos roles en el sistema', 'roles', 'Administración', 5, '2025-12-29 08:45:23'),
(9, 'roles.editar', 'Editar roles', 'Editar permisos y configuración de roles', 'roles', 'Administración', 5, '2025-12-29 08:45:23'),
(10, 'roles.eliminar', 'Eliminar roles', 'Eliminar roles del sistema', 'roles', 'Administración', 5, '2025-12-29 08:45:23'),
(11, 'sistema.config', 'Configurar sistema', 'Configuración general del sistema', 'sistema', 'Configuración', 4, '2025-12-29 08:45:23'),
(12, 'sistema.backup', 'Respaldar sistema', 'Crear y restaurar respaldos del sistema', 'sistema', 'Configuración', 5, '2025-12-29 08:45:23'),
(13, 'sistema.logs', 'Ver logs', 'Ver registros de actividades del sistema', 'sistema', 'Configuración', 3, '2025-12-29 08:45:23'),
(14, 'sistema.estado', 'Ver estado', 'Ver estado y métricas del sistema', 'sistema', 'Configuración', 2, '2025-12-29 08:45:23'),
(15, 'ventas.crear', 'Crear venta', 'Crear nueva venta en el sistema POS', 'ventas', 'Operaciones', 1, '2025-12-29 08:45:23'),
(16, 'ventas.ver', 'Ver ventas', 'Ver historial de ventas realizadas', 'ventas', 'Operaciones', 1, '2025-12-29 08:45:23'),
(17, 'ventas.editar', 'Editar venta', 'Editar ventas existentes', 'ventas', 'Operaciones', 3, '2025-12-29 08:45:23'),
(18, 'ventas.anular', 'Anular venta', 'Anular venta registrada', 'ventas', 'Operaciones', 3, '2025-12-29 08:45:23'),
(19, 'ventas.reporte', 'Reporte ventas', 'Generar reportes de ventas', 'ventas', 'Operaciones', 2, '2025-12-29 08:45:23'),
(20, 'caja.abrir', 'Abrir caja', 'Abrir caja registradora para operaciones', 'caja', 'Operaciones', 2, '2025-12-29 08:45:23'),
(21, 'caja.cerrar', 'Cerrar caja', 'Cerrar caja y realizar corte', 'caja', 'Operaciones', 2, '2025-12-29 08:45:23'),
(22, 'caja.ver', 'Ver caja', 'Ver estado actual de la caja', 'caja', 'Operaciones', 1, '2025-12-29 08:45:23'),
(23, 'caja.ajustar', 'Ajustar caja', 'Realizar ajustes en el monto de caja', 'caja', 'Operaciones', 4, '2025-12-29 08:45:23'),
(24, 'inventario.ver', 'Ver inventario', 'Ver lista de productos en inventario', 'inventario', 'Operaciones', 1, '2025-12-29 08:45:23'),
(25, 'inventario.crear', 'Crear producto', 'Agregar nuevo producto al inventario', 'inventario', 'Operaciones', 3, '2025-12-29 08:45:23'),
(26, 'inventario.editar', 'Editar producto', 'Editar información de productos', 'inventario', 'Operaciones', 3, '2025-12-29 08:45:23'),
(27, 'inventario.eliminar', 'Eliminar producto', 'Eliminar producto del inventario', 'inventario', 'Operaciones', 4, '2025-12-29 08:45:23'),
(28, 'inventario.ajustar', 'Ajustar stock', 'Ajustar cantidades de inventario', 'inventario', 'Operaciones', 3, '2025-12-29 08:45:23'),
(29, 'reportes.ventas', 'Reportes ventas', 'Ver y generar reportes de ventas', 'reportes', 'Reportes', 2, '2025-12-29 08:45:23'),
(30, 'reportes.inventario', 'Reportes inventario', 'Ver reportes de inventario', 'reportes', 'Reportes', 2, '2025-12-29 08:45:23'),
(31, 'reportes.financieros', 'Reportes financieros', 'Ver reportes financieros', 'reportes', 'Reportes', 3, '2025-12-29 08:45:23'),
(32, 'reportes.usuarios', 'Reportes usuarios', 'Ver reportes de actividad de usuarios', 'reportes', 'Reportes', 3, '2025-12-29 08:45:23'),
(33, 'reportes.personalizados', 'Reportes personalizados', 'Crear reportes personalizados', 'reportes', 'Reportes', 4, '2025-12-29 08:45:23'),
(34, 'inquilinos.ver', 'Ver inquilinos', 'Ver información de inquilinos', 'inquilinos', 'Gestión', 1, '2025-12-29 08:45:23'),
(35, 'inquilinos.contratos', 'Ver contratos', 'Ver contratos de inquilinos', 'inquilinos', 'Gestión', 2, '2025-12-29 08:45:23'),
(36, 'inquilinos.pagos', 'Ver pagos', 'Ver historial de pagos de alquiler', 'inquilinos', 'Gestión', 2, '2025-12-29 08:45:23'),
(37, 'inquilinos.solicitudes', 'Ver solicitudes', 'Ver solicitudes de inquilinos', 'inquilinos', 'Gestión', 2, '2025-12-29 08:45:23'),
(38, 'inquilinos.comunicar', 'Comunicar', 'Enviar comunicaciones a inquilinos', 'inquilinos', 'Gestión', 3, '2025-12-29 08:45:23'),
(39, 'servicios.solicitar', 'Solicitar servicio', 'Solicitar servicio de mantenimiento', 'servicios', 'Gestión', 1, '2025-12-29 08:45:23'),
(40, 'servicios.ver', 'Ver servicios', 'Ver servicios solicitados', 'servicios', 'Gestión', 1, '2025-12-29 08:45:23'),
(41, 'servicios.gestionar', 'Gestionar servicios', 'Gestionar servicios de mantenimiento', 'servicios', 'Gestión', 3, '2025-12-29 08:45:23'),
(42, 'visitantes.registrar', 'Registrar visitante', 'Registrar nuevo visitante', 'visitantes', 'Gestión', 2, '2025-12-29 08:45:23'),
(43, 'visitantes.ver', 'Ver visitantes', 'Ver lista de visitantes', 'visitantes', 'Gestión', 1, '2025-12-29 08:45:23'),
(44, 'visitantes.confirmar', 'Confirmar visitante', 'Confirmar acceso de visitante', 'visitantes', 'Gestión', 3, '2025-12-29 08:45:23'),
(45, 'visitantes.historial', 'Historial visitantes', 'Ver historial de visitas', 'visitantes', 'Gestión', 2, '2025-12-29 08:45:23'),
(46, 'mantenimiento.registrar', 'Registrar actividad', 'Registrar actividad de mantenimiento', 'mantenimiento', 'Operaciones', 2, '2025-12-29 08:45:23'),
(47, 'mantenimiento.ver', 'Ver actividades', 'Ver actividades de mantenimiento', 'mantenimiento', 'Operaciones', 1, '2025-12-29 08:45:23'),
(48, 'mantenimiento.gestionar', 'Gestionar actividades', 'Gestionar actividades programadas', 'mantenimiento', 'Operaciones', 3, '2025-12-29 08:45:23'),
(49, 'materiales.solicitar', 'Solicitar materiales', 'Solicitar materiales/repuestos', 'mantenimiento', 'Operaciones', 2, '2025-12-29 08:45:23'),
(50, 'incidencias.reportar', 'Reportar incidencia', 'Reportar incidencia o falla', 'mantenimiento', 'Operaciones', 2, '2025-12-29 08:45:23'),
(51, 'incidencias.ver', 'Ver incidencias', 'Ver reportes de incidencias', 'mantenimiento', 'Operaciones', 1, '2025-12-29 08:45:23'),
(52, 'seguridad.ver', 'Ver seguridad', 'Ver panel de seguridad', 'seguridad', 'Gestión', 1, '2025-12-29 08:45:23'),
(53, 'accesos.control', 'Control accesos', 'Controlar accesos al edificio', 'seguridad', 'Gestión', 3, '2025-12-29 08:45:23'),
(54, 'camaras.ver', 'Ver cámaras', 'Ver cámaras de seguridad (simulado)', 'seguridad', 'Gestión', 4, '2025-12-29 08:45:23'),
(55, 'emergencias.reportar', 'Reportar emergencia', 'Reportar emergencia al sistema', 'seguridad', 'Gestión', 2, '2025-12-29 08:45:23'),
(56, 'primeros.auxilios', 'Primeros auxilios', 'Registrar atención primeros auxilios', 'seguridad', 'Gestión', 3, '2025-12-29 08:45:23'),
(57, 'rondas.registrar', 'Registrar rondas', 'Registrar rondas de vigilancia', 'seguridad', 'Gestión', 2, '2025-12-29 08:45:23'),
(58, 'permisos.especiales', 'Permisos especiales', 'Gestionar permisos especiales de acceso', 'seguridad', 'Gestión', 4, '2025-12-29 08:45:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text,
  `categoria_id` int NOT NULL,
  `precio_compra` decimal(10,2) DEFAULT '0.00',
  `precio_venta` decimal(10,2) NOT NULL,
  `stock` int DEFAULT '0',
  `stock_minimo` int DEFAULT '5',
  `unidad_medida` enum('unidad','gramo','litro','paquete','botella') DEFAULT 'unidad',
  `imagen` varchar(255) DEFAULT NULL,
  `estado` enum('activo','inactivo','agotado') DEFAULT 'activo',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `codigo`, `nombre`, `descripcion`, `categoria_id`, `precio_compra`, `precio_venta`, `stock`, `stock_minimo`, `unidad_medida`, `imagen`, `estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'CAF-001', 'Café Americano', 'Café negro americano', 1, 0.50, 2.50, 99, 20, 'unidad', NULL, 'activo', '2026-01-12 03:39:31', '2026-01-12 09:10:34'),
(2, 'CAF-002', 'Capuchino', 'Café con leche espumosa', 1, 0.80, 3.50, 78, 15, 'unidad', NULL, 'activo', '2026-01-12 03:39:31', '2026-01-14 15:07:38'),
(3, 'CAF-003', 'Expreso Doble', 'Doble shot de café expreso', 1, 0.60, 3.00, 58, 10, 'unidad', NULL, 'activo', '2026-01-12 03:39:31', '2026-01-14 07:28:22'),
(4, 'CAF-004', 'Té Verde', 'Té verde natural', 1, 0.40, 2.00, 120, 25, 'unidad', NULL, 'activo', '2026-01-12 03:39:31', '2026-01-12 03:39:31'),
(5, 'BEB-001', 'Jugo de Naranja', 'Jugo natural de naranja', 2, 0.70, 3.00, 43, 10, 'botella', NULL, 'activo', '2026-01-12 03:39:31', '2026-01-14 20:59:37'),
(6, 'BEB-002', 'Limonada', 'Limonada natural con hielo', 2, 0.50, 2.50, 32, 8, 'botella', NULL, 'activo', '2026-01-12 03:39:31', '2026-01-14 20:59:22'),
(7, 'BEB-003', 'Agua Mineral', 'Agua mineral 500ml', 2, 0.30, 1.50, 194, 50, 'botella', NULL, 'activo', '2026-01-12 03:39:31', '2026-01-14 20:49:52'),
(8, 'COM-001', 'Sandwich de Pollo', 'Sandwich con pollo y vegetales', 3, 1.50, 5.00, 26, 10, 'unidad', NULL, 'activo', '2026-01-12 03:39:31', '2026-01-14 15:07:38'),
(9, 'COM-002', 'Ensalada César', 'Ensalada con pollo y aderezo césar', 3, 1.20, 4.50, 18, 8, 'unidad', NULL, 'activo', '2026-01-12 03:39:31', '2026-01-14 20:59:31'),
(10, 'COM-003', 'Wrap Vegetariano', 'Wrap con vegetales frescos', 3, 1.00, 4.00, 16, 5, 'unidad', NULL, 'activo', '2026-01-12 03:39:31', '2026-01-14 20:49:52'),
(11, 'SNK-001', 'Croissant', 'Croissant de mantequilla', 4, 0.40, 1.80, 38, 15, 'unidad', NULL, 'activo', '2026-01-12 03:39:31', '2026-01-14 20:59:22'),
(12, 'SNK-002', 'Galletas de Chocolate', 'Paquete con 3 galletas', 4, 0.60, 2.50, 54, 20, 'paquete', NULL, 'activo', '2026-01-12 03:39:31', '2026-01-14 18:28:31'),
(13, 'SNK-003', 'Papas Fritas', 'Bolsa pequeña de papas fritas', 4, 0.50, 2.00, 75, 30, 'paquete', NULL, 'activo', '2026-01-12 03:39:31', '2026-01-14 07:28:22'),
(14, 'POS-001', 'Pastel de Chocolate', 'Rebanada de pastel de chocolate', 5, 1.00, 4.50, 13, 5, 'unidad', NULL, 'activo', '2026-01-12 03:39:31', '2026-01-14 07:28:22'),
(15, 'POS-002', 'Helado de Vainilla', 'Bola de helado de vainilla', 5, 0.60, 3.00, 19, 8, 'unidad', NULL, 'activo', '2026-01-12 03:39:31', '2026-01-14 18:54:33'),
(16, 'POS-003', 'Brownie', 'Brownie con nueces', 5, 0.80, 3.50, 18, 6, 'unidad', NULL, 'activo', '2026-01-12 03:39:31', '2026-01-14 15:07:38'),
(17, 'POS-004', 'Helado de Chocolate', 'Helado EFE de Chocolate de 100 gramos en paleta', 5, 2.00, 3.00, 14, 12, 'paquete', NULL, 'activo', '2026-01-12 13:55:23', '2026-01-14 18:54:33');

--
-- Disparadores `productos`
--
DELIMITER $$
CREATE TRIGGER `before_update_precio_producto` BEFORE UPDATE ON `productos` FOR EACH ROW BEGIN
    IF OLD.precio_venta != NEW.precio_venta THEN
        INSERT INTO auditoria_precios (producto_id, usuario_id, precio_anterior, precio_nuevo, motivo, ip_address)
        VALUES (OLD.id, @usuario_actual_id, OLD.precio_venta, NEW.precio_venta, 'Cambio manual de precio', @ip_address);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int NOT NULL,
  `nombre_rol` varchar(100) NOT NULL,
  `descripcion` text,
  `color_pastel` varchar(7) DEFAULT '#E0F2FE',
  `nivel_prioridad` int DEFAULT '10',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre_rol`, `descripcion`, `color_pastel`, `nivel_prioridad`, `fecha_creacion`) VALUES
(1, 'Administrador del Sistema', 'Acceso total al sistema, gestión de usuarios y configuración', '#FEF3C7', 1, '2025-12-29 08:45:23'),
(2, 'Cajero', 'Operador de POS, gestión de ventas y caja', '#D1FAE5', 2, '2025-12-29 08:45:23'),
(3, 'Gerente', 'Supervisión de operaciones, reportes y aprobaciones', '#E0E7FF', 3, '2025-12-29 08:45:23'),
(4, 'Inquilino', 'Propietarios o arrendatarios del edificio', '#FDE68A', 4, '2025-12-29 08:45:23'),
(5, 'Visitante-No-Confirmado', 'Visitantes pendientes de confirmación', '#F3F4F6', 9, '2025-12-29 08:45:23'),
(6, 'Visitante-Confirmado-Admin', 'Visitantes confirmados por administración', '#E0F2FE', 8, '2025-12-29 08:45:23'),
(7, 'Visitante-Confirmado-Propietario', 'Visitantes confirmados por propietario', '#F0F9FF', 7, '2025-12-29 08:45:23'),
(8, 'Personal de Obras', 'Mantenimiento, seguridad, logística y transportistas', '#FEFCE8', 5, '2025-12-29 08:45:23'),
(9, 'Seguridad', 'Vigilancia, control de accesos y emergencias', '#FEF2F2', 6, '2025-12-29 08:45:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_modulos`
--

CREATE TABLE `rol_modulos` (
  `rol_id` int NOT NULL,
  `modulo_id` int NOT NULL,
  `acceso_total` tinyint(1) DEFAULT '0',
  `solo_lectura` tinyint(1) DEFAULT '0',
  `fecha_asignacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `asignado_por` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `rol_modulos`
--

INSERT INTO `rol_modulos` (`rol_id`, `modulo_id`, `acceso_total`, `solo_lectura`, `fecha_asignacion`, `asignado_por`) VALUES
(1, 1, 1, 0, '2025-12-29 08:45:23', NULL),
(1, 2, 1, 0, '2025-12-29 08:45:23', NULL),
(1, 3, 1, 0, '2025-12-29 08:45:23', NULL),
(1, 4, 1, 0, '2025-12-29 08:45:23', NULL),
(1, 5, 1, 0, '2025-12-29 08:45:23', NULL),
(1, 6, 1, 0, '2025-12-29 08:45:23', NULL),
(1, 7, 1, 0, '2025-12-29 08:45:23', NULL),
(1, 8, 1, 0, '2025-12-29 08:45:23', NULL),
(1, 9, 1, 0, '2025-12-29 08:45:23', NULL),
(1, 10, 1, 0, '2025-12-29 08:45:23', NULL),
(1, 11, 1, 0, '2025-12-29 08:45:23', NULL),
(1, 12, 1, 0, '2025-12-29 08:45:23', NULL),
(1, 13, 1, 0, '2025-12-29 08:45:23', NULL),
(1, 14, 1, 0, '2025-12-29 08:45:23', NULL),
(1, 15, 1, 0, '2025-12-29 08:45:23', NULL),
(2, 1, 0, 0, '2025-12-29 08:45:23', NULL),
(2, 4, 0, 0, '2025-12-29 08:45:23', NULL),
(2, 5, 0, 0, '2025-12-29 08:45:23', NULL),
(2, 6, 0, 0, '2025-12-29 08:45:23', NULL),
(3, 1, 0, 0, '2025-12-29 08:45:23', NULL),
(3, 4, 0, 0, '2025-12-29 08:45:23', NULL),
(3, 5, 0, 0, '2025-12-29 08:45:23', NULL),
(3, 6, 0, 0, '2025-12-29 08:45:23', NULL),
(3, 7, 0, 0, '2025-12-29 08:45:23', NULL),
(3, 14, 0, 0, '2025-12-29 08:45:23', NULL),
(4, 1, 0, 0, '2025-12-29 08:45:23', NULL),
(4, 8, 0, 0, '2025-12-29 08:45:23', NULL),
(4, 10, 0, 0, '2025-12-29 08:45:23', NULL),
(5, 1, 0, 0, '2025-12-29 08:45:23', NULL),
(6, 1, 0, 0, '2025-12-29 08:45:23', NULL),
(6, 9, 0, 0, '2025-12-29 08:45:23', NULL),
(6, 10, 0, 0, '2025-12-29 08:45:23', NULL),
(7, 1, 0, 0, '2025-12-29 08:45:23', NULL),
(7, 9, 0, 0, '2025-12-29 08:45:23', NULL),
(7, 10, 0, 0, '2025-12-29 08:45:23', NULL),
(8, 1, 0, 0, '2025-12-29 08:45:23', NULL),
(8, 11, 0, 0, '2025-12-29 08:45:23', NULL),
(9, 1, 0, 0, '2025-12-29 08:45:23', NULL),
(9, 9, 0, 0, '2025-12-29 08:45:23', NULL),
(9, 12, 0, 0, '2025-12-29 08:45:23', NULL),
(9, 14, 0, 0, '2025-12-29 08:45:23', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_permisos`
--

CREATE TABLE `rol_permisos` (
  `rol_id` int NOT NULL,
  `permiso_id` int NOT NULL,
  `fecha_asignacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `asignado_por` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `rol_permisos`
--

INSERT INTO `rol_permisos` (`rol_id`, `permiso_id`, `fecha_asignacion`, `asignado_por`) VALUES
(1, 1, '2025-12-29 08:45:23', NULL),
(1, 2, '2025-12-29 08:45:23', NULL),
(1, 3, '2025-12-29 08:45:23', NULL),
(1, 4, '2025-12-29 08:45:23', NULL),
(1, 5, '2025-12-29 08:45:23', NULL),
(1, 6, '2025-12-29 08:45:23', NULL),
(1, 7, '2025-12-29 08:45:23', NULL),
(1, 8, '2025-12-29 08:45:23', NULL),
(1, 9, '2025-12-29 08:45:23', NULL),
(1, 10, '2025-12-29 08:45:23', NULL),
(1, 11, '2025-12-29 08:45:23', NULL),
(1, 12, '2025-12-29 08:45:23', NULL),
(1, 13, '2025-12-29 08:45:23', NULL),
(1, 14, '2025-12-29 08:45:23', NULL),
(1, 15, '2025-12-29 08:45:23', NULL),
(1, 16, '2025-12-29 08:45:23', NULL),
(1, 17, '2025-12-29 08:45:23', NULL),
(1, 18, '2025-12-29 08:45:23', NULL),
(1, 19, '2025-12-29 08:45:23', NULL),
(1, 20, '2025-12-29 08:45:23', NULL),
(1, 21, '2025-12-29 08:45:23', NULL),
(1, 22, '2025-12-29 08:45:23', NULL),
(1, 23, '2025-12-29 08:45:23', NULL),
(1, 24, '2025-12-29 08:45:23', NULL),
(1, 25, '2025-12-29 08:45:23', NULL),
(1, 26, '2025-12-29 08:45:23', NULL),
(1, 27, '2025-12-29 08:45:23', NULL),
(1, 28, '2025-12-29 08:45:23', NULL),
(1, 29, '2025-12-29 08:45:23', NULL),
(1, 30, '2025-12-29 08:45:23', NULL),
(1, 31, '2025-12-29 08:45:23', NULL),
(1, 32, '2025-12-29 08:45:23', NULL),
(1, 33, '2025-12-29 08:45:23', NULL),
(1, 34, '2025-12-29 08:45:23', NULL),
(1, 35, '2025-12-29 08:45:23', NULL),
(1, 36, '2025-12-29 08:45:23', NULL),
(1, 37, '2025-12-29 08:45:23', NULL),
(1, 38, '2025-12-29 08:45:23', NULL),
(1, 39, '2025-12-29 08:45:23', NULL),
(1, 40, '2025-12-29 08:45:23', NULL),
(1, 41, '2025-12-29 08:45:23', NULL),
(1, 42, '2025-12-29 08:45:23', NULL),
(1, 43, '2025-12-29 08:45:23', NULL),
(1, 44, '2025-12-29 08:45:23', NULL),
(1, 45, '2025-12-29 08:45:23', NULL),
(1, 46, '2025-12-29 08:45:23', NULL),
(1, 47, '2025-12-29 08:45:23', NULL),
(1, 48, '2025-12-29 08:45:23', NULL),
(1, 49, '2025-12-29 08:45:23', NULL),
(1, 50, '2025-12-29 08:45:23', NULL),
(1, 51, '2025-12-29 08:45:23', NULL),
(1, 52, '2025-12-29 08:45:23', NULL),
(1, 53, '2025-12-29 08:45:23', NULL),
(1, 54, '2025-12-29 08:45:23', NULL),
(1, 55, '2025-12-29 08:45:23', NULL),
(1, 56, '2025-12-29 08:45:23', NULL),
(1, 57, '2025-12-29 08:45:23', NULL),
(1, 58, '2025-12-29 08:45:23', NULL),
(2, 15, '2025-12-29 08:45:23', NULL),
(2, 16, '2025-12-29 08:45:23', NULL),
(2, 17, '2025-12-29 08:45:23', NULL),
(2, 18, '2025-12-29 08:45:23', NULL),
(2, 20, '2025-12-29 08:45:23', NULL),
(2, 21, '2025-12-29 08:45:23', NULL),
(2, 22, '2025-12-29 08:45:23', NULL),
(2, 24, '2025-12-29 08:45:23', NULL),
(3, 1, '2025-12-29 08:45:23', NULL),
(3, 13, '2025-12-29 08:45:23', NULL),
(3, 14, '2025-12-29 08:45:23', NULL),
(3, 15, '2025-12-29 08:45:23', NULL),
(3, 16, '2025-12-29 08:45:23', NULL),
(3, 17, '2025-12-29 08:45:23', NULL),
(3, 18, '2025-12-29 08:45:23', NULL),
(3, 19, '2025-12-29 08:45:23', NULL),
(3, 20, '2025-12-29 08:45:23', NULL),
(3, 21, '2025-12-29 08:45:23', NULL),
(3, 22, '2025-12-29 08:45:23', NULL),
(3, 23, '2025-12-29 08:45:23', NULL),
(3, 24, '2025-12-29 08:45:23', NULL),
(3, 25, '2025-12-29 08:45:23', NULL),
(3, 26, '2025-12-29 08:45:23', NULL),
(3, 28, '2025-12-29 08:45:23', NULL),
(3, 29, '2025-12-29 08:45:23', NULL),
(3, 30, '2025-12-29 08:45:23', NULL),
(3, 31, '2025-12-29 08:45:23', NULL),
(4, 34, '2025-12-29 08:45:23', NULL),
(4, 35, '2025-12-29 08:45:23', NULL),
(4, 36, '2025-12-29 08:45:23', NULL),
(4, 37, '2025-12-29 08:45:23', NULL),
(4, 39, '2025-12-29 08:45:23', NULL),
(4, 40, '2025-12-29 08:45:23', NULL),
(5, 14, '2025-12-29 08:45:23', NULL),
(6, 14, '2025-12-29 08:45:23', NULL),
(6, 39, '2025-12-29 08:45:23', NULL),
(6, 40, '2025-12-29 08:45:23', NULL),
(6, 43, '2025-12-29 08:45:23', NULL),
(7, 14, '2025-12-29 08:45:23', NULL),
(7, 39, '2025-12-29 08:45:23', NULL),
(7, 40, '2025-12-29 08:45:23', NULL),
(7, 43, '2025-12-29 08:45:23', NULL),
(8, 14, '2025-12-29 08:45:23', NULL),
(8, 46, '2025-12-29 08:45:23', NULL),
(8, 47, '2025-12-29 08:45:23', NULL),
(8, 48, '2025-12-29 08:45:23', NULL),
(8, 49, '2025-12-29 08:45:23', NULL),
(8, 50, '2025-12-29 08:45:23', NULL),
(8, 51, '2025-12-29 08:45:23', NULL),
(9, 13, '2025-12-29 08:45:23', NULL),
(9, 14, '2025-12-29 08:45:23', NULL),
(9, 42, '2025-12-29 08:45:23', NULL),
(9, 43, '2025-12-29 08:45:23', NULL),
(9, 44, '2025-12-29 08:45:23', NULL),
(9, 52, '2025-12-29 08:45:23', NULL),
(9, 53, '2025-12-29 08:45:23', NULL),
(9, 54, '2025-12-29 08:45:23', NULL),
(9, 55, '2025-12-29 08:45:23', NULL),
(9, 56, '2025-12-29 08:45:23', NULL),
(9, 57, '2025-12-29 08:45:23', NULL),
(9, 58, '2025-12-29 08:45:23', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `secuencia_tickets`
--

CREATE TABLE `secuencia_tickets` (
  `fecha` date NOT NULL,
  `consecutivo` int NOT NULL DEFAULT '0',
  `ultima_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suscripciones`
--

CREATE TABLE `suscripciones` (
  `id` int NOT NULL,
  `email` varchar(150) NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `suscripciones`
--

INSERT INTO `suscripciones` (`id`, `email`, `fecha_registro`) VALUES
(1, 'elmismo@noticam.com', '2025-12-19 00:52:54'),
(2, 'lapulguita@delperro.info', '2025-12-19 02:48:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos_caja`
--

CREATE TABLE `turnos_caja` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `cajero_id` int NOT NULL,
  `dia_semana` enum('lunes','martes','miercoles','jueves','viernes','sabado','domingo') NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `turnos_caja`
--

INSERT INTO `turnos_caja` (`id`, `nombre`, `cajero_id`, `dia_semana`, `hora_inicio`, `hora_fin`, `estado`, `fecha_creacion`) VALUES
(1, 'Turno Matutino', 2, 'lunes', '07:00:00', '12:00:00', 'activo', '2026-01-12 03:39:31'),
(2, 'Turno Vespertino', 2, 'lunes', '13:00:00', '18:00:00', 'activo', '2026-01-12 03:39:31'),
(3, 'Turno Matutino', 2, 'martes', '07:00:00', '12:00:00', 'activo', '2026-01-12 03:39:31'),
(4, 'Turno Vespertino', 2, 'martes', '13:00:00', '18:00:00', 'activo', '2026-01-12 03:39:31'),
(5, 'Turno Matutino', 2, 'miercoles', '07:00:00', '12:00:00', 'activo', '2026-01-12 03:39:31'),
(6, 'Turno Vespertino', 2, 'miercoles', '13:00:00', '18:00:00', 'activo', '2026-01-12 03:39:31'),
(7, 'Turno Matutino', 2, 'jueves', '07:00:00', '12:00:00', 'activo', '2026-01-12 03:39:31'),
(8, 'Turno Vespertino', 2, 'jueves', '13:00:00', '18:00:00', 'activo', '2026-01-12 03:39:31'),
(9, 'Turno Matutino', 2, 'viernes', '07:00:00', '12:00:00', 'activo', '2026-01-12 03:39:31'),
(10, 'Turno Vespertino', 2, 'viernes', '13:00:00', '18:00:00', 'activo', '2026-01-12 03:39:31'),
(11, 'Turno Sabatino', 2, 'sabado', '08:00:00', '14:00:00', 'activo', '2026-01-12 03:39:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `rol_id` int NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `estado` enum('activo','inactivo','pendiente','suspendido') DEFAULT 'pendiente',
  `fecha_confirmacion` datetime DEFAULT NULL,
  `confirmado_por` int DEFAULT NULL,
  `creado_por` int DEFAULT NULL,
  `token_recuperacion` varchar(100) DEFAULT NULL,
  `ultimo_login` datetime DEFAULT NULL,
  `ultima_actividad` datetime DEFAULT NULL,
  `session_token` varchar(64) DEFAULT NULL,
  `notificaciones_activas` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `rol_id`, `nombre`, `email`, `password`, `telefono`, `direccion`, `foto_perfil`, `estado`, `fecha_confirmacion`, `confirmado_por`, `creado_por`, `token_recuperacion`, `ultimo_login`, `ultima_actividad`, `session_token`, `notificaciones_activas`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 1, 'Super Usuario', 'admin@torregeneral.com', '$2y$10$LKpoyH9A7t8qODOUp0P8xO.dOu7kwJR5DmeaJlIlevNwx.nnU96A6', '+584141234567', 'Oficina Principal, Piso 10', NULL, 'activo', '2025-12-29 08:45:23', NULL, NULL, NULL, '2026-01-14 12:53:25', '2026-01-14 12:53:25', NULL, 1, '2025-12-29 08:45:23', '2026-01-14 16:55:24'),
(2, 2, 'María González', 'maria.gonzalez@torregeneral.com', '$2y$10$B3R697ZezTTKDKiBHUWdfurKxenNTIz.Arg9KehSuEPptRd3HB1yS', '+584141234568', 'Cafetería Torre General', NULL, 'activo', '2025-12-29 08:45:23', 1, 1, NULL, '2026-01-17 18:20:04', '2026-01-17 18:20:04', 'efae111436e023022854938acced1ee5fa2efadd1c82bfa6b2ef3f180d935d06', 1, '2025-12-29 08:45:23', '2026-01-17 22:20:04'),
(3, 2, 'Carlos Rodríguez', 'carlos.rodriguez@torregeneral.com', '$2y$10$B3R697ZezTTKDKiBHUWdfurKxenNTIz.Arg9KehSuEPptRd3HB1yS', '+584141234569', 'Cafetería Torre General', NULL, 'activo', '2025-12-29 08:45:23', 1, 1, NULL, NULL, NULL, NULL, 1, '2025-12-29 08:45:23', '2026-01-13 06:03:59'),
(4, 3, 'Ana Martínez', 'ana.martinez@torregeneral.com', '$2y$10$B3R697ZezTTKDKiBHUWdfurKxenNTIz.Arg9KehSuEPptRd3HB1yS', '+584141234570', 'Oficina Gerencia, Piso 8', NULL, 'activo', '2025-12-29 08:45:23', 1, 1, NULL, '2026-01-14 11:53:01', '2026-01-14 11:53:01', NULL, 1, '2025-12-29 08:45:23', '2026-01-14 15:54:56'),
(5, 4, 'Empresa Tech Solutions C.A.', 'tech@torregeneral.com', '$2y$10$B3R697ZezTTKDKiBHUWdfurKxenNTIz.Arg9KehSuEPptRd3HB1yS', '+584141234571', 'Piso 5, Oficina 501-510', NULL, 'activo', '2025-12-29 08:45:23', 1, 1, NULL, '2026-01-13 02:15:21', NULL, 'e5cee2d9bdbccdfb21f73e1f1d999118b8d14f69c1837530bdb02623cfe33d46', 1, '2025-12-29 08:45:23', '2026-01-13 06:15:21'),
(6, 4, 'Consultores Asociados S.A.', 'consultores@torregeneral.com', '$2y$10$B3R697ZezTTKDKiBHUWdfurKxenNTIz.Arg9KehSuEPptRd3HB1yS', '+584141234572', 'Piso 3, Oficina 301-308', NULL, 'activo', '2025-12-29 08:45:23', 1, 1, NULL, NULL, NULL, NULL, 1, '2025-12-29 08:45:23', '2026-01-13 06:03:59'),
(7, 5, 'Juan Pérez (Pendiente)', 'juan.perez@email.com', '$2y$10$B3R697ZezTTKDKiBHUWdfurKxenNTIz.Arg9KehSuEPptRd3HB1yS', '+584141234573', 'Visitante temporal - Pendiente confirmación', NULL, 'pendiente', NULL, NULL, 1, NULL, NULL, NULL, NULL, 1, '2025-12-29 08:45:23', '2026-01-13 06:03:59'),
(8, 6, 'Carlos Mendoza (Confirmado)', 'carlos.mendoza@externo.com', '$2y$10$B3R697ZezTTKDKiBHUWdfurKxenNTIz.Arg9KehSuEPptRd3HB1yS', '+584141234574', 'Representante de Proveedor', NULL, 'activo', '2025-12-29 08:45:23', 1, 1, NULL, NULL, NULL, NULL, 1, '2025-12-29 08:45:23', '2026-01-13 06:03:59'),
(9, 7, 'Laura Sánchez (Invitada)', 'laura.sanchez@invitada.com', '$2y$10$B3R697ZezTTKDKiBHUWdfurKxenNTIz.Arg9KehSuEPptRd3HB1yS', '+584141234575', 'Invitada por inquilino', NULL, 'activo', '2025-12-29 08:45:23', 1, 1, NULL, NULL, NULL, NULL, 1, '2025-12-29 08:45:23', '2026-01-13 06:03:59'),
(10, 8, 'Pedro López (Mantenimiento)', 'pedro.lopez@torregeneral.com', '$2y$10$B3R697ZezTTKDKiBHUWdfurKxenNTIz.Arg9KehSuEPptRd3HB1yS', '+584141234576', 'Departamento de Mantenimiento', NULL, 'activo', '2025-12-29 08:45:23', 1, 1, NULL, NULL, NULL, NULL, 1, '2025-12-29 08:45:23', '2026-01-13 06:03:59'),
(11, 9, 'Roberto Jiménez (Seguridad)', 'roberto.jimenez@torregeneral.com', '$2y$10$B3R697ZezTTKDKiBHUWdfurKxenNTIz.Arg9KehSuEPptRd3HB1yS', '+584141234577', 'Puesto de Control Principal', NULL, 'activo', '2025-12-29 08:45:23', 1, 1, NULL, '2025-12-29 10:40:57', NULL, NULL, 1, '2025-12-29 08:45:23', '2026-01-13 06:03:59'),
(12, 9, 'Miguel Ángel Rojas', 'miguel.rojas@torregeneral.com', '$2y$10$B3R697ZezTTKDKiBHUWdfurKxenNTIz.Arg9KehSuEPptRd3HB1yS', '+584141234578', 'Puesto de Control Secundario', NULL, 'activo', '2025-12-29 08:45:23', 1, 1, NULL, NULL, NULL, NULL, 1, '2025-12-29 08:45:23', '2026-01-13 06:03:59'),
(13, 5, 'AQUA PARAISO', 'correo@existente.com', '$2y$10$B3R697ZezTTKDKiBHUWdfurKxenNTIz.Arg9KehSuEPptRd3HB1yS', '', '', NULL, 'pendiente', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-12-29 10:07:25', '2026-01-13 06:03:59'),
(14, 5, 'Francisco Blanco', 'fcoblancopaezp@gmail.com', '$2y$10$/cua9s66KmLoeSeReSfIcuCi0yovmt4OsrnA6jIP/.aL/mFZOr9fu', '', '', NULL, 'pendiente', NULL, NULL, NULL, NULL, '2025-12-29 11:45:49', NULL, NULL, 1, '2025-12-29 14:13:08', '2026-01-13 06:03:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id` int NOT NULL,
  `numero_ticket` varchar(50) NOT NULL,
  `cajero_id` int NOT NULL,
  `cliente_id` int DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `metodo_pago` enum('tarjeta_debito','pago_movil') NOT NULL,
  `referencia_pago` varchar(50) NOT NULL COMMENT 'Últimos 6 dígitos de la transacción',
  `estado` enum('pendiente','completada','anulada','reembolsada') DEFAULT 'completada',
  `observaciones` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `fecha_venta` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id`, `numero_ticket`, `cajero_id`, `cliente_id`, `subtotal`, `total`, `metodo_pago`, `referencia_pago`, `estado`, `observaciones`, `ip_address`, `user_agent`, `fecha_venta`, `fecha_actualizacion`) VALUES
(1, 'TG-0001-260112', 2, 6, 12.00, 12.00, 'pago_movil', '100200', 'completada', NULL, '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 09:10:34', '2026-01-12 09:10:34'),
(2, 'TG-18446744073709551357-260112', 2, 7, 16.00, 16.00, 'tarjeta_debito', '325915', 'completada', NULL, '190.205.20.255', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 13:38:07', '2026-01-12 13:38:07'),
(8, 'TG-0001-260114', 2, 9, 23.50, 23.50, 'pago_movil', '100300', 'completada', NULL, NULL, NULL, '2026-01-14 07:28:22', '2026-01-14 07:28:22'),
(9, 'TG-2147-260114', 2, 7, 13.50, 13.50, 'tarjeta_debito', '100900', 'completada', NULL, NULL, NULL, '2026-01-14 15:07:38', '2026-01-14 15:07:38'),
(13, 'TG-9580-260114', 2, 2, 8.00, 8.00, 'tarjeta_debito', '200325', 'completada', NULL, NULL, NULL, '2026-01-14 18:27:41', '2026-01-14 18:27:41'),
(14, 'TG-6461-260114', 2, 5, 5.50, 5.50, 'pago_movil', '954318', 'completada', NULL, NULL, NULL, '2026-01-14 18:28:31', '2026-01-14 18:28:31'),
(15, 'TG-9907-260114', 2, 6, 4.50, 4.50, 'pago_movil', '389157', 'completada', NULL, NULL, NULL, '2026-01-14 18:29:19', '2026-01-14 18:29:19'),
(16, 'TG-2135-260114', 2, 6, 12.00, 12.00, 'tarjeta_debito', '931815', 'completada', NULL, NULL, NULL, '2026-01-14 18:54:33', '2026-01-14 18:54:33'),
(17, 'TG-3992-260114', 2, 1, 5.50, 5.50, 'tarjeta_debito', '911711', 'completada', NULL, NULL, NULL, '2026-01-14 20:49:52', '2026-01-14 20:49:52'),
(18, 'TG-6345-260114', 2, 6, 6.80, 6.80, 'tarjeta_debito', '711911', 'completada', NULL, NULL, NULL, '2026-01-14 20:59:22', '2026-01-14 20:59:22'),
(19, 'TG-3276-260114', 2, 1, 4.50, 4.50, 'tarjeta_debito', '319008', 'completada', NULL, NULL, NULL, '2026-01-14 20:59:31', '2026-01-14 20:59:31'),
(20, 'TG-5640-260114', 2, 7, 6.00, 6.00, 'pago_movil', '323042', 'completada', NULL, NULL, NULL, '2026-01-14 20:59:37', '2026-01-14 20:59:37');

--
-- Disparadores `ventas`
--
DELIMITER $$
CREATE TRIGGER `after_update_venta_anulada` AFTER UPDATE ON `ventas` FOR EACH ROW BEGIN
    IF NEW.estado = 'anulada' AND OLD.estado != 'anulada' THEN
        -- Restaurar stock de todos los productos de la venta
        UPDATE productos p
        JOIN detalle_ventas dv ON p.id = dv.producto_id
        SET p.stock = p.stock + dv.cantidad,
            p.estado = CASE 
                WHEN p.stock + dv.cantidad <= 0 THEN 'agotado'
                WHEN p.stock + dv.cantidad <= p.stock_minimo THEN 'activo'
                ELSE p.estado
            END
        WHERE dv.venta_id = NEW.id;
        
        -- Registrar movimientos de inventario por anulación
        INSERT INTO movimientos_inventario (producto_id, tipo, cantidad, stock_anterior, stock_nuevo, referencia_id, usuario_id, observaciones)
        SELECT 
            dv.producto_id,
            'devolucion',
            dv.cantidad,
            p.stock - dv.cantidad, -- Stock anterior (antes de restaurar)
            p.stock, -- Stock nuevo (después de restaurar)
            NEW.id,
            NEW.cajero_id,
            'Anulación de venta'
        FROM detalle_ventas dv
        JOIN productos p ON p.id = dv.producto_id
        WHERE dv.venta_id = NEW.id;
    END IF;
END
$$
DELIMITER ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `actividades_log`
--
ALTER TABLE `actividades_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_modulo` (`modulo`),
  ADD KEY `idx_fecha` (`fecha_registro`),
  ADD KEY `idx_accion` (`accion`);

--
-- Indices de la tabla `anulaciones_ventas`
--
ALTER TABLE `anulaciones_ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `venta_id` (`venta_id`),
  ADD KEY `cajero_id` (`cajero_id`),
  ADD KEY `supervisor_id` (`supervisor_id`);

--
-- Indices de la tabla `auditoria_precios`
--
ALTER TABLE `auditoria_precios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `caja`
--
ALTER TABLE `caja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cajero` (`cajero_id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_fecha` (`fecha_apertura`);

--
-- Indices de la tabla `categorias_productos`
--
ALTER TABLE `categorias_productos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cedula` (`cedula`),
  ADD KEY `idx_cedula` (`cedula`),
  ADD KEY `idx_nombre` (`nombre`,`apellido`);

--
-- Indices de la tabla `configuraciones`
--
ALTER TABLE `configuraciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`),
  ADD KEY `idx_clave` (`clave`),
  ADD KEY `idx_categoria` (`categoria`);

--
-- Indices de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_venta` (`venta_id`),
  ADD KEY `idx_producto` (`producto_id`);

--
-- Indices de la tabla `modulos_sistema`
--
ALTER TABLE `modulos_sistema`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_codigo` (`codigo`),
  ADD KEY `idx_orden` (`orden`),
  ADD KEY `idx_visible` (`visible`);

--
-- Indices de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_producto` (`producto_id`),
  ADD KEY `idx_fecha` (`fecha_movimiento`),
  ADD KEY `idx_tipo` (`tipo`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_leida` (`leida`),
  ADD KEY `idx_fecha` (`fecha_envio`),
  ADD KEY `idx_tipo` (`tipo`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_codigo` (`codigo`),
  ADD KEY `idx_modulo` (`modulo`),
  ADD KEY `idx_categoria` (`categoria`),
  ADD KEY `idx_nivel_seguridad` (`nivel_seguridad`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_rol` (`nombre_rol`),
  ADD KEY `idx_nivel` (`nivel_prioridad`);

--
-- Indices de la tabla `rol_modulos`
--
ALTER TABLE `rol_modulos`
  ADD PRIMARY KEY (`rol_id`,`modulo_id`),
  ADD KEY `asignado_por` (`asignado_por`),
  ADD KEY `idx_rol` (`rol_id`),
  ADD KEY `idx_modulo` (`modulo_id`);

--
-- Indices de la tabla `rol_permisos`
--
ALTER TABLE `rol_permisos`
  ADD PRIMARY KEY (`rol_id`,`permiso_id`),
  ADD KEY `asignado_por` (`asignado_por`),
  ADD KEY `idx_rol` (`rol_id`),
  ADD KEY `idx_permiso` (`permiso_id`);

--
-- Indices de la tabla `secuencia_tickets`
--
ALTER TABLE `secuencia_tickets`
  ADD PRIMARY KEY (`fecha`);

--
-- Indices de la tabla `turnos_caja`
--
ALTER TABLE `turnos_caja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cajero` (`cajero_id`),
  ADD KEY `idx_dia` (`dia_semana`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `confirmado_por` (`confirmado_por`),
  ADD KEY `creado_por` (`creado_por`),
  ADD KEY `idx_rol` (`rol_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_fecha_creacion` (`fecha_creacion`),
  ADD KEY `idx_ultimo_login` (`ultimo_login`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_ticket` (`numero_ticket`),
  ADD KEY `idx_fecha` (`fecha_venta`),
  ADD KEY `idx_cajero` (`cajero_id`),
  ADD KEY `idx_cliente` (`cliente_id`),
  ADD KEY `idx_ticket` (`numero_ticket`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `actividades_log`
--
ALTER TABLE `actividades_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=837;

--
-- AUTO_INCREMENT de la tabla `anulaciones_ventas`
--
ALTER TABLE `anulaciones_ventas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `auditoria_precios`
--
ALTER TABLE `auditoria_precios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `caja`
--
ALTER TABLE `caja`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `categorias_productos`
--
ALTER TABLE `categorias_productos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `configuraciones`
--
ALTER TABLE `configuraciones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `modulos_sistema`
--
ALTER TABLE `modulos_sistema`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `turnos_caja`
--
ALTER TABLE `turnos_caja`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `actividades_log`
--
ALTER TABLE `actividades_log`
  ADD CONSTRAINT `actividades_log_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `anulaciones_ventas`
--
ALTER TABLE `anulaciones_ventas`
  ADD CONSTRAINT `anulaciones_ventas_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `anulaciones_ventas_ibfk_2` FOREIGN KEY (`cajero_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `anulaciones_ventas_ibfk_3` FOREIGN KEY (`supervisor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `auditoria_precios`
--
ALTER TABLE `auditoria_precios`
  ADD CONSTRAINT `auditoria_precios_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `auditoria_precios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT;

--
-- Filtros para la tabla `caja`
--
ALTER TABLE `caja`
  ADD CONSTRAINT `caja_ibfk_1` FOREIGN KEY (`cajero_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT;

--
-- Filtros para la tabla `detalle_ventas`
--
ALTER TABLE `detalle_ventas`
  ADD CONSTRAINT `detalle_ventas_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_ventas_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT;

--
-- Filtros para la tabla `movimientos_inventario`
--
ALTER TABLE `movimientos_inventario`
  ADD CONSTRAINT `movimientos_inventario_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `movimientos_inventario_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_productos` (`id`) ON DELETE RESTRICT;

--
-- Filtros para la tabla `rol_modulos`
--
ALTER TABLE `rol_modulos`
  ADD CONSTRAINT `rol_modulos_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rol_modulos_ibfk_2` FOREIGN KEY (`modulo_id`) REFERENCES `modulos_sistema` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rol_modulos_ibfk_3` FOREIGN KEY (`asignado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `rol_permisos`
--
ALTER TABLE `rol_permisos`
  ADD CONSTRAINT `rol_permisos_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rol_permisos_ibfk_2` FOREIGN KEY (`permiso_id`) REFERENCES `permisos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rol_permisos_ibfk_3` FOREIGN KEY (`asignado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `turnos_caja`
--
ALTER TABLE `turnos_caja`
  ADD CONSTRAINT `turnos_caja_ibfk_1` FOREIGN KEY (`cajero_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`confirmado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `usuarios_ibfk_3` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`cajero_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `ventas_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
