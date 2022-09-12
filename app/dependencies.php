<?php
declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
//flash
use Slim\Flash\Messages;
use Slim\Routing\RouteContext;


return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        'flash' => function () {
              $storage = [];
              return new Messages($storage);
          },

        PDO::class => function (ContainerInterface $c) {

          $settings = $c->get(SettingsInterface::class);

          $dbSettings = $settings->get('db');

          $host = $dbSettings['host'];
          $dbname = $dbSettings['database'];
          $username = $dbSettings['username'];
          $password = $dbSettings['password'];
          $charset = $dbSettings['charset'];
        //  $flags = $dbSettings['flags'];
          $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
          return new PDO($dsn, $username, $password);
        },
        'access' => function (ContainerInterface $c) {

               return function($rank = 'user', $post_owner_id = 0, $isPermissionTo = '') use($c) {


                  if(!isset($_SESSION['USER']))
                  {
                    return false;
                  }




                  $logged_in_rank = $_SESSION['USER']['RANK'];

                  $RANK['super_admin'] 	= ['super_admin','admin'];
                  $RANK['admin'] 			= ['admin', 'user'];
                  $RANK['user'] 			= ['user'];


                  if(!isset($RANK[$logged_in_rank]))
                  {
                    return false;
                  }



                  		if(in_array($rank,$RANK[$logged_in_rank]))
                  		{
                  			//	return true;
                  			if($logged_in_rank=='super_admin'){
                  				return true;
                  			}


                  			if($logged_in_rank=='admin'){

                          // is own this content
                          if($_SESSION['USER']['ID'] == $post_owner_id){
                            return true;
                          }

                  				//check is privilege to access
                  				if(isset($isPermissionTo)){
                            $user_id = $_SESSION['USER']['ID'];
                            $conn = $c->get(PDO::class);
                            $stmt = $conn->prepare("SELECT privileges FROM users WHERE id='$user_id'");
                            $stmt->execute();
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);
                            $arrPermission = unserialize($user['privileges']);
                          //  $arrPermission = array('edit', 'update', 'delete');

                  			       if(in_array($isPermissionTo, $arrPermission)){

                                 return true;
                               }

                  				}

                  			}

                  		}

                  		return false;

               };
          },
    ]);
};
