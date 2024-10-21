<?php
namespace Drupal\solych\Controller;

use Drupal\Core\Controller\ControllerBase;

class CatsPageController extends ControllerBase {
  public function content() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Hello! You can add here a photo of your cat.'),
    ];
  }
}
