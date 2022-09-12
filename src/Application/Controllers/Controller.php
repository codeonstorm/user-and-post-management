<?php

namespace App\Application\Controllers;

use Psr\Container\ContainerInterface;
use PDO;

class Controller
{

    protected $container;

    public function __construct(ContainerInterface $c)
    {
      $this->container = $c;
    }


    /*
    * some auth related function
    */

    public function haveAccess($permission)
    {
       if(!isset($_SESSION['USER'])){
         return false;
       }

       if($_SESSION['USER']['RANK']=='super_admin'){
          return true;
        }

      //  check privillages
        $user_id = $_SESSION['USER']['ID'];
        $conn =  $this->container->get(PDO::class);
        $stmt = $conn->prepare("SELECT privileges FROM users WHERE id='$user_id'");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $arrPermission = unserialize($user['privileges']);

        if($arrPermission){
          if(in_array($permission, $arrPermission)){
            return true;
          }
        }

        return false;
    }



	// public function have_privilege($row)//id post
}
?>
