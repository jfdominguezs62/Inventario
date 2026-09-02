<?php
$host = "localhost";
$user = "root";
$pass = "";

try {
    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $conn->select_db("inventario_db");

    $sql = "CREATE TABLE IF NOT EXISTS articulos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(50) NOT NULL UNIQUE,
        descripcion VARCHAR(255) NOT NULL,
        familia VARCHAR(100) DEFAULT '',
        unidadmedida VARCHAR(50) DEFAULT '',
        piezasxunidad INT DEFAULT 1,
        estatus TINYINT(1) DEFAULT 1,
        existencia DECIMAL(12,2) DEFAULT 0,
        fechaalta DATETIME DEFAULT CURRENT_TIMESTAMP,
        fechacambio DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    if ($conn->query($sql) === TRUE) {
        echo "Table 'articulos' created or already exists.\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }

    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
