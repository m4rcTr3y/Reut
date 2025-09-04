<?php
declare(strict_types=1);

namespace Reut\Models;

use Reut\DB\DataBase;
use Reut\DB\Types\Varchar;
use Reut\DB\Types\Integer;

// This class represents the Cars table in the database, extending the DataBase class for database operations
class CarsTable extends DataBase
{
    // Constructor initializes the model with configuration and table settings
    // @param array $config Database configuration settings
    public function __construct(array $config)
    {
        // Initialize the parent DataBase class with:
        // - $config: Database connection settings
        // - []: Initial empty columns array (to be populated below)
        // - 'Cars': The table name
        // - hasRelationships: Whether the table has relationships
        // - []: File fields array (for file uploads, if any)
        // - ['all']: Disabled routes array (routes to disable for this model)
        parent::__construct(
            $config,
            [],
            'Cars',
            true,
            [],
            ['all']
        );

        // Define table columns with their properties
        // id: Auto-incrementing primary key
        $this->addColumn('id', new Integer(
            false, // Not nullable
            true,  // Is primary key
            true,  // Auto-increment
            null   // Default value
        ));

        // TODO: Add your custom column definitions here

        // TODO: Add your relationship definitions here (e.g., hasMany, belongsTo)
    }

    // TODO: Add your custom methods here (e.g., custom queries, business logic)
}