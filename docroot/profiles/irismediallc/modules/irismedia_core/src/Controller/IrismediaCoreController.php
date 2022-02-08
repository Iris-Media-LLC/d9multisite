<?php

namespace Drupal\irismedia_core\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for irismedia_core routes.
 */
class IrismediaCoreController extends ControllerBase {

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
