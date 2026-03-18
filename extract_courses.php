<?php
require_once __DIR__ . '/assets/php/common_utilities.php';

function quoteIdentifier(string $value): string
{
    return '`' . str_replace('`', '``', $value) . '`';
}

function columnExists(PDO $pdo, string $tableName, string $columnName): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$tableName, $columnName]);
    return (int) $stmt->fetchColumn() > 0;
}

function ensureCourseUnitColumns(PDO $pdo): void
{
    $hasLectureUnits = columnExists($pdo, 'courses', 'lecture_units');
    $hasLabUnits = columnExists($pdo, 'courses', 'lab_units');

    if (!$hasLectureUnits && !$hasLabUnits) {
        $pdo->exec(
            'ALTER TABLE `courses`
             ADD COLUMN `lecture_units` DECIMAL(3,2) NOT NULL DEFAULT 0.00 AFTER `course_description`,
             ADD COLUMN `lab_units` DECIMAL(3,2) NOT NULL DEFAULT 0.00 AFTER `lecture_units`'
        );
    } elseif (!$hasLectureUnits) {
        $pdo->exec(
            'ALTER TABLE `courses`
             ADD COLUMN `lecture_units` DECIMAL(3,2) NOT NULL DEFAULT 0.00 AFTER `course_description`'
        );
    } elseif (!$hasLabUnits) {
        $pdo->exec(
            'ALTER TABLE `courses`
             ADD COLUMN `lab_units` DECIMAL(3,2) NOT NULL DEFAULT 0.00 AFTER `lecture_units`'
        );
    }

    $pdo->exec(
        'UPDATE `courses`
         SET `lecture_units` = `units`, `lab_units` = 0.00
         WHERE COALESCE(`lecture_units`, 0.00) = 0.00
           AND COALESCE(`lab_units`, 0.00) = 0.00'
    );
}

function buildInsertRows(PDO $pdo, string $tableName, array $rows): string
{
    if (empty($rows)) {
        return '';
    }

    $columns = array_keys($rows[0]);
    $quotedColumns = array_map('quoteIdentifier', $columns);
    $lines = [];

    foreach ($rows as $row) {
        $values = [];

        foreach ($columns as $column) {
            $value = $row[$column];
            $values[] = $value === null ? 'NULL' : $pdo->quote((string) $value);
        }

        $lines[] = '(' . implode(', ', $values) . ')';
    }

    return 'INSERT INTO ' . quoteIdentifier($tableName) . ' (' . implode(', ', $quotedColumns) . ") VALUES\n"
        . implode(",\n", $lines)
        . ";\n";
}

function exportTable(PDO $pdo, string $tableName, string $orderColumn): string
{
    $createTableRow = $pdo->query('SHOW CREATE TABLE ' . quoteIdentifier($tableName))->fetch();
    if (!$createTableRow || empty($createTableRow['Create Table'])) {
        throw new RuntimeException("Unable to read {$tableName} table schema.");
    }

    $rows = $pdo->query(
        'SELECT * FROM ' . quoteIdentifier($tableName) . ' ORDER BY ' . quoteIdentifier($orderColumn) . ' ASC'
    )->fetchAll();

    $sql = 'DROP TABLE IF EXISTS ' . quoteIdentifier($tableName) . ";\n";
    $sql .= $createTableRow['Create Table'] . ";\n\n";
    $insertSql = buildInsertRows($pdo, $tableName, $rows);
    if ($insertSql !== '') {
        $sql .= $insertSql . "\n";
    }

    return $sql;
}

try {
    $pdo = get_db_connection();
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    ensureCourseUnitColumns($pdo);

    $sql = "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    $sql .= exportTable($pdo, 'courses', 'course_id');
    $sql .= exportTable($pdo, 'schedules', 'schedule_id');
    $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

    $timestamp = date('Ymd_His');
    $filename = "courses_schedules_export_{$timestamp}.sql";

    if (PHP_SAPI === 'cli') {
        $outputPath = $argv[1] ?? '';
        if ($outputPath !== '') {
            if (file_put_contents($outputPath, $sql) === false) {
                throw new RuntimeException("Failed to write export file: {$outputPath}");
            }
            fwrite(STDOUT, "Exported courses and schedules tables to {$outputPath}" . PHP_EOL);
            exit(0);
        }

        fwrite(STDOUT, $sql);
        exit(0);
    }

    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sql));
    echo $sql;
    exit;
} catch (Throwable $e) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }

    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Error: ' . $e->getMessage();
}
