<?php
declare(strict_types=1);

namespace App\Application\Controllers;

use App\Application\Controllers\Controller;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
//flash
use Slim\Routing\RouteContext;
use Slim\Views\Twig;
use PDO;


class AuthController extends Controller
{

    public function login(Request $request, Response $response, $args)
    {
      $view = Twig::fromRequest($request);
      return $view->render($response, 'login.html');
    }




    public function login_process(Request $request, Response $response)
    {


      $data = $request->getParsedBody();
      $email = $data['email'];
      $password = $data['password'];

      //validate
      if(!$data['email'] || !$data['password']){
        $this->container->get('flash')->addMessage('alert', "Please fill all the fields.");
        $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('login');
        return $response->withStatus(302)->withHeader('Location', $url);
      }




      try {

          $conn =  $this->container->get(PDO::class);
          $stmt = $conn->prepare("SELECT * FROM users WHERE email='$email'");
          $stmt->execute();

          $user = $stmt->fetch(PDO::FETCH_ASSOC);

          if(empty($user)){
            $this->container->get('flash')->addMessage('alert', "Please enter valid email or password");
            $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('login');
            return $response->withStatus(302)->withHeader('Location', $url);
          }

          if($user['status']==0){
            $this->container->get('flash')->addMessage('info', "Please contact your administration to activate your account.");
            $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('login');
            return $response->withStatus(302)->withHeader('Location', $url);
          }

          //check for password
          if(password_verify($password ,$user['password']))
          {
            $rank = $user['rank'];
            $stmt = $conn->prepare("SELECT rank FROM roles WHERE id='$rank'");
            $stmt->execute();
            $rank = $stmt->fetch(PDO::FETCH_ASSOC)['rank'];


             $_SESSION['USER']['ID'] = $user['id'];
             $_SESSION['USER']['NAME'] = $user['name'];
             $_SESSION['USER']['EMAIL'] = $user['email'];
             $_SESSION['USER']['RANK'] = $rank ;

          }else{
            $this->container->get('flash')->addMessage('alert', "Please enter valid email or password");
            $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('login');
            return $response->withStatus(302)->withHeader('Location', $url);
          }

        } catch(PDOException $e) {

          $this->container->get('flash')->addMessage('warning', "Something goes wrong, please try again. $e->getMessage()");
          $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('login');
          return $response->withStatus(302)->withHeader('Location', $url);
        }
        $conn = null;

        $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('dashboard');
        return $response->withStatus(302)->withHeader('Location', $url);

        }



  //logout
  public function logout(Request $request, Response $response)
	{

		if(isset($_SESSION['USER']))
		{
			unset($_SESSION['USER']);
		 }

     $this->container->get('flash')->addMessage('success', "Logout success.");
     $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('login');
     return $response->withStatus(302)->withHeader('Location', $url);
	}





  //signup
  public function signup(Request $request, Response $response)
  {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'signup.html');
  }


  //signup process
  public function signup_process(Request $request, Response $response)
  {
    $data = $request->getParsedBody();

    //validate
    if(!$data['name'] || !$data['email'] || !$data['password']){
      $this->container->get('flash')->addMessage('alert', "Please fill all the fields.");
      $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('signup');
      return $response->withStatus(302)->withHeader('Location', $url);
    }


    try {

      //insert data
      $conn = $this->container->get(PDO::class);

      $email = $data['email'];
      $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM users WHERE email = '$email'");
      $stmt->execute();
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if($user['count']){
        $this->container->get('flash')->addMessage('alert', "This email is already exists. please use another");
        $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('signup');
        return $response->withStatus(302)->withHeader('Location', $url);
      }

      $stmt = $conn->prepare("INSERT INTO users (name, email, password, rank, status, added_on)
      VALUES (:name, :email, :password, :rank, :status, :added_on)");
      $stmt->bindParam(':name', $name);
      $stmt->bindParam(':email', $email);
      $stmt->bindParam(':password', $password);
      $stmt->bindParam(':rank', $rank);
      $stmt->bindParam(':status', $status);
      $stmt->bindParam(':added_on', $added_on);

      // insert a row
      $name = $data['name'];
      $email = $data['email'];
      $password = password_hash($data['password'], PASSWORD_DEFAULT);
      $rank = 2;
      $status = 1;
      $added_on = date('Y-m-d h:m:s');
      $stmt->execute();

      echo "New records created successfully";
    } catch(PDOException $e) {
      $this->container->get('flash')->addMessage('alert', "Something goes wrong, please try again. $e->getMessage()");
      $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('signup');
      return $response->withStatus(302)->withHeader('Location', $url);
    }

    $conn = null;

    $this->container->get('flash')->addMessage('success', "Registration processs success, Please login to start your session.");
    $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('login');
    return $response->withStatus(302)->withHeader('Location', $url);
  }




}
