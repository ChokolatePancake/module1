<?php
namespace Drupal\solych\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\solych\Form\CatsForm;

/**
 * Provides the Cats page controller.
 *
 * This controller generates the Cats page, which includes a title,
 * descriptive text, and a form for submitting cat and owner information.
 */
class CatsPageController extends ControllerBase {

  /**
   * Returns the content for the Cats page, including a form.
   *
   * @return array
   *   A render array containing the title, markup, and form.
   */
  public function content() {
    $form = $this->formBuilder()->getForm(CatsForm::class);

    return [
      '#theme' => 'cats-page',
      '#title' => $this->t('Cats page'),
      '#markup' => $this->t('Hello! You can add here a photo of your cat.'),
      '#cats_form' => $form,
    ];
  }

}
