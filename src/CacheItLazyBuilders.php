<?php

/**
 * @file
 * Contains \Drupal\cacheit\CacheItLazyBuilders.
 */

namespace Drupal\cacheit;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines a service for CacheIt #lazy_builder callbacks.
 */
class CacheItLazyBuilders {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CacheItLazyBuilders object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * #lazy_builder callback; builds the validity time.
   *
   * @param integer $nid
   *   Node ID of the personal ad.
   */
  public function renderAdValidity($nid) {

    /** @var \Drupal\node\Entity\Node[] $nodes */
    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    // Build a render array with the remaining time info.
    $expiry_time = $node->field_ad_expiration->value;
    $timestamp = strtotime($expiry_time) - REQUEST_TIME;
    $time = date('h:i:s', $timestamp);
    $build = array(
      '#prefix' => '<p>',
      '#markup' => t('Remaining time @time', ['@time' => $time]),
      '#suffix' => '</p>',
      '#cache' => FALSE,
    );
    // Q: What if I decide to add a JavaScript countdown timer?
    // A: Than we add the code to the render array.
    // $build['#attached']['library'][] = 'cacheit/countdown';

    return $build;
  }

}
