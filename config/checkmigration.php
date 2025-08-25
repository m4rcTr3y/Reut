<?php
// check_migrations.php
// Lists applied migrations and checks for pending migrations by comparing model classes with the migrations table.
// Features:
// - Displays all applied migrations from the migrations table.
// - Identifies pending table creations (missing create_{tableName}_table migrations).
// - Identifies pending column additions (missing columns in existing tables).
// - Respects hasRelationships and relationships for dependency ordering.
// - Uses sqlQuery(), genSQL(), and getAddColumnSQL() from DataBase class.
// - Normalizes table names to lowercase.

require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/../config.php";

use Reut\DB\DataBase;

// Autoload models dynamically
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
    if (!$baseDb->connect()) {
        throw new Exception("Failed to connect to the database. Check your config or MySQL availability.");
    }

    // Check if migrations table exists
    if (!$baseDb->tableExists('migrations')) {
        echo "No migrations table found. Run migrate.php to create it.\n";
        exit;
    }

    // List applied migrations
    echo "\n=== Applied Migrations ===\n";
    $migrations = $baseDb->sqlQuery("SELECT id, name, sql_text, batch, applied_at FROM migrations ORDER BY batch, id");
   

    if (empty($migrations)) {
        echo "No migrations have been applied.\n";
    } else {
        foreach ($migrations as $migration) {
            echo "ID: {$migration['id']}\n";
            echo "Name: {$migration['name']}\n";
            echo "Batch: {$migration['batch']}\n";
            echo "Applied At: {$migration['applied_at']}\n";
            echo "SQL:\n{$migration['sql_text']}\n";
            echo "------------------------\n";
        }
    }

    // Check for pending migrations
    echo "\n=== Pending Migrations ===\n";
    $modelFiles = array_diff(scandir(__DIR__ . '/../models/'), ['.', '..']);
    $noRelations = [];
    $withRelations = [];

    // Load model classes
    foreach ($modelFiles as $fileName) {
        echo "Checking class: $fileName\n";
        $className = 'Reut\\Models\\' . pathinfo($fileName, PATHINFO_FILENAME);

        if (class_exists($className)) {
            $tableInstance = new $className($config);
            if (property_exists($tableInstance, 'hasRelationships') && $tableInstance->hasRelationships) {
                $withRelations[] = $tableInstance;
            } else {
                $noRelations[] = $tableInstance;
            }
        } else {
            echo "Class $className does not exist.\n";
        }
    }

    usort($withRelations, fn($a, $b) => $a->relationships <=> $b->relationships);

    $pendingMigrations = [];

    // Function to check pending migrations for a table
    function checkPendingMigration($baseDb, $tableInstance, &$pendingMigrations): void
    {
        $tableName = $tableInstance->tableName;
        $migrationName = 'create_' . $tableName . '_table';

        // Check if table creation migration is pending
        $checkStmt = $baseDb->sqlQuery("SELECT COUNT(*) as cnt FROM migrations WHERE name = :name", ['name' => $migrationName]);
      
        if ($checkStmt[0]['cnt'] === 0 && !$tableInstance->tableExists($tableName)) {
            $sql = $tableInstance->genSQL();
            if ($sql !== false) {
                $pendingMigrations[] = [
                    'name' => $migrationName,
                    'sql' => $sql,
                    'type' => 'create_table',
                    'class' => get_class($tableInstance)
                ];
            }
        } elseif ($tableInstance->tableExists($tableName)) {
            // Check for missing columns
            $dbColumns = $tableInstance->getTableSchema($tableName);
            $modelColumns = array_filter($tableInstance->columns, fn($key) => strpos($key, 'FOREIGN KEY') === false, ARRAY_FILTER_USE_KEY);
            $modelColumnNames = array_keys($modelColumns);
            $missingColumns = array_diff($modelColumnNames, $dbColumns);

            foreach ($missingColumns as $column) {
                $definition = $tableInstance->columns[$column];
                $colMigrationName = 'add_' . $column . '_to_' . $tableName . '_table';
                $checkColStmt = $baseDb->sqlQuery("SELECT COUNT(*) as cnt FROM migrations WHERE name = :name", ['name' => $colMigrationName]);
                if ($checkColStmt[0]['cnt'] === 0) {
                    $sql = $tableInstance->getAddColumnSQL($column, $definition);
                    $pendingMigrations[] = [
                        'name' => $colMigrationName,
                        'sql' => $sql,
                        'type' => 'add_column',
                        'class' => get_class($tableInstance)
                    ];
                }
            }
        }
    }

    // Check tables without relations
    foreach ($noRelations as $tableInstance) {
        checkPendingMigration($baseDb, $tableInstance, $pendingMigrations);
    }

    // Check tables with relations
    foreach ($withRelations as $tableInstance) {
        checkPendingMigration($baseDb, $tableInstance, $pendingMigrations);
    }

    // Display pending migrations
    if (empty($pendingMigrations)) {
        echo "No pending migrations found.\n";
    } else {
        echo "Found " . count($pendingMigrations) . " pending migration(s):\n";
        foreach ($pendingMigrations as $migration) {
            echo "Class: {$migration['class']}\n";
            echo "Name: {$migration['name']}\n";
            echo "Type: {$migration['type']}\n";
            echo "SQL:\n{$migration['sql']}\n";
            echo "------------------------\n";
        }
        echo "Run migrate.php or update.php to apply these migrations.\n";
    }

    echo "\n";
} catch (PDOException $e) {
    echo "PDOException: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}