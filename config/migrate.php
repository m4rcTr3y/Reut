<?php
require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/../config.php";

use Reut\DB\DataBase;

// Autoload models dynamically
spl_autoload_register(function ($class) {
    $prefix = 'Reut\\Models\\';
    $baseDir = __DIR__ . '/../models/';

    // Check if the class uses the namespace prefix
    if (strpos($class, $prefix) === 0) {
        // Get the relative class name
        $relativeClass = substr($class, strlen($prefix));

        // Replace namespace separators with directory separators
        $file = realpath($baseDir . str_replace('\\', '/', $relativeClass) . '.php');

        // Require the file if it exists
        if (file_exists($file)) {
            echo "Loading class: $file\n";
            require_once $file;
        }
    }
});

// Create database
$baseDb = new DataBase($config);
if ($baseDb->createDatabase($config['dbname'])) {
    $dbname = $config['dbname'];
   // echo "$dbname Database created successfully.\n \n";
}

// Connect to the database
if ($baseDb->connect()) {
    echo "Getting tables ...\n";

    // Get all model class files in the 'models' directory
    $modelClasses = array_diff(scandir(__DIR__ . '/../models/'), ['.', '..']);

    //echo "Found: " . json_encode(array_values($modelClasses)) . "\n";

    $noRelations = [];
    $withRelations = [];

    foreach (array_values($modelClasses) as $fileName) {
        echo "Loading class: $fileName\n";
        $className = 'Reut\\Models\\' . pathinfo($fileName, PATHINFO_FILENAME);

        // Check if the class exists
        if (class_exists($className)) {
            // Instantiate the class
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

    foreach ($noRelations as $tableInstance) {
        $classNameParts = explode('Table', get_class($tableInstance));
        $className = $classNameParts[0];

        if ($tableInstance->tableExists($tableInstance->tableName)) {
            echo $tableInstance.'talbr....';
            echo get_class($tableInstance) . " already exists \n";
        } else if ($tableInstance->createTable()) {
            echo get_class($tableInstance) . " table created successfully.\n";
        } else {
            echo "Error creating " . get_class($tableInstance) . " table.\n";
        }
    }

    foreach ($withRelations as $tableInstance) {
        $classNameParts = explode('Table', get_class($tableInstance));
        $className = $classNameParts[0];
        $qrry = $tableInstance->tableExists($tableInstance->tableName);
        //echo json_encode($qrry);
        if (  $qrry[0]['total'] != 0) {
            //echo $tableInstance->tableName.'talbr....';
            echo get_class($tableInstance) . " already exists\n";
        } else if ($tableInstance->createTable()) {
            echo get_class($tableInstance) . " table created successfully.\n";
        } else {
            echo "Error creating " . get_class($tableInstance) . " table.\n";
        }
    }

    echo "\n";

} else {
    echo "Failed to connect to the database.\n";
}
