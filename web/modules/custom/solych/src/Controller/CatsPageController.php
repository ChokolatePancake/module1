<?php
namespace Drupal\solych\Controller;

use Drupal\Core\Controller\ControllerBase;

class CatsPageController extends ControllerBase {
  public function content() {
    return [
      '#theme' => 'cats-page',
      '#title' => 'Cats page',
      '#markup' =>'Hello! You can add here a photo of your cat.',
    ];
  }
}
