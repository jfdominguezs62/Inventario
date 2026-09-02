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

    $columns = [
        'existenciainicial' => 'DECIMAL(12,2) DEFAULT 0',
        'entradas'          => 'DECIMAL(12,2) DEFAULT 0',
        'salidas'           => 'DECIMAL(12,2) DEFAULT 0',
        'existenciafisica'  => 'DECIMAL(12,2) DEFAULT 0',
        'inventariado'      => 'TINYINT(1) DEFAULT 0',
    ];

    foreach ($columns as $col => $def) {
        $r = $conn->query("SHOW COLUMNS FROM articulos LIKE '$col'");
        if ($r->num_rows == 0) {
            $conn->query("ALTER TABLE articulos ADD COLUMN $col $def");
            echo "Column '$col' added.\n";
        } else {
            echo "Column '$col' already exists.\n";
        }
    }

    $conn->close();
    echo "Done.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
