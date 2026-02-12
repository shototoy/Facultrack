<?php
// sqlfix.php: normalize announcements audience + rebuild deans table with program_id-based schema
require_once 'assets/php/common_utilities.php';
$pdo = get_db_connection();
try {
    $pdo->beginTransaction();

    // Fix announcements.target_audience
    $pdo->exec("ALTER TABLE announcements MODIFY target_audience VARCHAR(100) NOT NULL DEFAULT 'all'");

    // Rebuild deans table to use program_id (drop old schema first)
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $pdo->exec("DROP TABLE IF EXISTS deans");
    $pdo->exec("CREATE TABLE deans (
        dean_id INT AUTO_INCREMENT PRIMARY KEY,
        faculty_id INT NOT NULL,
        program_id INT NOT NULL,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_program_id (program_id),
        KEY idx_deans_faculty_id (faculty_id),
        CONSTRAINT fk_deans_faculty FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id),
        CONSTRAINT fk_deans_program FOREIGN KEY (program_id) REFERENCES programs(program_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Backfill deans table from existing programs.dean_id assignments
    $pdo->exec("INSERT INTO deans (faculty_id, program_id, assigned_at)
        SELECT f.faculty_id, p.program_id, NOW()
        FROM programs p
        INNER JOIN faculty f ON f.user_id = p.dean_id
        WHERE p.dean_id IS NOT NULL");

    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    $pdo->commit();
    echo "Success: announcements.target_audience updated and deans table rebuilt with program_id schema.";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    } catch (Exception $ignore) {
    }
    echo "Error: " . $e->getMessage();
}
