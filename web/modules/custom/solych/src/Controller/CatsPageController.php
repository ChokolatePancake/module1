<?php
namespace Drupal\solych\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\solych\Form\CatsForm;

class CatsPageController extends ControllerBase {
  /**
   * Returns the content for the Cats page, including a form.
   *
   * @return array
   *   A render array containing the title, markup, and form.
   */
  public function content() {
    $form = \Drupal::formBuilder()->getForm(CatsForm::class);

    return [
      '#theme' => 'cats-page',
      '#title' => $this->t('Cats page'),
      '#markup' => $this->t('Hello! You can add here a photo of your cat.'),
      '#cats_form' => $form,
    ];
  }
}
