<?php

namespace Drupal\redhen_contact\Routing;

use Symfony\Component\Routing\Route;

class UserEditRoutes {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = array();
    // Declares a single route under the name 'example.content'.
    // Returns an array of Route objects.
    $path = '/user/{user}/edit/redhen_contact';
    $defaults = [
      '_controller' => '\Drupal\redhen_contact\Controller\ContactEditController::editForm',
      '_title_callback' => '\Drupal\redhen_contact\Controller\ContactEditController::editTitle',
    ];
    $requirements = [
      '_permission'  => 'access content',
      '_user' => '\d+',
    ];
    $routes['redhen_contact.user.contact'] = new Route($path, $defaults, $requirements);
    return $routes;
  }
}
