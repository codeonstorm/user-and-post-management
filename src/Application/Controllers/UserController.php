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


class UserController extends Controller
{

    public function index(Request $request, Response $response)
    {
      try {
          $conn =  $this->container->get(PDO::class);
          $stmt = $conn->prepare("SELECT * FROM users");
          $stmt->execute();

          $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {
          echo "Error: " . $e->getMessage();
        }
        $conn = null;

      $view = Twig::fromRequest($request);
      return $view->render($response, 'users.html', ['users' => $data]);
    }




    public function edit_user(Request $request, Response $response, $args)
    {


      try {
          $conn =  $this->container->get(PDO::class);
          $stmt = $conn->prepare("SELECT * FROM roles");
          $stmt->execute();

          $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);


            //edit
            if(isset($args['id'])){
              $id=$args['id'];
              $stmt = $conn->prepare("SELECT * FROM users where id='$id'");
              $stmt->execute();
              $user = $stmt->fetch(PDO::FETCH_ASSOC);

              if($user['privileges'] != null)
                  $user['privileges'] =  unserialize($user['privileges']);


              $Arrdata = array('user'=>$user , 'roles'=>$roles);
            }else{
              $Arrdata = array('roles'=>$roles);
            }

        } catch(PDOException $e) {
          echo "Error: " . $e->getMessage();
        }
        $conn = null;
        //
        // echo "<pre>";
        // print_r($Arrdata); die;

      $view = Twig::fromRequest($request);
      return $view->render($response, 'user_frm.html', $Arrdata);
    }



    public function add_user(Request $request, Response $response, $args)
    {
      $data = $request->getParsedBody();

      //validate
      if(!$data['name'] || !$data['email'] || !$data['password']){
        $this->container->get('flash')->addMessage('alert', "Please fill all the fields.");
        $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('edit_user');
        return $response->withStatus(302)->withHeader('Location', $url);
      }

      //privileges
      $arrPrivileges  = array();;
      if(isset($data['read'])){
        array_push($arrPrivileges, "read");
      }
      if(isset($data['update'])){
        array_push($arrPrivileges, "update");
      }
      if(isset($data['delete'])){
        array_push($arrPrivileges, "delete");
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
          $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('users');
          return $response->withStatus(302)->withHeader('Location', $url);
        }

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, rank, status, privileges, added_on)
        VALUES (:name, :email, :password, :rank, :status, :privileges, :added_on)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':rank', $rank);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':privileges', $privileges);
        $stmt->bindParam(':added_on', $added_on);

        // insert a row
        $name = $data['name'];
        $email = $data['email'];
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $rank = $data['rank'];
        $status = 1;
        $privileges = serialize($arrPrivileges);
        $added_on = date('Y-m-d h:m:s');
        $stmt->execute();

        echo "New records created successfully";
      } catch(PDOException $e) {
        $this->container->get('flash')->addMessage('warning', "Something goes goes wrong $e->getMessage().");
        $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('edit_user');
        return $response->withStatus(302)->withHeader('Location', $url);
      }

      $conn = null;

      $this->container->get('flash')->addMessage('success', "New user created successfully.");
      $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('users');
      return $response->withStatus(302)->withHeader('Location', $url);
    }



    public function update_user(Request $request, Response $response)
    {
      $data = $request->getParsedBody();
      $id = $data['id'];

      //validate
      if(!$data['name'] || !$data['email']){
        $this->container->get('flash')->addMessage('alert', "Please fill all the fields.");
        $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('users');
        return $response->withStatus(302)->withHeader('Location', $url);
      }



      //privileges
      $arrPrivileges  = array();;
      if(isset($data['read'])){
        array_push($arrPrivileges, "read");
      }

      if(isset($data['update'])){
        array_push($arrPrivileges, "update");
      }
      if(isset($data['delete'])){
        array_push($arrPrivileges, "delete");
      }



      try {

        $conn = $this->container->get(PDO::class);

        $stmt = $conn->prepare("SELECT email FROM users WHERE id = '$id'");
        $stmt->execute();

        $user_check = $stmt->fetch(PDO::FETCH_ASSOC);


        if(!isset($user_check['email'])){
          $this->container->get('flash')->addMessage('alert', "This user does'nt exists.");
          $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('users');
          return $response->withStatus(302)->withHeader('Location', $url);
        }else{

          if($data['email'] != $user_check['email']){
            $email = $data['email'];
            $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM users WHERE email = '$email'");
            $stmt->execute();
            $usert_slug_check = $stmt->fetch(PDO::FETCH_ASSOC);

            if($usert_slug_check['count']){
              $this->container->get('flash')->addMessage('alert', "This email already registered.");
              $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('users');
              return $response->withStatus(302)->withHeader('Location', $url);
             }
          }

        }



          $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM users WHERE id = '$id'");
          $stmt->execute();

          $user = $stmt->fetch(PDO::FETCH_ASSOC);

          if($user['count']){
            $stmt = $conn->prepare("UPDATE users SET name=:name, email=:email, rank=:rank, privileges=:privileges WHERE id = :id");

            $updatedData = [
              'name' => $data['name'],
              'email' => $data['email'],
              'rank' => $data['rank'],
              'privileges' => serialize($arrPrivileges),
              'id' => $id,
            ];

              $stmt->execute($updatedData);
          }


        } catch(PDOException $e) {
          echo "Error: " . $e->getMessage();
          $this->container->get('flash')->addMessage('warning', "Somthing goes wrong, please try again.");
          $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('users');
          return $response->withStatus(302)->withHeader('Location', $url);
        }
        $conn = null;

        $this->container->get('flash')->addMessage('success', "User data updated successfully.");
        $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('users');
        return $response->withStatus(302)->withHeader('Location', $url);
    }



    public function delete_user(Request $request, Response $response, $args)
    {


      $id = $args['id'];

      try {

        //delete data
        $conn = $this->container->get(PDO::class);

        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM users WHERE id = '$id'");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$user['count']){
          $this->container->get('flash')->addMessage('alert', "User does't exists.");
          $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('users');
          return $response->withStatus(302)->withHeader('Location', $url);
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE id = '$id'");
        $stmt->execute();


      } catch(PDOException $e) {
        $this->container->get('flash')->addMessage('warning', "Something goes wrong, please try again.");
        $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('users');
        return $response->withStatus(302)->withHeader('Location', $url);
      }

      $conn = null;

      $this->container->get('flash')->addMessage('success', "User deleted successfully.");
      $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('users');
      return $response->withStatus(302)->withHeader('Location', $url);

  }

}
