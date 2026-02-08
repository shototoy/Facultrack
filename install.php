<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load common config to get credentials ($servername, $username etc)
require_once 'assets/php/common_utilities.php';

echo "<h1>Database Population Tool</h1>";

try {
    // Re-connect with multi-statement support explicitly enabled
    // We use the variables $servername, $username, etc. imported from common_utilities.php
    $dsn = "mysql:host=$servername;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true
    ];
    
    echo "Connecting to $servername...<br>";
    $pdo = new PDO($dsn, $username, $password, $options);
    
    $sql_file = __DIR__ . '/facultrack.sql';
    if (!file_exists($sql_file)) {
        die("Error: facultrack.sql not found at $sql_file");
    }
    
    echo "Reading SQL file...<br>";
    $sql = file_get_contents($sql_file);
    
    echo "Executing SQL...<br>";
    $pdo->exec($sql);
    
    echo "<h2 style='color:green'>Success! Database populated.</h2>";
    echo "<p>You can now delete this file and <a href='index.php'>Login</a>.</p>";
    
} catch (Exception $e) {
    echo "<h2 style='color:red'>Error: " . $e->getMessage() . "</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
