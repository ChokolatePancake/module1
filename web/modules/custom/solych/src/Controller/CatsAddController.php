<?php
namespace Drupal\solych\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\solych\Form\CatsForm;
use Drupal\Core\Url;

/**
 * Provides the Cats Add page controller.
 *
 * This controller generates the Cats page, which includes a title,
 * descriptive text, and a form for submitting cat and owner information.
 */
class CatsAddController extends ControllerBase {

  /**
   * Returns the content for the Cats page, including a form, table of cats and button to page cats list.
   *
   * @return array
   *   A render array containing the title, markup, and form.
   */
  public function content() {
    $form = $this->formBuilder()->getForm(CatsForm::class);

    $list_button = [
      '#type' => 'link',
      '#title' => $this->t('View Cats List'),
      '#url' => Url::fromRoute('solych.cats_list'),
      '#attributes' => ['class' => ['button', 'button--secondary']],
    ];

    return [
      '#theme' => 'cats-page',
      '#title' => $this->t('Cats page'),
      '#markup' => $this->t('Hello! You can add here a photo of your cat.'),
      '#cats_form' => $form,
      '#list_button' => $list_button,
    ];
  }

}
