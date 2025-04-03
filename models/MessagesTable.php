<?php
declare(strict_types=1);
namespace Reut\Models;
use Reut\DB\DataBase;
use Reut\DB\Types\Integer;

class MessagesTable extends DataBase{
    public function __construct(array $config){
        parent::__construct($config, [],'Messages',true,0,['all']);

        $this->addColumn('id',new Integer(false,null,true,true));
       
    }
}