<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'assets/php/common_utilities.php';
echo "<h1>Database Population Tool (mysqli)</h1>";
$db_host = $servername;
$db_user = $username;
$db_pass = $password;
$db_name = $dbname;
$db_port = $port;
echo "Connecting to $db_host...<br>";
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
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
$mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
if ($result = $mysqli->query("SHOW TABLES")) {
    while ($row = $result->fetch_row()) {
        $table = $row[0];
        $mysqli->query("DROP TABLE IF EXISTS `$table`");
    }
    $result->free();
}
$mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
$content = file_get_contents($sql_file);
echo "<h3>File Integrity Check</h3>";
echo "File size: " . strlen($content) . " bytes<br>";
$pos = strpos($content, "DIR-1");
if ($pos !== false) {
    echo "Found 'DIR-1' at position $pos. Context:<br>";
    echo "<pre>" . htmlspecialchars(substr($content, $pos - 100, 200)) . "</pre>";
} else {
    echo "DID NOT FIND 'DIR-1' in SQL file!<br>";
}
$pos2 = strpos($content, "\n(41,");
if ($pos2 !== false) {
    echo "Found line starting with (41, at position $pos2. Context:<br>";
    echo "<pre>" . htmlspecialchars(substr($content, $pos2 - 50, 100)) . "</pre>";
}
echo "Executing SQL...<br>";
if ($mysqli->multi_query($content)) {
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
        if ($mysqli->more_results()) {
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

