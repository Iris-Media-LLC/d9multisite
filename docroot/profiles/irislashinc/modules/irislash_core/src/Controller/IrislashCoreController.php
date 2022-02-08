<?php

namespace Drupal\irislash_core\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for irislash_core routes.
 */
class IrislashCoreController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
