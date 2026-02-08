<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load common config to get credentials ($servername, $username, etc)
require_once 'assets/php/common_utilities.php';

echo "<h1>Database Population Tool (mysqli)</h1>";

// Manually ensure credentials are plain strings
$db_host = $servername;
$db_user = $username;
$db_pass = $password;
$db_name = $dbname;
$db_port = $port;

echo "Connecting to $db_host...<br>";

// Create connection
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Ensure UTF-8
$mysqli->set_charset("utf8mb4");

echo "Current directory: " . __DIR__ . "<br>";
$sql_file = __DIR__ . '/facultrack.sql';

if (!file_exists($sql_file)) {
    die("Error: facultrack.sql not found at $sql_file");
}

echo "Reading SQL file...<br>";
$sql = file_get_contents($sql_file);

if (empty($sql)) {
    die("Error: SQL file is empty");
}

echo "Executing SQL using multi_query...<br>";

// NUCLEAR OPTION: Wipe database clean first
$mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
if ($result = $mysqli->query("SHOW TABLES")) {
    while ($row = $result->fetch_row()) {
        $table = $row[0];
        echo "Dropping table $table...<br>";
        $mysqli->query("DROP TABLE IF EXISTS `$table`");
    }
    $result->free();
}
$mysqli->query("SET FOREIGN_KEY_CHECKS = 1");

// Proceed with import
if ($mysqli->multi_query($sql)) {
    do {
        /* store first result set */
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
        /* print divider */
        if ($mysqli->more_results()) {
            // echo "."; 
        }
    } while ($mysqli->next_result());
    
    if ($mysqli->errno) {
        echo "<h2 style='color:red'>Error executing statement: " . $mysqli->error . "</h2>";
    } else {
        echo "<h2 style='color:green'>Success! Database populated.</h2>";
        echo "<p>You can now delete this file and <a href='index.php'>Login</a>.</p>";
    }
} else {
    echo "<h2 style='color:red'>First statement failed: " . $mysqli->error . "</h2>";
}

$mysqli->close();
?>
