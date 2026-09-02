<?php
$mysqli = new mysqli('localhost', 'root', '', 'inventario');
if ($mysqli->connect_error) { die("Conexión fallida: " . $mysqli->connect_error); }

$result = $mysqli->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    echo $row[0] . "\n";
}
$mysqli->close();