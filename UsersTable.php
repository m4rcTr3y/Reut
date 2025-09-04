<?php
declare(strict_types=1);

namespace Reut\Models;

use Reut\DB\DataBase;
use Reut\DB\Types\Varchar;
use Reut\DB\Types\Integer;

class UsersTable extends DataBase
{
    public function __construct($config)
    {
        parent::__construct($config, [], 'Users', true, 0, ['all']);

        //add columns
        $this->addColumn('id', new Integer(
            false,
            true,
            true,
            null
        ));
        $this->addColumn(
            'name',
            new Varchar(
                40,
                true,
                null,
                false
            )
        );
    }
}