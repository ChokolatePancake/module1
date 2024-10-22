<?php
namespace Drupal\solych\Controller;

use Drupal\Core\Controller\ControllerBase;

class CatsPageController extends ControllerBase {
  public function content() {
    return [
      '#theme' => 'page',
      '#type' => 'markup',
      '#title' => $this->t('Cats page'),
      '#markup' => $this->t('Hello! You can add here a photo of your cat.'),
    ];
  }
}
