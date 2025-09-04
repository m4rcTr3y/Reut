<?php
use Slim\App as App; 
use Reut\Routers\AccountsRouter;
use Reut\Routers\CarsRouter;

return function (App $app,Array $config) {
 new AccountsRouter($app,$config);
 new CarsRouter($app,$config);

};