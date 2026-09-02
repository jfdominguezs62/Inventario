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

    // Tipos de movimiento
    $sql = "CREATE TABLE IF NOT EXISTS tipos_movimiento (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(10) NOT NULL UNIQUE,
        nombre VARCHAR(50) NOT NULL,
        signo TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    if ($conn->query($sql) === TRUE) {
        echo "Table 'tipos_movimiento' created or already exists.\n";
        // Insert default types
        $conn->query("INSERT IGNORE INTO tipos_movimiento (codigo, nombre, signo) VALUES ('E', 'ENTRADA', 1)");
        $conn->query("INSERT IGNORE INTO tipos_movimiento (codigo, nombre, signo) VALUES ('S', 'SALIDA', -1)");
        echo "Default movement types inserted.\n";
    } else {
        echo "Error creating tipos_movimiento: " . $conn->error . "\n";
    }

    // Vales (maestro)
    $sql = "CREATE TABLE IF NOT EXISTS vales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        folio VARCHAR(20) NOT NULL,
        fecha DATE NOT NULL,
        id_tipomov INT NOT NULL,
        tipomov VARCHAR(10) NOT NULL,
        quienentrega VARCHAR(150) DEFAULT '',
        quienrecibe VARCHAR(150) DEFAULT '',
        descripcion_trabajo TEXT,
        observaciones TEXT,
        estatus VARCHAR(20) DEFAULT 'ACTIVO',
        usuario_crea VARCHAR(100) DEFAULT '',
        fechaalta DATETIME DEFAULT CURRENT_TIMESTAMP,
        fechacambio DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (id_tipomov) REFERENCES tipos_movimiento(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    if ($conn->query($sql) === TRUE) {
        echo "Table 'vales' created or already exists.\n";
    } else {
        echo "Error creating vales: " . $conn->error . "\n";
    }

    // Vales detalle
    $sql = "CREATE TABLE IF NOT EXISTS vales_detalle (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_vale INT NOT NULL,
        id_articulo INT NOT NULL,
        codigo VARCHAR(50) NOT NULL,
        producto VARCHAR(255) NOT NULL,
        categoria VARCHAR(100) DEFAULT '',
        unidad VARCHAR(50) DEFAULT '',
        existencia_actual DECIMAL(12,2) DEFAULT 0,
        existencia_despues DECIMAL(12,2) DEFAULT 0,
        cantidad DECIMAL(12,2) NOT NULL,
        FOREIGN KEY (id_vale) REFERENCES vales(id) ON DELETE CASCADE,
        FOREIGN KEY (id_articulo) REFERENCES articulos(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    if ($conn->query($sql) === TRUE) {
        echo "Table 'vales_detalle' created or already exists.\n";
    } else {
        echo "Error creating vales_detalle: " . $conn->error . "\n";
    }

    // Inventario fisico
    $sql = "CREATE TABLE IF NOT EXISTS inventario_fisico (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fecha DATE NOT NULL,
        folio VARCHAR(20) NOT NULL,
        observaciones TEXT,
        estatus VARCHAR(20) DEFAULT 'ABIERTO',
        usuario_crea VARCHAR(100) DEFAULT '',
        fechaalta DATETIME DEFAULT CURRENT_TIMESTAMP,
        fechacambio DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    if ($conn->query($sql) === TRUE) {
        echo "Table 'inventario_fisico' created or already exists.\n";
    } else {
        echo "Error creating inventario_fisico: " . $conn->error . "\n";
    }

    // Inventario fisico detalle
    $sql = "CREATE TABLE IF NOT EXISTS inventario_fisico_detalle (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_inventario INT NOT NULL,
        id_articulo INT NOT NULL,
        codigo VARCHAR(50) NOT NULL,
        producto VARCHAR(255) NOT NULL,
        existencia_sistema DECIMAL(12,2) DEFAULT 0,
        existencia_fisica DECIMAL(12,2) DEFAULT 0,
        diferencia DECIMAL(12,2) DEFAULT 0,
        FOREIGN KEY (id_inventario) REFERENCES inventario_fisico(id) ON DELETE CASCADE,
        FOREIGN KEY (id_articulo) REFERENCES articulos(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    if ($conn->query($sql) === TRUE) {
        echo "Table 'inventario_fisico_detalle' created or already exists.\n";
    } else {
        echo "Error creating inventario_fisico_detalle: " . $conn->error . "\n";
    }

    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
