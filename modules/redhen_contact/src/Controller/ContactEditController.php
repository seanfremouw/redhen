<?php

namespace Drupal\redhen_contact\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\user\UserInterface;


/**
 * Class ContactEditController.
 *
 * @package Drupal\redhen_contact\Controller
 */
class ContactEditController extends ContactControllerBase {

  public function editTitle(UserInterface $user) {
    return $this->getContactFromUser($user)->label();
  }

  public function editForm(UserInterface $user) {
    return $this->entityFormBuilder()->getForm($this->getContactFromUser($user));
  }

  protected function getContactFromUser(UserInterface $user) {
    return \Drupal\redhen_contact\Entity\Contact::loadByUser($user);
  }
}
