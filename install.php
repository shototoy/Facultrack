<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_OFF);
require_once 'assets/php/common_utilities.php';

function splitSqlStatements(string $sql): array {
    $statements = [];
    $buffer = '';
    $length = strlen($sql);
    $inSingle = false;
    $inDouble = false;
    $inBacktick = false;
    $inLineComment = false;
    $inBlockComment = false;

    for ($index = 0; $index < $length; $index++) {
        $char = $sql[$index];
        $next = $index + 1 < $length ? $sql[$index + 1] : '';

        if ($inLineComment) {
            if ($char === "\n") {
                $inLineComment = false;
            }
            continue;
        }

        if ($inBlockComment) {
            if ($char === '*' && $next === '/') {
                $inBlockComment = false;
                $index++;
            }
            continue;
        }

        if (!$inSingle && !$inDouble && !$inBacktick) {
            if ($char === '-' && $next === '-') {
                $nextNext = $index + 2 < $length ? $sql[$index + 2] : '';
                if ($nextNext === ' ' || $nextNext === "\t" || $nextNext === "\r" || $nextNext === "\n") {
                    $inLineComment = true;
                    $index++;
                    continue;
                }
            }
            if ($char === '#') {
                $inLineComment = true;
                continue;
            }
            if ($char === '/' && $next === '*') {
                $inBlockComment = true;
                $index++;
                continue;
            }
        }

        if ($char === "'" && !$inDouble && !$inBacktick) {
            $escaped = $index > 0 && $sql[$index - 1] === '\\';
            if (!$escaped) {
                $inSingle = !$inSingle;
            }
            $buffer .= $char;
            continue;
        }

        if ($char === '"' && !$inSingle && !$inBacktick) {
            $escaped = $index > 0 && $sql[$index - 1] === '\\';
            if (!$escaped) {
                $inDouble = !$inDouble;
            }
            $buffer .= $char;
            continue;
        }

        if ($char === '`' && !$inSingle && !$inDouble) {
            $inBacktick = !$inBacktick;
            $buffer .= $char;
            continue;
        }

        if ($char === ';' && !$inSingle && !$inDouble && !$inBacktick) {
            $statement = trim($buffer);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    $tail = trim($buffer);
    if ($tail !== '') {
        $statements[] = $tail;
    }

    return $statements;
}

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
$sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
$mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
if ($result = $mysqli->query("SHOW TABLES")) {
    while ($row = $result->fetch_row()) {
        $table = $row[0];
        $mysqli->query("DROP TABLE IF EXISTS `$table`");
    }
    $result->free();
}
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
$pos_dean = strpos($content, "dean_id");
if ($pos_dean !== false) {
    echo "Found 'dean_id' at position $pos_dean. Context:<br>";
    echo "<pre>" . htmlspecialchars(substr($content, $pos_dean - 100, 200)) . "</pre>";
} else {
    echo "DID NOT FIND 'dean_id' in SQL file!<br>";
}
$pos2 = strpos($content, "\n(41,");
if ($pos2 !== false) {
    echo "Found line starting with (41, at position $pos2. Context:<br>";
    echo "<pre>" . htmlspecialchars(substr($content, $pos2 - 50, 100)) . "</pre>";
}
echo "Executing SQL...<br>";
$statements = splitSqlStatements($sql);
echo "Total parsed SQL statements: " . count($statements) . "<br>";

$executed = 0;
$failed = [];

foreach ($statements as $statement) {
    $normalized = strtoupper(trim($statement));
    if (
        $normalized === '' ||
        str_starts_with($normalized, 'START TRANSACTION') ||
        str_starts_with($normalized, 'COMMIT') ||
        str_starts_with($normalized, 'ROLLBACK') ||
        str_starts_with($normalized, 'SET FOREIGN_KEY_CHECKS')
    ) {
        continue;
    }

    $ok = $mysqli->query($statement);
    if ($ok) {
        $executed++;
    } else {
        $failed[] = [
            'code' => $mysqli->errno,
            'error' => $mysqli->error,
            'sql' => substr($statement, 0, 300)
        ];
    }
}

$mysqli->query("SET FOREIGN_KEY_CHECKS = 1");

if (empty($failed)) {
    echo "<h2 style='color:green'>Success! Database populated.</h2>";
    echo "<p>Executed statements: {$executed}</p>";
    echo "<p>You can now delete this file and <a href='index.php'>Login</a>.</p>";
} else {
    echo "<h2 style='color:red'>Import completed with errors.</h2>";
    echo "<p>Executed statements: {$executed}</p>";
    echo "<p>Failed statements: " . count($failed) . "</p>";
    echo "<h3>First error</h3>";
    echo "<pre>Code: " . htmlspecialchars((string)$failed[0]['code']) . "\n"
        . "Message: " . htmlspecialchars($failed[0]['error']) . "\n"
        . "SQL: " . htmlspecialchars($failed[0]['sql']) . "</pre>";
}
$mysqli->close();
?>

