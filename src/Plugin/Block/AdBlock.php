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

    // Build the ad teaser.
    /** @var \Drupal\node\Entity\Node[] $nodes */
    $node = $this->entityTypeManager->getStorage('node')->load(reset($nids));
    $teaser = $this->entityTypeManager->getViewBuilder('node')->view($node, 'teaser');
    $build['ad'] = $teaser;

    // Q: A render array of a teaser. Does it contain cache data?

    //
    // A: Yes. Look at the '#cache' element.
    // dpm($build['ad']['#cache']);
    //

    // Let's add the call to action link.
    $build['cart_link'] = array(
      '#type' => 'link',
      '#url' => Url::fromRoute('cacheit.shopping_cart', array(), ['query' => ['product_id' => $node->id()]]),
      '#title' => t('Add to cart'),
      '#attributes' => ['class' => ['button']],
      '#weight' => 10,
    );
    // Q: What #cache data do we need to add?

    // A: Nothing to add for the URL. The route and URL are hard coded, no
    //    configurable parts.  (?? user role, what about alias for route url?)
    // A: The link text is translatable, In case of multiple languages the
    //    context languages:language_interface is required. By default the
    //    languages:language_interface is one of the required_cache_contexts and
    //    therefore it will be added at block level.
    //    See: core.services.yml and Renderer::doRender
    // $build['cart_link']['#cache'] = ['context' => ['languages:language_interface']];

    // Q: Now we add a conditions link. Does this require cache settings?
    // Q: What makes it vary? By what or when does it outdate?

//    $build['conditions_link'] = array(
//      '#type' => 'link',
//      '#url' => Url::fromUri('entity:node/2'),
//      '#title' => t('Conditions'),
//      '#weight' => 11,
//      // A: The URL will change if the node url alias changes. Add a tag.
//      '#cache' => ['tags' => ['node:2']],
//    );

    // Q: We want to remove the ad when it has expired.

    //
    // A: Lets's use the simple solution and expire after 1 hour.
    $build['#cache']['max-age'] = 3600;
    //

    // Q: What would a the more complex (and more precise) solution look like?

    //
    // A: Cron job that expires this ad block when the node became invalid.
    //    Plus a unique cache key and tag for the block.
    //

    // Q: But let's take this one step further. We want to display the remaining
    //    time this ad is valid (hours:minutes:seconds). This is highly dynamic
    //    data.

    // A: No problem, we can do that.
    // Q: What options do we have?
    // A: 1. Not to cache; 2. Cache for 1 hour and use JS countdown timer;
    //    3. Use a placeholder.
    $build['validity'] = [
      '#lazy_builder' => ['cacheit.lazy_builders:renderAdValidity', [$node->id()]],
      '#create_placeholder' => TRUE,
    ];
    // Read about automatic placeholdering (#create_placeholder) at
    // \Drupal\Core\Render\PlaceholderGenerator::shouldAutomaticallyPlaceholder

    // Disable cache for debugging.
//     $build['#cache']['max-age'] = 0;

    return $build;
  }

}
