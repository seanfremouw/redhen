<?php

namespace Drupal\redhen_connection\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Connection entities.
 */
class ConnectionViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Unset the default entity relationships.
    // It does not work properly, the target type it is not defined.
    unset($data['redhen_connection']['endpoint_1']['relationship']);
    unset($data['redhen_connection']['endpoint_2']['relationship']);

    // Collect all connectionable entity types.
    $connection_types = ConnectionType::loadMultiple();
    /** @var \Drupal\redhen_connection\ConnectionTypeInterface $connection_type */
    foreach ($connection_types as $connection_type) {
      if ($entity_type_id = $connection_type->getEndpointEntityTypeId('1')) {
        $this->setViewsData($entity_type_id, 1, $data);
      }
      if ($entity_type_id = $connection_type->getEndpointEntityTypeId('2')) {
        $this->setViewsData($entity_type_id, 2, $data);
      }
    }

    return $data;
  }

  protected function setViewsData($entity_type_id, $endpoint, &$data) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    $data['redhen_connection']["{$entity_type_id}_{$endpoint}"] = [
      'relationship' => [
        'title' => $entity_type->getLabel(),
        'help' => t('The related @entity_type as endpoint @endpoint.', [
          '@entity_type' => $entity_type->getSingularLabel(),
          '@endpoint' => $endpoint,
         ]),
        'base' => $entity_type->getDataTable() ?: $entity_type->getBaseTable(),
        'base field' => $entity_type->getKey('id'),
        'relationship field' => 'endpoint_' . $endpoint,
        'id' => 'standard',
        'label' => $entity_type->getLabel(),
      ],
    ];
  }
}
