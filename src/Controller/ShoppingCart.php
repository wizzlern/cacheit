<?php

/**
 * @file
 * Contains Drupal\cacheit\Controller\ShoppingCart.
 */

namespace Drupal\cacheit\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class ShoppingCart.
 *
 * @package Drupal\cacheit\Controller
 */
class ShoppingCart extends ControllerBase {
  /**
   * Dummy shopping cart controller.
   *
   * @return string
   *   Return dummy text.
   */
  public function dummy() {
    return [
        '#type' => 'markup',
        '#markup' => $this->t('Thank you for shopping.')
    ];
  }

}
