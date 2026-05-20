<?php
require_once 'config.php';

$tables = ['users', 'admins'];

foreach ($tables as $table) {
    echo "Checking table: $table\n";
    try {
        // Check if column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'username'");
        if ($stmt->rowCount() > 0) {
            echo "Dropping column username from $table...\n";
            $pdo->exec("ALTER TABLE `$table` DROP COLUMN `username`");
            echo "Dropped successfully.\n";
        } else {
            echo "Column username does not exist in $table.\n";
        }
    } catch (Exception $e) {
        echo "Error for table $table: " . $e->getMessage() . "\n";
    }
}
echo "Migration completed.\n";
?>
