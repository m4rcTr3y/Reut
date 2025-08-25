<?php

/** please don't edit this file unless you understand what you doing or till the documentation is out on how this works */

declare(strict_types=1);
namespace Reut\Router;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require dirname(__DIR__).'/../config.php';

use Slim\App;
use Slim\Routing\RouteCollectorProxy;

class ModelRouter{
    /**
     * Route this is responsilbe for adding the default crude operations on all the models/database tables you created
     * 
     * @param App $app slim app
     * @param array $config  databse config is also required
     * @param string $modelsDir=/models the directory where your database classes are defined defualts to /models 
     * 
    */
    public static function Route(App $app,Array $config,String $modelsDir='/models'){

       
            // global $config;
           
            $app->group('/api', function (\Slim\Routing\RouteCollectorProxy $group) use ($modelsDir) {
                $modelsPath = dirname(__DIR__) . '/..'.$modelsDir;
                $modelClasses = array_diff(scandir($modelsPath), ['.', '..']);
                
                if($modelClasses){

                    foreach ($modelClasses as $classFile) {
                        $className = pathinfo($classFile, PATHINFO_FILENAME);
                        // $tableName = $className);
            
                        $classNameParts = explode('Table', $className);
                        $tableName = $classNameParts[0];

                        
                
                        $group->group("/{$tableName}", function (\Slim\Routing\RouteCollectorProxy $tableGroup) use ($className, $modelsPath) {
                           global $config;
                            require_once $modelsPath . '/' . $className . '.php';
                            $cls = ucfirst($className);
                            $instance = (Object) new  $cls($config);
                
                            $tableGroup->get('/all', function (Request $request, Response $response) use ($instance) {
                                $params =$request->getQueryParams();
                                $page = isset($params['page']) ? $params['page'] :1;
                                $limit = isset($params['limit']) ? $params['limit'] :5;
                                $data = $instance->findAll()->paginate(intval($page),intval($limit));
                                $response->getBody()->write(json_encode($data));
                                return $response->withHeader('Content-Type', 'application/json');
                            });
                
                            $tableGroup->get('/find/{id}', function (Request $request, Response $response, $args) use ($instance) {
                                $id = $args['id'];
                                $data = (Object) $instance->findOne(['id' => $id]);
                                $response->getBody()->write(json_encode($data->results));
                                return $response->withHeader('Content-Type', 'application/json');
                            });
                
                            $tableGroup->post('/add', function (Request $request, Response $response) use ($instance) {
                                $input = $request->getParsedBody();
                                $result = $instance->addOne($input);
                                $response->getBody()->write(json_encode(['status' => $result]));
                                return $response->withHeader('Content-Type', 'application/json');
                            });
                
                            $tableGroup->post('/update/{id}', function (Request $request, Response $response, $args) use ($instance) {
                                $id = $args['id'];
                                $input = $request->getParsedBody();
                                $result = $input;
                                $result = $instance->update($input, ['id' => $id]);
                                $response->getBody()->write(json_encode(['status' => $result]));
                                return $response->withHeader('Content-Type', 'application/json');
                            });
                
                            $tableGroup->delete('/delete/{id}', function (Request $request, Response $response, $args) use ($instance) {
                                $id = $args['id'];
                                $result = $instance->delete(['id' => $id]);
                                $response->getBody()->write(json_encode(['status' => $result]));
                                return $response->withHeader('Content-Type', 'application/json');
                            });
                        });
                    }
                }else{
                    return ['Error'=>'No models found in '.$modelsPath.' Please check if you provided the right path or move them to a'.dirname(__DIR__).'..'.'/models'];
                }
            });
        
    }
}


