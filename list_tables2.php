<?php
$mysqli = new mysqli('localhost', 'root', '', 'inventario_db');
if ($mysqli->connect_error) { die("Conexión fallida: " . $mysqli->connect_error); }

$result = $mysqli->query("SHOW TABLES");
echo "Tablas en inventario_db:\n";
while ($row = $result->fetch_row()) {
    echo "  " . $row[0] . "\n";
}
$mysqli->close();