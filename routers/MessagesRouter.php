<?php
declare(strict_types=1);
namespace Reut\Routers;

use Reut\Models\MessagesTable;
use Reut\Auth\Auth;
use Slim\App;
use Slim\Routing\RouteCollectorProxy as CollectionProxy;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


class MessagesRouter extends Auth{
    
    protected $config,$jwtAuth;
    public function __construct(App $app,Array $config){
        $this->config = $config;
        parent::__construct($app,$config);
      
    }

    

    protected function genRoutes(){

        $this->app->group('/messages', function (CollectionProxy $group) {


            $group->get('/all', function (Request $request, Response $response) {
                $table = new MessagesTable($this->config);
                $table->findAll();
                $data = $table->paginate();
                $response->getBody()->write(json_encode($data));
                return $response->withHeader('Content-Type', 'application/json');
            });

           
        });
    }

}
