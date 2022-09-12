<?php
declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as Res;


class SupperAdminCheckMiddleware implements Middleware
{

  public function process(Request $request, RequestHandler $handler): Response
  {

    if(isset($_SESSION['USER']) && $_SESSION['USER']['RANK'] == 'super_admin'){

      return $handler->handle($request);

    }

    $response = new Res();
    return $response
    ->withHeader('Location', '/login')
    ->withStatus(302);

  }

}
