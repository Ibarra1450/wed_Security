<?php
require_once 'config.php';

$tables = ['users', 'admins'];
$columns = [
    'first_name' => "VARCHAR(50) DEFAULT NULL AFTER email",
    'last_name' => "VARCHAR(50) DEFAULT NULL AFTER first_name",
    'middle_initial' => "VARCHAR(5) DEFAULT NULL AFTER last_name"
];

foreach ($tables as $table) {
    echo "Checking table: $table\n";
    foreach ($columns as $column => $definition) {
        try {
            // Check if column exists
            $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
            if ($stmt->rowCount() == 0) {
                echo "Adding column $column to $table...\n";
                $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
                echo "Added $column successfully.\n";
            } else {
                echo "Column $column already exists in $table.\n";
            }
        } catch (Exception $e) {
            echo "Error for table $table, column $column: " . $e->getMessage() . "\n";
        }
    }
}
echo "Migration completed.\n";
?>
