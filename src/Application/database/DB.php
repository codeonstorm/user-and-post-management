<?php
declare(strict_types=1);

namespace App\Application\Database;

use Psr\Container\ContainerInterface;
use App\Application\Settings\SettingsInterface;
use PDO;

class DB
{

    public static $con;

    public function __construct(ContainerInterface $c)
    {
      // code...

      try{

          self::$con = $c->get(PDO::class);

        }catch (PDOException $e){

          die($e->getMessage());
        }

      }




    public static function getInstance()
    {
      if(self::$con){

        return self::$con;
      }

      return $instance = new self();
    }

    public static function newInstance()
    {
      return $instance = new self();
    }



    public function query($query,$data = array(),$data_type = "object")
    {

      $con = self::getInstance();
      $stm = $con->prepare($query);

      $result = false;
      if($stm){
        $check = $stm->execute($data);
        if($check){
          if($data_type == "object"){
            $result = $stm->fetchAll(PDO::FETCH_OBJ);
          }else{
            $result = $stm->fetchAll(PDO::FETCH_ASSOC);
          }

        }
      }

 

      if(is_array($result) && count($result) >0){
        return $result;
      }

      return false;
    }


  }
