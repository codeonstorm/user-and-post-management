<?php
declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;//
use Slim\Psr7\Response as Res;//
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Slim\Routing\RouteCollectorProxy;
use App\Application\Middleware\AuthMiddleware;
use App\Application\Middleware\SupperAdminCheckMiddleware;
use Slim\Views\Twig;
use Slim\Routing\RouteContext;


return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });



    // Auth route
    $app->get('/login', '\App\Application\Controllers\AuthController:login')->setName('login');
    $app->post('/login_process/', '\App\Application\Controllers\AuthController:login_process')->setName('login_process');
    $app->get('/signup','\App\Application\Controllers\AuthController:signup')->setName('signup');
    $app->post('/signup_process', '\App\Application\Controllers\AuthController:signup_process')->setName('signup_process');
    $app->get('/logout', '\App\Application\Controllers\AuthController:logout')->setName('logout');



    // protected routes
    $app->group('/', function (RouteCollectorProxy $group) use ($app) {


      $app->get('/', function ($request, $response) {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'index.html');
      })->setName('dashboard');




      // user management route only suber admin access it..
      $app->group('/', function (RouteCollectorProxy $group) use ($app) {

        $app->get('/users', '\App\Application\Controllers\UserController:index')->setName('users');
        $app->get('/user/new', '\App\Application\Controllers\UserController:edit_user')->setName('edit_user');
        $app->post('/user/add', '\App\Application\Controllers\UserController:add_user')->setName('add_user');
        $app->get('/user/edit/{id}', '\App\Application\Controllers\UserController:edit_user')->setName('edit_user');
        $app->post('/user/update/', '\App\Application\Controllers\UserController:update_user')->setName('update_user');
        $app->get('/user/delete/{id}', '\App\Application\Controllers\UserController:delete_user')->setName('delete_user');


      })->add(SupperAdminCheckMiddleware::class);




      // post management route
      $app->get('/posts', '\App\Application\Controllers\PostController:index')->setName('posts');
      $app->get('/post/new', '\App\Application\Controllers\PostController:add_post')->setName('add_post');
      $app->post('/post/new/process', '\App\Application\Controllers\PostController:add_post_processs')->setName('add_post_processs');
      $app->get('/post/edit/{slug}', '\App\Application\Controllers\PostController:edit_post')->setName('edit_post');
      $app->post('/post/update', '\App\Application\Controllers\PostController:update_post')->setName('update_post');

      $app->get('/post/delete/{slug}', '\App\Application\Controllers\PostController:delete_post')->setName('delete_post');


    })->add(AuthMiddleware::class);







    // $app->get('/user/{name}', function ($request, $response, $args) {
    //   $view = Twig::fromRequest($request);
    //   return $view->render($response, 'index.html', [
    //       'name' => $args['name']
    //   ]);
    // });

    // $app->get('/date', function ($request, $response) {
    //   $response->getBody()->write('Hello world!');
    //        return $response;
    //  });
};
