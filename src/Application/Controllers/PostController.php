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


class PostController extends Controller
{


    public function index(Request $request, Response $response)
    {

      try {
          $conn =  $this->container->get(PDO::class);

          if($this->haveAccess('read')){
            $stmt = $conn->prepare("SELECT  posts.*, users.name FROM posts, users WHERE posts.owner = users.id");
            $stmt->execute();
          }
          else{
              $user_id = $_SESSION['USER']['ID'];
              $stmt = $conn->prepare("SELECT  posts.*, users.name FROM posts, users WHERE posts.owner = users.id AND owner = '$user_id'");
              $stmt->execute();
          }

          $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch(PDOException $e) {
          echo "Error: " . $e->getMessage();
        }
        $conn = null;


      $view = Twig::fromRequest($request);
      return $view->render($response, 'posts.html',['posts' => $data]);
    }



    public function add_post(Request $request, Response $response)
    {
      $view = Twig::fromRequest($request);
      return $view->render($response, 'post_frm.html');
    }



    //
    public function add_post_processs(Request $request, Response $response)
    {

        $data = $request->getParsedBody();

        //validate
        if(!$data['title'] || !$data['slug'] || !$data['desc']){
          $this->container->get('flash')->addMessage('alert', "Please fill all the fields.");
          $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('add_post');
          return $response->withStatus(302)->withHeader('Location', $url);
        }


          try {

            //insert data
            $conn = $this->container->get(PDO::class);
            $slug = $data['slug'];
            $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM posts WHERE slug = '$slug'");
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if($user['count']){
              $this->container->get('flash')->addMessage('alert', "This slug is already exists. please use another");
              $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('add_post');
              return $response->withStatus(302)->withHeader('Location', $url);
            }

            $stmt = $conn->prepare("INSERT INTO posts (title, slug, description, owner, contribute_by, created_on)
            VALUES (:title, :slug, :description, :owner, :contribute_by, :created_on)");
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':slug', $slug);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':owner', $owner);
            $stmt->bindParam(':contribute_by', $contribute_by);
            $stmt->bindParam(':created_on', $created_on);

            // insert a row
            $title = $data['title'];
            $slug = $data['slug'];
            $description = $data['desc'];
            $owner = $_SESSION['USER']['ID'];
            $contribute_by = Null;
            $created_on = date('Y-m-d h:m:s');

            $stmt->execute();

            $this->container->get('flash')->addMessage('message', "New records created successfully");

          } catch(PDOException $e) {
            $this->container->get('flash')->addMessage('warning', "Something goes wrong.  $e->getMessage()");
            $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('add_post');
            return $response->withStatus(302)->withHeader('Location', $url);
          }

          $conn = null;

          $this->container->get('flash')->addMessage('success', "New post created successfully.");
          $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('posts');
          return $response->withStatus(302)->withHeader('Location', $url);
    }




    public function edit_post(Request $request, Response $response, $args)
    {

      if(!$this->haveAccess('update')){
        $this->container->get('flash')->addMessage('info', "You have't no permission to edit this post, please contact your administrator.");
        $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('posts');
        return $response->withStatus(302)->withHeader('Location', $url);
      }

      $slug = $args['slug'];

        try {

          //insert data
          $conn = $this->container->get(PDO::class);

          $stmt = $conn->prepare("SELECT  * FROM posts WHERE slug='$slug'");
          $stmt->execute();
          $data = $stmt->fetch(PDO::FETCH_ASSOC);


        } catch(PDOException $e) {
          echo "Error: " . $e->getMessage();
        }

        $conn = null;

      $view = Twig::fromRequest($request);
      return $view->render($response, 'post_frm.html', ['post' => $data]);
    }



    public function delete_post(Request $request, Response $response, $args)
    {
      if(!$this->haveAccess('delete')){
        $this->container->get('flash')->addMessage('info', "You have't no permission to delete post, please contact your administrator.");
        $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('posts');
        return $response->withStatus(302)->withHeader('Location', $url);
      }

      $slug = $args['slug'];

      try {

        //delete data
        $conn = $this->container->get(PDO::class);
        // prepare sql and bind parameters
        $stmt = $conn->prepare("DELETE FROM posts WHERE slug = '$slug'");
        $stmt->execute();


      } catch(PDOException $e) {
        $this->container->get('flash')->addMessage('warning', "Something goes wrong, please try again.");
        $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('posts');
        return $response->withStatus(302)->withHeader('Location', $url);
      }

      $conn = null;

      $this->container->get('flash')->addMessage('success', "Post delete successfully.");
      $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('posts');
      return $response->withStatus(302)->withHeader('Location', $url);

  }


    public function update_post(Request $request, Response $response)
    {

      if(!$this->haveAccess('update')){
        $this->container->get('flash')->addMessage('info', "You have't no permission to delete post, please contact your administrator.");
        $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('posts');
        return $response->withStatus(302)->withHeader('Location', $url);
      }

      $data = $request->getParsedBody();


      $id = $data['id'];
      $slug = $data['slug'];



      try {
          $conn =  $this->container->get(PDO::class);
          $stmt = $conn->prepare("SELECT slug FROM posts WHERE id = '$id'");
          $stmt->execute();


          $post_check = $stmt->fetch(PDO::FETCH_ASSOC);


          if(!isset($post_check['slug'])){
            $this->container->get('flash')->addMessage('alert', "Post does'nt exists.");
            $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('posts');
            return $response->withStatus(302)->withHeader('Location', $url);
          }else{

            if($data['slug'] != $post_check['slug']){

              $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM posts WHERE slug = '$slug'");
              $stmt->execute();
              $post_slug_check = $stmt->fetch(PDO::FETCH_ASSOC);

              if($post_slug_check['count']){
                $this->container->get('flash')->addMessage('alert', "Please change post slug, it's already exists.");
                $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('posts');
                return $response->withStatus(302)->withHeader('Location', $url);
               }
            }

          }


              //update_post
               $stmt = $conn->prepare("UPDATE posts SET title=:title, slug=:slug, description=:description, contribute_by=:contribute_by WHERE id = :id");

                    $updatedData = [
                      'title' => $data['title'],
                      'slug' => $data['slug'],
                      'description' => $data['desc'],
                      'contribute_by' => $_SESSION['USER']['ID'],
                      'id' => $id,
                    ];

                $stmt->execute($updatedData);






        } catch(PDOException $e) {
          echo "Error: " . $e->getMessage();
          $this->container->get('flash')->addMessage('warning', "Something goes wrong, please try again.");
          $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('posts');
          return $response->withStatus(302)->withHeader('Location', $url);
        }
        $conn = null;

        $this->container->get('flash')->addMessage('success', "Post updated successfully.");
        $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('posts');
        return $response->withStatus(302)->withHeader('Location', $url);
    }
}
