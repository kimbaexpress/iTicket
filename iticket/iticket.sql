-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 12-12-2024 a las 13:53:44
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `iticket`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `support_tickets`
--

CREATE TABLE `support_tickets` (
  `ticket_id` int(11) NOT NULL,
  `create_by_user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `celular` varchar(255) NOT NULL,
  `sector` varchar(255) NOT NULL,
  `classification` enum('urgente','alta','media','baja') DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `status` enum('Pendiente','En Proceso','Resuelto','Rechazado') DEFAULT NULL,
  `internal_number` varchar(255) DEFAULT NULL,
  `creation_date` datetime NOT NULL,
  `update_date` datetime DEFAULT NULL,
  `is_group_work` tinyint(1) DEFAULT 0,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_activities`
--

CREATE TABLE `ticket_activities` (
  `activity_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `unidad` varchar(50) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `activity_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_assignments`
--

CREATE TABLE `ticket_assignments` (
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assignment_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_responses`
--

CREATE TABLE `ticket_responses` (
  `response_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `responder_id` int(11) NOT NULL,
  `response_text` varchar(255) NOT NULL,
  `response_date` datetime NOT NULL,
  `is_private` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ticket_unit_assignments`
--

CREATE TABLE `ticket_unit_assignments` (
  `ticket_id` int(11) NOT NULL,
  `unidad` varchar(50) NOT NULL,
  `assigned_at` datetime DEFAULT current_timestamp(),
  `unassigned_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unit_tasks`
--

CREATE TABLE `unit_tasks` (
  `task_id` int(11) NOT NULL,
  `unidad` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user','support','coordinator') DEFAULT NULL,
  `unidad` enum('u_helpdesk','u_soporte','u_desarrollo','u_seguridad','user') NOT NULL,
  `last_check` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`user_id`, `username`, `name`, `password`, `role`, `unidad`, `last_check`) VALUES
(1, 'helpdesk', 'HelpDesk', '$2y$10$WSn/q1Q5miod1EjST1lre.fOCZ3CVtKMXZ2AeyZs1g.zy5fmed.T6', 'admin', 'u_helpdesk', NULL),
(2, 'soporte', 'Soporte', '$2y$10$XUkhS4Bi.y/G56xt4w2wruRX0yGSDkPyjWsb1JRGLD5HZ0yqYkuHe', 'admin', 'u_soporte', NULL),
(3, 'desarrollo', 'Desarrollo', '$2y$10$aQqzhpy9PIcjo00PuEJ2MOBwJM0rG9wnB1QWaaYxfm.LVZiIlQKSS', 'admin', 'u_desarrollo', NULL),
(4, 'seguridad', 'Seguridad', '$2y$10$q1OZU4JH2ERhrqYvBeRR7.NkylPLBrkx.nuM7wbE7V9XYRkeG8AAy', 'admin', 'u_seguridad', NULL),
(5, 'wspbot', 'WhatsApp BOT', '$2y$10$l4WaQ83TqyaqTVu18DQR5O4GD9X0O18./71g.5JTOQ0AGC3jIwr6a', 'admin', 'user', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `user_id` (`create_by_user_id`);

--
-- Indices de la tabla `ticket_activities`
--
ALTER TABLE `ticket_activities`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `ticket_assignments`
--
ALTER TABLE `ticket_assignments`
  ADD PRIMARY KEY (`ticket_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `ticket_responses`
--
ALTER TABLE `ticket_responses`
  ADD PRIMARY KEY (`response_id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `responder_id` (`responder_id`);

--
-- Indices de la tabla `ticket_unit_assignments`
--
ALTER TABLE `ticket_unit_assignments`
  ADD PRIMARY KEY (`ticket_id`,`unidad`);

--
-- Indices de la tabla `unit_tasks`
--
ALTER TABLE `unit_tasks`
  ADD PRIMARY KEY (`task_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ticket_activities`
--
ALTER TABLE `ticket_activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ticket_responses`
--
ALTER TABLE `ticket_responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `unit_tasks`
--
ALTER TABLE `unit_tasks`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD CONSTRAINT `support_tickets_ibfk_1` FOREIGN KEY (`create_by_user_id`) REFERENCES `users` (`user_id`);

--
-- Filtros para la tabla `ticket_activities`
--
ALTER TABLE `ticket_activities`
  ADD CONSTRAINT `ticket_activities_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`ticket_id`),
  ADD CONSTRAINT `ticket_activities_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Filtros para la tabla `ticket_assignments`
--
ALTER TABLE `ticket_assignments`
  ADD CONSTRAINT `ticket_assignments_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`ticket_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ticket_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ticket_responses`
--
ALTER TABLE `ticket_responses`
  ADD CONSTRAINT `ticket_responses_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`ticket_id`);

--
-- Filtros para la tabla `ticket_unit_assignments`
--
ALTER TABLE `ticket_unit_assignments`
  ADD CONSTRAINT `ticket_unit_assignments_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`ticket_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
