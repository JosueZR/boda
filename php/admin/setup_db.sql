-- ============================================
-- BASE DE DATOS: BODA - Luis & Erendira
-- ============================================

CREATE DATABASE IF NOT EXISTS boda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE boda;

-- Tabla de familias invitadas
CREATE TABLE IF NOT EXISTS familias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    lugares_asignados INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de confirmaciones de asistencia
CREATE TABLE IF NOT EXISTS confirmaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    familia_id INT NOT NULL,
    personas_confirmadas INT NOT NULL,
    nota TEXT,
    fecha_confirmacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (familia_id) REFERENCES familias(id) ON DELETE CASCADE
);

-- Datos de ejemplo
INSERT INTO familias (nombre, lugares_asignados) VALUES
('Familia García', 4),
('Familia Pérez', 3),
('Familia Rodríguez', 5),
('Familia López', 2),
('Familia Martínez', 6);
