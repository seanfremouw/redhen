<?php

namespace Drupal\redhen_contact\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ContactAddController.
 *
 * @package Drupal\redhen_contact\Controller
 */
class ContactAddController extends ContactControllerBase {

  /**
   * Displays add links for available bundles/types for entity redhen_contact .
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A render array for a list of the redhen_contact bundles/types that can be added or
   *   if there is only one bundle/type defined for the site, the function returns the add page for that bundle/type.
   */
  public function add(Request $request) {
    $types = $this->typeStorage->loadMultiple();
    if ($types && count($types) == 1) {
      $type = reset($types);
      return $this->addForm($type, $request);
    }
    if (count($types) === 0) {
      return [
        '#markup' => $this->t('You have not created any %bundle types yet. @link to add a new type.', [
          '%bundle' => 'Contact',
          '@link' => Link::createFromRoute($this->t('Go to the type creation page'), 'entity.redhen_contact_type.add_form'),
        ]),
      ];
    }
    return ['#theme' => 'redhen_contact_content_add_list', '#content' => $types];
  }

  /**
   * Presents the creation form for redhen_contact entities of given bundle/type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $redhen_contact_type
   *   The custom bundle to add.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function addForm(EntityInterface $redhen_contact_type, Request $request) {
    $entity = $this->storage->create([
      'type' => $redhen_contact_type->id()
    ]);
    return $this->entityFormBuilder()->getForm($entity);
  }

  /**
   * Provides the page title for this controller.
   *
   * @param \Drupal\Core\Entity\EntityInterface $redhen_contact_type
   *   The custom bundle/type being added.
   *
   * @return string
   *   The page title.
   */
  public function getAddFormTitle(EntityInterface $redhen_contact_type) {
    return t('Create of bundle @label',
      ['@label' => $redhen_contact_type->label()]
    );
  }

}
