<?php
$conn = new mysqli('localhost', 'root', '', 'inventario_db');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Add rol column
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS rol VARCHAR(20) NOT NULL DEFAULT 'operador' AFTER username");
// If ALTER with IF NOT EXISTS fails (older MySQL), try without
if ($conn->error) {
    $r = $conn->query("SHOW COLUMNS FROM users LIKE 'rol'");
    if ($r->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN rol VARCHAR(20) NOT NULL DEFAULT 'operador' AFTER username");
        echo "Column 'rol' added.\n";
    } else {
        echo "Column 'rol' already exists.\n";
    }
}

// Set existing users defaults
$conn->query("UPDATE users SET rol = 'operador' WHERE rol IS NULL OR rol = ''");

// If no users exist, create a default admin (user: admin, pass: admin123)
$result = $conn->query("SELECT COUNT(*) as cnt FROM users");
$row = $result->fetch_assoc();
if ($row['cnt'] == 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (username, user, pasw1, rol) VALUES ('Administrador', 'admin', '$hash', 'admin')");
    echo "Default admin created: user=admin, password=admin123\n";
}

echo "Done.\n";
$conn->close();
