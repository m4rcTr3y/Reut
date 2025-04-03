<?php

declare(strict_types=1);

namespace Reut\DB\Creator;

class DatabaseCreator{

    public static function Generate(){
        global $argv;
        // $data = $argc;
        if ($argv < 2) {
            echo "\nUsage: php script.php <command>\n";
            echo "Commands:\n";
            echo "  create  - Initial start of project\n";
            echo "  update  - Update tables\n";
            exit(1);
        }
        
        $command = (String) $argv[1];
        
        switch ($command) {
            case 'create':
                require dirname(__DIR__). '/migrate.php';
                break;
            case 'update':
                require dirname(__DIR__) . '/update.php';
                break;
            default:
                echo "Invalid command. Usage: php script.php <command>\n";
                echo "Commands:\n";
                echo "  create  - Initial start of project\n";
                echo "  update  - Update tables\n";
                exit(1);
        }
    }




}