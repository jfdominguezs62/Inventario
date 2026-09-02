<?php
// Conexión directa a MySQL
$mysqli = new mysqli('localhost', 'root', '', 'inventario');

if ($mysqli->connect_error) {
    die("Conexión fallida: " . $mysqli->connect_error);
}

$sql = "CREATE TABLE IF NOT EXISTS inventario_mensual (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_articulo INT NOT NULL,
    anio INT NOT NULL,
    mes TINYINT NOT NULL,
    existencia_inicial DECIMAL(15,4) NOT NULL DEFAULT 0,
    entradas DECIMAL(15,4) NOT NULL DEFAULT 0,
    salidas DECIMAL(15,4) NOT NULL DEFAULT 0,
    existencia_cierre DECIMAL(15,4) NOT NULL DEFAULT 0,
    fecha_cierre DATETIME NOT NULL,
    usuario_cierre VARCHAR(100) NOT NULL,
    UNIQUE KEY uk_articulo_mes (id_articulo, anio, mes),
    INDEX idx_anio_mes (anio, mes),
    FOREIGN KEY (id_articulo) REFERENCES articulos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($mysqli->query($sql)) {
    echo "Tabla 'inventario_mensual' creada/verificada correctamente\n";
} else {
    echo "Error al crear tabla: " . $mysqli->error . "\n";
}

$mysqli->close();