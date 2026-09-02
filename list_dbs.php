<?php
$mysqli = new mysqli('localhost', 'root', '', '');
if ($mysqli->connect_error) { die("Conexión fallida: " . $mysqli->connect_error); }

$result = $mysqli->query("SHOW DATABASES");
echo "Bases de datos:\n";
while ($row = $result->fetch_row()) {
    echo "  " . $row[0] . "\n";
}
$mysqli->close();