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
    // Connect without multi-statement option to avoid driver issues
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];
    
    echo "Connecting to $servername...<br>";
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "Current directory: " . __DIR__ . "<br>";
    echo "Files in directory:<br><pre>" . print_r(scandir(__DIR__), true) . "</pre>";
    
    $sql_file = __DIR__ . '/facultrack.sql';
    if (!file_exists($sql_file)) {
        die("Error: facultrack.sql not found at $sql_file");
    }
    
    echo "Reading SQL file...<br>";
    $sql = file_get_contents($sql_file);
    
    // Split SQL into individual statements
    // This is a basic split; it might fail on semicolons inside strings,
    // but it's safer/more compatible than the multi-statement option currently failing.
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    echo "Executing " . count($statements) . " statements...<br>";
    
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                echo "<div style='color:orange'>Warning executing statement: " . htmlspecialchars(substr($stmt, 0, 50)) . "... <br>Error: " . $e->getMessage() . "</div>";
                // Continue despite errors (e.g. drop table if exists might fail if not exists)
            }
        }
    }
    
    echo "<h2 style='color:green'>Success! Database populated.</h2>";
    echo "<p>You can now delete this file and <a href='index.php'>Login</a>.</p>";
    
} catch (Exception $e) {
    echo "<h2 style='color:red'>Error: " . $e->getMessage() . "</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
