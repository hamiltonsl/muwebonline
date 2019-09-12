<?php

namespace App\Middlewares;

use App\Database\DashboardDatabase;
use App\Views\ViewMessages;

class AccesPageMiddeware
{

  function __invoke($request, $response, $next)
  {
    //Classes
    $data     = new DashboardDatabase();
    $messages = new ViewMessages();

    //Varaiables
    $username = (!isset($_SESSION['usernameuser'])) ? NULL : $_SESSION['usernameuser'];
    $get_user = (empty($username)) ? NULL : $data->getUserDefault($username);

    if (empty($get_user)) {
      $return = array(
        'error'   => true,
        'success' => false,
        'message' => 'Você não está logado'
      );

      $messages->addMessage('response', $return);

      $response = $response->withRedirect("/login");
    } else {
      $vips       = $data->getVipsConfigs();
      $route      = $request->getAttribute('route');
      $routeName  = $route->getName();
      $get_access = $data->getAccessPageInfo($routeName);

      if (empty($vips)) {
        $user_vip = 0;
      } else {
        foreach ($vips as $key => $value) {
          if ($get_user[$value['column_level']] == 0) {
            $user_vip = 0;
          } elseif ($get_user[$value['column_level']] == $value['level']) {
            $user_vip = $value['level'];
          } elseif ($get_user[$value['column_level']] > $value['level']) {
            $user_vip = 0;
          }
        }
      }

      if (empty($get_access)) {
        $blocked = 0;
      } else {
        $name_user = array(
          "$username",
        );
        if (empty($get_access['blocked'])) {
          $names_blocked = array();
        } else {
          $names_blocked = explode(',', $get_access['blocked']);
        }
        if ($user_vip < $get_access['access']) {
          $blocked = 1;
        } elseif (in_array($name_user, $names_blocked)) {
          $blocked = 2;
        } else {
          $blocked = 0;
        }
      }

      if ($blocked == 1) {
        $return = array(
          'error'   => true,
          'success' => false,
          'message' => 'Você não tem nível vip necessário para acessar essa página'
        );

        $messages->addMessage('response', $return);

        $response = $response->withRedirect("/dashboard/home");
      } elseif ($blocked == 2) {
        $return = array(
          'error'   => true,
          'success' => false,
          'message' => 'Você está bloqueado para acessar essa página'
        );

        $messages->addMessage('response', $return);

        $response = $response->withRedirect("/dashboard/home");
      } else {
        $response = $next($request, $response);
      }
    }

    return $response;
  }
}