<?php

/**
 * @file
 * Contains Drupal\cacheit\Plugin\Block\AdBlock.
 */

namespace Drupal\cacheit\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Advertisement block.
 *
 * @Block(
 *  id = "cacheit_ad_block",
 *  admin_label = @Translation("Advertisement"),
 * )
 */
class AdBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the Advertisement block object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\Query\QueryFactory
   *   The entity query factory.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueryFactory $entity_query, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('entity.query'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Load one advertisement node.
    $nids = $this->entityQuery->get('node')
      ->condition('type', 'ad')
      ->range(0, 1)
      ->condition('status', 1)
      ->condition('field_ad_expiration', REQUEST_TIME, '>')
      ->sort('created', 'DESC')
      ->execute();

    // Quick and dirty exit if no ad was created.
    if (empty($nids)) {
      return 'Create an Ad first.';
    }

    // Build the ad teaser.
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->entityTypeManager->getStorage('node')->load(reset($nids));
    $teaser = $this->entityTypeManager->getViewBuilder('node')->view($node, 'teaser');
    $build['ad'] = $teaser;

    $build['cart_link'] = array(
      '#type' => 'link',
      '#url' => Url::fromRoute('cacheit.shopping_cart', array(), ['query' => ['product_id' => $node->id()]]),
      '#title' => t('Add to cart'),
      '#attributes' => ['class' => ['button']],
      '#weight' => 10,
    );

    $build['conditions_link'] = array(
      '#type' => 'link',
      '#url' => Url::fromUri('entity:node/2'),
      '#title' => t('Conditions'),
      '#weight' => 11,
      '#cache' => [
        'tags' => ['node:2'],
        'max-age' => 3600,
      ],
    );

    // Dynamic content added using a placeholder.
    $build['validity'] = [
      '#lazy_builder' => ['cacheit.lazy_builders:renderAdValidity', [$node->id()]],
      '#create_placeholder' => TRUE,
    ];

    return $build;
  }

}
