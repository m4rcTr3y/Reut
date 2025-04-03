<?php
require __DIR__ . '/vendor/autoload.php';
use Reut\DB\Creator\DatabaseCreator;

$newDB = new DatabaseCreator();

$newDB->Generate();