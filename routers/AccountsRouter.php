<?php
declare(strict_types=1);
namespace Reut\Routers;

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Reut\Auth\NoAuth;

//import the Accounts model here

use Reut\Models\AccountsTable;

// NoAuth class implements endpoints without authentication, authenticaton can be changed using the Auth class
class AccountsRouter extends NoAuth {
    protected $config;
     public function __construct(App $app,Array $config){
        $this->config = $config;
        parent::__construct($app);
    
    }

    protected function genRoutes() {
        $this->app->group('/accounts', function (RouteCollectorProxy $group) {

            $instance = new AccountsTable($this->config);

            //get all Accountss from database
            $group->get( '/all', function (Request $request, Response $response) use ($instance) {
                $params = $request->getQueryParams();
                $page = $params['page'] ?? 1;
                $limit = $params['limit'] ?? 20;
                $data = $instance->findAll()->paginate((int)$page, (int)$limit);
                $response->getBody()->write(json_encode($data));
                return $response->withHeader('Content-Type', 'application/json');
            });

            //Get single Accounts from the table " http://endpoint/Accounts/find/id
            $group->get('/find',function (Request $request, Response $response, $args) use ($instance) {
                $id = $args['id'];
                $data = $instance->findOne(['id' => $id]);
                $response->getBody()->write(json_encode($data->results));
                return $response->withHeader('Content-Type', 'application/json');
            });
            $group->post('/add', function (Request $request, Response $response) use ($instance) {
                $input = $request->getParsedBody();
                $result = $instance->addOne($input);
                $response->getBody()->write(json_encode(['status' => $result]));
                return $response->withHeader('Content-Type', 'application/json');
            });

            //Update single Accounts from the table " http://endpoint/Accounts/update/id
            $group->put( 'update',function (Request $request, Response $response, $args) use ($instance) {
                $id = $args['id'];
                $input = $request->getParsedBody();
                $result = $instance->update($input, ['id' => $id]);
                $response->getBody()->write(json_encode(['status' => $result]));
                return $response->withHeader('Content-Type', 'application/json');
            });

            //delete single Accounts from the table " http://endpoint/Accounts/delete/id
            $group->delete('delete', function (Request $request, Response $response,$args) use ($instance) {
                $id = $args['id'];
                $result = $instance->delete(['id' => $id]);
                $response->getBody()->write(json_encode(['status' => $result]));
                return $response->withHeader('Content-Type', 'application/json');
            });


        });
    }
}