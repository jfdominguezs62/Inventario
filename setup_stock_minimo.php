<?php
$mysqli = new mysqli('localhost', 'root', 'P3m3xCatalina*2026', 'inventario_db');
if ($mysqli->connect_error) { die("Conexión fallida: " . $mysqli->connect_error); }

// Add stock_minimo column if not exists
$sql = "ALTER TABLE articulos ADD COLUMN stock_minimo DECIMAL(15,4) NOT NULL DEFAULT 0 AFTER existencia";
if ($mysqli->query($sql)) {
    echo "Columna 'stock_minimo' agregada correctamente\n";
} else {
    if ($mysqli->errno == 1060) {
        echo "La columna 'stock_minimo' ya existe\n";
    } else {
        echo "Error: " . $mysqli->error . "\n";
    }
}

$mysqli->close();