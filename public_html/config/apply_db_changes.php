<?php
/**
 * Script para aplicar cambios en la estructura de la base de datos
 */

// Include configuration file
require_once __DIR__ . '/config.php';

// Incluir clase Database
require_once __DIR__ . '/../includes/db/Database.php';

// Conectar a la base de datos
$db = Database::getInstance();

echo "Iniciando proceso de actualización de la base de datos...<br>\n";

try {
    // Añadir columnas login_attempts, account_locked y last_attempt_time a la tabla users
    $query1 = "ALTER TABLE users ADD COLUMN login_attempts INT DEFAULT 0 AFTER is_active";
    $db->query($query1);
    echo "✓ Columna login_attempts agregada correctamente<br>\n";
    
    $query2 = "ALTER TABLE users ADD COLUMN account_locked TINYINT(1) DEFAULT 0 AFTER login_attempts";
    $db->query($query2);
    echo "✓ Columna account_locked agregada correctamente<br>\n";
    
    $query3 = "ALTER TABLE users ADD COLUMN last_attempt_time TIMESTAMP NULL AFTER account_locked";
    $db->query($query3);
    echo "✓ Columna last_attempt_time agregada correctamente<br>\n";
    
    echo "<br>Todas las actualizaciones completadas correctamente.<br>\n";
    echo "La base de datos ha sido actualizada con éxito.<br>\n";
    
} catch (PDOException $e) {
    echo "<br><strong>Error al actualizar la base de datos:</strong><br>\n";
    echo $e->getMessage() . "<br>\n";
    
    // Si las columnas ya existen, informamos que ya estaba actualizada
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "<br>La estructura de la base de datos ya estaba actualizada.<br>\n";
    }
}

echo "<br><a href='/login.php'>Volver al login</a>";
