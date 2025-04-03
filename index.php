<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__.'/config.php';

use Reut\Routers\AuthRouter;
use Reut\Routers\ContentRouter;
use Reut\Routers\EmailsRouter;
use Reut\Routers\MessagesRouter;
use Slim\Psr7\Response as SlimResponse;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;
use Dotenv\Dotenv;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
//mine
use Reut\Middleware\JwtAuth;
use Reut\Router\ModelRouterTwo;

// use Reut\DB\DataBase;

date_default_timezone_set('Africa/Nairobi');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers:Access-Control-Allow-Headers,Content-Type,X-Requested-With,Authorization,Access-Control-Allow-Methods');


$app = AppFactory::create();

$twig = Twig::create(__DIR__.'/views',[
    'cache'=>false
    ]);

$app->add(TwigMiddleware::create($app,$twig));

$app->addBodyParsingMiddleware();
//load default database functionality routes
$rout = new ModelRouterTwo();
$rout->Route($app,$config);
//PDO Connection creator




// Create JwtAuth instance
//$jwtAuth = new JwtAuth($secretKey, $pdo);

new MessagesRouter($app,$config);




//render html documents
$app->get('/', function (Request $request, Response $response) use ($config,$twig) {
  
  
    $response = $twig->render($response,'index.html');
   
    return $response->withHeader('Content-Type', 'text/html');
});





//error handler 
$errorHandler = $app->addErrorMiddleware(true, true, true);
$errorHandler->setErrorHandler(HttpNotFoundException::class,function(Request $request,Throwable $exception,bool $displayErrorDetails){
    $response = new SlimResponse();
    $response->getBody()->write('notfound');

    return $response->withStatus(404);
});


$app->options('/{routes:.+}', function (Request $request,  $response) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withStatus(204);
});



$app->run();

