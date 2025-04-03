<?php

declare(strict_types=1);

namespace Reut\Routers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy as CollectionProxy;
//load modals
use Reut\Models\AccountsTable;


class AuthRouter
{

    public $group,$config, $pdo, $jwtAuth;
    private $middleware;

    public function __construct(CollectionProxy $group, $config, \PDO $pdo, $jwtAuth)
    {
        $this->group = $group;
        $this->config = $config;
        $this->pdo = $pdo;
        $this->jwtAuth = $jwtAuth;
     
        
    }
    public function __invoke()
    {
        $group = $this->group;
        $config = $this->config;
        $pdo = $this->pdo;
        $jwtAuth = $this->jwtAuth;

        $group->post('/login', function (Request $request, Response $response, $args) use ($jwtAuth, $pdo) {
            $data = $request->getParsedBody();

            // Fetch user from database
            $stmt = $pdo->prepare('SELECT userID, id, password FROM accounts WHERE email = ?');
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user && password_verify($data['password'], $user['password'])) {
                // Generate tokens
                $token = $jwtAuth->generateToken($user['userID']);
                $refreshToken = $jwtAuth->generateRefreshToken($user['userID']);

                $response->getBody()->write(json_encode([
                    'token' => $token,
                    'refresh_token' => $refreshToken,
                    'user' => $user['userID'],
                ]));
            } else {
                $response->getBody()->write(json_encode(['error' => 'Invalid credentials']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

            return $response->withHeader('Content-Type', 'application/json');
        })->add(function($request,$handler){return $handler->handle($request);});


        $group->post('/adduseraccount', function (Request $request, Response $response) {
            global $config;
            $posted = $request->getParsedBody();
            $class = new AccountsTable($config);
            $fn = $class->saveUserAccount($posted);
            $response->getBody()->write(json_encode($fn));
            return $response->withHeader('Content-Type', 'application/json');
        });

        //get user after login
        $group->get('/getuser', function (Request $request, Response $response, $args) use ($pdo, $config) {
            $userID = $request->getQueryParams()['id'];
            //global $config;
            if ($userID) {

                $stmt = $pdo->prepare('SELECT * FROM accounts WHERE userID = ?');
                $stmt->execute([$userID]);
                $user = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($user) {
                    $class = new AccountsTable($config);
                    $data = $class->getAdminUser($userID);

                    $response->getBody()->write(json_encode([$data]));
                    return $response->withHeader('Content-Type', 'application/json');
                } else {
                    $response->getBody()->write(json_encode(['error' => 'user does not exist']));
                    return  $response->withHeader('Content-Type', 'application/json');
                }
            } else {
                $response->getBody()->write(json_encode(['error' => 'you are not logged in']));
                return  $response->withHeader('Content-Type', 'application/json');
            }
        })->add($this->jwtAuth);

        $group->post('/refresh-token', function (Request $request, Response $response, $args)  {
            $data = $request->getParsedBody();

            $userId = $data['userId']; // This should be the user ID associated with the refresh token
            $refreshToken = $data['refresh_token'];

            if (!$this->jwtAuth->validateRefreshToken($userId, $refreshToken)) {
                $response->getBody()->write(json_encode(['error' => 'Invalid refresh token']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

            // Generate a new JWT
            $newToken = $this->jwtAuth->generateToken($userId, 'admin'); // Adjust role as necessary

            $response->getBody()->write(json_encode(['token' => $newToken]));
            return $response->withHeader('Content-Type', 'application/json');
        });
    }
}
