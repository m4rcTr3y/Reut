<?php

require_once dirname(__DIR__).'/config/dataBase.php';

require dirname(__DIR__).'/config.php';

spl_autoload_register(function ($className) {
    $path = dirname(__DIR__) . '/models/' . $className . '.php';
    if (file_exists($path)) {
        include_once $path;
    }
});

use Reut\DB\DataBase;

$baseDb = new DataBase($config);
try{

    if ($baseDb->connect()) {
       
    
        // Get a list of all classes in the models folder
        $modelClasses = array_diff(scandir(dirname(__DIR__). '/models'), ['.', '..']);
      
        $tablesInDatabase = $baseDb->getTablesList();
     
        // echo json_encode($tablesInDatabase);
    
        foreach ($tablesInDatabase as $tableName) {
            
            $classNameParts = explode('Table', $tableName);
            $className = $classNameParts[0]; // Assuming class names are similar to table names
    
            if (!in_array($className . 'Table.php', $modelClasses)) {
                echo "Table '{$tableName}' class exists in  but not in {$config['dbname']} database.\n";
                echo "Do you want to create this table? (yes/no): ";
                $response = trim(fgets(STDIN));
    
                if (strtolower($response) == 'yes') {
                    $baseDb->createDatabase($className);
                    echo "'{$tableName}' created in database.\n";
                    echo "checking other tables \n";
                }else{
                    echo "proceeding... \n";
                }
            }
        }
    
    
        foreach ($modelClasses as $className) {
            $className = pathinfo($className, PATHINFO_FILENAME);
    
            $classNameParts = explode('Table', $className);
            $classStringName = $classNameParts[0];
            
           $table = (Object) new $className($config);
            //  type cast the $table variable into an object
            
            if (!$table->connect() || !$table->tableExists($classStringName)) {
                // Create table if it doesn't exist
                $table->createTable();
                echo "{$className} table created.\n";
            } else {
                // Check for updates in the table
                $tableSchema = $table->getTableSchema($classStringName);
                $tableColumns = removeItemT($table->schema,'FOREIGN KEY');
    
                
                $localArray = array_keys($tableColumns);
    
                if( empty(array_diff($tableSchema,$localArray)) && empty(array_diff($localArray,$tableSchema)) ){
                    echo "No chnages to apply in $className \n";
                }else{
                echo "Applying changes to: $className \n";

              
                foreach ($localArray as $column) {
                    $schm = $table->schema[$column];
                    if (!in_array($column, $tableSchema)) {
                        $table->addColumn($classStringName,$column, $schm);
                        echo "Added column '{$column}' to {$className} table.\n";
                    }
                }
                }
              
            }
        }
    } else {
        throw new Exception("Failed to connect to the database.\nMore: Check your config for the database name or if mysql is available");
    }
}catch (PDOException $e) {
    echo "\nPDOException: " . $e->getMessage()."\n";
} catch (Exception $e) {
    echo "\nException: " . $e->getMessage()."\n";
}




function removeItemT($array, $textToSearch) {
    foreach ($array as $key => $value) {
        if (strpos($key, $textToSearch) !== false) {
            unset($array[$key]);
        }
    }

    return $array;
}