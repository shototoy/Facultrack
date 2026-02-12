<?php
require_once 'assets/php/common_utilities.php';

$pdo = get_db_connection();
$messages = [];

function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function columnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function indexExists(PDO $pdo, string $table, string $index): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
    $stmt->execute([$table, $index]);
    return (int)$stmt->fetchColumn() > 0;
}

function foreignKeyExists(PDO $pdo, string $table, string $constraint): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
    $stmt->execute([$table, $constraint]);
    return (int)$stmt->fetchColumn() > 0;
}

function runStep(PDO $pdo, array &$messages, string $sql, string $label): void {
    try {
        $pdo->exec($sql);
        $messages[] = "OK: {$label}";
    } catch (Exception $e) {
        $messages[] = "WARN: {$label} - " . $e->getMessage();
    }
}

try {
    if (tableExists($pdo, 'announcements') && columnExists($pdo, 'announcements', 'target_audience')) {
        runStep($pdo, $messages,
            "ALTER TABLE announcements MODIFY target_audience VARCHAR(100) NOT NULL DEFAULT 'all'",
            "normalized announcements.target_audience"
        );
    } else {
        $messages[] = "SKIP: announcements.target_audience not found";
    }

    if (!tableExists($pdo, 'deans')) {
        runStep($pdo, $messages,
            "CREATE TABLE deans (
                dean_id INT AUTO_INCREMENT PRIMARY KEY,
                faculty_id INT NOT NULL,
                program_id INT NOT NULL,
                assigned_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_program_id (program_id),
                KEY idx_deans_faculty_id (faculty_id),
                CONSTRAINT fk_deans_faculty FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id),
                CONSTRAINT fk_deans_program FOREIGN KEY (program_id) REFERENCES programs(program_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "created deans table"
        );
    } else {
        if (!columnExists($pdo, 'deans', 'dean_id')) {
            runStep($pdo, $messages,
                "ALTER TABLE deans ADD COLUMN dean_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST",
                "added deans.dean_id"
            );
        }

        if (!columnExists($pdo, 'deans', 'faculty_id')) {
            runStep($pdo, $messages,
                "ALTER TABLE deans ADD COLUMN faculty_id INT NULL",
                "added deans.faculty_id"
            );
            if (columnExists($pdo, 'deans', 'dean_user_id')) {
                runStep($pdo, $messages,
                    "UPDATE deans d JOIN faculty f ON f.user_id = d.dean_user_id SET d.faculty_id = f.faculty_id WHERE d.faculty_id IS NULL",
                    "migrated deans.dean_user_id to faculty_id"
                );
            } elseif (columnExists($pdo, 'deans', 'user_id')) {
                runStep($pdo, $messages,
                    "UPDATE deans d JOIN faculty f ON f.user_id = d.user_id SET d.faculty_id = f.faculty_id WHERE d.faculty_id IS NULL",
                    "migrated deans.user_id to faculty_id"
                );
            }
        }

        if (!columnExists($pdo, 'deans', 'program_id')) {
            runStep($pdo, $messages,
                "ALTER TABLE deans ADD COLUMN program_id INT NULL",
                "added deans.program_id"
            );
            if (columnExists($pdo, 'deans', 'program_name')) {
                runStep($pdo, $messages,
                    "UPDATE deans d JOIN programs p ON p.program_name = d.program_name SET d.program_id = p.program_id WHERE d.program_id IS NULL",
                    "migrated deans.program_name to program_id"
                );
            } elseif (columnExists($pdo, 'deans', 'program')) {
                runStep($pdo, $messages,
                    "UPDATE deans d JOIN programs p ON p.program_name = d.program SET d.program_id = p.program_id WHERE d.program_id IS NULL",
                    "migrated deans.program to program_id"
                );
            }
        }

        if (!columnExists($pdo, 'deans', 'assigned_at')) {
            runStep($pdo, $messages,
                "ALTER TABLE deans ADD COLUMN assigned_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP",
                "added deans.assigned_at"
            );
        }

        runStep($pdo, $messages,
            "DELETE d FROM deans d LEFT JOIN faculty f ON f.faculty_id = d.faculty_id WHERE d.faculty_id IS NULL OR f.faculty_id IS NULL",
            "removed invalid faculty references in deans"
        );
        runStep($pdo, $messages,
            "DELETE d FROM deans d LEFT JOIN programs p ON p.program_id = d.program_id WHERE d.program_id IS NULL OR p.program_id IS NULL",
            "removed invalid program references in deans"
        );

        runStep($pdo, $messages,
            "DELETE d1 FROM deans d1 JOIN deans d2 ON d1.program_id = d2.program_id AND d1.dean_id < d2.dean_id",
            "deduplicated deans by program_id"
        );

        runStep($pdo, $messages,
            "ALTER TABLE deans MODIFY faculty_id INT NOT NULL, MODIFY program_id INT NOT NULL",
            "enforced NOT NULL on deans.faculty_id/program_id"
        );

        if (!indexExists($pdo, 'deans', 'idx_deans_faculty_id')) {
            runStep($pdo, $messages,
                "ALTER TABLE deans ADD INDEX idx_deans_faculty_id (faculty_id)",
                "added index idx_deans_faculty_id"
            );
        }
        if (!indexExists($pdo, 'deans', 'uniq_program_id')) {
            runStep($pdo, $messages,
                "ALTER TABLE deans ADD UNIQUE KEY uniq_program_id (program_id)",
                "added unique index uniq_program_id"
            );
        }

        if (!foreignKeyExists($pdo, 'deans', 'fk_deans_faculty')) {
            runStep($pdo, $messages,
                "ALTER TABLE deans ADD CONSTRAINT fk_deans_faculty FOREIGN KEY (faculty_id) REFERENCES faculty(faculty_id) ON DELETE RESTRICT ON UPDATE CASCADE",
                "added FK fk_deans_faculty"
            );
        }
        if (!foreignKeyExists($pdo, 'deans', 'fk_deans_program')) {
            runStep($pdo, $messages,
                "ALTER TABLE deans ADD CONSTRAINT fk_deans_program FOREIGN KEY (program_id) REFERENCES programs(program_id) ON DELETE RESTRICT ON UPDATE CASCADE",
                "added FK fk_deans_program"
            );
        }
    }

    if (tableExists($pdo, 'deans') && tableExists($pdo, 'programs') && tableExists($pdo, 'faculty')) {
        runStep($pdo, $messages,
            "INSERT INTO deans (faculty_id, program_id, assigned_at)
             SELECT f.faculty_id, p.program_id, NOW()
             FROM programs p
             INNER JOIN faculty f ON f.user_id = p.dean_id
             WHERE p.dean_id IS NOT NULL
             ON DUPLICATE KEY UPDATE faculty_id = VALUES(faculty_id), assigned_at = VALUES(assigned_at)",
            "backfilled/upserted deans from programs.dean_id"
        );
    }

    if (tableExists($pdo, 'iftl_weekly_compliance')) {
        if (!columnExists($pdo, 'iftl_weekly_compliance', 'is_override')) {
            runStep($pdo, $messages,
                "ALTER TABLE iftl_weekly_compliance ADD COLUMN is_override TINYINT(1) NOT NULL DEFAULT 0 AFTER status",
                "added iftl_weekly_compliance.is_override"
            );
        }
        if (columnExists($pdo, 'iftl_weekly_compliance', 'is_override') && tableExists($pdo, 'iftl_entries')) {
            runStep($pdo, $messages,
                "UPDATE iftl_weekly_compliance iwc
                 SET is_override = CASE
                    WHEN EXISTS (SELECT 1 FROM iftl_entries ie WHERE ie.compliance_id = iwc.compliance_id) THEN 1
                    ELSE 0
                 END",
                "backfilled iftl override flags"
            );
        }
    } else {
        $messages[] = "SKIP: iftl_weekly_compliance table not found";
    }

    echo "Success: sqlfix completed.<br>" . implode('<br>', $messages);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>" . implode('<br>', $messages);
}
