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

function executeSqlFile(mysqli $mysqli, string $filePath, string $label): array {
    if (!file_exists($filePath)) {
        return [
            'executed' => 0,
            'failed' => [[
                'code' => 0,
                'error' => "$label file not found: $filePath",
                'sql' => ''
            ]]
        ];
    }

    $sql = file_get_contents($filePath);
    if ($sql === false || trim($sql) === '') {
        return [
            'executed' => 0,
            'failed' => [[
                'code' => 0,
                'error' => "$label file is empty or unreadable: $filePath",
                'sql' => ''
            ]]
        ];
    }

    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
    $statements = splitSqlStatements($sql);

    $executed = 0;
    $failed = [];

    foreach ($statements as $statement) {
        $normalized = strtoupper(trim($statement));
        if (
            $normalized === '' ||
            str_starts_with($normalized, 'START TRANSACTION') ||
            str_starts_with($normalized, 'COMMIT') ||
            str_starts_with($normalized, 'ROLLBACK')
        ) {
            continue;
        }

        if ($mysqli->query($statement)) {
            $executed++;
        } else {
            $failed[] = [
                'code' => $mysqli->errno,
                'error' => $mysqli->error,
                'sql' => substr($statement, 0, 300)
            ];
        }
    }

    return ['executed' => $executed, 'failed' => $failed];
}

echo "<h1>Database Installer (Schema + Seed)</h1>";

$db_host = $servername;
$db_user = $username;
$db_pass = $password;
$db_name = $dbname;
$db_port = $port;

$schemaFile = __DIR__ . '/facultrack_schema.sql';
$seedFile = __DIR__ . '/facultrack_seed.sql';
$monolithFile = __DIR__ . '/facultrack.sql';

echo "Connecting to MySQL server...<br>";
$mysqli = new mysqli($db_host, $db_user, $db_pass, '', $db_port);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");

echo "Recreating database <strong>" . htmlspecialchars($db_name) . "</strong>...<br>";
if (!$mysqli->query("DROP DATABASE IF EXISTS `{$db_name}`")) {
    die("Failed to drop database: " . htmlspecialchars($mysqli->error));
}
if (!$mysqli->query("CREATE DATABASE `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
    die("Failed to create database: " . htmlspecialchars($mysqli->error));
}
if (!$mysqli->select_db($db_name)) {
    die("Failed to select database: " . htmlspecialchars($mysqli->error));
}

if (file_exists($schemaFile) && file_exists($seedFile)) {
    echo "Importing schema file...<br>";
    $schemaResult = executeSqlFile($mysqli, $schemaFile, 'Schema');

    if (!empty($schemaResult['failed'])) {
        echo "<h2 style='color:red'>Schema import failed.</h2>";
        echo "<pre>Code: " . htmlspecialchars((string)$schemaResult['failed'][0]['code']) . "\n"
            . "Message: " . htmlspecialchars($schemaResult['failed'][0]['error']) . "\n"
            . "SQL: " . htmlspecialchars($schemaResult['failed'][0]['sql']) . "</pre>";
        $mysqli->close();
        exit;
    }

    echo "Importing seed file...<br>";
    $seedResult = executeSqlFile($mysqli, $seedFile, 'Seed');

    if (!empty($seedResult['failed'])) {
        echo "<h2 style='color:red'>Seed import failed.</h2>";
        echo "<pre>Code: " . htmlspecialchars((string)$seedResult['failed'][0]['code']) . "\n"
            . "Message: " . htmlspecialchars($seedResult['failed'][0]['error']) . "\n"
            . "SQL: " . htmlspecialchars($seedResult['failed'][0]['sql']) . "</pre>";
        $mysqli->close();
        exit;
    }

    $totalExecuted = $schemaResult['executed'] + $seedResult['executed'];
    echo "<h2 style='color:green'>Success! Database installed.</h2>";
    echo "<p>Import mode: schema + seed</p>";
    echo "<p>Schema statements executed: {$schemaResult['executed']}</p>";
    echo "<p>Seed statements executed: {$seedResult['executed']}</p>";
    echo "<p>Total executed: {$totalExecuted}</p>";
} else {
    echo "Split files not found. Falling back to monolithic dump...<br>";
    $monoResult = executeSqlFile($mysqli, $monolithFile, 'Monolithic SQL');

    if (!empty($monoResult['failed'])) {
        echo "<h2 style='color:red'>Monolithic import failed.</h2>";
        echo "<pre>Code: " . htmlspecialchars((string)$monoResult['failed'][0]['code']) . "\n"
            . "Message: " . htmlspecialchars($monoResult['failed'][0]['error']) . "\n"
            . "SQL: " . htmlspecialchars($monoResult['failed'][0]['sql']) . "</pre>";
        $mysqli->close();
        exit;
    }

    echo "<h2 style='color:green'>Success! Database installed.</h2>";
    echo "<p>Import mode: monolithic</p>";
    echo "<p>Total executed: {$monoResult['executed']}</p>";
}

echo "<p>You can now delete this file and <a href='index.php'>Login</a>.</p>";

$mysqli->close();
?>

