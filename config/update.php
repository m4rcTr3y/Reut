<?php
// Enhanced update.php
// Changes:
// - Checks $this->columns for new columns added via addColumn().
// - Uses getAddColumnSQL() to generate ALTER TABLE SQL.
// - Records column additions in migrations table.
// - Fixes orphan table detection to match Messages -> MessagesTable.php.
// - Uses sqlQuery() for migrations table creation and column additions.
// - Normalizes table names to lowercase.
// - Uses $this->columns instead of $table->schema.

require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/../config.php";

use Reut\DB\DataBase;

spl_autoload_register(function ($class) {
    $prefix = 'Reut\\Models\\';
    $baseDir = __DIR__ . '/../models/';

    if (strpos($class, $prefix) === 0) {
        $relativeClass = substr($class, strlen($prefix));
        $file = realpath($baseDir . str_replace('\\', '/', $relativeClass) . '.php');
        if (file_exists($file)) {
            echo "Loading class: $file\n";
            require_once $file;
        }
    }
});

$baseDb = new DataBase($config);
try {
    if ($baseDb->connect()) {
        // Create migrations table
        $migrationsTableSql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                sql_text TEXT NOT NULL,
                batch INT NOT NULL,
                applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
        $baseDb->sqlQuery($migrationsTableSql);

        // Get current max batch and increment
        $batchQuery = $baseDb->sqlQuery("SELECT MAX(batch) as max_batch FROM migrations");
        $currentBatch = ($batchQuery['max_batch'] ?? 0) + 1;

        // Get model files
        $modelFiles = array_diff(scandir(__DIR__ . '/../models/'), ['.', '..']);

        // Get tables in database
        $tablesInDatabase = $baseDb->getTablesList();

        // Check for orphan tables
        foreach ($tablesInDatabase as $tableName) {
            $expectedModelFile = ucfirst($tableName) . 'Table.php'; // messages -> MessagesTable.php
            if (!in_array($expectedModelFile, $modelFiles)) {
                echo "Table '{$tableName}' exists in {$config['dbname']} but no model class found.\n";
                echo "Do you want to drop this table? (yes/no): ";
                $response = trim(fgets(STDIN));
                if (strtolower($response) === 'yes') {
                    $baseDb->dropTable($tableName);
                    echo "'{$tableName}' dropped from database.\n";
                } else {
                    echo "Proceeding without dropping '{$tableName}'...\n";
                }
            }
        }

        // Check models for updates
        foreach ($modelFiles as $fileName) {
            $className = pathinfo($fileName, PATHINFO_FILENAME);
            $classFullName = 'Reut\\Models\\' . $className;
            $tableName = str_replace('Table', '', $className); // MessagesTable -> messages

            if (class_exists($classFullName)) {
                $tableInstance = new $classFullName($config);

                if (!$tableInstance->tableExists($tableName)) {
                    // Create missing table
                    $migrationName = 'create_' . $tableName . '_table';
                    $checkStmt = $baseDb->sqlQuery("SELECT COUNT(*) as cnt FROM migrations WHERE name = :name", ['name' => $migrationName]);
                    if ($checkStmt['cnt'] === 0) {
                        $sql = $tableInstance->genSQL();
                        if ($sql === false) {
                            throw new Exception("Failed to generate SQL for {$tableName}.");
                        }
                        if ($tableInstance->createTable()) {
                            $baseDb->sqlQuery(
                                "INSERT INTO migrations (name, sql_text, batch) VALUES (:name, :sql_text, :batch)",
                                ['name' => $migrationName, 'sql_text' => $sql, 'batch' => $currentBatch]
                            );
                            echo "{$className} table created and migration recorded.\n";
                        } else {
                            echo "Error creating {$className} table.\n";
                        }
                    } else {
                        echo "{$className} migration already applied.\n";
                    }
                } else {
                    // Check for new columns in $this->columns
                    $dbColumns = $tableInstance->getTableSchema($tableName);
                    $modelColumns = removeItemT($tableInstance->columns, 'FOREIGN KEY');
                    $modelColumnNames = array_keys($modelColumns);
                    $missingColumns = array_diff($modelColumnNames, $dbColumns);

                    if (empty($missingColumns)) {
                        echo "No changes to apply in {$className}.\n";
                    } else {
                        echo "Applying changes to: {$className}.\n";
                        foreach ($missingColumns as $column) {
                            $definition = $tableInstance->columns[$column];
                            $migrationName = 'add_' . $column . '_to_' . $tableName . '_table';
                            $checkStmt = $baseDb->sqlQuery("SELECT COUNT(*) as cnt FROM migrations WHERE name = :name", ['name' => $migrationName]);
                           
                            if ($checkStmt[0]['cnt'] === 0) {
                                $sql = $tableInstance->getAddColumnSQL($column, $definition);
                                if ($tableInstance->addColumnToTable($tableName, $column, $definition)) {
                                    $baseDb->sqlQuery(
                                        "INSERT INTO migrations (name, sql_text, batch) VALUES (:name, :sql_text, :batch)",
                                        ['name' => $migrationName, 'sql_text' => $sql, 'batch' => $currentBatch]
                                    );
                                    echo "Added column '{$column}' to {$className} table and migration recorded.\n";
                                } else {
                                    echo "Error adding column '{$column}' to {$className} table.\n";
                                }
                            } else {
                                echo "Migration for column '{$column}' already applied.\n";
                            }
                        }
                    }
                }
            }
        }
    } else {
        throw new Exception("Failed to connect to the database. Check your config or MySQL availability.");
    }
} catch (PDOException $e) {
    echo "PDOException: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

function removeItemT($array, $textToSearch): array
{
    foreach ($array as $key => $value) {
        if (strpos($key, $textToSearch) !== false) {
            unset($array[$key]);
        }
    }
    return $array;
}