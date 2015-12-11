<?php

/**
 * @file
 * Contains Drupal\cacheit\Plugin\Block\RecentContent.
 */

namespace Drupal\cacheit\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'RecentContent' block that displays links to recent nodes.
 *
 * @Block(
 *  id = "cacheit_recent_content",
 *  admin_label = @Translation("Recent content"),
 * )
 */
class RecentContent extends BlockBase implements ContainerFactoryPluginInterface {

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
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs RecentContent class.
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
   * @param \Drupal\Core\Render\RendererInterface
   *   The renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, QueryFactory $entity_query, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('entity.query'),
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $limit = 5;
    $items = array();
    $build = array();

    $nids = $this->entityQuery->get('node')
      ->range(0, $limit)
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->execute();

    // Quick and dirty exit if no content was created.
    if (empty($nids)) {
      return 'Bummer, you have no content yet.';
    }

    // Build node links and collect the cache metadata.
    /** @var \Drupal\node\Entity\Node[] $nodes */
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    foreach ($nodes as $node) {
      $items[] = $node->link();
      $this->renderer->addCacheableDependency($build, $node);
    }

    // Adding a Cache tag allows cache invalidation when content is added.
    // @see cacheit_entity_insert()
    $build['recent_content'] = array(
      '#theme' => 'item_list',
      '#items' => $items,
      '#cache' => ['tags' => ['cacheit_recent_content']],
    );

    // Q: Are we rendering content here?

    //
    // A: Yes. Let's add #cache thingies.
    //
    //

    // Q: Does the rendered content vary per something?

    // A: Hmm, not sure. Gimme some context examples.
    //
    // $contexts_manager = \Drupal::getContainer()->get('cache_contexts_manager');
    // debug(array_keys($contexts_manager->getLabels(TRUE)));

    // Q: What cache thingies are already available?

    // A: Let's see
    // debug($build['recent_content']['#items'][0]);
    //
    // A: Hm, noting :(

    // Q: What causes the content to be outdated?

    // A: Changes in the node. So, we tags.
    //
    // Q: But which cache thingies does a node have?
    //

    // debug($node->getCacheContexts());
    // debug($node->getCacheTags());
    // debug($node->getCacheMaxAge());
    //

    // Q: How to get the node's cache metadata into the render array?

    // A: With this code:
//    foreach($nodes as $node) {
//      $this->renderer->addCacheableDependency($build, $node);
//    }
    // debug($build['#cache']);

    // Q: When does it become outdated?

    // A: Never. Don't need max-age.
    //
    // Q: Which cache thingies are now available at block level?
    //

    // A: Use Renderviz module to find out.
    //
    // Q: Ok, lets test. Change a node title, delete a node, add one.
    //   Oops, when I add a node, the list does not change. Now what?

    // A: You need to invalidate the cache.
    //
    // Q: But how? What options do I have to invalidate?
    //

    //
    // A: 1. Simple: time based; ...
//     $build['recent_content']['#cache'] = ['max-age' => 3600];
    // A: 2. Complex: When a node is created.
//      $build['recent_content']['#cache']['tags'][] = 'cacheit_recent_content';
    // @see cacheit_entity_insert()

    // For debugging:
//    $build['recent_content']['#cache'] = ['max-age' => 0];

    return $build;
  }

}
