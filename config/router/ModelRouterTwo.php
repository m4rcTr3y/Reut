<?php

/** please don't edit this file unless you understand what you doing or till the documentation is out on how this works */

declare(strict_types=1);
namespace Reut\Router;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Models\School;
require dirname(__DIR__).'/../config.php';
use Reut\Models;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;



class ModelRouterTwo
{
    /**
     * This method is responsible for adding the default CRUD operations on all the models/database tables you created.
     *
     * @param App $app Slim app
     * @param array $config Database config is also required
     * @param string $modelsDir The directory where your database classes are defined, defaults to /models
     */
    public static function Route(App $app, array $config, string $modelsDir = '/models',$namespacePrefix="Josoi\Models"){
        $app->group('/api', function (RouteCollectorProxy $group) use ($modelsDir, $config,$namespacePrefix) {
            $modelsPath = dirname(__DIR__) . '/../' . $modelsDir;

            if (!is_dir($modelsPath) || !is_readable($modelsPath)) {
                return $group->any('/{routes:.+}', function (Request $request, Response $response) use ($modelsPath) {
                    $response->getBody()->write(json_encode(['error' => 'Invalid models directory: ' . $modelsPath]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
                });
            }

            $modelClasses = array_diff(scandir($modelsPath), ['.', '..']);

            if (empty($modelClasses)) {
                return $group->any('/{routes:.+}', function (Request $request, Response $response) use ($modelsPath) {
                    $response->getBody()->write(json_encode(['error' => 'No models found in ' . $modelsPath]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
                });
            }

            foreach ($modelClasses as $classFile) {
                $className = pathinfo($classFile, PATHINFO_FILENAME);
                
                $classNameParts = explode('Table', $className);
                $tableName = $classNameParts[0];

                $fullClassName = $namespacePrefix.'\\'. ucfirst($className);

              //  $group->group("/{$tableName}", function (RouteCollectorProxy $tableGroup) use ($className, $modelsPath, $config,$tableName) {
                $group->group("/{$tableName}", function (RouteCollectorProxy $tableGroup) use ($fullClassName,$config,$tableName) {
                    //require_once $modelsPath . '/' . $className . '.php';

                    //$cls = ucfirst($className);
                    $cls = $fullClassName;
                    //$instance = new $cls($config)
                    $instance = new $cls($config);

                    $disabledRoutes = $instance->disabledRoutes ?? [];

                    // Define a default response for disabled routes
                    $defaultHandler = function (Request $request, Response $response) {
                        $response->getBody()->write(json_encode(['error' => 'This route is unavailable.']));
                        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
                    };

                    // Add each route, checking if it's disabled
                    $routes = [
                        'all' => function (Request $request, Response $response) use ($instance) {
                            $params = $request->getQueryParams();
                            $page = $params['page'] ?? 1;
                            $limit = $params['limit'] ?? 20;
                            $data = $instance->findAll()->paginate((int)$page, (int)$limit);

                            $response->getBody()->write(json_encode($data));
                            return $response->withHeader('Content-Type', 'application/json');
                        },
                        'find' => function (Request $request, Response $response, $args) use ($instance) {
                            $id = $args['id'];
                            $data = $instance->findOne(['id' => $id]);

                            $response->getBody()->write(json_encode($data->results));
                            return $response->withHeader('Content-Type', 'application/json');
                        },
                        'add' => function (Request $request, Response $response) use ($instance) {
                            $input = $request->getParsedBody();
                            $result = $instance->addOne($input);
                            $response->getBody()->write(json_encode(['status' => $result]));
                            return $response->withHeader('Content-Type', 'application/json');
                        },
                        'update' => function (Request $request, Response $response, $args) use ($instance) {
                            $id = $args['id'];
                            $input = $request->getParsedBody();
                            $result = $instance->update($input, ['id' => $id]);

                            $response->getBody()->write(json_encode(['status' => $result]));
                            return $response->withHeader('Content-Type', 'application/json');
                        },
                        'delete' => function (Request $request, Response $response, $args) use ($instance) {
                            $id = $args['id'];
                            $result = $instance->delete(['id' => $id]);

                            $response->getBody()->write(json_encode(['status' => $result]));
                            return $response->withHeader('Content-Type', 'application/json');
                        },
                      


                    ];

                    foreach ($routes as $route => $handler) {
                        if (in_array($route, $disabledRoutes)) {
                            $tableGroup->map(['GET','POST', 'DELETE'], "/{$route}[/{id}]", $defaultHandler);
                        } else {
                            $tableGroup->map(['GET','POST', 'DELETE'], "/{$route}[/{id}]", $handler);
                        }
                    }
                });
            }
        });
    }
}


