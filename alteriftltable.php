<?php
// Run this script ONCE to add the number of students column to the IFTL entries table
// Usage: php alteriftltable.php

require_once 'assets/php/common_utilities.php'; // adjust path if needed

try {
    $pdo = get_db_connection();
    // Remove activity_type column if it exists
    $sqlDrop = "ALTER TABLE iftl_entries DROP COLUMN activity_type";
    try {
        $pdo->exec($sqlDrop);
        echo "Column 'activity_type' removed from iftl_entries table.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Check that column/key exists') === false && strpos($e->getMessage(), 'Unknown column') === false) {
            throw $e;
        } else {
            echo "Column 'activity_type' does not exist or already removed.\n";
        }
    }
    // Add total_students column if not exists
    $sql = "ALTER TABLE iftl_entries ADD COLUMN total_students INT NULL AFTER class_name";
    try {
        $pdo->exec($sql);
        echo "Column 'total_students' added to iftl_entries table.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            throw $e;
        } else {
            echo "Column 'total_students' already exists in iftl_entries table.\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
