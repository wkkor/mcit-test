<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

const USER = [
    'admin' => [
        'pwd' => 'admin123',
        'name' => 'Admin',
    ],
];

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->post('/login', function (Request $request, Response $response) {
        $response = $response->withHeader('Content-Type', 'application/json');

        $post_param = $request->getParsedBody();
        $json_response = [];

        try {
            if (!isset($post_param['username']) || !isset($post_param['pwd'])) {
                throw new Exception('Missing parameter');
            }

            if (!isset(USER[$post_param['username']]) || USER[$post_param['username']]['pwd'] != $post_param['pwd']) {
                throw new Exception('Invalid username or password');
            }

            session_start();
            $session_id = session_id();
            $_SESSION['username'] = $post_param['username'];
            #$response = $response->withoutHeader('set-cookie');
            header_remove('Set-Cookie');

            $json_response['token'] = $session_id;
        } catch (Exception $e) {
            $response = $response->withStatus(400);
            $json_response['message'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($json_response));

        return $response;
    });

    $app->get('/data', function (Request $request, Response $response) {
        $response = $response->withHeader('Content-Type', 'application/json');

        $json_response = [];


        # slim php framework will session_start when there has header "Authorization"
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
            header_remove('Set-Cookie');
        }

        $request_header = $request->getHeaders();

        try {
            if (!isset($request_header['Authorization'][0])) {
                throw new Exception('Invalid credential');
            }

            if (preg_match('/Bearer\s(\S+)/', $request_header['Authorization'][0], $matches)) {
                session_id($matches[1]);
                session_start();
                header_remove('Set-Cookie');

                if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
                    throw new Exception('Invalid credential');
                }

                $json_response['message'] = 'Hello World';
            } else {
                throw new Exception('Invalid credential');
            }
        } catch (Exception $e) {
            $response = $response->withStatus(400);
            $json_response['message'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($json_response));

        return $response;
    });

    $app->get('/catlist', function (Request $request, Response $response) {
        $response = $response->withHeader('Content-Type', 'application/json');

        $get_param = $request->getQueryParams();
        $json_response = [];

        # slim php framework will session_start when there has header "Authorization"
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
            header_remove('Set-Cookie');
        }

        $request_header = $request->getHeaders();

        try {
            if (!isset($request_header['Authorization'][0])) {
                throw new Exception('Invalid credential');
            }

            if (preg_match('/Bearer\s(\S+)/', $request_header['Authorization'][0], $matches)) {
                session_id($matches[1]);
                session_start();
                header_remove('Set-Cookie');

                if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
                    throw new Exception('Invalid credential');
                }

                $cat_api_url = 'https://api.thecatapi.com/v1/breeds';
                $cat_api_key = 'ad5dcefa-6b99-453d-a140-e908b205b389';

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['x-api-key: ' . $cat_api_key]);
                if (!empty($get_param)){
                    #curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($get_param));
                    $cat_api_url .= '?'.http_build_query($get_param);
                }
                
                curl_setopt($ch, CURLOPT_URL, $cat_api_url);

                $curl_response = curl_exec($ch);
                curl_close($ch);
                $json_response['data'] = json_decode($curl_response, false);

            } else {
                throw new Exception('Invalid credential');
            }
        } catch (Exception $e) {
            $response = $response->withStatus(400);
            $json_response['message'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($json_response));

        return $response;
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });
};
