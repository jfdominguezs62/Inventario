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

    $sql = "CREATE TABLE IF NOT EXISTS trabajadores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        idnomina VARCHAR(30) DEFAULT NULL,
        nombre VARCHAR(150) NOT NULL,
        departamento VARCHAR(100) DEFAULT NULL,
        bodeguero TINYINT(1) NOT NULL DEFAULT 0,
        estatus TINYINT(1) NOT NULL DEFAULT 1,
        fechaalta DATETIME DEFAULT CURRENT_TIMESTAMP,
        fechacambio DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    if ($conn->query($sql) === TRUE) {
        echo "Table 'trabajadores' created or already exists.\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }

    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
