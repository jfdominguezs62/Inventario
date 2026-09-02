<?php
$mysqli = new mysqli('localhost', 'root', '', 'inventario');
if ($mysqli->connect_error) { die("Conexión fallida: " . $mysqli->connect_error); }

// Check articulos table structure
$result = $mysqli->query("SHOW COLUMNS FROM articulos WHERE Field = 'id'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "id column: " . json_encode($row) . "\n";
    }
}

$result = $mysqli->query("SHOW TABLE STATUS WHERE Name = 'articulos'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "Engine: " . $row['Engine'] . "\n";
        echo "Collation: " . $row['Collation'] . "\n";
    }
}
$mysqli->close();