<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_OFF);

require_once 'assets/php/common_utilities.php';

$db_host = $servername;
$db_user = $username;
$db_pass = $password;
$db_name = $dbname;
$db_port = $port;

$focusTables = ['users', 'faculty', 'programs', 'deans', 'iftl_weekly_compliance', 'iftl_entries'];
$tableFilter = isset($_GET['table']) ? trim($_GET['table']) : '';

echo "<h1>FaculTrack Schema Check</h1>";
echo "<p><strong>Host:</strong> " . htmlspecialchars($db_host) . "</p>";
echo "<p><strong>Database:</strong> " . htmlspecialchars($db_name) . "</p>";

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($mysqli->connect_error) {
    die("<h3 style='color:red'>Connection failed: " . htmlspecialchars($mysqli->connect_error) . "</h3>");
}
$mysqli->set_charset('utf8mb4');

function fetchAllAssoc(mysqli $mysqli, string $sql, array $params = []): array {
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        return [];
    }
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

if ($tableFilter !== '') {
    $tables = [strtolower($tableFilter)];
} else {
    $rows = fetchAllAssoc($mysqli, "
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
        ORDER BY table_name
    ");
    $tables = array_map(fn($row) => $row['table_name'], $rows);
}

echo "<h2>Tables Found (" . count($tables) . ")</h2>";
echo "<pre>" . htmlspecialchars(implode(', ', $tables)) . "</pre>";

$inspectTables = $tableFilter !== ''
    ? $tables
    : array_values(array_unique(array_merge($focusTables, $tables)));

foreach ($inspectTables as $table) {
    $exists = fetchAllAssoc($mysqli, "
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = ?
        LIMIT 1
    ", [$table]);

    if (empty($exists)) {
        if (in_array($table, $focusTables, true)) {
            echo "<h3 style='color:#b00020'>Table missing: " . htmlspecialchars($table) . "</h3>";
        }
        continue;
    }

    echo "<hr><h2>Table: " . htmlspecialchars($table) . "</h2>";

    $columns = fetchAllAssoc($mysqli, "
        SELECT column_name, column_type, is_nullable, column_default, extra
        FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = ?
        ORDER BY ordinal_position
    ", [$table]);

    echo "<h3>Columns</h3>";
    echo "<table border='1' cellpadding='6' cellspacing='0'>";
    echo "<tr><th>Name</th><th>Type</th><th>Nullable</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>"
            . "<td>" . htmlspecialchars($col['column_name']) . "</td>"
            . "<td>" . htmlspecialchars($col['column_type']) . "</td>"
            . "<td>" . htmlspecialchars($col['is_nullable']) . "</td>"
            . "<td>" . htmlspecialchars((string)$col['column_default']) . "</td>"
            . "<td>" . htmlspecialchars($col['extra']) . "</td>"
            . "</tr>";
    }
    echo "</table>";

    $indexes = fetchAllAssoc($mysqli, "
        SELECT index_name, non_unique, GROUP_CONCAT(column_name ORDER BY seq_in_index SEPARATOR ', ') AS columns
        FROM information_schema.statistics
        WHERE table_schema = DATABASE() AND table_name = ?
        GROUP BY index_name, non_unique
        ORDER BY index_name
    ", [$table]);

    echo "<h3>Indexes</h3>";
    if (empty($indexes)) {
        echo "<p>None</p>";
    } else {
        echo "<table border='1' cellpadding='6' cellspacing='0'>";
        echo "<tr><th>Index</th><th>Unique</th><th>Columns</th></tr>";
        foreach ($indexes as $idx) {
            echo "<tr>"
                . "<td>" . htmlspecialchars($idx['index_name']) . "</td>"
                . "<td>" . ((int)$idx['non_unique'] === 0 ? 'YES' : 'NO') . "</td>"
                . "<td>" . htmlspecialchars($idx['columns']) . "</td>"
                . "</tr>";
        }
        echo "</table>";
    }

    $fks = fetchAllAssoc($mysqli, "
        SELECT tc.constraint_name, kcu.column_name, kcu.referenced_table_name, kcu.referenced_column_name
        FROM information_schema.table_constraints tc
        JOIN information_schema.key_column_usage kcu
            ON tc.constraint_name = kcu.constraint_name
            AND tc.table_schema = kcu.table_schema
            AND tc.table_name = kcu.table_name
        WHERE tc.table_schema = DATABASE()
          AND tc.table_name = ?
          AND tc.constraint_type = 'FOREIGN KEY'
        ORDER BY tc.constraint_name, kcu.ordinal_position
    ", [$table]);

    echo "<h3>Foreign Keys</h3>";
    if (empty($fks)) {
        echo "<p>None</p>";
    } else {
        echo "<table border='1' cellpadding='6' cellspacing='0'>";
        echo "<tr><th>Constraint</th><th>Column</th><th>Ref Table</th><th>Ref Column</th></tr>";
        foreach ($fks as $fk) {
            echo "<tr>"
                . "<td>" . htmlspecialchars($fk['constraint_name']) . "</td>"
                . "<td>" . htmlspecialchars($fk['column_name']) . "</td>"
                . "<td>" . htmlspecialchars($fk['referenced_table_name']) . "</td>"
                . "<td>" . htmlspecialchars($fk['referenced_column_name']) . "</td>"
                . "</tr>";
        }
        echo "</table>";
    }

    $countRows = fetchAllAssoc($mysqli, "SELECT COUNT(*) AS total FROM `" . str_replace('`', '``', $table) . "`");
    $total = $countRows[0]['total'] ?? '0';
    echo "<p><strong>Row count:</strong> " . htmlspecialchars((string)$total) . "</p>";
}

$mysqli->close();

echo "<hr><p>Tip: add <code>?table=deans</code> (or any table name) to inspect only one table.</p>";
echo "<p style='color:#b00020'><strong>Security:</strong> remove this file after debugging production.</p>";
