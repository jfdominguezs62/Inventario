<?php
$host = "localhost";
$user = "root";
$pass = "";

try {
    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Create database if not exists
    $sql = "CREATE DATABASE IF NOT EXISTS inventario_db CHARACTER SET utf8 COLLATE utf8_general_ci";
    if ($conn->query($sql) === TRUE) {
        echo "Database inventario_db created or already exists.\n";
    } else {
        echo "Error creating database: " . $conn->error . "\n";
    }

    $conn->select_db("inventario_db");

    // Create users table
    $sqlTable = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user VARCHAR(50) NOT NULL UNIQUE,
        pasw1 VARCHAR(255) NOT NULL,
        username VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    if ($conn->query($sqlTable) === TRUE) {
        echo "Table 'users' created or already exists.\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }

    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
